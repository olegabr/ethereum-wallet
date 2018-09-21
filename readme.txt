=== Ethereum Wallet ===
Contributors: ethereumicoio
Tags: Ethereum Wallet, ethereum, wallet, erc20, ICO, initial coin offering, cryptocurrency
Requires at least: 3.7
Tested up to: 4.9.4
Stable tag: 1.4.2
Donate link: https://etherscan.io/address/0x476Bb28Bc6D0e9De04dB5E19912C392F9a76535d
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 7.0

The user friendly Ethereum Wallet for your WordPress site.

== Description ==

The Ethereum Wallet WordPress plugin auto-creates a user wallet upon registration and allows user to send Ether or ERC20/ERC223 tokens from it.

> It is a valuable addition for the [Cryptocurrency Product for WooCommerce](https://ethereumico.io/product/cryptocurrency-product-for-woocommerce-standard-license/ "Cryptocurrency Product for WooCommerce") plugin.

Using these two plugins your non-techie customers can register to obtain an Ethereum account address and then buy your tokens to be sent to this new address.

* To show user's Ethereum account address insert the `[ethereum-wallet-account]` shortcode wherever you like. You can use `label="My label"` attribute to set your own label text. And `nolabel="yes"` attribute to display no label at all.
* To show user's Ethereum account address's Ether balance insert the `[ethereum-wallet-balance]` shortcode wherever you like
* To show user's Ethereum account address's TSX ERC20 token balance insert the `[ethereum-wallet-balance tokenname="TSX" tokenaddress="0x6Fe928d427b0E339DB6FF1c7a852dc31b651bD3a"]` shortcode wherever you like. [PRO version only!](https://ethereumico.io/product/wordpress-ethereum-wallet-plugin/ "The Ethereum Wallet WordPress plugin")
* To show the send Ether form insert the `[ethereum-wallet-sendform]` shortcode wherever you like. The ERC20 token send functionality is available in the [PRO version only!](https://ethereumico.io/product/wordpress-ethereum-wallet-plugin/ "The Ethereum Wallet WordPress plugin").
* To show an account's transactions history insert the `[ethereum-wallet-history direction="in"]` shortcode wherever you like. The direction attribute can have values `in` to show only input transactions, `out` to show only output transactions, or `inout` to show both input and output transactions. If attribute is omitted, the `inout` is used by default. The ERC20 tokens transactions are displayed in a [PRO version only!](https://ethereumico.io/product/wordpress-ethereum-wallet-plugin/ "The Ethereum Wallet WordPress plugin")
* Use the `user_ethereum_wallet_address` user_meta key to display the user's account address, or for the `Ethereum Wallet meta key` setting of the [Cryptocurrency Product for WooCommerce](https://ethereumico.io/product/cryptocurrency-product-for-woocommerce-standard-license/ "Cryptocurrency Product for WooCommerce") plugin
* The Ethereum Gas price is auto adjusted according to the [etherchain.org](https://www.etherchain.org) API
* This plugin is l10n ready

> See the official site for a live demo: [https://ethereumico.io/ethereum-wallet/](https://ethereumico.io/ethereum-wallet/ "The Ethereum Wallet WordPress plugin")

== PRO version features ==

> The [PRO version only](https://ethereumico.io/product/wordpress-ethereum-wallet-plugin/ "The Ethereum Wallet WordPress plugin") features

* An Ethereum account address's ERC20 token balance can be displayed with the `[ethereum-wallet-balance tokenname="TSX" tokenaddress="0x6Fe928d427b0E339DB6FF1c7a852dc31b651bD3a"]` shortcode wherever you like
* The `send form` shortcode can be used to send ERC20 tokens: `[ethereum-wallet-sendform]`
* The transactions history shortcode can display ERC20 tokens transfers: `[ethereum-wallet-history]`

== Screenshots ==

1. The `[ethereum-wallet-account]` and `[ethereum-wallet-balance]` display
2. The `[ethereum-wallet-sendform]` display
3. The `[ethereum-wallet-history]` display
4. The plugin settings

== Disclaimer ==

**By using this plugin you accept all responsibility for handling the account balances for all your users.**

Under no circumstances is **ethereumico.io** or any of its affiliates responsible for any damages incurred by the use of this plugin.

Every effort has been made to harden the security of this plugin, but its safe operation depends on your site being secure overall. You, the site administrator, must take all necessary precautions to secure your WordPress installation before you connect it to any live wallets.

You are strongly advised to take the following actions (at a minimum):

- [Educate yourself about cold and hot cryptocurrency storage](https://en.bitcoin.it/wiki/Cold_storage)
- Obtain hardware wallet to store your coins, like [Ledger Nano S](https://www.ledgerwallet.com/r/4caf109e65ab?path=/products/ledger-nano-s), [TREZOR](https://shop.trezor.io?a=ethereumico.io) or [KeepKey](http://keepkey.go2cloud.org/aff_c?offer_id=1&aff_id=5037)
- [Educate yourself about hardening WordPress security](https://codex.wordpress.org/Hardening_WordPress)
- [Install a security plugin such as Jetpack](https://jetpack.com/pricing/?aff=9181&cid=886903) or any other security plugin
- **Enable SSL on your site** if you have not already done so.

> By continuing to use the Ethereum Wallet WordPress plugin, you indicate that you have understood and agreed to this disclaimer.

== Installation ==

Enter your settings in admin pages and place the `[ethereum-wallet-sendform]`, `[ethereum-wallet-balance]` and other shortcodes wherever you need it.

= bcmath and gmp =

`
sudo apt-get install php-bcmath php-gmp
service apache2 restart
`

For AWS bitnami AMI restart apache2 with this command:

`
sudo /opt/bitnami/ctlscript.sh restart apache
`

= Shortcodes =

Possible shortcodes configuration:

`
[ethereum-wallet-account label="Your wallet:"]

[ethereum-wallet-account nolabel="yes"]

[ethereum-wallet-balance]

[ethereum-wallet-balance tokenname="TSX" tokenaddress="0x6Fe928d427b0E339DB6FF1c7a852dc31b651bD3a"]

[ethereum-wallet-sendform]

[ethereum-wallet-history]

[ethereum-wallet-history direction="in"]

[ethereum-wallet-history direction="out"]
`

= Infura.io Api Key =

Register for an infura.io API key and put it in admin settings. It is required to interact with Ethereum blockchain. After register you'll get a mail with links like that: `https://mainnet.infura.io/1234567890`. The `1234567890` part is the API Key required.

== Testing ==

You can test this plugin in some test network for free.

=== Testing in ropsten ===

* Set the `Blockchain` setting to `ropsten`
* "Buy" some Ropsten Ether for free using [MetaMask](https://metamask.io)
* Send some Ropsten Ether to the account this plugin generated for you. Use `[ethereum-wallet-account]` shortcode to display it
* Send some Ropsten Ether to the `0x773F803b0393DFb7dc77e3f7a012B79CCd8A8aB9` address to obtain TSX tokens. The TSX token has the `0x6Fe928d427b0E339DB6FF1c7a852dc31b651bD3a` address.
* Use your favorite wallet to send TSX tokens to the account this plugin generated for you
* Now test the plugin by sending some Ropsten Ether and/or TSX tokens from the generated account address to your other address. Use the `[ethereum-wallet-sendform]` shortcode to render the send form on a page.
* Check that proper amount of Ropsten Ether and/or TSX tokens has been sent to your payment address
* You can use your own token to test the same

=== Testing in rinkeby ===

* Set the `Blockchain` setting to `rinkeby`
* You can "buy" some Rinkeby Ether for free here: [rinkeby.io](https://www.rinkeby.io/#faucet)
* Send some Rinkeby Ether to the account this plugin generated for you. Use `[ethereum-wallet-account]` shortcode to display it
* Send some Rinkeby Ether to the `0x669519e1e150dfdfcf0d747d530f2abde2ab3f0e` address to obtain TSX tokens. The TSX token has the `0x194c35B62fF011507D6aCB55B95Ad010193d303E` address.
* Use your favorite wallet to send TSX tokens to the account this plugin generated for you
* Now test the plugin by sending some Rinkeby Ether and/or TSX tokens from the generated account address to your other address. Use the `[ethereum-wallet-sendform]` shortcode to render the send form on a page.
* Check that proper amount of Rinkeby Ether and/or TSX tokens has been sent to your payment address
* You can use your own token to test the same

== Upgrade Notice ==

When new plugin version is released, do not use the standard WordPress update.
Download new PRO version from the [https://ethereumico.io/](https://ethereumico.io/) site, deactivate and delete the old plugin version, then install the new version..

> Do not worry about your settings, they would be preserved.

== l10n ==

This plugin is localization ready.

Languages this plugin is available now:

* English
* Russian(Русский)

Feel free to translate this plugin to your language.

== Changelog ==

= 1.4.2 =

* Fix path for wpspin_light.gif in CSS

= 1.4.1 =

* CSS fix for some themes

= 1.4.0 =

* Warn user if there are no Ether to pay tx fee when sending tokens

= 1.3.0 =

* Implement internal tx display in a history table
* Russian language translation is added

= 1.2.1 =

* Check for PHP version before the `autoload.php` file inclusion to prevent errors for PHP 5.X versions

= 1.2.0 =

* The Ethereum Gas price is auto adjusted according to the [etherchain.org](https://www.etherchain.org) API
* Check for the gmp PHP module is added on the plugin activation
* js error on pages with no Ethereum Wallet WordPress plugin shortcodes is fixed

= 1.1.0 =

* `label` and `nolabel` attributes for the `ethereum-wallet-account` shortcode added
* `direction` attribute for the `ethereum-wallet-history` shortcode added

= 1.0.2 =

* Fix etherscan.io empty decimal and symbol fields API issues

= 1.0.1 =

* Fix error in history shortcode

= 1.0.0 =

* Initial release
