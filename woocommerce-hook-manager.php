<?php
/**
 * Plugin Name: WooCommerce Hook Manager
 * Plugin URI:  https://example.com/woocommerce-hook-manager
 * Description: Manage WooCommerce hooks visually. Inspect hooks, change priority, add wrappers, disable callbacks, and insert custom content using a GUI.
 * Version:     1.0.0
 * Author:      Developer Tools
 * Author URI:  https://example.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-hook-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 6.0
 * WC tested up to:      8.0
 *
 * @package WHM
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
define( 'WHM_VERSION', '1.0.0' );

// Plugin directory path.
define( 'WHM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Plugin directory URL.
define( 'WHM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Plugin basename.
define( 'WHM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Option key for settings storage.
define( 'WHM_OPTION_KEY', 'whm_hook_settings' );

/**
 * Check if WooCommerce is active before loading the plugin.
 *
 * @return bool
 */
function whm_is_woocommerce_active() {
	return in_array(
		'woocommerce/woocommerce.php',
		apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ),
		true
	);
}

/**
 * Display an admin notice when WooCommerce is not active.
 */
function whm_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: WooCommerce plugin link */
					__( '<strong>WooCommerce Hook Manager</strong> requires %s to be installed and active.', 'woocommerce-hook-manager' ),
					'<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Load the plugin after all plugins are loaded to verify WooCommerce is active.
 */
function whm_init() {
	if ( ! whm_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'whm_woocommerce_missing_notice' );
		return;
	}

	// Load text domain for translations.
	load_plugin_textdomain(
		'woocommerce-hook-manager',
		false,
		dirname( WHM_PLUGIN_BASENAME ) . '/languages'
	);

	// Require core class files.
	require_once WHM_PLUGIN_DIR . 'includes/class-plugin-loader.php';
	require_once WHM_PLUGIN_DIR . 'includes/class-hook-inspector.php';
	require_once WHM_PLUGIN_DIR . 'includes/class-hook-manager.php';
	require_once WHM_PLUGIN_DIR . 'includes/class-shortcode-manager.php';
	require_once WHM_PLUGIN_DIR . 'includes/class-admin-page.php';
	require_once WHM_PLUGIN_DIR . 'includes/class-export-manager.php';

	// Boot the plugin loader.
	WHM_Plugin_Loader::get_instance();
}
add_action( 'plugins_loaded', 'whm_init' );

/**
 * Plugin activation hook.
 * Sets default options on first activation.
 */
function whm_activate() {
	if ( ! get_option( WHM_OPTION_KEY ) ) {
		update_option( WHM_OPTION_KEY, array() );
	}
	update_option( 'whm_debug_mode', 0 );
}
register_activation_hook( __FILE__, 'whm_activate' );

/**
 * Plugin deactivation hook.
 */
function whm_deactivate() {
	// Nothing to do on deactivation – settings are kept.
}
register_deactivation_hook( __FILE__, 'whm_deactivate' );
