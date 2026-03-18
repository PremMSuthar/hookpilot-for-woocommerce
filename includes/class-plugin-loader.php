<?php
/**
 * Plugin Loader
 *
 * Bootstraps all plugin subsystems and is the single entry-point
 * after the main plugin file has verified WooCommerce is active.
 *
 * @package WHM
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WHM_Plugin_Loader
 *
 * Singleton that initialises every plugin component.
 */
class WHM_Plugin_Loader {

	/**
	 * Singleton instance.
	 *
	 * @var WHM_Plugin_Loader|null
	 */
	private static $instance = null;

	/**
	 * Returns (and lazily creates) the singleton instance.
	 *
	 * @return WHM_Plugin_Loader
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor – call get_instance() instead.
	 */
	private function __construct() {
		$this->init_components();
	}

	/**
	 * Instantiate every plugin component and let each one register its hooks.
	 */
	private function init_components() {
		// Admin UI.
		$admin = new WHM_Admin_Page();
		$admin->register_hooks();

		// Hook manager (applies saved settings to the front-end).
		$manager = new WHM_Hook_Manager();
		$manager->register_hooks();


		// Hook inspector (AJAX data provider for the admin panel).
		$inspector = new WHM_Hook_Inspector();
		$inspector->register_hooks();

		// Export manager.
		$export = new WHM_Export_Manager();
		$export->register_hooks();
	}
}
