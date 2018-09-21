<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function ETHEREUM_WALLET_options_page() {

	// Require admin privs
	if ( ! current_user_can( 'manage_options' ) )
		return false;
	
	$new_options = array();
	
	// Which tab is selected?
	$possible_screens = array( 'default', 'floating' );
	$current_screen = ( isset( $_GET['action'] ) && in_array( $_GET['action'], $possible_screens ) ) ? $_GET['action'] : 'default';
	
	if ( isset( $_POST['Submit'] ) ) {
		
		// Nonce verification 
		check_admin_referer( 'ethereum-wallet-update-options' );

        // Standard options screen

//        $new_options['wallet_address']        = ( ! empty( $_POST['ETHEREUM_WALLET_wallet_address'] )       /*&& is_numeric( $_POST['ETHEREUM_WALLET_wallet_address'] )*/ )       ? sanitize_text_field($_POST['ETHEREUM_WALLET_wallet_address'])        : '';
//        if ( ! empty( $_POST['ETHEREUM_WALLET_wallet_private_key'] ) ) {
//            $new_options['wallet_private_key'] = sanitize_text_field($_POST['ETHEREUM_WALLET_wallet_private_key']);
//        }
        $new_options['gas_limit']         = ( ! empty( $_POST['ETHEREUM_WALLET_gas_limit'] )         && is_numeric( $_POST['ETHEREUM_WALLET_gas_limit'] ) )             ? intval(sanitize_text_field($_POST['ETHEREUM_WALLET_gas_limit']))  : 200000;
        $new_options['gas_price']             = ( ! empty( $_POST['ETHEREUM_WALLET_gas_price'] )             && is_numeric( $_POST['ETHEREUM_WALLET_gas_price'] ) )                 ? floatval(sanitize_text_field($_POST['ETHEREUM_WALLET_gas_price']))    : 2;
        $new_options['blockchain_network']      = ( ! empty( $_POST['ETHEREUM_WALLET_blockchain_network'] )      /*&& is_numeric( $_POST['ETHEREUM_WALLET_blockchain_network'] )*/ )      ? sanitize_text_field($_POST['ETHEREUM_WALLET_blockchain_network'])       : 'mainnet';
        $new_options['infuraApiKey']     = ( ! empty( $_POST['ETHEREUM_WALLET_infuraApiKey'] )     /*&& is_numeric( $_POST['ETHEREUM_WALLET_infuraApiKey'] )*/ )     ? sanitize_text_field($_POST['ETHEREUM_WALLET_infuraApiKey'])      : '';

		// Get all existing Ethereum Wallet options
		$existing_options = get_option( 'ethereum-wallet_options', array() );
		
		// Merge $new_options into $existing_options to retain Ethereum Wallet options from all other screens/tabs
		if ( $existing_options ) {
			$new_options = array_merge( $existing_options, $new_options );
		}
		
        if ( get_option('ethereum-wallet_options') ) {
            update_option('ethereum-wallet_options', $new_options);
        } else {
            $deprecated='';
            $autoload='no';
            add_option('ethereum-wallet_options', $new_options, $deprecated, $autoload);
        }
		
		?>
		<div class="updated"><p><?php _e( 'Settings saved.' ); ?></p></div>
		<?php
		
	} else if ( isset( $_POST['Reset'] ) ) {
		// Nonce verification 
		check_admin_referer( 'ethereum-wallet-update-options' );
		
		delete_option( 'ethereum-wallet_options' );
	}

	$options = stripslashes_deep( get_option( 'ethereum-wallet_options', array() ) );
	
	?>
	
	<div class="wrap">
	
	<h1><?php _e( 'Ethereum Wallet Settings', 'ethereum-wallet' ); ?></h1>
	
	<h2 class="nav-tab-wrapper">
		<a href="<?php echo admin_url( 'options-general.php?page=ethereum-wallet' ); ?>" class="nav-tab<?php if ( 'default' == $current_screen ) echo ' nav-tab-active'; ?>"><?php esc_html_e( 'Standard' ); ?></a>
	</h2>

	<form id="ethereum-wallet_admin_form" method="post" action="">
	
	<?php wp_nonce_field('ethereum-wallet-update-options'); ?>

		<table class="form-table">
		
		<?php if ( 'default' == $current_screen ) : ?>			
			<tr valign="top">
			<th scope="row"><?php _e("Infura.io API Key", 'ethereum-wallet'); ?></th>
			<td><fieldset>
				<label>
                    <input class="text" name="ETHEREUM_WALLET_infuraApiKey" type="text" maxlength="35" placeholder="<?php _e("Put your Infura.io API Key here", 'ethereum-wallet'); ?>" value="<?php echo ! empty( $options['infuraApiKey'] ) ? esc_attr( $options['infuraApiKey'] ) : ''; ?>">
                    <p><?php echo sprintf(
                            __('The API key for the %1$s. You need to register on this site to obtain it. After register you\'ll get a mail with links like that: %2$s. Copy the %3$s part here.', 'ethereum-wallet')
                            , '<a target="_blank" href="https://infura.io/signup">https://infura.io/</a>'
                            , 'https://mainnet.infura.io/1234567890'
                            , '<strong>1234567890</strong>'
                        )?></p>
                </label>
			</fieldset></td>
			</tr>
			
			<tr valign="top">
			<th scope="row"><?php _e("Blockchain", 'ethereum-wallet'); ?></th>
			<td><fieldset>
				<label>
                    <input class="text" name="ETHEREUM_WALLET_blockchain_network" type="text" maxlength="128" placeholder="mainnet" value="<?php echo ! empty( $options['blockchain_network'] ) ? esc_attr( $options['blockchain_network'] ) : 'mainnet'; ?>">
                    <p><?php _e("The blockchain used: mainnet or ropsten. Use mainnet in production, and ropsten in test mode. See plugin documentation for the testing guide.", 'ethereum-wallet') ?></p>
                </label>
			</fieldset></td>
			</tr>

			<tr valign="top">
			<th scope="row"><?php _e("Gas Limit", 'ethereum-wallet'); ?></th>
			<td><fieldset>
				<label>
                    <input class="text" name="ETHEREUM_WALLET_gas_limit" type="number" min="0" step="10000" maxlength="8" placeholder="200000" value="<?php echo ! empty( $options['gas_limit'] ) ? esc_attr( $options['gas_limit'] ) : '200000'; ?>">
                    <p><?php _e("The default gas limit to to spend on your transactions. 200000 is a reasonable default value.", 'ethereum-wallet') ?></p>
                </label>
			</fieldset></td>
			</tr>
			
			<tr valign="top">
			<th scope="row"><?php _e("Gas price", 'ethereum-wallet'); ?></th>
			<td><fieldset>
				<label>
                    <input class="text" name="ETHEREUM_WALLET_gas_price" type="number" min="0" step="1" maxlength="8" placeholder="2" value="<?php echo ! empty( $options['gas_price'] ) ? esc_attr( $options['gas_price'] ) : '2'; ?>">
                    <p><?php _e("The gas price in Gwei. Reasonable values are in a 2-40 ratio. The default value is 2 that is cheap but not very fast. Increase if you want transactions to be mined faster, decrease if you want pay less fee per transaction.", 'ethereum-wallet') ?></p>
                </label>
			</fieldset></td>
			</tr>

		<?php endif; ?>
		
		</table>

		<p class="submit">
			<input class="button-primary" type="submit" name="Submit" value="<?php _e('Save Changes', 'ethereum-wallet' ) ?>" />
			<input id="ETHEREUM_WALLET_reset_options" type="submit" name="Reset" onclick="return confirm('<?php _e('Are you sure you want to delete all Ethereum Wallet options?', 'ethereum-wallet' ) ?>')" value="<?php _e('Reset', 'ethereum-wallet' ) ?>" />
		</p>
	
	</form>
    
    <h2><?php _e("Want to sell ERC20 token for fiat and/or Bitcoin?", 'ethereum-wallet'); ?></h2>
    <p><?php echo sprintf(
        __('Install the %1$sCryptocurrency Product for WooCommerce plugin%2$s!', 'ethereum-wallet')
        , '<a target="_blank" href="https://ethereumico.io/product/cryptocurrency-product-for-woocommerce-standard-license/">'
        , '</a>'
    )?></p>

    <h2><?php _e("Want to accept Ether or any ERC20/ERC223 token in your WooCommerce store?", 'ethereum-wallet'); ?></h2>
    <p><?php echo sprintf(
        __('Install the %1$sEther and ERC20 tokens WooCommerce Payment Gateway%2$s plugin!', 'ethereum-wallet')
        , '<a target="_blank" href="https://wordpress.org/plugins/ether-and-erc20-tokens-woocommerce-payment-gateway/">'
        , '</a>'
    )?></p>

    <h2><?php _e("Want to sell your ERC20/ERC223 ICO token from your ICO site?", 'ethereum-wallet'); ?></h2>
    <p><?php echo sprintf(
        __('Install the %1$sThe EthereumICO Wordpress plugin%2$s!', 'ethereum-wallet')
        , '<a target="_blank" href="https://ethereumico.io/product/ethereum-ico-wordpress-plugin/">'
        , '</a>'
    )?></p>

    <h2><?php _e("Need help to configure this plugin?", 'ethereum-wallet'); ?></h2>
    <p><?php echo sprintf(
        __('Feel free to %1$shire me!%2$s', 'ethereum-wallet')
        , '<a target="_blank" href="https://www.upwork.com/freelancers/~0134e80b874bd1fa5f">'
        , '</a>'
    )?></p>

    <h2><?php _e("Need help to develop a ERC20 token for your ICO?", 'ethereum-wallet'); ?></h2>
    <p><?php echo sprintf(
        __('Feel free to %1$shire me!%2$s', 'ethereum-wallet')
        , '<a target="_blank" href="https://www.upwork.com/freelancers/~0134e80b874bd1fa5f">'
        , '</a>'
    )?></p>

    </div>

<?php

    // Used in Free version only
    $_ = __('In order to sell your ERC20/ERC223 tokens install the %1$sPRO plugin version%2$s please.', 'ethereum-wallet');

}
