<?php
/**
 * Uninstall – Hookpilot for WooCommerce
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Cleans up all options created by the plugin.
 *
 * @package Hookpilot
 */

// Only run when WordPress triggers the uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only clean up if the user has opted in.
if ( get_option( 'hkplt_uninstall_cleanup', 0 ) ) {
	delete_option( 'hkplt_hook_settings' );
	delete_option( 'hkplt_debug_mode' );
	delete_option( 'hkplt_uninstall_cleanup' );
}
