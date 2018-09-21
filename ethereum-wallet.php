<?php
/*
Plugin Name: Ethereum Wallet
Plugin URI: https://www.ethereumico.io/
Description: Wallet for Ether and ERC20 tokens for WordPress
Version: 1.4.2
Author: ethereumicoio
Text Domain: ethereum-wallet
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Explicitly globalize to support bootstrapped WordPress
global 
	$ETHEREUM_WALLET_plugin_basename, $ETHEREUM_WALLET_options, $ETHEREUM_WALLET_plugin_dir, $ETHEREUM_WALLET_plugin_url_path, 
	$ETHEREUM_WALLET_services, $ETHEREUM_WALLET_amp_icons_css;

$ETHEREUM_WALLET_plugin_basename = plugin_basename( dirname( __FILE__ ) );
$ETHEREUM_WALLET_plugin_dir = untrailingslashit( plugin_dir_path( __FILE__ ) );
$ETHEREUM_WALLET_plugin_url_path = untrailingslashit( plugin_dir_url( __FILE__ ) );

// HTTPS?
$ETHEREUM_WALLET_plugin_url_path = is_ssl() ? str_replace( 'http:', 'https:', $ETHEREUM_WALLET_plugin_url_path ) : $ETHEREUM_WALLET_plugin_url_path;
// Set plugin options
$ETHEREUM_WALLET_options = get_option( 'ethereum-wallet_options', array() );

/**
 * The ERC20 smart contract ABI
 * 
 * @var string The ERC20 smart contract ABI
 * @see http://www.webtoolkitonline.com/json-minifier.html
 */
$ETHEREUM_WALLET_erc20ContractABI = '[{"constant":true,"inputs":[],"name":"totalSupply","outputs":[{"name":"supply","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"symbol","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[{"name":"_owner","type":"address"}],"name":"balanceOf","outputs":[{"name":"balance","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[{"name":"_owner","type":"address"},{"name":"_spender","type":"address"}],"name":"allowance","outputs":[{"name":"remaining","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"anonymous":false,"inputs":[{"indexed":true,"name":"_owner","type":"address"},{"indexed":true,"name":"_spender","type":"address"},{"indexed":false,"name":"_value","type":"uint256"}],"name":"Approval","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"name":"_from","type":"address"},{"indexed":true,"name":"_to","type":"address"},{"indexed":false,"name":"_value","type":"uint256"}],"name":"Transfer","type":"event"},{"constant":false,"inputs":[{"name":"_spender","type":"address"},{"name":"_value","type":"uint256"}],"name":"approve","outputs":[{"name":"success","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"_to","type":"address"},{"name":"_value","type":"uint256"}],"name":"transfer","outputs":[{"name":"success","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"_from","type":"address"},{"name":"_to","type":"address"},{"name":"_value","type":"uint256"}],"name":"transferFrom","outputs":[{"name":"success","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"}]';

function ETHEREUM_WALLET_deactivate() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    deactivate_plugins( plugin_basename( __FILE__ ) );
}

