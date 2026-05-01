<?php
/**
 * Export Manager
 *
 * Generates PHP code snippets from the current hook settings so that
 * developers can paste them into a theme's functions.php.
 *
 * @package Hookpilot
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HKPLT_Export_Manager
 */
class HKPLT_Export_Manager {

	/**
	 * Register WordPress hooks.
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_hkplt_export_config', array( $this, 'ajax_export_config' ) );
	}

	/**
	 * Generate and return a PHP code string from all saved settings.
	 *
	 * @return string PHP snippet.
	 */
	public function generate_export_code() {
		$settings = get_option( HKPLT_OPTION_KEY, array() );
		if ( ! is_array( $settings ) || empty( $settings ) ) {
			return '// No hook settings found.';
		}

		$lines   = array();
		$lines[] = '<?php';
		$lines[] = '/**';
		$lines[] = ' * Hookpilot for WooCommerce – Exported Configuration';
		$lines[] = ' * Generated: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC';
		$lines[] = ' * Place this code inside your theme\'s functions.php';
		$lines[] = ' */';
		$lines[] = '';

		foreach ( $settings as $setting ) {
			if ( empty( $setting['hook_name'] ) ) {
				continue;
			}

			$hook     = $setting['hook_name'];
			$callback = isset( $setting['callback_name'] ) ? $setting['callback_name'] : '';
			$priority = isset( $setting['priority'] ) ? (int) $setting['priority'] : 10;
			$status   = isset( $setting['status'] ) ? $setting['status'] : 'active';

			switch ( $status ) {
				case 'disable':
					$lines[] = sprintf(
						"remove_action( '%s', '%s', %d );",
						$hook,
						$callback,
						$priority
					);
					break;

				case 'priority':
					$old_priority = isset( $setting['old_priority'] ) ? (int) $setting['old_priority'] : 10;
					$lines[]      = sprintf(
						"remove_action( '%s', '%s', %d );",
						$hook,
						$callback,
						$old_priority
					);
					$lines[] = sprintf(
						"add_action( '%s', '%s', %d );",
						$hook,
						$callback,
						$priority
					);
					break;

				case 'wrapper':
					$tag   = ! empty( $setting['wrapper_tag'] ) ? $setting['wrapper_tag'] : 'div';
					$class = ! empty( $setting['wrapper_class'] ) ? $setting['wrapper_class'] : '';
					$attrs = ! empty( $setting['wrapper_attrs'] ) ? $setting['wrapper_attrs'] : '';
					$open  = '<' . $tag . ( $class ? ' class="' . $class . '"' : '' ) . ( $attrs ? ' ' . $attrs : '' ) . '>';
					$close = '</' . $tag . '>';
					$lines[] = sprintf(
						"add_action( '%s', function() { echo '%s'; }, %d );",
						$hook,
						addslashes( $open ),
						( $priority - 1 )
					);
					$lines[] = sprintf(
						"add_action( '%s', function() { echo '%s'; }, PHP_INT_MAX );",
						$hook,
						addslashes( $close )
					);
					break;

				case 'custom_content':
					$content = isset( $setting['custom_content'] ) ? $setting['custom_content'] : '';
					$lines[] = sprintf(
						"add_action( '%s', function() { echo '%s'; }, %d );",
						$hook,
						addslashes( $content ),
						$priority
					);
					break;
			}

			$lines[] = '';
		}

		return implode( "\n", $lines );
	}

	/**
	 * AJAX handler – returns export code as JSON.
	 */
	public function ajax_export_config() {
		check_ajax_referer( 'hkplt_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'hookpilot-for-woocommerce' ) ), 403 );
		}

		wp_send_json_success( array( 'code' => $this->generate_export_code() ) );
	}
}
