<?php
/**
 * Admin Page
 *
 * Registers the WordPress admin menu, enqueues admin assets, and
 * includes the admin page template.
 *
 * @package WHM
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WHM_Admin_Page
 */
class WHM_Admin_Page {

	/**
	 * Register WordPress hooks.
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Admin bar – visible on every page (frontend + backend) for admins.
		add_action( 'admin_bar_menu', array( $this, 'register_admin_bar_node' ), 999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_admin_bar_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_bar_assets' ) );
	}

	/**
	 * Register admin menu and sub-menu pages.
	 */
	public function register_admin_menus() {
		// Custom SVG Hook Icon (Base64 Encoded for WP Admin Menu).
		$hook_icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M10,1C8.9,1,8,1.9,8,3s0.9,2,2,2s2-0.9,2-2S11.1,1,10,1z M10,4.2C9.3,4.2,8.8,3.7,8.8,3S9.3,1.8,10,1.8S11.2,2.3,11.2,3S10.7,4.2,10,4.2z M10.5,5v9.5c0,1.4-1.1,2.5-2.5,2.5S5.5,15.9,5.5,14.5V12h1.5v2.5c0,0.6,0.4,1,1,1s1-0.4,1-1V5H10.5z M4.5,12l2.5-3l1.5,3H4.5z"/></svg>' );

		// Top-level menu.
		add_menu_page(
			esc_html__( 'Woo Hook Manager', 'woocommerce-hook-manager' ),
			esc_html__( 'Woo Hook Manager', 'woocommerce-hook-manager' ),
			'manage_options',
			'whm-dashboard',
			array( $this, 'render_dashboard' ),
			$hook_icon,
			58
		);

		// Sub-pages.
		$subpages = array(
			array(
				'title'  => esc_html__( 'Hook Inspector', 'woocommerce-hook-manager' ),
				'label'  => esc_html__( 'Hook Inspector', 'woocommerce-hook-manager' ),
				'slug'   => 'whm-inspector',
				'cb'     => array( $this, 'render_inspector' ),
			),
			array(
				'title'  => esc_html__( 'Hook Manager', 'woocommerce-hook-manager' ),
				'label'  => esc_html__( 'Hook Manager', 'woocommerce-hook-manager' ),
				'slug'   => 'whm-manager',
				'cb'     => array( $this, 'render_manager' ),
			),
			array(
				'title'  => esc_html__( 'Add Custom Hook', 'woocommerce-hook-manager' ),
				'label'  => esc_html__( 'Add Custom Hook', 'woocommerce-hook-manager' ),
				'slug'   => 'whm-add-hook',
				'cb'     => array( $this, 'render_add_hook' ),
			),
			array(
				'title'  => esc_html__( 'Shortcodes', 'woocommerce-hook-manager' ),
				'label'  => esc_html__( 'Shortcodes', 'woocommerce-hook-manager' ),
				'slug'   => 'whm-shortcodes',
				'cb'     => array( $this, 'render_shortcodes' ),
			),
			array(
				'title'  => esc_html__( 'Import/Export', 'woocommerce-hook-manager' ),
				'label'  => esc_html__( 'Import/Export', 'woocommerce-hook-manager' ),
				'slug'   => 'whm-import-export',
				'cb'     => array( $this, 'render_import_export' ),
			),
		);

		foreach ( $subpages as $page ) {
			add_submenu_page(
				'whm-dashboard',
				$page['title'],
				$page['label'],
				'manage_options',
				$page['slug'],
				$page['cb']
			);
		}
	}

