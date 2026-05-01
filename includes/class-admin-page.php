<?php
/**
 * Admin Page
 *
 * Registers the WordPress admin menu, enqueues admin assets, and
 * includes the admin page template.
 *
 * @package Hookpilot
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HKPLT_Admin_Page
 */
class HKPLT_Admin_Page {

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
		$hook_icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" d="M10,1C8.9,1,8,1.9,8,3s0.9,2,2,2s2-0.9,2-2S11.1,1,10,1z M10,4.2C9.3,4.2,8.8,3.7,8.8,3S9.3,1.8,10,1.8S11.2,2.3,11.2,3S10.7,4.2,10,4.2z M10.5,5v9.5c0,1.4-1.1,2.5-2.5,2.5S5.5,15.9,5.5,14.5V12h1.5v2.5c0,0.6,0.4,1,1,1s1-0.4,1-1V5H10.5z M4.5,12l2.5-3l1.5,3H4.5z"/></svg>' );

		add_menu_page(
			esc_html__( 'Hookpilot', 'hookpilot-for-woocommerce' ),
			esc_html__( 'Hookpilot', 'hookpilot-for-woocommerce' ),
			'manage_options',
			'hkplt-dashboard',
			array( $this, 'render_dashboard' ),
			$hook_icon,
			58
		);

		$subpages = array(
			array(
				'title' => esc_html__( 'Hook Inspector', 'hookpilot-for-woocommerce' ),
				'label' => esc_html__( 'Hook Inspector', 'hookpilot-for-woocommerce' ),
				'slug'  => 'hkplt-inspector',
				'cb'    => array( $this, 'render_inspector' ),
			),
			array(
				'title' => esc_html__( 'Hook Manager', 'hookpilot-for-woocommerce' ),
				'label' => esc_html__( 'Hook Manager', 'hookpilot-for-woocommerce' ),
				'slug'  => 'hkplt-manager',
				'cb'    => array( $this, 'render_manager' ),
			),
			array(
				'title' => esc_html__( 'Add Custom Hook', 'hookpilot-for-woocommerce' ),
				'label' => esc_html__( 'Add Custom Hook', 'hookpilot-for-woocommerce' ),
				'slug'  => 'hkplt-add-hook',
				'cb'    => array( $this, 'render_add_hook' ),
			),
			array(
				'title' => esc_html__( 'Shortcodes', 'hookpilot-for-woocommerce' ),
				'label' => esc_html__( 'Shortcodes', 'hookpilot-for-woocommerce' ),
				'slug'  => 'hkplt-shortcodes',
				'cb'    => array( $this, 'render_shortcodes' ),
			),
			array(
				'title' => esc_html__( 'Import/Export', 'hookpilot-for-woocommerce' ),
				'label' => esc_html__( 'Import/Export', 'hookpilot-for-woocommerce' ),
				'slug'  => 'hkplt-import-export',
				'cb'    => array( $this, 'render_import_export' ),
			),
		);