if ( version_compare( phpversion(), '7.0', '<' ) ) {
	add_action( 'admin_init', 'ETHEREUM_WALLET_deactivate' );
	add_action( 'admin_notices', 'ETHEREUM_WALLET_admin_notice' );
	function ETHEREUM_WALLET_admin_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="error"><p><strong>WordPress Ethereum Wallet</strong> requires PHP version 7.0 or above.</p></div>';
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
} else if (!function_exists('gmp_init') ) {
	add_action( 'admin_init', 'ETHEREUM_WALLET_deactivate' );
	add_action( 'admin_notices', 'ETHEREUM_WALLET_admin_notice_gmp' );
	function ETHEREUM_WALLET_admin_notice_gmp() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="error"><p><strong>WordPress Ethereum Wallet</strong> requires <a href="http://php.net/manual/en/book.gmp.php">GMP</a> module to be installed.</p></div>';
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
} else {

require $ETHEREUM_WALLET_plugin_dir . '/vendor/autoload.php';

function ETHEREUM_WALLET_init() {
	global $ETHEREUM_WALLET_plugin_dir,
		$ETHEREUM_WALLET_plugin_basename, 
		$ETHEREUM_WALLET_options;
	
	// Load the textdomain for translations
	load_plugin_textdomain( 'ethereum-wallet', false, $ETHEREUM_WALLET_plugin_basename . '/languages/' );
}
add_filter( 'init', 'ETHEREUM_WALLET_init' );

// Takes a hex (string) address as input
function ETHEREUM_WALLET_checksum_encode($addr_str) {
    $out = array();
    $addr = str_replace('0x', '', strtolower($addr_str));
    $addr_array = str_split($addr);
    $hash_addr = \kornrunner\Keccak::hash($addr, 256);
    $hash_addr_array = str_split($hash_addr);
    for ($idx = 0; $idx < count($addr_array); $idx++) {
        $ch = $addr_array[$idx];
        if ((int) hexdec($hash_addr_array[$idx]) >= 8) {
            $out[] = strtoupper($ch) . '';
        } else {
            $out[] = $ch . '';
        }
    }
    return '0x' . implode('', $out);
}

// create Ethereum wallet on user register
// see https://wp-kama.ru/hook/user_register
add_action( 'user_register', 'ETHEREUM_WALLET_user_registration' );
function ETHEREUM_WALLET_user_registration( $user_id ) {
    $random = new \BitWasp\Bitcoin\Crypto\Random\Random();
    $privateKeyBuffer = $random->bytes(32);
    $privateKeyHex = $privateKeyBuffer->getHex();
    $compressedKeyFactory = \BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory::uncompressed();
    $privateKey = $compressedKeyFactory->fromBuffer($privateKeyBuffer);

    $pubKeyHex = $privateKey->getPublicKey()->getHex();
    $hash = \kornrunner\Keccak::hash(substr(hex2bin($pubKeyHex), 1), 256);
    $ethAddress = '0x' . substr($hash, 24);
    $ethAddressChkSum = ETHEREUM_WALLET_checksum_encode($ethAddress);

//    echo 'Private key: ' . $privateKeyHex . "\n";
//    echo 'Public key: ' . $pubKeyHex . "\n";
//    echo 'Address: ' . $ethAddress . "\n";
//    echo 'Address chksum: ' . $ethAddressChkSum . "\n";
    update_user_meta( $user_id, 'user_ethereum_wallet_address', $ethAddressChkSum);
    update_user_meta( $user_id, 'user_ethereum_wallet_key', $privateKeyHex);
}

function ETHEREUM_WALLET_calc_display_value( $value ) {
    if ($value < 1) {
        return [0.01 * floor(100 * $value), __( 'ETH', 'ethereum-wallet' )];
    }
    if ($value < 1000) {
        return [0.1 * floor(10 * $value), __( 'ETH', 'ethereum-wallet' )];
    }
    if ($value < 1000000) {
        return [0.1 * floor(10 * 0.001 * $value), __( 'K', 'ethereum-wallet' )];
    }
    return [0.1 * floor(10 * 0.000001 * $value), __( 'M', 'ethereum-wallet' )];
}

function ETHEREUM_WALLET_log($error) {
    error_log($error);
}

function ETHEREUM_WALLET_getBalanceEth($providerUrl, $accountAddress) {
    $requestManager = new \Web3\RequestManagers\HttpRequestManager($providerUrl, 10/* seconds */);
    $web3 = new \Web3\Web3(new \Web3\Providers\HttpProvider($requestManager));
    $eth = $web3->eth;

    $ether_quantity_wei = null;
    $eth->getBalance($accountAddress, function ($err, $balance) use(&$ether_quantity_wei) {
        if ($err !== null) {
            ETHEREUM_WALLET_log("Failed to getBalance: " . $err);
            return;
        }
        $ether_quantity_wei = $balance;
    });
    return $ether_quantity_wei;
}

function ETHEREUM_WALLET_getWeb3Endpoint() {
    global $ETHEREUM_WALLET_options;
    $infuraApiKey = '';
    if (isset($ETHEREUM_WALLET_options['infuraApiKey'])) {
        $infuraApiKey = esc_attr($ETHEREUM_WALLET_options['infuraApiKey']);
    }
    $blockchainNetwork = ETHEREUM_WALLET_getBlockchainNetwork();
    $web3Endpoint = "https://" . esc_attr($blockchainNetwork) . ".infura.io/" . esc_attr($infuraApiKey);
    return $web3Endpoint;
}

function ETHEREUM_WALLET_getBlockchainNetwork() {
    global $ETHEREUM_WALLET_options;
    if (!isset($ETHEREUM_WALLET_options['blockchain_network'])) {
        return 'mainnet';
    }
    $blockchainNetwork = esc_attr($ETHEREUM_WALLET_options['blockchain_network']);
    if (empty($blockchainNetwork)) {
        $blockchainNetwork = 'mainnet';
    }
    return $blockchainNetwork;
}

function ETHEREUM_WALLET_balance_shortcode( $attrs ) {
    $user_id = get_current_user_id();
    if ( $user_id <= 0 ) {
        return;
    }
    $accountAddress = get_user_meta( $user_id, 'user_ethereum_wallet_address', true);
    if (empty($accountAddress)) {
        ETHEREUM_WALLET_user_registration( $user_id );
        $accountAddress = get_user_meta( $user_id, 'user_ethereum_wallet_address', true);
    }

    $providerUrl = ETHEREUM_WALLET_getWeb3Endpoint();
    
    /**
     * @param phpseclib\Math\BigInteger $balance The Ether or Token balance in wei.
     */
    $balance = new phpseclib\Math\BigInteger(0);
    $strBalance = '0';
    $strCurrencyName = "ETH";
    if (!empty($accountAddress)) {
        // ETH
        $balance = ETHEREUM_WALLET_getBalanceEth($providerUrl, $accountAddress);
        if (!is_null($balance)) {
            $powDecimals = new phpseclib\Math\BigInteger(pow(10, 18));
            list($q, $r) = $balance->divide($powDecimals);
            $sR = $r->toString();
            $strBalanceDecimals = sprintf('%018s', $sR);
            $strBalance = rtrim($q->toString() . '.' . $strBalanceDecimals, '0');
            $strBalance = rtrim($strBalance, '.');
        } else {
            $strBalance = __('Failed to retrive Ether balance', 'ethereum-wallet');
        }
    }

    $js = '';
    $ret = 
'<div class="twbs"><div class="container-fluid ethereum-wallet-balance-shortcode">
    <div class="row ethereum-wallet-balance-content">
        <div class="col-md-6 col-6 ethereum-wallet-balance-value-wrapper">
            <div class="ethereum-wallet-balance-value">'.$strBalance.'</div>
        </div>
        <div class="col-md-6 col-6 ethereum-wallet-balance-token-name-wrapper">
            <div class="ethereum-wallet-balance-token-name">'.$strCurrencyName.'</div>
        </div>
    </div>
</div></div>';

    return $js . str_replace("\n", " ", str_replace("\r", " ", str_replace("\t", " ", $js . $ret)));
}

add_shortcode( 'ethereum-wallet-balance', 'ETHEREUM_WALLET_balance_shortcode' );

function ETHEREUM_WALLET_account_shortcode( $attrs ) {
    $user_id = get_current_user_id();
    if ( $user_id <= 0 ) {
        return;
    }
    $accountAddress = get_user_meta( $user_id, 'user_ethereum_wallet_address', true);
    if (empty($accountAddress)) {
        ETHEREUM_WALLET_user_registration( $user_id );
        $accountAddress = get_user_meta( $user_id, 'user_ethereum_wallet_address', true);
    }

    $attributes = shortcode_atts( array(
        'label' => '',
        'nolabel' => '',
    ), $attrs, 'ethereum-wallet' );

    $label = ! empty( $attributes['label'] ) ? esc_attr($attributes['label']) : __('Account', 'ethereum-wallet');
    $nolabel = ! empty( $attributes['nolabel'] ) ? esc_attr($attributes['nolabel']) : '';
    
    $labelTag = '<label class="control-label" for="ethereum-wallet-account">'.$label.'</label>';
    if ('yes' == $nolabel) {
        $labelTag = '';
    }
    
    $js = '';
    $ret = 
'<div class="twbs"><div class="container-fluid ethereum-wallet-account-shortcode">
    <div class="row ethereum-wallet-account-account-wrapper">
        <div class="col-12">
            <div class="form-group">
                '.$labelTag.'
                <div class="input-group" style="margin-top: 8px">
                    <input type="text"
                           value="'.$accountAddress.'" 
                           disabled="disabled" 
                           id="ethereum-wallet-account" 
                           class="form-control">
                </div>
            </div>
        </div>
    </div>
</div></div>';

    return $js . str_replace("\n", " ", str_replace("\r", " ", str_replace("\t", " ", $js . $ret)));
}

add_shortcode( 'ethereum-wallet-account', 'ETHEREUM_WALLET_account_shortcode' );

function ETHEREUM_WALLET_sendform_shortcode( $attributes ) {
    $user_id = get_current_user_id();
    if ( $user_id <= 0 ) {
        return;
    }

    $ops = '';
    
	$js = '';
	$ret = 
'<form method="post" action="" onsubmit="return ETHEREUM_WALLET_validate_send_form()"><div class="twbs"><div class="container-fluid ethereum-wallet-sendform-shortcode">
    <div class="row ethereum-wallet-sendform-content">
        <div class="col-12">
            <div class="form-group">
                <label class="control-label" for="ethereum-wallet-sendform-to">'.__('To', 'ethereum-wallet').'</label>
                <div class="input-group" style="margin-top: 8px">
                    <input type="text"
                           value="" 
                           placeholder="'.__('Input the recipient ethereum address', 'ethereum-wallet').'" 
                           id="ethereum-wallet-sendform-to" 
                           name="ethereum-wallet-sendform-to" 
                           class="form-control">
                </div>
            </div>
        </div>
    </div>
    <div class="row ethereum-wallet-sendform-content">
        <div class="col-12">
            <div class="form-group">
                <label class="control-label" for="ethereum-wallet-sendform-amount">'. __('Amount', 'ethereum-wallet') . '</label>
                <div class="input-group" style="margin-top: 8px">
                    <input style="cursor: text;" 
                        type="number"
                        value="" 
                        min="0"
                        step="0.000000000000000001"
                        id="ethereum-wallet-sendform-amount" 
                        name="ethereum-wallet-sendform-amount" 
                        class="form-control">
                    <select style="max-width: 80px;"
                        class="custom-select form-control" 
                        id="ethereum-wallet-sendform-currency"
                        name="ethereum-wallet-sendform-currency" >
                        <option value="0x0000000000000000000000000000000000000001" selected="">'. __('ETH', 'ethereum-wallet') . '</option>
                        '.$ops.'
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="row ethereum-wallet-sendform-content">
        <div class="col-12">
            <div id="ethereum-wallet-tx-in-progress-alert" class="form-group hidden" hidden>
                <div class="alert alert-warning" role="alert">
                    <!--http://jsfiddle.net/0vzmmn0v/1/-->
                    <div class="fa fa-exclamation-triangle" aria-hidden="true"></div>
                    <div>'. __('Last transaction is still in progress. Wait please.', 'ethereum-wallet'). '</div>
                </div>
            </div>
            <div class="form-group">
                '.wp_nonce_field( 'ethereum-wallet-send_form', 'ethereum-wallet-send-form-nonce', true, false ).'
                <input type="hidden" name="action" value="ethereum_wallet_send" />
                <button 
                    id="ethereum-wallet-send-button" 
                    name="ethereum-wallet-send-button" 
                    type="submit" 
                    value="'. __('Send', 'ethereum-wallet') . '" 
                    class="button btn btn-default float-right col-12 col-md-4">'. __('Send', 'ethereum-wallet') . '</button>
                <div id="ethereum-wallet-tx-in-progress-spinner" class="spinner float-right"></div>
            </div>
        </div>
    </div>
</div></div></form>';
    
    return $js . str_replace("\n", " ", str_replace("\r", " ", str_replace("\t", " ", $js . $ret)));
}

add_shortcode( 'ethereum-wallet-sendform', 'ETHEREUM_WALLET_sendform_shortcode' );

function ETHEREUM_WALLET_sendform_action() {
    global $wp;
    
    if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
        return;
    }
    if ( empty( $_POST['action'] ) || 'ethereum_wallet_send' !== $_POST['action'] ) {
        return;
    }
    if (function_exists('wc_nocache_headers')) {
        wc_nocache_headers();
    } else {
        nocache_headers();
    }
    $nonce_value = '';
    if (isset($_REQUEST['ethereum-wallet-send-form-nonce'])) {
        $nonce_value = $_REQUEST['ethereum-wallet-send-form-nonce'];
    } else if (isset($_REQUEST['_wpnonce'])) {
        $nonce_value = $_REQUEST['_wpnonce'];
    }
    if ( ! wp_verify_nonce( $nonce_value, 'ethereum-wallet-send_form' ) ) {
        ETHEREUM_WALLET_log("ETHEREUM_WALLET_sendform_action: bad nonce detected: " . $nonce_value);
        return;
    }
    $user_id = get_current_user_id();
    if ( $user_id <= 0 ) {
        return;
    }
    
    $accountAddress = get_user_meta( $user_id, 'user_ethereum_wallet_address', true);
    $privateKey = get_user_meta( $user_id, 'user_ethereum_wallet_key', true);

    // To address
    
    if (!isset( $_REQUEST['ethereum-wallet-sendform-to'] )) {
        ETHEREUM_WALLET_log("ethereum-wallet-sendform-to not set");
        return;
    }
    
    $to = sanitize_text_field($_REQUEST['ethereum-wallet-sendform-to']);
    if (empty( $to )) {
        ETHEREUM_WALLET_log("empty ethereum-wallet-sendform-to");
        return;
    }
    
    if (42 != strlen( $to )) {
        ETHEREUM_WALLET_log("strlen ethereum-wallet-sendform-to != 42: " . $to);
        return;
    }
    
    if ('0x' != substr( $to, 0, 2 )) {
        ETHEREUM_WALLET_log("startsWith ethereum-wallet-sendform-to != 0x: " . $to);
        return;
    }
    
    // Amount
    
    if (!isset( $_REQUEST['ethereum-wallet-sendform-amount'] )) {
        ETHEREUM_WALLET_log("ethereum-wallet-sendform-amount not set");
        return;
    }
    
    $amount = sanitize_text_field($_REQUEST['ethereum-wallet-sendform-amount']);
    if (empty( $amount )) {
        ETHEREUM_WALLET_log("empty ethereum-wallet-sendform-amount");
        return;
    }
    
    if (!is_numeric( $amount )) {
        ETHEREUM_WALLET_log("non-numeric ethereum-wallet-sendform-amount: " . $amount);
        return;
    }
        
    // Currency address
    
    if (!isset( $_REQUEST['ethereum-wallet-sendform-currency'] )) {
        ETHEREUM_WALLET_log("ethereum-wallet-sendform-currency not set");
        return;
    }
    
    $currency = sanitize_text_field($_REQUEST['ethereum-wallet-sendform-currency']);
    if (empty( $currency )) {
        ETHEREUM_WALLET_log("empty ethereum-wallet-sendform-currency");
        return;
    }
    
    if (42 != strlen( $currency )) {
        ETHEREUM_WALLET_log("strlen ethereum-wallet-sendform-currency != 42: " . $to);
        return;
    }
    
    if ('0x' != substr( $currency, 0, 2 )) {
        ETHEREUM_WALLET_log("startsWith ethereum-wallet-sendform-currency != 0x: " . $to);
        return;
    }
    
    if ("0x0000000000000000000000000000000000000001" === $currency) {
        // ETH
        $txhash = ETHEREUM_WALLET_send_ether($accountAddress, $to, $amount, $privateKey);
    }
    if (false !== $txhash) {
        update_user_meta( $user_id, 'user_ethereum_wallet_last_txhash', $txhash);
        update_user_meta( $user_id, 'user_ethereum_wallet_last_txtime', time());
    }

}

