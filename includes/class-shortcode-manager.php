<?php
/**
 * Shortcode Manager
 *
 * Registers the [whm_hook] shortcode so that WooCommerce hook output
 * can be rendered anywhere on the site.
 *
 * @package WHM
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WHM_Shortcode_Manager
 */
class WHM_Shortcode_Manager {

	/**
	 * Register WordPress hooks.
	 */
	public function register_hooks() {
		add_shortcode( 'whm_hook', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the [whm_hook] shortcode.
	 *
	 * Attributes:
	 *  - name          (string, required) WooCommerce hook to do/apply.
	 *  - priority      (int,    optional) Priority at which to do the action.     Default 10.
	 *  - wrapper_class (string, optional) CSS class(es) for the outer <div>.
	 *
	 * Usage: [whm_hook name="woocommerce_after_add_to_cart_button" wrapper_class="my-wrapper"]
	 *
	 * @param  array  $atts    Shortcode attributes.
	 * @param  string $content Inner content (unused).
	 * @return string          Buffered output.
	 */
	public function render_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'name'          => '',
				'priority'      => 10,
				'wrapper_class' => '',
			),
			$atts,
			'whm_hook'
		);

		$hook_name     = sanitize_text_field( $atts['name'] );
		$priority      = (int) $atts['priority'];
		$wrapper_class = sanitize_html_class( $atts['wrapper_class'] );

		if ( empty( $hook_name ) ) {
			return '';
		}

		ob_start();

		if ( $wrapper_class ) {
			echo '<div class="' . esc_attr( $wrapper_class ) . '">';
		}

		do_action( $hook_name ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

		if ( $wrapper_class ) {
			echo '</div>';
		}

		return ob_get_clean();
	}
}
