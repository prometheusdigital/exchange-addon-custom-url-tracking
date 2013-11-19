<?php
/**
 * iThemes Exchange Custom URL Tracking Add-on
 * @package IT_Exchange_Addon_Custom_URL_Tracking
 * @since 1.0.0
*/

/**
 * New Product Features added by the Exchange Membership Add-on.
*/
require( 'lib/product-features/load.php' );

/**
 * Enqueues styles for Free Offers pages
 *
 * @since 1.0.0
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type WordPress Post Type
*/
function it_exchange_custom_url_tracking_addon_admin_wp_enqueue_styles( $hook_suffix, $post_type ) {
	if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		wp_enqueue_style( 'it-exchange-custom-url-tracking-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/lib/styles/add-edit-product.css' );
	}
}
//add_action( 'it_exchange_admin_wp_enqueue_styles', 'it_exchange_custom_url_tracking_addon_admin_wp_enqueue_styles', 10, 2 );

/**
 * Enqueues JS on add/edit product page
 *
 * @since 1.0.0
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type WordPress Post Type
*/
function it_exchange_custom_url_tracking_addon_admin_wp_enqueue_scripts( $hook_suffix, $post_type ) {
	if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		wp_enqueue_script( 'it-exchange-custom-url-tracking-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/lib/js/add-edit-product.js' );
	}
}
add_action( 'it_exchange_admin_wp_enqueue_scripts', 'it_exchange_custom_url_tracking_addon_admin_wp_enqueue_scripts', 10, 2 );

/**
 * Registers the custom query_vars we use to track clicks
 *
 * @since CHANGEME
 *
 * @param array $query_vars existing query vars
 * @return array
*/
function it_exchange_custom_url_tracking_register_query_vars( $vars ) {

	// Don't add if using default permalinks
	if ( ! get_option( 'permalink_structure' ) )
		return $vars;

	$vars[] = 'it_exchange_custom_url';
	return $vars;
}
add_filter( 'query_vars', 'it_exchange_custom_url_tracking_register_query_vars' );

/**
 * Grabs all the needed rewrite rules
 *
 * @since 1.0.0
 *
 * @return array
*/
function it_exchange_custom_url_tracking_addon_get_rewrite_rules() {
	
	$args = array(
		'meta_query' => array(
			array(
				'key'     => '_it-exchange-product-feature-custom-url-tracking',
				'compare' => 'EXISTS',
			)   
		),  
		'show_hidden' => true,
	);  
	$rewrite_posts = it_exchange_get_products( $args );

	$rules = array();
	foreach( (array) $rewrite_posts as $key => $values ) {
		$custom_urls = get_post_meta( $values->ID, '_it-exchange-product-feature-custom-url-tracking', true );
		if ( ! empty( $custom_urls ) ) {
			foreach( (array) $custom_urls as $custom_url ) {
				$slug   = empty( $custom_url['slug'] ) ? false :  $custom_url['slug'];
				$method = empty( $custom_url['method'] ) ? 'passthrough' : $custom_url['method'];
				if ( empty( $slug ) || ! in_array( $method, array( 'passthrough', 'redirect' ) ) )
					continue;

				if ( 'redirect' == $method ) {
					$rules = array_merge( array( $slug => 'index.php?p=' . $values->ID . '&it_exchange_custom_url=' . urlencode( $slug ) ), $rules );
				} else {
					$post_type    = 'it_exchange_prod';
					$product_slug = it_exchange_get_page_slug( 'product' );
					$product_name = $values->post_name;
					$rules = array_merge( array( $slug => 'index.php?page=0&post_type=' . $post_type . '&' . $product_slug . '=' . $product_name . '&p=' . $values->ID . '&it_exchange_custom_url=' . urlencode( $slug ) ), $rules );
				}
			}
		}
	}

	return $rules;
}

/**
 * This adds to the IT Exchange Rewrite Rules
 *
 * @since 1.0.0
 *
 * @param array $existing_rules the WP rewrite rules after Exchange adds to them
 * @return array
*/
function it_exchange_custom_url_tracking_addon_filter_rewrite_rules( $rules ) {

	// Don't add if using default permalinks
	if ( ! get_option( 'permalink_structure' ) )
		return $rules;

	$rules = array_merge( it_exchange_custom_url_tracking_addon_get_rewrite_rules(), $rules );
	return $rules;
}
add_filter( 'rewrite_rules_array', 'it_exchange_custom_url_tracking_addon_filter_rewrite_rules', 11 );

/**
 * Increment custom query var click
 *
 * @since 1.0.0
 *
 * return void
*/
function it_exchange_custom_url_tracking_addon_increment_custom_url_click() {

	// Don't add if using default permalinks
	if ( ! get_option( 'permalink_structure' ) )
		return;

	$post_id = get_query_var( 'p' );
	$custom_url = get_query_var( 'it_exchange_custom_url' );
	$custom_url = empty( $custom_url ) ? false : urldecode( $custom_url );

	// Set cookie on first time today
	if ( ! empty( $custom_url ) && empty( $_COOKIE['it-exchange-custom-url-' . sanitize_title_with_dashes( $custom_url )] ) ) {
		setcookie( 'it-exchange-custom-url-' . sanitize_title_with_dashes( $custom_url ), true, time()+3600*24 );
		$first_time = true;
	}

	if ( ! empty( $first_time) && ! empty( $post_id ) && ! empty( $custom_url ) ) {
		$custom_url_clicks = get_post_meta( $post_id, '_it_exchange_custom_url_clicks', true );
		$custom_url_clicks[$custom_url] = empty( $custom_url_clicks[$custom_url] ) ? 1 : $custom_url_clicks[$custom_url] + 1;
		update_post_meta( $post_id, '_it_exchange_custom_url_clicks', $custom_url_clicks );
	}
	unset( $custom_url );
}
add_action( 'wp', 'it_exchange_custom_url_tracking_addon_increment_custom_url_click', 99 );

/**
 * Filter the Builder Layout for the Custom URL
 *
 * @since 1.0.0
 *
 * @param string $layout the default builder layout
 * @return string
*/
function it_exchange_custom_url_tracking_addon_builder_layout( $layout ) {

	$var = get_query_var( 'it_exchange_custom_url' );
	if ( empty( $var ) || ! is_singular() )
		return $layout;

	$post_id     = empty( $GLOBALS['post']->ID ) ? 0 : $GLOBALS['post']->ID;
	$custom_urls = get_post_meta( $post_id, '_it-exchange-product-feature-custom-url-tracking', true ); 
	foreach( $custom_urls as $url => $data ) {
		if ( $var = $data['slug'] && ! empty( $data['builder-layout'] ) )
			return $data['builder-layout'];
	}

	return $layout;
}
add_filter( 'builder_filter_current_layout', 'it_exchange_custom_url_tracking_addon_builder_layout' );