add_action( 'wp_loaded', "ETHEREUM_WALLET_sendform_action", 20 );

function ETHEREUM_WALLET_history_shortcode( $attributes ) {
    $user_id = get_current_user_id();
    if ( $user_id <= 0 ) {
        return;
    }

	$attributes = shortcode_atts( array(
		'direction' => '',
	), $attributes, 'ethereum-wallet' );

    // The displayed tx direction: in/out/inout
    $direction = ! empty( $attributes['direction'] ) ? $attributes['direction'] : 'inout';
    if (!in_array($direction, array('in', 'out', 'inout'))) {
        $direction = 'inout';
    }
	$js = '';
	$ret = 
'<div class="twbs"><div class="container-fluid ethereum-wallet-history-shortcode">
    <div class="row ethereum-wallet-history-table-wrapper">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-striped table-condensed ethereum-wallet-history-table ethereum-wallet-history-table-direction-'.$direction.'">
                    <thead>
                        <tr>
                            <th>'.__('#', 'ethereum-wallet').'</th>
                            <th>'.__('Amount', 'ethereum-wallet').'</th>
                            <th>'.__('Date', 'ethereum-wallet').'</th>
                            <th>'.__('Tx', 'ethereum-wallet').'</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div></div>';

    return $js . str_replace("\n", " ", str_replace("\r", " ", str_replace("\t", " ", $js . $ret)));
}

