<?php
/**
 * Hook Inspector
 *
 * Captures and exposes all registered WooCommerce hooks so that they
 * can be displayed in the admin panel.
 *
 * @package WHM
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WHM_Hook_Inspector
 */
class WHM_Hook_Inspector {

	/**
	 * Known WooCommerce action/filter hooks to inspect.
	 * This list covers the most commonly used hooks.
	 *
	 * @var string[]
	 */
	private $wc_hooks = array(
		// Product single page.
		'woocommerce_single_product_summary',
		'woocommerce_before_single_product',
		'woocommerce_after_single_product',
		'woocommerce_before_single_product_summary',
		'woocommerce_after_single_product_summary',
		'woocommerce_before_add_to_cart_button',
		'woocommerce_after_add_to_cart_button',
		'woocommerce_before_add_to_cart_form',
		'woocommerce_after_add_to_cart_form',
		// Shop / archive.
		'woocommerce_before_shop_loop',
		'woocommerce_after_shop_loop',
		'woocommerce_before_shop_loop_item',
		'woocommerce_after_shop_loop_item',
		'woocommerce_before_shop_loop_item_title',
		'woocommerce_after_shop_loop_item_title',
		'woocommerce_shop_loop_item_title',
		// Cart.
		'woocommerce_before_cart',
		'woocommerce_after_cart',
		'woocommerce_before_cart_table',
		'woocommerce_after_cart_table',
		'woocommerce_cart_contents',
		'woocommerce_after_cart_contents',
		'woocommerce_cart_collaterals',
		// Checkout.
		'woocommerce_before_checkout_form',
		'woocommerce_after_checkout_form',
		'woocommerce_checkout_before_customer_details',
		'woocommerce_checkout_after_customer_details',
		'woocommerce_checkout_order_review',
		'woocommerce_checkout_billing',
		'woocommerce_checkout_shipping',
		// Account.
		'woocommerce_before_account_navigation',
		'woocommerce_after_account_navigation',
		'woocommerce_account_content',
		// Thank-you page.
		'woocommerce_before_thankyou',
		'woocommerce_thankyou',
		'woocommerce_after_thankyou',
		// Misc.
		'woocommerce_before_main_content',
		'woocommerce_after_main_content',
		'woocommerce_sidebar',
		'woocommerce_product_thumbnails',
		'woocommerce_product_meta_start',
		'woocommerce_product_meta_end',
	);

	/**
	 * Register WordPress hooks.
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_whm_get_hooks', array( $this, 'ajax_get_hooks' ) );
	}

	/**
	 * Returns the list of WooCommerce hooks the inspector tracks.
	 *
	 * @return string[]
	 */
	public function get_wc_hook_list() {
		return $this->wc_hooks;
	}

	/**
	 * Build a structured array of all registered callbacks for each
	 * monitored WooCommerce hook.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_all_hook_data() {
		global $wp_filter;

		$result = array();

		foreach ( $this->wc_hooks as $hook_name ) {
			$callbacks = array();

			if ( isset( $wp_filter[ $hook_name ] ) ) {
				$hook_obj = $wp_filter[ $hook_name ];

				// WP_Hook stores callbacks in $callbacks property indexed by priority.
				foreach ( $hook_obj->callbacks as $priority => $fns ) {
					foreach ( $fns as $fn_key => $fn_data ) {
						$callback     = $fn_data['function'];
						$callback_str = $this->callback_to_string( $callback );
						$source       = $this->get_callback_source( $callback );

						$callbacks[] = array(
							'priority'      => (int) $priority,
							'callback'      => $callback_str,
							'accepted_args' => (int) $fn_data['accepted_args'],
							'source'        => $source,
							'type'          => $this->is_filter( $hook_name ) ? 'Filter' : 'Action',
						);
					}
				}
			}

			$result[ $hook_name ] = $callbacks;
		}

		return $result;
	}

	/**
	 * AJAX handler – returns hook data as JSON.
	 * Requires capability: manage_options.
	 */
	public function ajax_get_hooks() {
		check_ajax_referer( 'whm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'woocommerce-hook-manager' ) ), 403 );
		}

		wp_send_json_success( $this->get_all_hook_data() );
	}

	/**
	 * Convert any callable to a readable string.
	 *
	 * @param  mixed $callback The raw callback stored by WordPress.
	 * @return string
	 */
	private function callback_to_string( $callback ) {
		if ( is_string( $callback ) ) {
			return $callback;
		}

		if ( is_array( $callback ) ) {
			$class  = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
			$method = (string) $callback[1];
			return $class . '::' . $method;
		}

		if ( $callback instanceof Closure ) {
			return 'Closure';
		}

		return 'Unknown';
	}

	/**
	 * Attempt to identify what plugin/theme registered the callback.
	 *
	 * @param  mixed $callback Callable.
	 * @return string
	 */
	private function get_callback_source( $callback ) {
		try {
			if ( is_array( $callback ) ) {
				$ref = new ReflectionMethod( $callback[0], $callback[1] );
			} elseif ( $callback instanceof Closure || is_string( $callback ) ) {
				$ref = new ReflectionFunction( $callback );
			} else {
				return 'Unknown';
			}

			$file = $ref->getFileName();

			if ( ! $file ) {
				return 'WordPress Core';
			}

			// Normalise slashes.
			$file = wp_normalize_path( $file );
			$base = wp_normalize_path( WP_CONTENT_DIR );

			if ( strpos( $file, $base . '/plugins/' ) !== false ) {
				$rel   = str_replace( $base . '/plugins/', '', $file );
				$parts = explode( '/', $rel );
				return 'Plugin: ' . $parts[0];
			}

			if ( strpos( $file, $base . '/themes/' ) !== false ) {
				$rel   = str_replace( $base . '/themes/', '', $file );
				$parts = explode( '/', $rel );
				return 'Theme: ' . $parts[0];
			}

			return 'WordPress Core';
		} catch ( ReflectionException $e ) {
			return 'Unknown';
		}
	}

	/**
	 * Determine whether a hook name is a filter rather than an action.
	 * (Heuristic: WooCommerce filters usually have "filter" in the name
	 * or return values.)
	 *
	 * @param  string $hook_name Hook name.
	 * @return bool
	 */
	private function is_filter( $hook_name ) {
		$filter_patterns = array( '_filter', 'get_', 'woocommerce_loop_add_to_cart' );
		foreach ( $filter_patterns as $p ) {
			if ( false !== strpos( $hook_name, $p ) ) {
				return true;
			}
		}
		return false;
	}
}
