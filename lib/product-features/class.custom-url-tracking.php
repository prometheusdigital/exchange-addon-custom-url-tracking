<?php
/**
 * This controls the Custom URLs
 * @since 1.0.0
 * @package IT_Exchange_Addon_Custom_URL_Tracking
*/
class IT_Exchange_Addon_Custom_URL_Tracking_Product_Feature {

	/**
	 * Constructor. Registers hooks
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Addon_Custom_URL_Tracking_Product_Feature() {
		if ( is_admin() ) {
			add_action( 'load-post-new.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'load-post.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'it_exchange_save_product', array( $this, 'save_feature_on_product_save' ) );
			add_action( 'admin_init', array( $this, 'reset_url_counter' ) );
		}
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'add_feature_support_to_product_types' ) );
		add_action( 'it_exchange_update_product_feature_custom-url-tracking', array( $this, 'save_feature' ), 9, 3 );
		add_filter( 'it_exchange_get_product_feature_custom-url-tracking', array( $this, 'get_feature' ), 9, 3 );
		add_filter( 'it_exchange_product_has_feature_custom-url-tracking', array( $this, 'product_has_feature') , 9, 2 );
		add_filter( 'it_exchange_product_supports_feature_custom-url-tracking', array( $this, 'product_supports_feature') , 9, 2 );
	}

	/**
	 * Register the product feature and add it to enabled product-type addons
	 *
	 * @since 1.0.0
	*/
	function add_feature_support_to_product_types() {
		// Register the product feature
		$slug        = 'custom-url-tracking';
		$description = __( "Allows you to add custom URLs to products and to track usage.", 'LION' );
		it_exchange_register_product_feature( $slug, $description );

		// Add it to all enabled product-type addons
		$products = it_exchange_get_enabled_addons( array( 'category' => 'product-type' ) );
		foreach( $products as $key => $params ) { 
			it_exchange_add_feature_support_to_product_type( 'custom-url-tracking', $params['slug'] );
		}
	}

	/**
	 * Register's the metabox for any product type that supports the feature
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function init_feature_metaboxes() {
		
		global $post;
		
		if ( isset( $_REQUEST['post_type'] ) ) {
			$post_type = $_REQUEST['post_type'];
		} else {
			if ( isset( $_REQUEST['post'] ) )
				$post_id = (int) $_REQUEST['post'];
			elseif ( isset( $_REQUEST['post_ID'] ) )
				$post_id = (int) $_REQUEST['post_ID'];
			else
				$post_id = 0;

			if ( $post_id )
				$post = get_post( $post_id );

			if ( isset( $post ) && ! empty( $post ) )
				$post_type = $post->post_type;
		}
			
		if ( ! empty( $_REQUEST['it-exchange-product-type'] ) )
			$product_type = $_REQUEST['it-exchange-product-type'];
		else
			$product_type = it_exchange_get_product_type( $post );
		
		if ( ! empty( $post_type ) && 'it_exchange_prod' === $post_type ) {
			if ( ! empty( $product_type ) &&  it_exchange_product_type_supports_feature( $product_type, 'custom-url-tracking' ) )
				add_action( 'it_exchange_product_metabox_callback_' . $product_type, array( $this, 'register_metabox' ) );
		}
		
	}

	/**
	 * Registers the feature metabox for a specific product type
	 *
	 * Hooked to it_exchange_product_metabox_callback_[product-type] where product type supports the feature 
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function register_metabox() {
		add_meta_box( 'it-exchange-product-feature-custom-url-tracking', __( 'Custom URLs', 'LION' ), array( $this, 'print_metabox' ), 'it_exchange_prod', 'it_exchange_advanced' );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function print_metabox( $post ) {

		// Print notice and exist if using default permalinks
		if ( ! get_option( 'permalink_structure' ) ) {
			?>
			<div><p><?php _e( 'This feature is not available when using default permalinks.', 'LION' ); ?></p></div>
			<?php
			return;
		}

		// Grab the iThemes Exchange Product object from the WP $post object
		$product = it_exchange_get_product( $post );

		// Set the value of the feature for this product
		$existing_values = it_exchange_get_product_feature( $product->ID, 'custom-url-tracking' );

		if ( ! empty( $existing_values ) )
			$description = sprintf( __( "The following URLs will all map to %s.", 'LION' ), get_permalink( $post->ID ) );
		else
			$description = sprintf( __( 'No custom URLs have been created for this product.', 'LION' ), get_permalink( $post->ID ) );

		if ( $description ) {
			echo '<p class="intro-description">' . $description . '</p>';
		}
	
		?>
		<div class="existing-custom-urls">
			<div class="existing-header">
				<div class="existing-header-custom-urls custom-url-column-left"><?php _e( 'Custom URLs', 'LION' ); ?></div>
				<div class="existing-header-redirect custom-url-column-right"><?php _e( 'Redirect', 'LION' ); ?></div>
			</div>

			<?php $int = 0; ?>
			<?php while( $int < 3 ) : ?>
				<?php
				$int++;
				$existing_value = empty( $existing_values[$int] ) ? array() : $existing_values[$int];
				$slug           = empty( $existing_value['slug'] ) ? '' : $existing_value['slug'];
				$method         = ( ! empty( $slug) && ! empty( $existing_value['method'] ) && 'redirect' == $existing_value['method'] );
				$builder_layout = ( empty( $slug ) || empty( $existing_value['builder-layout'] ) ) ? false : $existing_value['builder-layout'];
				?>
				<div class="existing-custom-url existing-custom-url-<?php esc_attr_e( $int ); ?>">
					<div class="custom-url-column-left">
						<span class="base-url">
							<?php echo site_url(); ?>/
						</span>
						<input type="text" class="existing-url-slug existing-url-slug-<?php esc_attr_e( $int ); ?>" data-original-value="<?php esc_attr_e( $slug ); ?>" value="<?php esc_attr_e( $slug ); ?>" name="it-exchange-product-feature-custom-url-tracking[<?php esc_attr_e( $int ); ?>][slug]" />
						<?php if ( ! empty( $slug ) ) : ?>
						<a target="_blank" class="existing-url-link existing-url-link-<?php esc_attr_e( $int ); ?>" href="<?php esc_attr_e( get_site_url() ); ?>/<?php esc_attr_e( $slug ); ?>"><?php _e( 'View', 'LION' ); ?></a>
						<?php endif; ?>
					</div>
					
					<div class="custom-url-column-right">
						<input type="checkbox" class="existing-url-method existing-url-method-<?php esc_attr_e( $int ); ?>" value="redirect" name="it-exchange-product-feature-custom-url-tracking[<?php esc_attr_e( $int ); ?>][method]" <?php checked( $method ); ?>/>
						<?php /*
						<a class="existing-url-delete existing-url-delete-<?php esc_attr_e( $int ); ?>" href="<?php echo wp_nonce_url( add_query_arg( 'it-exchange-delete-custom-url', urlencode( $slug ) ), 'it-exchange-delete-custom-url-' . urlencode( $slug ) ); ?>">&times;</a>
						*/ ?>
					</div>