add_shortcode( 'ethereum-wallet-history', 'ETHEREUM_WALLET_history_shortcode' );


function ETHEREUM_WALLET_stylesheet() {
	global $ETHEREUM_WALLET_plugin_url_path;
	
    $deps = array('font-awesome', 'bootstrap-ethereum-wallet');
    $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
    if( ( ! wp_style_is( 'font-awesome', 'queue' ) ) && ( ! wp_style_is( 'font-awesome', 'done' ) ) ) {
        wp_dequeue_style('font-awesome');
        wp_deregister_style('font-awesome');
        wp_register_style(
            'font-awesome', 
            $ETHEREUM_WALLET_plugin_url_path . "/css/font-awesome{$min}.css", array(), '4.7.0'
        );
    }
    if ( !wp_style_is( 'bootstrap-ethereum-wallet', 'queue' ) && !wp_style_is( 'bootstrap-ethereum-wallet', 'done' ) ) {
        wp_dequeue_style('bootstrap-ethereum-wallet');
        wp_deregister_style('bootstrap-ethereum-wallet');
        wp_register_style(
            'bootstrap-ethereum-wallet', 
            $ETHEREUM_WALLET_plugin_url_path . "/css/bootstrap-ns{$min}.css", array(), '4.0.0'
        );
    }

    wp_enqueue_style( 'ethereum-wallet', $ETHEREUM_WALLET_plugin_url_path . '/ethereum-wallet.css', $deps, '1.4.2' );
}

