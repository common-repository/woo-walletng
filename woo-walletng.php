<?php
/*
	Plugin Name:			WooCommerce wallet.ng Payment Gateway
	Plugin URI: 			https://wallet.ng
	Description: 			WooCommerce payment gateway for wallet.ng
	Version:				1.0.0
	Author: 				Tunbosun Ayinla
	Author URI: 			https://bosun.me
	License:        		GPL-2.0+
	License URI:    		http://www.gnu.org/licenses/gpl-2.0.txt
	WC requires at least: 	3.0.0
	WC tested up to: 		3.3.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TBZ_WC_WNG_MAIN_FILE', __FILE__ );

define( 'TBZ_WC_WNG_VERSION', '1.0.0' );

function tbz_wc_wallet_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once dirname( __FILE__ ) . '/includes/class-walletng.php';


	add_filter( 'woocommerce_payment_gateways', 'tbz_wc_add_wallet_gateway' );

}
add_action( 'plugins_loaded', 'tbz_wc_wallet_init', 0 );


/**
* Add Settings link to the plugin entry in the plugins menu
**/
function tbz_woo_wallet_plugin_action_links( $links ) {

    $settings_link = array(
    	'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=walletng' ) . '" title="View Settings">Settings</a>'
    );

    return array_merge( $links, $settings_link );

}
add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'tbz_woo_wallet_plugin_action_links' );


/**
* Add wallet.ng Gateway to WC
**/
function tbz_wc_add_wallet_gateway( $methods ) {

	$methods[] = 'Tbz_WC_Wallet_Gateway';

	return $methods;

}


/**
* Check if plugin is configured
**/
function tbz_wc_wallet_admin_notices() {

	$settings   = get_option( 'woocommerce_walletng_settings' );

	$public_key = $settings['testmode'] === 'yes' ? $settings['test_public_key'] : $settings['live_public_key'];
	$secret_key = $settings['testmode'] === 'yes' ? $settings['test_secret_key'] : $settings['live_secret_key'];

	if ( isset( $settings['testmode'] ) && ( 'yes' === $settings['testmode'] ) ) {

		echo sprintf( '<div class="update-nag" style="color:red;">wallet.ng test mode is still enabled, remember to disable it when you want to start accepting real payment on your site. You can do that <a href="%s">here</a></div>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=walletng' ) );

	}

	if ( ( ! $public_key && ! $secret_key ) ) {

		echo sprintf( '<div class="update-nag" style="color:red;">You need to enter your wallet.ng Public Key and Secret Key <a href="%s">here</a> to be able to process payment using the wallet.ng WooCommerce payment gateway plugin.</div>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=walletng' ) );

	}

	else if ( ! $public_key ) {

		echo sprintf( '<div class="update-nag" style="color:red;">You need to enter your wallet.ng Public Key <a href="%s">here</a> to be able to process payment using the wallet.ng WooCommerce payment gateway plugin.</div>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=walletng' ) );

	}

	else if ( ! $secret_key ) {

		echo sprintf( '<div class="update-nag" style="color:red;">You need to enter your wallet.ng Secret Key <a href="%s">here</a> to be able to process payment using the wallet.ng WooCommerce payment gateway plugin.</div>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=walletng' ) );
	}

}
add_action( 'admin_notices', 'tbz_wc_wallet_admin_notices' );