					<?php if ( function_exists( 'builder_add_theme_features' ) ) : ?>
					<div class="existing-url-builder-layout-div existing-url-builder-layout-div-<?php esc_attr_e( $int ); ?>">
						<span>Builder Layout</span>
						<select class="existing-url-builder-layout existing-url-builder-layout-<?php esc_attr_e( $int ); ?>" name="it-exchange-product-feature-custom-url-tracking[<?php esc_attr_e( $int ); ?>][builder-layout]">
							<?php $this->print_builder_layout_select_box_options( $builder_layout ); ?>
						</select>
					</div>
					<?php endif; ?>
				</div>
			<?php endwhile; ?>
		</div>

		<div class="custom-stats">
			<?php
			$custom_clicks = get_post_meta( $post->ID, '_it_exchange_custom_url_clicks', true );
			if ( is_array( $custom_clicks ) && count( $custom_clicks ) > 0 ) {
				echo '<br />';
				echo '<div class="unique-views-heading">' . __( 'Unique Views', 'LION' ) . '</div>';
				echo '<div class="unique-views-list">';
				foreach( $custom_clicks as $url => $int ) {
					echo '<div class="unique-views-item">';
					echo '<div class="unique-views-item-url">/' . $url . '</div>';
					echo '<div class="unique-views-item-count">' . $int . '</div>';
					echo '<div class="unique-views-item-count-reset">';
					echo '	<a href="' . wp_nonce_url( add_query_arg( 'it-exchange-custom-url', urlencode( $url ) ), 'it-exchange-reset-custom-url-count-' . urlencode( $url ) ) .'">' . __( 'Reset', 'LION' ) . '</a>';
					echo '</div>';
					echo '</div>';
				}
				echo '</div>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Generates the Builder Layout select box
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function print_builder_layout_select_box_options( $selected=false ) {
		$layout_data = apply_filters( 'it_storage_load_layout_settings', array() );
		?>
		<option value=""><?php _e( 'Default Layout', 'LION' ); ?></option>
		<?php
		foreach ( (array) $layout_data['layouts'] as $layout => $layout_data ) {
			echo '<option value="' . esc_attr( $layout ) . '"' . selected( $layout, $selected ) . '>' . $layout_data['description'] . '</option>';
		}
	}

	/**
	 * Reset the counter
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function reset_url_counter() {
		$url   = empty( $_GET['it-exchange-custom-url'] ) ? false : $_GET['it-exchange-custom-url'];
		$nonce = empty( $_GET['_wpnonce'] ) ? false : $_GET['_wpnonce'];
		$post  = empty( $_GET['post'] ) ? false : $_GET['post'];

		if ( empty( $url ) || empty( $nonce ) || empty( $post ) || ! wp_verify_nonce( $nonce, 'it-exchange-reset-custom-url-count-' . urlencode( $url ) ) )
			return;

		// Grab URL counts
		$custom_clicks = get_post_meta( $post, '_it_exchange_custom_url_clicks', true );
		if ( isset( $custom_clicks[$url] ) ){
			$custom_clicks[$url] = 0;
			update_post_meta( $post, '_it_exchange_custom_url_clicks', $custom_clicks );
			$updated = true;
		}

		/**
		* @todo Add notification messages to add/edit screen. We don't display them currently
		* $message = empty( $updated ) ? __( 'Error. URL counter not reset. Please try again.', 'LION' ) : __( 'Custom URL counter reset.', 'LION' );
		* it_exchange_add_message( empty( $updated) ? 'error' : 'notice', $message );
		*/
		wp_redirect( remove_query_arg( '_wpnonce', remove_query_arg( 'it-exchange-custom-url' ) ) );
		die();
	}