		foreach ( $subpages as $page ) {
			add_submenu_page(
				'hkplt-dashboard',
				$page['title'],
				$page['label'],
				'manage_options',
				$page['slug'],
				$page['cb']
			);
		}
	}

	/**
	 * Enqueue admin CSS and JS only on Hookpilot admin pages.
	 *
	 * @param  string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook ) {
		$hkplt_pages = array(
			'toplevel_page_hkplt-dashboard',
			'hookpilot_page_hkplt-inspector',
			'hookpilot_page_hkplt-manager',
			'hookpilot_page_hkplt-add-hook',
			'hookpilot_page_hkplt-shortcodes',
			'hookpilot_page_hkplt-import-export',
		);

		if ( ! in_array( $hook, $hkplt_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'hkplt-admin-styles',
			HKPLT_PLUGIN_URL . 'admin/admin-styles.css',
			array(),
			HKPLT_VERSION
		);

		wp_enqueue_script(
			'hkplt-admin-scripts',
			HKPLT_PLUGIN_URL . 'admin/admin-scripts.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			HKPLT_VERSION,
			true
		);

		wp_localize_script(
			'hkplt-admin-scripts',
			'hkpltData',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'hkplt_nonce' ),
				'debug_mode' => (int) get_option( 'hkplt_debug_mode', 0 ),
				'settings'   => get_option( HKPLT_OPTION_KEY, array() ),
				'strings'    => array(
					'confirm_delete' => esc_html__( 'Are you sure you want to delete this setting?', 'hookpilot-for-woocommerce' ),
					'saved'          => esc_html__( 'Saved successfully.', 'hookpilot-for-woocommerce' ),
					'deleted'        => esc_html__( 'Deleted.', 'hookpilot-for-woocommerce' ),
					'error'          => esc_html__( 'An error occurred. Please try again.', 'hookpilot-for-woocommerce' ),
					'loading'        => esc_html__( 'Loading hooks…', 'hookpilot-for-woocommerce' ),
					'edit_rule'      => esc_html__( 'Edit Rule', 'hookpilot-for-woocommerce' ),
					'update'         => esc_html__( 'Update Rule', 'hookpilot-for-woocommerce' ),
					'debug_enabled'  => esc_html__( 'Debug mode enabled.', 'hookpilot-for-woocommerce' ),
					'debug_disabled' => esc_html__( 'Debug mode disabled.', 'hookpilot-for-woocommerce' ),
					'reload_hooks'   => esc_html__( 'Reload Hooks', 'hookpilot-for-woocommerce' ),
					'load_hooks'     => esc_html__( 'Load Hooks', 'hookpilot-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Add Hookpilot debug toggle node to the WordPress admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function register_admin_bar_node( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$debug = (int) get_option( 'hkplt_debug_mode', 0 );
		$state = $debug ? 'is-on' : 'is-off';

		$label = sprintf(
			'<span class="hkplt-ab-label">%s</span>
			 <span class="hkplt-ab-switch %s"><span class="hkplt-ab-slider"></span></span>',
			esc_html__( 'Hookpilot Debug', 'hookpilot-for-woocommerce' ),
			$state
		);

		$wp_admin_bar->add_node(
			array(
				'id'    => 'hkplt-debug-bar',
				'title' => $label,
				'href'  => '#',
				'meta'  => array(
					'class' => 'hkplt-admin-bar-node',
					'title' => esc_attr__( 'Toggle Hookpilot Debug Mode', 'hookpilot-for-woocommerce' ),
				),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'hkplt-debug-bar-toggle',
				'parent' => 'hkplt-debug-bar',
				'title'  => $debug
					? esc_html__( 'Disable Debug Overlay', 'hookpilot-for-woocommerce' )
					: esc_html__( 'Enable Debug Overlay', 'hookpilot-for-woocommerce' ),
				'href'   => '#',
				'meta'   => array(
					'class'      => 'hkplt-ab-toggle-action',
					'data-debug' => $debug,
				),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'hkplt-debug-bar-settings',
				'parent' => 'hkplt-debug-bar',
				'title'  => esc_html__( 'Hookpilot Dashboard', 'hookpilot-for-woocommerce' ),
				'href'   => admin_url( 'admin.php?page=hkplt-dashboard' ),
			)
		);
	}

	/**
	 * Enqueue the lightweight admin-bar JS + inline CSS on every page for admins.
	 */
	public function enqueue_admin_bar_assets() {
		if ( ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing() ) {
			return;
		}

		wp_enqueue_script(
			'hkplt-admin-bar',
			HKPLT_PLUGIN_URL . 'public/admin-bar.js',
			array( 'jquery' ),
			HKPLT_VERSION,
			true
		);

		wp_localize_script(
			'hkplt-admin-bar',
			'hkpltBarData',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'hkplt_nonce' ),
				'debug_mode' => (int) get_option( 'hkplt_debug_mode', 0 ),
				'strings'    => array(
					'enabled_label'  => esc_html__( 'Enable Debug Overlay', 'hookpilot-for-woocommerce' ),
					'disabled_label' => esc_html__( 'Disable Debug Overlay', 'hookpilot-for-woocommerce' ),
					'visible'        => esc_html__( 'Visible', 'hookpilot-for-woocommerce' ),
					'hidden'         => esc_html__( 'Hidden', 'hookpilot-for-woocommerce' ),
				),
			)
		);

		$inline_css = '
			#wp-admin-bar-hkplt-debug-bar > .ab-item { display: flex !important; align-items: center; gap: 8px; height: 32px; padding: 0 12px; }
			#wpadminbar .hkplt-ab-switch.is-on { background: #00a0d2; }
			#wpadminbar .hkplt-ab-label { font-weight: 600; font-size: 13px; }
			#wpadminbar .hkplt-ab-switch {
				display: block; position: relative; width: 44px; min-width: 44px; height: 18px;
				background: #8c8f94; border-radius: 10px; transition: all 0.3s;
				cursor: pointer; flex-shrink: 0; box-sizing: border-box;
			}
			#wpadminbar .hkplt-ab-switch:after {
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
			#wpadminbar .hkplt-ab-switch.is-on:after {
				content: "ON";
				left: 7px;
				right: auto;
			}
			#wpadminbar .hkplt-ab-slider {
				position: absolute; left: 2px; top: 2px; width: 14px; height: 14px;
				background: #fff; border-radius: 50%; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
				z-index: 2; box-sizing: border-box;
			}
			#wpadminbar .hkplt-ab-switch.is-on .hkplt-ab-slider { transform: translateX(26px); }
			#wp-admin-bar-hkplt-debug-bar-toggle a { cursor:pointer; }
		';
		wp_add_inline_style( 'admin-bar', $inline_css );
	}

	/* ------------------------------------------------------------------
	 * Page render callbacks
	 * ------------------------------------------------------------------ */

	/** Dashboard page. */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include HKPLT_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/** Hook Inspector page. */
	public function render_inspector() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$hkplt_view = 'inspector';
		include HKPLT_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/** Hook Manager page. */
	public function render_manager() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$hkplt_view = 'manager';
		include HKPLT_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/** Add Custom Hook page. */
	public function render_add_hook() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$hkplt_view = 'add_hook';
		include HKPLT_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/** Shortcodes page. */
	public function render_shortcodes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$hkplt_view = 'shortcodes';
		include HKPLT_PLUGIN_DIR . 'admin/admin-page.php';
	}

	/** Import/Export page. */
	public function render_import_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$hkplt_view = 'import_export';
		include HKPLT_PLUGIN_DIR . 'admin/admin-page.php';
	}
}