add_action( 'wp_enqueue_scripts', 'ETHEREUM_WALLET_stylesheet', 20 );

function ETHEREUM_WALLET_enqueue_script() {
	global $ETHEREUM_WALLET_plugin_url_path;
	
    $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
    wp_enqueue_script( 'web3', $ETHEREUM_WALLET_plugin_url_path . '/web3.min.js', array( 'jquery' ), '0.19.0' );
    wp_enqueue_script( 'ethereum-wallet', $ETHEREUM_WALLET_plugin_url_path . "/ethereum-wallet{$min}.js", array( 'jquery', 'web3' ), '1.4.2' );

	$options = stripslashes_deep( get_option( 'ethereum-wallet_options', array() ) );
	
	$etherscanApiKey = ! empty( $options['etherscanApiKey'] ) ? esc_attr( $options['etherscanApiKey'] ) : 
        '';
	$blockchain_network = ! empty( $options['blockchain_network'] ) ? esc_attr( $options['blockchain_network'] ) : 
        'mainnet';
	$infuraApiKey = ! empty( $options['infuraApiKey'] ) ? esc_attr( $options['infuraApiKey'] ) : 
        '';
	$gaslimit = ! empty( $options['gaslimit'] ) ? esc_attr( $options['gaslimit'] ) : "200000";
	$gasprice = ! empty( $options['gasprice'] ) ? esc_attr( $options['gasprice'] ) : "21";

    $accountAddress = '';
    $lastTxHash = '';
    $lastTxTime = '';
    $tokens_json = '[]';
    $user_id = get_current_user_id();
    if ($user_id > 0) {
        $accountAddress = get_user_meta( $user_id, 'user_ethereum_wallet_address', true);
        if (empty($accountAddress)) {
            ETHEREUM_WALLET_user_registration( $user_id );
            $accountAddress = get_user_meta( $user_id, 'user_ethereum_wallet_address', true);
        }
        
        $lastTxHash = get_user_meta( $user_id, 'user_ethereum_wallet_last_txhash', true);
        $lastTxTime = get_user_meta( $user_id, 'user_ethereum_wallet_last_txtime', true);
        if( empty( $lastTxTime ) ) {
            $lastTxTime = '';
        } else {
            $lastTxTime = time() - intval($lastTxTime);
        }

    }
    
    wp_localize_script(
        'ethereum-wallet', 'ethereumWallet', [
            // variables
            'user_wallet_address' => esc_html($accountAddress),
            'user_wallet_last_txhash' => esc_html($lastTxHash),
            'user_wallet_last_txtime' => esc_html($lastTxTime),
            'tokens' => esc_html($tokens_json),
            'web3Endpoint' => esc_html("https://" . $blockchain_network . ".infura.io/" . $infuraApiKey),
            'blockchain_network' => esc_html($blockchain_network),
            'gasLimit' => esc_html($gaslimit),
            'gasPrice' => esc_html($gasprice),
            // translations
            'str_copied_msg' => __('Copied to clipboard', 'ethereum-wallet'),
            'str_insufficient_eth_balance_msg' => __('Insufficient Ether balance for tx fee payment.', 'ethereum-wallet'),
        ]
    );
}