	/**
	 * This saves the value
	 *
	 * @since 1.0.0
	 *
	 * @param object $post wp post object
	 * @return void
	*/
	function save_feature_on_product_save() {

		// Don't add if using default permalinks
		if ( ! get_option( 'permalink_structure' ) ) 
			return;

		// Abort if we can't determine a product type
		if ( ! $product_type = it_exchange_get_product_type() )
			return;

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];
		if ( ! $product_id )
			return;

		// Abort if this product type doesn't support this feature 
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'custom-url-tracking' ) || empty( $_POST['it-exchange-product-feature-custom-url-tracking']  ))
			return;

		// If the value is empty (0), delete the key, otherwise save
		if ( empty( $_POST['it-exchange-product-feature-custom-url-tracking'] ) )
			delete_post_meta( $product_id, '_it-exchange-product-feature-custom-url-tracking' );
		else
			it_exchange_update_product_feature( $product_id, 'custom-url-tracking', $_POST['it-exchange-product-feature-custom-url-tracking'] );
	}

	/**
	 * This updates the feature for a product
	 *
	 * @since 1.0.0
	 *
	 * @param integer $product_id the product id
	 * @param mixed $new_value the new value 
	 * @return bolean
	*/
	function save_feature( $product_id, $new_value ) {

		// Don't save if using default permalinks
		if ( ! get_option( 'permalink_structure' ) ) 
			return false;

		// Delete any vars that are empty
		$new_value = array_filter( $new_value );

		if ( empty( $new_value ) ) {
			delete_post_meta( $product_id, '_it-exchange-product-feature-custom-url-tracking' );
		} else {
			update_post_meta( $product_id, '_it-exchange-product-feature-custom-url-tracking', $new_value );
			add_option( '_it-exchange-flush-rewrites', true );
		}
		return true;
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.0.0
	 * @param mixed $existing the values passed in by the WP Filter API. Ignored here.
	 * @param integer product_id the WordPress post ID
	 * @return array product feature
	*/
	function get_feature( $existing, $product_id ) {

		// Don't return if using default permalinks
		if ( ! get_option( 'permalink_structure' ) ) 
			return false;

		// Is the the add / edit product page?
		$current_screen = is_admin() ? get_current_screen(): false;
		$editing_product = ( ! empty( $current_screen->id ) && 'it_exchange_prod' == $current_screen->id );

		// Return the value if supported or on add/edit screen
		if ( it_exchange_product_supports_feature( $product_id, 'custom-url-tracking' ) || $editing_product )
			return get_post_meta( $product_id, '_it-exchange-product-feature-custom-url-tracking', true );

		return false;
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_has_feature( $result, $product_id ) {

		// Return false if using default permalinks
		if ( ! get_option( 'permalink_structure' ) ) 
			return false;

		// Does this product type support this feature?
		if ( false === $this->product_supports_feature( false, $product_id ) )
			return false;
		return (boolean) $this->get_feature( false, $product_id );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can 
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_supports_feature( $result, $product_id ) {

		// Return false if using default permalinks
		if ( ! get_option( 'permalink_structure' ) ) 
			return false;

		// Does this product type support this feature?
		$product_type = it_exchange_get_product_type( $product_id );
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'custom-url-tracking' ) )
			return false;

		return true;
	}
}
$IT_Exchange_Addon_Custom_URL_Tracking_Product_Feature = new IT_Exchange_Addon_Custom_URL_Tracking_Product_Feature();
