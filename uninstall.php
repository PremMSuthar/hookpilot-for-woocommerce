<?php
/**
 * Uninstall – WooCommerce Hook Manager
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Cleans up all options created by the plugin.
 *
 * @package WHM
 */

// Only run when WordPress triggers the uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only clean up if the user has opted in.
if ( get_option( 'whm_uninstall_cleanup', 0 ) ) {
	// Delete plugin options.
	delete_option( 'whm_hook_settings' );
	delete_option( 'whm_debug_mode' );
	delete_option( 'whm_uninstall_cleanup' );
}