add_action( 'wp_enqueue_scripts', 'ETHEREUM_WALLET_enqueue_script' );

/**
 * Admin Options
 */

if ( is_admin() ) {
	include_once $ETHEREUM_WALLET_plugin_dir . '/ethereum-wallet.admin.php';
}

function ETHEREUM_WALLET_add_menu_link() {
	$page = add_options_page(
		__( 'Ethereum Wallet Settings', 'ethereum-wallet' ),
		__( 'Ethereum Wallet', 'ethereum-wallet' ),
		'manage_options',
		'ethereum-wallet',
		'ETHEREUM_WALLET_options_page'
	);
}

add_filter( 'admin_menu', 'ETHEREUM_WALLET_add_menu_link' );

// Place in Option List on Settings > Plugins page 
function ETHEREUM_WALLET_actlinks( $links, $file ) {
	// Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	
	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}
	
	if ( $file == $this_plugin ) {
		$settings_link = '<a href="options-general.php?page=ethereum-wallet">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}
	
	return $links;
}

add_filter( 'plugin_action_links', 'ETHEREUM_WALLET_actlinks', 10, 2 );

function ETHEREUM_WALLET_get_default_gas_price_gwei() {
    global $ETHEREUM_WALLET_options;
    $gasPriceMaxGwei = doubleval(isset($ETHEREUM_WALLET_options['gas_price']) ? $ETHEREUM_WALLET_options['gas_price'] : '41');
    return array('tm' => time(), 'gas_price' => $gasPriceMaxGwei);
}
    