	/**
	 * Enqueue admin CSS and JS only on WHM admin pages.
	 *
	 * @param  string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook ) {
		$whm_pages = array(
			'toplevel_page_whm-dashboard',
			'woo-hook-manager_page_whm-inspector',
			'woo-hook-manager_page_whm-manager',
			'woo-hook-manager_page_whm-add-hook',
			'woo-hook-manager_page_whm-shortcodes',
			'woo-hook-manager_page_whm-import-export',
		);

		if ( ! in_array( $hook, $whm_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'whm-admin-styles',
			WHM_PLUGIN_URL . 'admin/admin-styles.css',
			array(),
			WHM_VERSION
		);

		wp_enqueue_script(
			'whm-admin-scripts',
			WHM_PLUGIN_URL . 'admin/admin-scripts.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			WHM_VERSION,
			true
		);

		wp_localize_script(
			'whm-admin-scripts',
			'whmData',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'whm_nonce' ),
				'debug_mode' => (int) get_option( 'whm_debug_mode', 0 ),
				'settings'   => get_option( WHM_OPTION_KEY, array() ),
				'strings'    => array(
					'confirm_delete' => esc_html__( 'Are you sure you want to delete this setting?', 'woocommerce-hook-manager' ),
					'saved'          => esc_html__( 'Saved successfully.', 'woocommerce-hook-manager' ),
					'deleted'        => esc_html__( 'Deleted.', 'woocommerce-hook-manager' ),
					'error'          => esc_html__( 'An error occurred. Please try again.', 'woocommerce-hook-manager' ),
					'loading'        => esc_html__( 'Loading hooks…', 'woocommerce-hook-manager' ),
					'edit_rule'      => esc_html__( 'Edit Rule', 'woocommerce-hook-manager' ),
					'update'         => esc_html__( 'Update Rule', 'woocommerce-hook-manager' ),
					'debug_enabled'  => esc_html__( 'Debug mode enabled.', 'woocommerce-hook-manager' ),
					'debug_disabled' => esc_html__( 'Debug mode disabled.', 'woocommerce-hook-manager' ),
					'reload_hooks'   => esc_html__( 'Reload Hooks', 'woocommerce-hook-manager' ),
					'load_hooks'     => esc_html__( 'Load Hooks', 'woocommerce-hook-manager' ),
				),
			)
		);
	}

	/**
	 * Add WHM debug toggle node to the WordPress admin bar.
	 * Visible on every page (frontend + backend) for administrators.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function register_admin_bar_node( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$debug = (int) get_option( 'whm_debug_mode', 0 );
		$state = $debug ? 'is-on' : 'is-off';
		
		$label = sprintf(
			'<span class="whm-ab-label">%s</span>
			 <span class="whm-ab-switch %s"><span class="whm-ab-slider"></span></span>',
			esc_html__( 'WHM Debug', 'woocommerce-hook-manager' ),
			$state
		);

		// Parent node.
		$wp_admin_bar->add_node(
			array(
				'id'    => 'whm-debug-bar',
				'title' => $label,
				'href'  => '#',
				'meta'  => array(
					'class' => 'whm-admin-bar-node',
					'title' => esc_attr__( 'Toggle WooCommerce Hook Manager Debug Mode', 'woocommerce-hook-manager' ),
				),
			)
		);

		// Sub-node: toggle action.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'whm-debug-bar-toggle',
				'parent' => 'whm-debug-bar',
				'title'  => $debug
					? esc_html__( 'Disable Debug Overlay', 'woocommerce-hook-manager' )
					: esc_html__( 'Enable Debug Overlay', 'woocommerce-hook-manager' ),
				'href'   => '#',
				'meta'   => array(
					'class'      => 'whm-ab-toggle-action',
					'data-debug' => $debug,
				),
			)
		);

		// Sub-node: link to settings.
		$wp_admin_bar->add_node(
			array(
				'id'     => 'whm-debug-bar-settings',
				'parent' => 'whm-debug-bar',
				'title'  => esc_html__( 'Hook Manager Dashboard', 'woocommerce-hook-manager' ),
				'href'   => admin_url( 'admin.php?page=whm-dashboard' ),
			)
		);
	}

	/**
	 * Enqueue the lightweight admin-bar JS + inline CSS on every page for admins.
	 * This powers the debug toggle in the admin bar on both frontend & backend.
	 */
	public function enqueue_admin_bar_assets() {
		if ( ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing() ) {
			return;
		}

		wp_enqueue_script(
			'whm-admin-bar',
			WHM_PLUGIN_URL . 'public/admin-bar.js',
			array( 'jquery' ),
			WHM_VERSION,
			true
		);

		wp_localize_script(
			'whm-admin-bar',
			'whmBarData',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'whm_nonce' ),
				'debug_mode' => (int) get_option( 'whm_debug_mode', 0 ),
				'strings'    => array(
					'enabled_label'  => esc_html__( 'Enable Debug Overlay', 'woocommerce-hook-manager' ),
					'disabled_label' => esc_html__( 'Disable Debug Overlay', 'woocommerce-hook-manager' ),
					'visible'        => esc_html__( 'Visible', 'woocommerce-hook-manager' ),
					'hidden'         => esc_html__( 'Hidden', 'woocommerce-hook-manager' ),
				),
			)
		);

		// Inline CSS for admin bar node – no extra stylesheet needed.
		$inline_css = '
			#wp-admin-bar-whm-debug-bar > .ab-item { display: flex !important; align-items: center; gap: 8px; height: 32px; padding: 0 12px; }
			#wpadminbar .whm-ab-switch.is-on { background: #00a0d2; }
			#wpadminbar .whm-ab-label { font-weight: 600; font-size: 13px; }
			#wpadminbar .whm-ab-switch { 
				display: block; position: relative; width: 44px; min-width: 44px; height: 18px; 
				background: #8c8f94; border-radius: 10px; transition: all 0.3s;
				cursor: pointer; flex-shrink: 0; box-sizing: border-box;
			}
			#wpadminbar .whm-ab-switch:after {
				content: "OFF";
				position: absolute;
				right: 6px;
				top: 50%;
				transform: translateY(-50%);
				font-size: 8px;
				font-weight: 800;
				color: #fff;
				text-transform: uppercase;
				line-height: 1;
			}
			#wpadminbar .whm-ab-switch.is-on:after {
				content: "ON";
				left: 7px;
				right: auto;
			}
			#wpadminbar .whm-ab-slider { 
				position: absolute; left: 2px; top: 2px; width: 14px; height: 14px; 
				background: #fff; border-radius: 50%; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
				z-index: 2; box-sizing: border-box;
			}
			#wpadminbar .whm-ab-switch.is-on .whm-ab-slider { transform: translateX(26px); }
			#wp-admin-bar-whm-debug-bar-toggle a { cursor:pointer; }
		';
		wp_add_inline_style( 'admin-bar', $inline_css );
	}

	/* ------------------------------------------------------------------
	 * Page render callbacks
	 * ------------------------------------------------------------------ */

	/**
	 * Dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include WHM_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/**
	 * Hook Inspector page.
	 */
	public function render_inspector() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$view = 'inspector';
		include WHM_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/**
	 * Hook Manager page.
	 */
	public function render_manager() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$view = 'manager';
		include WHM_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/**
	 * Add Custom Hook page.
	 */
	public function render_add_hook() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$view = 'add_hook';
		include WHM_PLUGIN_DIR . 'admin/admin-page.php';
	}

	public function render_shortcodes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$view = 'shortcodes';
		include WHM_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/**
	 * Import/Export page.
	 */
	public function render_import_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$view = 'import_export';
		include WHM_PLUGIN_DIR . 'admin/admin-page.php';
	}
}
