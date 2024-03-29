<?php
/*
 * Plugin Name: ExchangeWP - Custom URL Tracking
 * Version: 1.2.2
 * Description: Allows you to add custom URLs to products and to track usage.
 * Plugin URI: https://exchangewp.com/downloads/custom-url-tracking/
 * Author: ExchangeWP
 * Author URI: https://exchangewp.com
 * ExchangeWP Package: exchange-addon-custom-url-tracking

 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 *
*/

/**
 * This registers our plugin as a membership addon
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_register_custom_url_tracking_addon() {
	$options = array(
		'name'              => __( 'Custom URL Tracking', 'LION' ),
		'description'       => __( 'Allows you to add custom URLs to products and to track usage.', 'LION' ),
		'author'            => 'ExchangeWP',
		'author_url'        => 'https://exchangewp.com/downloads/custom-url-tracking/',
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/custom-url-tracking-50px.png' ),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'category'          => 'product-feature',
		'basename'          => plugin_basename( __FILE__ ),
	);
	it_exchange_register_addon( 'custom-url-tracking', $options );
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_custom_url_tracking_addon' );

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
*/
function it_exchange_custom_url_tracking_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'it_exchange_custom_url_tracking_set_textdomain' );

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
function exchange_custom_url_tracking_plugin_updater() {

	$license_check = get_transient( 'exchangewp_license_check' );
		if ($license_check->license == 'valid' ) {
			$license_key = it_exchange_get_option( 'exchangewp_licenses' );
			$license = $license_key['exchange_license'];

			// setup the updater
			$edd_updater = new EDD_SL_Plugin_Updater( 'https://exchangewp.com', __FILE__, array(
					'version' 		=> '1.2.2', 				// current version number
					'license' 		=> $license, 		// license key (used get_option above to retrieve from DB)
					'item_id'		 	=> 450,					 	  // name of this plugin
					'author' 	  	=> 'ExchangeWP',    // author of this plugin
					'url'       	=> home_url(),
					'wp_override' => true,
					'beta'		  	=> false
				)
			);
		}
}

add_action( 'admin_init', 'exchange_custom_url_tracking_plugin_updater', 0 );