function ETHEREUM_WALLET_query_gas_price_gwei() {    
    $apiEndpoint = "https://www.etherchain.org/api/gasPriceOracle";
    $response = wp_remote_get( $apiEndpoint, array('sslverify' => false) );
    if( is_wp_error( $response ) ) {
        ETHEREUM_WALLET_log("Error in gasPriceOracle response: ", $response);
        return ETHEREUM_WALLET_get_default_gas_price_gwei();
    }
    
    $http_code = wp_remote_retrieve_response_code( $response );
    if (200 != $http_code) {
        ETHEREUM_WALLET_log("Bad response code in gasPriceOracle response: ", $http_code);
        return ETHEREUM_WALLET_get_default_gas_price_gwei();
    }
    
    $body = wp_remote_retrieve_body( $response );
    if (!$body) {
        ETHEREUM_WALLET_log("empty body in gasPriceOracle response");
        return ETHEREUM_WALLET_get_default_gas_price_gwei();
    }
    
    $j = json_decode($body, true);
    if (!isset($j["fast"])) {
        ETHEREUM_WALLET_log("no fast field in gasPriceOracle response");
        return ETHEREUM_WALLET_get_default_gas_price_gwei();
    }
    
    $gasPriceGwei = $j["fast"];
    $cache_gas_price = array('tm' => time(), 'gas_price' => $gasPriceGwei);

    if ( get_option('ethereumicoio_cache_gas_price') ) {
        update_option('ethereumicoio_cache_gas_price', $cache_gas_price);
    } else {
        $deprecated='';
        $autoload='no';
        add_option('ethereumicoio_cache_gas_price', $cache_gas_price, $deprecated, $autoload);
    }
    return $cache_gas_price;
}

function ETHEREUM_WALLET_get_gas_price_wei() {
    // Get all existing Cryptocurrency Product options
    $cache_gas_price_gwei = get_option( 'ethereumicoio_cache_gas_price', array() );
    if (!$cache_gas_price_gwei) {
        $cache_gas_price_gwei = ETHEREUM_WALLET_query_gas_price_gwei();
    }
    
    $tm_diff = time() - intval($cache_gas_price_gwei['tm']);
    // TODO: admin setting
    $timeout = 10 * 60; // seconds
    if ($tm_diff > $timeout) {
        $cache_gas_price_gwei = ETHEREUM_WALLET_query_gas_price_gwei();
    }
    
    $gasPriceGwei = doubleval($cache_gas_price_gwei['gas_price']);
    $gasPriceMaxGwei = doubleval(ETHEREUM_WALLET_get_default_gas_price_gwei()['gas_price']);
    
    if ($gasPriceMaxGwei < $gasPriceGwei) {
        $gasPriceGwei = $gasPriceMaxGwei;
    }
    $gasPriceWei = 1000000000 * $gasPriceGwei; // gwei -> wei
    return intval($gasPriceWei);
}

