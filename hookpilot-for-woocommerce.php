<?php
/**
 * Plugin Name: Hookpilot for WooCommerce
 * Plugin URI:  https://github.com/PremMSuthar/hookpilot-for-woocommerce
 * Description: Manage WooCommerce hooks visually. Inspect hooks, change priority, add wrappers, disable callbacks, and insert custom content using a GUI.
 * Version:     1.0.0
 * Author:      premsutharm, hdkothari81, wpjitendra
 * Author URI:  https://github.com/PremMSuthar
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hookpilot-for-woocommerce
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 6.0
 * WC tested up to:      8.0
 *
 * @package Hookpilot
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
define( 'HKPLT_VERSION', '1.0.0' );

// Plugin directory path.
define( 'HKPLT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Plugin directory URL.
define( 'HKPLT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Plugin basename.
define( 'HKPLT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Option key for settings storage.
define( 'HKPLT_OPTION_KEY', 'hkplt_hook_settings' );

/**
 * Check if WooCommerce is active before loading the plugin.
 *
 * @return bool
 */
function hkplt_is_woocommerce_active() {
	return in_array(
		'woocommerce/woocommerce.php',
		apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ),
		true
	);
}

/**
 * Display an admin notice when WooCommerce is not active.
 */
function hkplt_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: WooCommerce plugin link */
					__( '<strong>Hookpilot for WooCommerce</strong> requires %s to be installed and active.', 'hookpilot-for-woocommerce' ),
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
function hkplt_init() {
	if ( ! hkplt_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'hkplt_woocommerce_missing_notice' );
		return;
	}

	// Require core class files.
	require_once HKPLT_PLUGIN_DIR . 'includes/class-plugin-loader.php';
	require_once HKPLT_PLUGIN_DIR . 'includes/class-hook-inspector.php';
	require_once HKPLT_PLUGIN_DIR . 'includes/class-hook-manager.php';
	require_once HKPLT_PLUGIN_DIR . 'includes/class-admin-page.php';
	require_once HKPLT_PLUGIN_DIR . 'includes/class-export-manager.php';

	// Boot the plugin loader.
	HKPLT_Plugin_Loader::get_instance();
}
add_action( 'plugins_loaded', 'hkplt_init' );

/**
 * Declare compatibility with WooCommerce HPOS (High-Performance Order Storage).
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Plugin activation hook.
 * Sets default options on first activation.
 */
function hkplt_activate() {
	if ( ! get_option( HKPLT_OPTION_KEY ) ) {
		update_option( HKPLT_OPTION_KEY, array() );
	}
	update_option( 'hkplt_debug_mode', 0 );
}
register_activation_hook( __FILE__, 'hkplt_activate' );

/**
 * Plugin deactivation hook.
 */
function hkplt_deactivate() {
	// Nothing to do on deactivation – settings are kept.
}
register_deactivation_hook( __FILE__, 'hkplt_deactivate' );