function ETHEREUM_WALLET_send_ether($from, $to, $eth_value, $privateKey) {
    global $ETHEREUM_WALLET_options;
    
    $chainId = ETHEREUM_WALLET_getChainId();
    if (null === $chainId) {
        ETHEREUM_WALLET_log("chainId is null");
        return false;
    }
    
    $providerUrl = ETHEREUM_WALLET_getWeb3Endpoint();
    
    $eth_value_wei = _ETHEREUM_WALLET_double_int_multiply($eth_value, pow(10, 18));
    $eth_value_wei_str = $eth_value_wei->toString();
    
    // 1. check deposit
    $eth_balance = ETHEREUM_WALLET_getBalanceEth($providerUrl, $from);
    if (null === $eth_balance) {
        ETHEREUM_WALLET_log("eth_balance is null");
        return false;
    }
    if ($eth_balance->compare($eth_value_wei) < 0) {
        $eth_balance_str = $eth_balance->toString();
        ETHEREUM_WALLET_log("Insufficient funds: eth_balance_wei($eth_balance_str) < eth_value_wei($eth_value_wei_str)");
        return false;
    }
    $requestManager = new \Web3\RequestManagers\HttpRequestManager($providerUrl, 10/* seconds */);
    $web3 = new \Web3\Web3(new \Web3\Providers\HttpProvider($requestManager));
    $eth = $web3->eth;
    $nonce = 0;
    $eth->getTransactionCount($from, function ($err, $transactionCount) use(&$nonce) {
        if ($err !== null) {
            ETHEREUM_WALLET_log("Failed to getTransactionCount: " . $err);
            $nonce = null;
            return;
        }
        $nonce = intval($transactionCount->toString());
    });
    if (null === $nonce) {
        return false;
    }
    $data = '';
    $gasLimit = intval(isset($ETHEREUM_WALLET_options['gas_limit']) ? $ETHEREUM_WALLET_options['gas_limit'] : '200000');
    $gasPrice = ETHEREUM_WALLET_get_gas_price_wei();

    $nonce = \BitWasp\Buffertools\Buffer::int($nonce);
    $gasPrice = \BitWasp\Buffertools\Buffer::int($gasPrice);
    $gasLimit = \BitWasp\Buffertools\Buffer::int($gasLimit);
    $value = $eth_value_wei->toHex();
    ETHEREUM_WALLET_log("value: " . $value);

    $transaction = new \Web3p\EthereumTx\Transaction([
        'nonce' => '0x' . $nonce->getHex(),
        'to' => strtolower($to),
        'gas' => '0x' . $gasLimit->getHex(),
        'gasPrice' => '0x' . $gasPrice->getHex(),
        'value' => '0x' . $value,
        'chainId' => $chainId,
        'data' => '0x' . $data
    ]);
    $signedTransaction = "0x" . $transaction->sign($privateKey);
    ETHEREUM_WALLET_log("signedTransaction: " . $signedTransaction);

    $txHash = null;
    $eth->sendRawTransaction((string)$signedTransaction, function ($err, $transaction) use(&$txHash) {
        if ($err !== null) {
            ETHEREUM_WALLET_log("Failed to sendRawTransaction: " . $err);
            return;
        }
        $txHash = $transaction;
    });
    
    if (null === $txHash) {
        return false;
    }
    
    ETHEREUM_WALLET_log("txHash: " . $txHash);
    
    return $txHash;
}

function ETHEREUM_WALLET_getChainId() {
    $blockchainNetwork = ETHEREUM_WALLET_getBlockchainNetwork();
    if (empty($blockchainNetwork)) {
        $blockchainNetwork = 'mainnet';
    }
    if ($blockchainNetwork === 'mainnet') {
        return 1;
    }
    if ($blockchainNetwork === 'ropsten') {
        return 3;
    }
    if ($blockchainNetwork === 'rinkeby') {
        return 4;
    }
    ETHEREUM_WALLET_log("Bad blockchain_network setting:" . $blockchainNetwork);
    return null;
}

function _ETHEREUM_WALLET_double_int_multiply($dval, $ival) {
    $dval = doubleval($dval);
    $ival = intval($ival);
    $dv1 = floor($dval);
    $ret = new phpseclib\Math\BigInteger(intval($dv1));
    $ret = $ret->multiply(new phpseclib\Math\BigInteger($ival));
    if ($dv1 === $dval) {
        return $ret;
    }
    $dv2 = $dval - $dv1;
    $iv1 = intval($dv2 * $ival);
    $ret = $ret->add(new phpseclib\Math\BigInteger($iv1));
    return $ret;
}
        
} // PHP version
