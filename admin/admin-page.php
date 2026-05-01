<?php
/**
 * Admin Page Template
 *
 * Single template file that renders all sub-page views based on
 * the $view variable set by HKPLT_Admin_Page render callbacks.
 *
 * @package Hookpilot
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Capability guard.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'hookpilot-for-woocommerce' ) );
}

// Default view.
$view = isset( $view ) ? $view : 'dashboard';

// Load settings for views that need them.
$settings   = get_option( HKPLT_OPTION_KEY, array() );
if ( ! is_array( $settings ) ) {
	$settings = array();
}
$debug_mode = (int) get_option( 'hkplt_debug_mode', 0 );

// Inspector data.
$inspector = new HKPLT_Hook_Inspector();
$hook_list = $inspector->get_wc_hook_list();

// Export manager.
$export_manager = new HKPLT_Export_Manager();

// Helper: human-readable status label.
function hkplt_status_label( $status ) {
	$map = array(
		'disable'        => __( 'Disable Callback', 'hookpilot-for-woocommerce' ),
		'priority'       => __( 'Change Priority', 'hookpilot-for-woocommerce' ),
		'wrapper'        => __( 'HTML Wrapper', 'hookpilot-for-woocommerce' ),
		'custom_content' => __( 'Custom Content', 'hookpilot-for-woocommerce' ),
		'shortcode'      => __( 'Shortcode', 'hookpilot-for-woocommerce' ),
		'active'         => __( 'Active', 'hookpilot-for-woocommerce' ),
		'inactive'       => __( 'Inactive', 'hookpilot-for-woocommerce' ),
	);
	return isset( $map[ $status ] ) ? $map[ $status ] : ucfirst( str_replace( '_', ' ', $status ) );
}
?>

<div class="wrap hkplt-wrap">
	<?php /* WordPress notices are injected after the first h1. This hidden H1 catches them. */ ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'WooCommerce Hook Manager', 'hookpilot-for-woocommerce' ); ?></h1>

	<div class="hkplt-admin-layout">
		
		<!-- Main Column -->
		<div class="hkplt-main-column">
			<div class="hkplt-header-premium">
				<div class="hkplt-header-content">
					<div class="hkplt-header-info">
						<div class="hkplt-logo-group">
							<div class="hkplt-icon-box">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10,1C8.9,1,8,1.9,8,3s0.9,2,2,2s2-0.9,2-2S11.1,1,10,1z M10,4.2C9.3,4.2,8.8,3.7,8.8,3S9.3,1.8,10,1.8S11.2,2.3,11.2,3S10.7,4.2,10,4.2z M10.5,5v9.5c0,1.4-1.1,2.5-2.5,2.5S5.5,15.9,5.5,14.5V12h1.5v2.5c0,0.6,0.4,1,1,1s1-0.4,1-1V5H10.5z M4.5,12l2.5-3l1.5,3H4.5z"/></svg>
							</div>
							<h2 class="hkplt-logo-title"><?php esc_html_e( 'Hook Manager', 'hookpilot-for-woocommerce' ); ?></h2>
							<span class="hkplt-version-badge">v<?php echo esc_html( HKPLT_VERSION ); ?></span>
						</div>
						<p class="hkplt-header-tagline"><?php esc_html_e( 'Visually manage WooCommerce hooks without editing any files.', 'hookpilot-for-woocommerce' ); ?></p>
					</div>
					<div class="hkplt-header-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=hkplt-add-hook' ) ); ?>" class="hkplt-btn hkplt-btn--primary">
							<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'New Rule', 'hookpilot-for-woocommerce' ); ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Navigation Tabs -->
			<nav class="hkplt-nav-tabs">
				<?php
				$tabs = array(
					'dashboard'  => array( 'label' => __( 'Dashboard', 'hookpilot-for-woocommerce' ), 'slug' => 'hkplt-dashboard', 'icon' => 'dashboard' ),
					'inspector'  => array( 'label' => __( 'Hook Inspector', 'hookpilot-for-woocommerce' ), 'slug' => 'hkplt-inspector', 'icon' => 'search' ),
					'manager'    => array( 'label' => __( 'Hook Manager', 'hookpilot-for-woocommerce' ), 'slug' => 'hkplt-manager', 'icon' => 'sort' ),
					'add_hook'   => array( 'label' => __( '+ Add Rule', 'hookpilot-for-woocommerce' ), 'slug' => 'hkplt-add-hook', 'icon' => 'edit' ),
					'import_export' => array( 'label' => __( 'Import/Export', 'hookpilot-for-woocommerce' ), 'slug' => 'hkplt-import-export', 'icon' => 'migrate' ),
				);
				foreach ( $tabs as $key => $tab ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $tab['slug'] ) ); ?>"
					   class="hkplt-tab <?php echo ( $view === $key ) ? 'hkplt-tab--active' : ''; ?>">
						<span class="dashicons dashicons-<?php echo esc_attr( $tab['icon'] ); ?>"></span>
						<?php echo esc_html( $tab['label'] ); ?>
						<?php if ( 'manager' === $key && ! empty( $settings ) ) : ?>
							<span class="hkplt-tab-count"><?php echo count( $settings ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="hkplt-content">
				<div id="hkplt-notice" class="hkplt-notice" style="display:none;"></div>

				<?php
				/* ================================================================
				 * VIEW: DASHBOARD
				 * ============================================================== */
				if ( 'dashboard' === $view ) : ?>
					<div class="hkplt-dashboard">
						<div class="hkplt-welcome-box">
							<div class="hkplt-welcome-text">
								<h2><?php esc_html_e( 'Welcome back!', 'hookpilot-for-woocommerce' ); ?></h2>
								<p><?php esc_html_e( 'You\'re currently monitoring active WooCommerce hooks. Use the inspector to find exact locations or manage rules in real-time.', 'hookpilot-for-woocommerce' ); ?></p>
								<div class="hkplt-welcome-actions">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=hkplt-inspector' ) ); ?>" class="hkplt-btn hkplt-btn--primary"><?php esc_html_e( 'Get Started', 'hookpilot-for-woocommerce' ); ?></a>
								</div>
							</div>
							<div class="hkplt-welcome-image">
								<span class="dashicons dashicons-shield"></span>
							</div>
						</div>

						<div class="hkplt-cards">
							<div class="hkplt-card">
								<span class="dashicons dashicons-search"></span>
								<h3><?php esc_html_e( 'Hook Inspector', 'hookpilot-for-woocommerce' ); ?></h3>
								<p><?php esc_html_e( 'Find exact hook locations and registered callbacks on your active WooCommerce pages.', 'hookpilot-for-woocommerce' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=hkplt-inspector' ) ); ?>" class="hkplt-btn hkplt-btn--outline hkplt-btn--full"><?php esc_html_e( 'Inspect Hooks', 'hookpilot-for-woocommerce' ); ?></a>
							</div>
							<div class="hkplt-card">
								<span class="dashicons dashicons-admin-settings"></span>
								<h3><?php esc_html_e( 'Hook Manager', 'hookpilot-for-woocommerce' ); ?></h3>
								<p><?php esc_html_e( 'Review your active rules, modify priorities, or delete existing modifications.', 'hookpilot-for-woocommerce' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=hkplt-manager' ) ); ?>" class="hkplt-btn hkplt-btn--outline hkplt-btn--full"><?php esc_html_e( 'Manage Rules', 'hookpilot-for-woocommerce' ); ?></a>
							</div>
							<div class="hkplt-card">
								<span class="dashicons dashicons-welcome-add-page"></span>
								<h3><?php esc_html_e( 'Add Custom Content', 'hookpilot-for-woocommerce' ); ?></h3>
								<p><?php esc_html_e( 'Insert your own HTML, shortcodes, or text into any WooCommerce action hook.', 'hookpilot-for-woocommerce' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=hkplt-add-hook' ) ); ?>" class="hkplt-btn hkplt-btn--outline hkplt-btn--full"><?php esc_html_e( 'Create Rule', 'hookpilot-for-woocommerce' ); ?></a>
							</div>
						</div>
					</div>

				<?php
				/* ================================================================
				 * VIEW: HOOK INSPECTOR
				 * ============================================================== */
				elseif ( 'inspector' === $view ) : ?>
					<div class="hkplt-inspector">
						<h2><?php esc_html_e( 'Hook Inspector', 'hookpilot-for-woocommerce' ); ?></h2>
						<p><?php esc_html_e( 'Displays registered callbacks for known WooCommerce hooks.', 'hookpilot-for-woocommerce' ); ?></p>

						<div class="hkplt-toolbar">
							<input type="text" id="hkplt-inspector-search" class="hkplt-input" placeholder="<?php esc_attr_e( 'Filter by hook name…', 'hookpilot-for-woocommerce' ); ?>" />
							<button id="hkplt-load-inspector" class="hkplt-btn hkplt-btn--primary"><?php esc_html_e( 'Load Hooks', 'hookpilot-for-woocommerce' ); ?></button>
						</div>

						<div id="hkplt-inspector-table-wrap">
							<p class="hkplt-placeholder"><?php esc_html_e( 'Click "Load Hooks" to inspect registered callbacks.', 'hookpilot-for-woocommerce' ); ?></p>
						</div>
					</div>

				<?php
				/* ================================================================
				 * VIEW: HOOK MANAGER
				 * ============================================================== */
				elseif ( 'manager' === $view ) : ?>
					<div class="hkplt-manager" id="hkplt-manager-view">
						<div class="hkplt-section-header" style="margin-bottom: 32px;">
							<h2><?php esc_html_e( 'Hook Manager', 'hookpilot-for-woocommerce' ); ?></h2>
							<p><?php esc_html_e( 'Review, edit, or delete existing hook rules. Drag to reorder priorities.', 'hookpilot-for-woocommerce' ); ?></p>
						</div>

						<div class="hkplt-rules-container">
							<div class="hkplt-rules-table-header">
								<span><?php esc_html_e( 'Rule Details', 'hookpilot-for-woocommerce' ); ?></span>
								<span><?php esc_html_e( 'Target Hook', 'hookpilot-for-woocommerce' ); ?></span>
								<span><?php esc_html_e( 'Priority', 'hookpilot-for-woocommerce' ); ?></span>
								<span style="text-align: right;"><?php esc_html_e( 'Actions', 'hookpilot-for-woocommerce' ); ?></span>
							</div>

							<?php if ( empty( $settings ) ) : ?>
								<div class="hkplt-empty-state" style="padding: 60px;">
									<span class="dashicons dashicons-info-outline"></span>
									<p><?php esc_html_e( 'No hook rules configured yet.', 'hookpilot-for-woocommerce' ); ?></p>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=hkplt-add-hook' ) ); ?>" class="hkplt-btn hkplt-btn--primary"><?php esc_html_e( '+ Add Rule', 'hookpilot-for-woocommerce' ); ?></a>
								</div>
							<?php else : ?>
								<div class="hkplt-rules-grid" id="hkplt-rules-grid">
									<?php foreach ( $settings as $index => $s ) :
										$status    = isset( $s['status'] ) ? $s['status'] : 'active';
										$hook_name = isset( $s['hook_name'] ) ? $s['hook_name'] : '';
										$priority  = isset( $s['priority'] ) ? (int) $s['priority'] : 10;
										$is_active = ( $status !== 'inactive' );
									?>
										<div class="hkplt-rule-card" data-id="<?php echo (int) $index; ?>">
											<div class="hkplt-rule-title-cell">
												<strong><?php echo esc_html( !empty($s['rule_title']) ? $s['rule_title'] : hkplt_status_label( $status ) ); ?></strong>
												<span class="hkplt-badge hkplt-badge--<?php echo esc_attr( $status ); ?>" style="font-size: 10px; padding: 2px 8px;">
													<?php echo esc_html( hkplt_status_label( $status ) ); ?>
												</span>
											</div>

											<div class="hkplt-rule-hook-cell">
												<code><?php echo esc_html( $hook_name ); ?></code>
											</div>

											<div class="hkplt-rule-priority-cell">
												<span class="pill"><?php echo (int) $priority; ?></span>
											</div>



											<div class="hkplt-rule-actions-cell">
												<button class="hkplt-btn hkplt-btn--sm hkplt-btn--outline hkplt-btn-edit" data-id="<?php echo (int) $index; ?>" title="<?php esc_attr_e( 'Edit', 'hookpilot-for-woocommerce' ); ?>">
													<span class="dashicons dashicons-edit"></span>
												</button>
												<button class="hkplt-btn hkplt-btn--sm hkplt-btn--danger hkplt-btn-delete" data-id="<?php echo (int) $index; ?>" title="<?php esc_attr_e( 'Delete', 'hookpilot-for-woocommerce' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</button>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<div id="hkplt-edit-modal" class="hkplt-modal" style="display:none;" aria-modal="true" role="dialog">
						<div class="hkplt-modal__backdrop"></div>
						<div class="hkplt-modal__box">
							<div class="hkplt-modal__head">
								<h3><?php esc_html_e( 'Edit Hook Rule', 'hookpilot-for-woocommerce' ); ?></h3>
								<button class="hkplt-modal__close">&times;</button>
							</div>
							<div class="hkplt-modal__body">
								<form id="hkplt-edit-form" class="hkplt-form hkplt-form--modal">
									<input type="hidden" id="hkplt-edit-id" name="setting[id]" value="" />

									<div class="hkplt-form-row">
										<label><?php esc_html_e( 'Hook Name', 'hookpilot-for-woocommerce' ); ?></label>
										<select id="hkplt-edit-hook-name" name="setting[hook_name]" class="hkplt-select hkplt-hook-select-edit" required>
											<option value=""><?php esc_html_e( '— Select a Hook —', 'hookpilot-for-woocommerce' ); ?></option>
											<?php foreach ( $hook_list as $h ) : ?>
												<option value="<?php echo esc_attr( $h ); ?>"><?php echo esc_html( $h ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>

									<div class="hkplt-form-row">
										<label><?php esc_html_e( 'Rule Type', 'hookpilot-for-woocommerce' ); ?></label>
										<select id="hkplt-edit-rule-type" name="setting[status]" class="hkplt-select hkplt-edit-type-select">
											<option value="disable"><?php esc_html_e( 'Disable Callback', 'hookpilot-for-woocommerce' ); ?></option>
											<option value="priority"><?php esc_html_e( 'Change Priority', 'hookpilot-for-woocommerce' ); ?></option>
											<option value="wrapper"><?php esc_html_e( 'Add HTML Wrapper', 'hookpilot-for-woocommerce' ); ?></option>
											<option value="custom_content"><?php esc_html_e( 'Insert Custom Content', 'hookpilot-for-woocommerce' ); ?></option>
											<option value="shortcode"><?php esc_html_e( 'Insert Shortcode', 'hookpilot-for-woocommerce' ); ?></option>
										</select>
									</div>

									<!-- Disable / Priority shared callback field -->
									<div class="hkplt-edit-section hkplt-section-box" id="edit-section-callback">
										<p class="hkplt-section-box__head"><?php esc_html_e( 'Callback', 'hookpilot-for-woocommerce' ); ?></p>
										<div class="hkplt-form-row" style="margin-bottom:0">
											<label><?php esc_html_e( 'Callback Name', 'hookpilot-for-woocommerce' ); ?></label>
											<input type="text" id="hkplt-edit-callback" name="setting[callback_name]" class="hkplt-input" placeholder="function_name or Class::method" />
											<p class="hkplt-field-hint"><?php esc_html_e( 'Enter the exact callback name to disable or re-prioritise.', 'hookpilot-for-woocommerce' ); ?></p>
										</div>
									</div>

									<!-- Priority section -->
									<div class="hkplt-edit-section hkplt-section-box" id="edit-section-priority" style="display:none">
										<p class="hkplt-section-box__head"><?php esc_html_e( 'Priority Settings', 'hookpilot-for-woocommerce' ); ?></p>
										<div class="hkplt-priority-grid">
											<div class="hkplt-form-row" style="margin-bottom:0">
												<label><?php esc_html_e( 'Current Priority', 'hookpilot-for-woocommerce' ); ?></label>
												<input type="number" id="hkplt-edit-old-priority" name="setting[old_priority]" class="hkplt-input" value="10" min="0" max="9999" />
											</div>
											<div class="hkplt-form-row" style="margin-bottom:0">
												<label><?php esc_html_e( 'New Priority', 'hookpilot-for-woocommerce' ); ?></label>
												<input type="number" id="hkplt-edit-priority-val" name="setting[priority]" class="hkplt-input hkplt-priority-input" value="10" min="0" max="9999" />
												<div class="hkplt-priority-presets">
													<?php foreach ( array( 1, 5, 10, 20, 100, 999 ) as $p ) : ?>
													<button type="button" class="hkplt-preset-btn" data-target="hkplt-edit-priority-val" data-val="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></button>
													<?php endforeach; ?>
												</div>
											</div>
										</div>
									</div>

									<!-- Wrapper section -->
									<div class="hkplt-edit-section hkplt-section-box" id="edit-section-wrapper" style="display:none">
										<p class="hkplt-section-box__head"><?php esc_html_e( 'HTML Wrapper', 'hookpilot-for-woocommerce' ); ?></p>
										<div class="hkplt-form-row">
											<label><?php esc_html_e( 'HTML Tag', 'hookpilot-for-woocommerce' ); ?></label>
											<div class="hkplt-tag-picker">
												<?php foreach ( array( 'div','span','section','article','aside','header','footer','p','nav' ) as $tag ) : ?>
												<button type="button" class="hkplt-tag-btn <?php echo $tag === 'div' ? 'is-active' : ''; ?>" data-tag="<?php echo esc_attr( $tag ); ?>">&lt;<?php echo esc_html( $tag ); ?>&gt;</button>
												<?php endforeach; ?>
											</div>
											<input type="hidden" id="hkplt-edit-wrapper-tag" name="setting[wrapper_tag]" value="div" />
										</div>
										<div class="hkplt-priority-grid">
											<div class="hkplt-form-row">
												<label><?php esc_html_e( 'CSS Class(es)', 'hookpilot-for-woocommerce' ); ?></label>
												<input type="text" id="hkplt-edit-wrapper-class" name="setting[wrapper_class]" class="hkplt-input" placeholder="my-class" />
											</div>
											<div class="hkplt-form-row">
												<label><?php esc_html_e( 'Extra Attributes', 'hookpilot-for-woocommerce' ); ?></label>
												<input type="text" id="hkplt-edit-wrapper-attrs" name="setting[wrapper_attrs]" class="hkplt-input" placeholder='data-id="1"' />
											</div>
										</div>
										<div class="hkplt-form-row" style="margin-bottom:0">
											<label><?php esc_html_e( 'Priority', 'hookpilot-for-woocommerce' ); ?></label>
											<input type="number" name="setting[priority]" class="hkplt-input hkplt-input--small hkplt-priority-input" value="10" min="0" max="9999" />
										</div>
									</div>

									<!-- Custom Content -->
									<div class="hkplt-edit-section hkplt-section-box" id="edit-section-content" style="display:none">
										<p class="hkplt-section-box__head"><?php esc_html_e( 'Custom Content', 'hookpilot-for-woocommerce' ); ?></p>
										<textarea id="hkplt-edit-content" name="setting[custom_content]" class="hkplt-textarea" rows="7" placeholder="<?php esc_attr_e( 'Enter custom HTML...', 'hookpilot-for-woocommerce' ); ?>"></textarea>
										<p class="hkplt-field-hint"><?php esc_html_e( 'Full WYSIWYG is available on the Add Rule page. HTML is preserved here.', 'hookpilot-for-woocommerce' ); ?></p>
										<div class="hkplt-form-row" style="margin-top:10px;margin-bottom:0">
											<label><?php esc_html_e( 'Output Priority', 'hookpilot-for-woocommerce' ); ?></label>
											<input type="number" name="setting[priority]" class="hkplt-input hkplt-input--small hkplt-priority-input" value="10" min="0" max="9999" />
										</div>
									</div>

									<!-- Shortcode -->
									<div class="hkplt-edit-section hkplt-section-box" id="edit-section-shortcode" style="display:none">
										<p class="hkplt-section-box__head"><?php esc_html_e( 'Shortcode', 'hookpilot-for-woocommerce' ); ?></p>
										<div class="hkplt-form-row">
											<label><?php esc_html_e( 'Shortcode', 'hookpilot-for-woocommerce' ); ?></label>
											<input type="text" id="hkplt-edit-shortcode" name="setting[shortcode_content]" class="hkplt-input" placeholder="[my_shortcode]" />
										</div>
										<div class="hkplt-form-row" style="margin-bottom:0">
											<label><?php esc_html_e( 'Output Priority', 'hookpilot-for-woocommerce' ); ?></label>
											<input type="number" id="hkplt-edit-priority-shortcode" name="setting[priority]" class="hkplt-input hkplt-input--small hkplt-priority-input" value="10" min="0" max="9999" />
										</div>
									</div>

								</form>
							</div>
							<div class="hkplt-modal__foot">
								<button type="button" class="hkplt-modal__close hkplt-btn hkplt-btn--outline"><?php esc_html_e( 'Cancel', 'hookpilot-for-woocommerce' ); ?></button>
								<button type="button" id="hkplt-edit-save" class="hkplt-btn hkplt-btn--primary">
									<?php esc_html_e( 'Update Rule', 'hookpilot-for-woocommerce' ); ?>
								</button>
								<span class="hkplt-spinner spinner"></span>
							</div>
						</div>
					</div>

				<?php
				/* ================================================================
				 * VIEW: ADD CUSTOM HOOK
				 * ============================================================== */
				elseif ( 'add_hook' === $view ) : ?>
					<div class="hkplt-add-hook">
						<div class="hkplt-section-header" style="margin-bottom: 32px;">
							<h2><?php esc_html_e( 'Create New Rule', 'hookpilot-for-woocommerce' ); ?></h2>
							<p><?php esc_html_e( 'Modify how WooCommerce behaves with custom hook rules.', 'hookpilot-for-woocommerce' ); ?></p>
						</div>

						<form id="hkplt-add-hook-form" class="hkplt-form">
							<?php wp_nonce_field( 'hkplt_nonce', 'hkplt_nonce_field' ); ?>

							<div class="hkplt-form-row">
								<label><?php esc_html_e( 'Rule Label', 'hookpilot-for-woocommerce' ); ?> <span class="hkplt-text-muted" style="font-weight: 400; font-size: 12px;"><?php esc_html_e( '(Internal name for your reference)', 'hookpilot-for-woocommerce' ); ?></span></label>
								<input type="text" name="setting[rule_title]" class="hkplt-input" placeholder="<?php esc_attr_e( 'e.g., Custom Upsell Banner', 'hookpilot-for-woocommerce' ); ?>" />
							</div>

							<div class="hkplt-form-row">
								<label><?php esc_html_e( 'Select Hook', 'hookpilot-for-woocommerce' ); ?> <span class="required">*</span></label>
								<select id="hkplt-hook-name" name="setting[hook_name]" class="hkplt-select hkplt-hook-select" required>
									<option value=""><?php esc_html_e( '— Select a hook —', 'hookpilot-for-woocommerce' ); ?></option>
									<?php foreach ( $hook_list as $h ) : ?>
										<option value="<?php echo esc_attr( $h ); ?>"><?php echo esc_html( $h ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="hkplt-form-row">
								<label><?php esc_html_e( 'Rule Type', 'hookpilot-for-woocommerce' ); ?></label>
								<div class="hkplt-type-cards">
									<?php
									$types = array(
										'disable'        => array( '&#x1F6AB;', __( 'Disable Callback', 'hookpilot-for-woocommerce' ),      __( 'Remove a callback from this hook', 'hookpilot-for-woocommerce' ) ),
										'priority'       => array( '&#x26A1;', __( 'Change Priority', 'hookpilot-for-woocommerce' ),        __( 'Move a callback to a new priority', 'hookpilot-for-woocommerce' ) ),
										'wrapper'        => array( '&#x1F4E6;', __( 'Add HTML Wrapper', 'hookpilot-for-woocommerce' ),       __( 'Wrap hook output in an HTML element', 'hookpilot-for-woocommerce' ) ),
										'custom_content' => array( '&#x270F;&#xFE0F;', __( 'Insert Custom Content', 'hookpilot-for-woocommerce' ), __( 'Output custom HTML at this hook', 'hookpilot-for-woocommerce' ) ),
										'shortcode'      => array( '&#x1F516;', __( 'Insert Shortcode', 'hookpilot-for-woocommerce' ),       __( 'Run a shortcode at this hook', 'hookpilot-for-woocommerce' ) ),
									);
									foreach ( $types as $val => $info ) : ?>
									<label class="hkplt-type-card <?php echo $val === 'disable' ? 'is-active' : ''; ?>">
										<input type="radio" name="setting[status]" value="<?php echo esc_attr( $val ); ?>" class="hkplt-rule-type-radio" <?php echo $val === 'disable' ? 'checked' : ''; ?>>
										<span class="hkplt-type-card__icon"><?php echo wp_kses_post( $info[0] ); ?></span>
										<strong><?php echo esc_html( $info[1] ); ?></strong>
										<small><?php echo esc_html( $info[2] ); ?></small>
									</label>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- DISABLE CALLBACK -->
							<div class="hkplt-rule-section hkplt-section-box" id="section-callback">
								<h3 class="hkplt-section-box__head"><?php esc_html_e( 'Select Callback to Disable', 'hookpilot-for-woocommerce' ); ?></h3>
								<div class="hkplt-form-row" style="margin-bottom:0">
									<label><?php esc_html_e( 'Registered Callbacks', 'hookpilot-for-woocommerce' ); ?></label>
									<div style="display:flex;gap:12px;align-items:center">
										<select id="hkplt-callback-select" name="setting[callback_name]" class="hkplt-select" style="flex:1">
											<option value=""><?php esc_html_e( '— Select a hook first —', 'hookpilot-for-woocommerce' ); ?></option>
										</select>
										<span id="hkplt-cb-spinner" style="display:none;font-size:18px">&#x23F3;</span>
									</div>
									<p class="hkplt-field-hint"><?php esc_html_e( 'Callbacks load automatically when you select a hook.', 'hookpilot-for-woocommerce' ); ?></p>
								</div>
							</div>

							<!-- CHANGE PRIORITY -->
							<div class="hkplt-rule-section hkplt-section-box" id="section-priority" style="display:none">
								<h3 class="hkplt-section-box__head"><?php esc_html_e( 'Change Priority', 'hookpilot-for-woocommerce' ); ?></h3>
								<div class="hkplt-form-row">
									<label><?php esc_html_e( 'Callback', 'hookpilot-for-woocommerce' ); ?></label>
									<select id="hkplt-priority-callback-select" name="setting[callback_name]" class="hkplt-select">
										<option value=""><?php esc_html_e( '— Select a hook first —', 'hookpilot-for-woocommerce' ); ?></option>
									</select>
								</div>
								<div class="hkplt-priority-grid">
									<div class="hkplt-form-row" style="margin-bottom:0">
										<label><?php esc_html_e( 'Current Priority', 'hookpilot-for-woocommerce' ); ?></label>
										<input type="number" id="hkplt-old-priority" name="setting[old_priority]" class="hkplt-input" value="10" min="0" max="9999" />
										<p class="hkplt-field-hint"><?php esc_html_e( 'Auto-filled when callback is picked', 'hookpilot-for-woocommerce' ); ?></p>
									</div>
									<div class="hkplt-form-row" style="margin-bottom:0">
										<label><?php esc_html_e( 'New Priority', 'hookpilot-for-woocommerce' ); ?></label>
										<input type="number" id="hkplt-new-priority" name="setting[priority]" class="hkplt-input" value="10" min="0" max="9999" />
										<div class="hkplt-priority-presets">
											<?php foreach ( array( 1, 5, 10, 20, 100, 999 ) as $p ) : ?>
											<button type="button" class="hkplt-preset-btn" data-target="hkplt-new-priority" data-val="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>

							<!-- HTML WRAPPER -->
							<div class="hkplt-rule-section hkplt-section-box" id="section-wrapper" style="display:none">
								<h3 class="hkplt-section-box__head"><?php esc_html_e( 'HTML Wrapper', 'hookpilot-for-woocommerce' ); ?></h3>
								<div class="hkplt-form-row">
									<label><?php esc_html_e( 'HTML Tag', 'hookpilot-for-woocommerce' ); ?></label>
									<div class="hkplt-tag-picker">
										<?php foreach ( array( 'div','span','section','article','aside','header','footer','p','nav' ) as $tag ) : ?>
										<button type="button" class="hkplt-tag-btn <?php echo $tag === 'div' ? 'is-active' : ''; ?>" data-tag="<?php echo esc_attr( $tag ); ?>">&lt;<?php echo esc_html( $tag ); ?>&gt;</button>
										<?php endforeach; ?>
									</div>
									<input type="hidden" id="hkplt-wrapper-tag" name="setting[wrapper_tag]" value="div" />
								</div>
								<div class="hkplt-priority-grid">
									<div class="hkplt-form-row">
										<label><?php esc_html_e( 'CSS Class(es)', 'hookpilot-for-woocommerce' ); ?></label>
										<input type="text" id="hkplt-wrapper-class" name="setting[wrapper_class]" class="hkplt-input" placeholder="my-wrapper card" />
									</div>
									<div class="hkplt-form-row">
										<label><?php esc_html_e( 'ID', 'hookpilot-for-woocommerce' ); ?></label>
										<input type="text" name="setting[wrapper_id]" class="hkplt-input" placeholder="section-id" />
									</div>
								</div>
								<div class="hkplt-form-row">
									<label><?php esc_html_e( 'Extra Attributes', 'hookpilot-for-woocommerce' ); ?></label>
									<input type="text" id="hkplt-wrapper-attrs" name="setting[wrapper_attrs]" class="hkplt-input" placeholder='data-id="123" aria-label="My section"' />
								</div>
								<div class="hkplt-wrapper-preview">
									<span><?php esc_html_e( 'Preview:', 'hookpilot-for-woocommerce' ); ?></span>
									<code id="hkplt-wrapper-preview-code">&lt;div&gt; &hellip; &lt;/div&gt;</code>
								</div>
								<div class="hkplt-form-row" style="margin-top:12px;margin-bottom:0">
									<label><?php esc_html_e( 'Priority', 'hookpilot-for-woocommerce' ); ?></label>
									<input type="number" name="setting[priority]" class="hkplt-input hkplt-input--small hkplt-priority-input" value="10" min="0" max="9999" />
								</div>
							</div>

							<!-- CUSTOM CONTENT -->
							<div class="hkplt-rule-section hkplt-section-box" id="section-content" style="display:none">
								<h3 class="hkplt-section-box__head"><?php esc_html_e( 'Custom Content (WYSIWYG)', 'hookpilot-for-woocommerce' ); ?></h3>
								<?php wp_editor( '', 'hkplt_custom_content', array(
									'textarea_name' => 'setting[custom_content]',
									'textarea_rows' => 10,
									'teeny'         => false,
									'media_buttons' => true,
								) ); ?>
								<div class="hkplt-form-row" style="margin-top:14px;margin-bottom:0">
									<label><?php esc_html_e( 'Output Priority', 'hookpilot-for-woocommerce' ); ?></label>
									<input type="number" name="setting[priority]" class="hkplt-input hkplt-input--small hkplt-priority-input" value="10" min="0" max="9999" />
								</div>
							</div>

							<!-- SHORTCODE -->
							<div class="hkplt-rule-section hkplt-section-box" id="section-shortcode" style="display:none">
								<h3 class="hkplt-section-box__head"><?php esc_html_e( 'Shortcode', 'hookpilot-for-woocommerce' ); ?></h3>
								<div class="hkplt-form-row">
									<label><?php esc_html_e( 'Shortcode', 'hookpilot-for-woocommerce' ); ?></label>
									<input type="text" name="setting[shortcode_content]" class="hkplt-input" placeholder="[my_shortcode]" />
								</div>
								<div class="hkplt-form-row" style="margin-bottom:0">
									<label><?php esc_html_e( 'Output Priority', 'hookpilot-for-woocommerce' ); ?></label>
									<input type="number" name="setting[priority]" class="hkplt-input hkplt-input--small hkplt-priority-input" value="10" min="0" max="9999" />
								</div>
							</div>

							<div class="hkplt-form-actions">
								<button type="submit" class="hkplt-btn hkplt-btn--primary" id="hkplt-save-hook">
									<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Rule', 'hookpilot-for-woocommerce' ); ?>
								</button>
								<span class="hkplt-spinner spinner"></span>
							</div>
						</form>
					</div>

				<?php
				/* ================================================================

				<?php
				/* ================================================================
				 * VIEW: IMPORT / EXPORT
				 * ============================================================== */
				elseif ( 'import_export' === $view ) : ?>
					<div class="hkplt-import-export">
						<div class="hkplt-section-header" style="margin-bottom: 32px;">
							<h2><?php esc_html_e( 'Import / Export Settings', 'hookpilot-for-woocommerce' ); ?></h2>
							<p><?php esc_html_e( 'Transfer your hook rules between different sites or environments using JSON.', 'hookpilot-for-woocommerce' ); ?></p>
						</div>

						<div class="hkplt-ie-grid">
							<!-- Export Section -->
							<div class="hkplt-ie-section">
								<div class="hkplt-ie-header">
									<div class="hkplt-ie-icon hkplt-ie-icon--export">
										<span class="dashicons dashicons-upload"></span>
									</div>
									<div>
										<h3><?php esc_html_e( 'Export Rules', 'hookpilot-for-woocommerce' ); ?></h3>
										<p><?php esc_html_e( 'Copy this JSON to import on another site.', 'hookpilot-for-woocommerce' ); ?></p>
									</div>
								</div>
								<textarea id="hkplt-export-json" class="hkplt-textarea" rows="12" readonly><?php echo esc_textarea( wp_json_encode( $settings, JSON_PRETTY_PRINT ) ); ?></textarea>
								<div class="hkplt-ie-actions">
									<button type="button" id="hkplt-copy-json" class="hkplt-btn hkplt-btn--primary hkplt-btn--full">
										<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy to Clipboard', 'hookpilot-for-woocommerce' ); ?>
									</button>
								</div>
								<p class="hkplt-ie-hint">
									<span class="dashicons dashicons-info-outline"></span>
									<?php
									printf(
										/* translators: %d: number of rules */
										esc_html__( 'Currently exporting %d rule(s).', 'hookpilot-for-woocommerce' ),
										count( $settings )
									);
									?>
								</p>
							</div>

							<!-- Import Section -->
							<div class="hkplt-ie-section">
								<div class="hkplt-ie-header">
									<div class="hkplt-ie-icon hkplt-ie-icon--import">
										<span class="dashicons dashicons-download"></span>
									</div>
									<div>
										<h3><?php esc_html_e( 'Import Rules', 'hookpilot-for-woocommerce' ); ?></h3>
										<p><?php esc_html_e( 'Paste a previously exported JSON string.', 'hookpilot-for-woocommerce' ); ?></p>
									</div>
								</div>
								<textarea id="hkplt-import-json" class="hkplt-textarea" rows="12" placeholder='[{"hook_name":"woocommerce_after_main_content","status":"custom_content","priority":10}]'></textarea>
								<div class="hkplt-ie-actions">
									<button type="button" id="hkplt-process-import" class="hkplt-btn hkplt-btn--primary hkplt-btn--full">
										<span class="dashicons dashicons-database-import"></span> <?php esc_html_e( 'Import Rules', 'hookpilot-for-woocommerce' ); ?>
									</button>
								</div>
								<div class="hkplt-ie-warning">
									<span class="dashicons dashicons-warning"></span>
									<div>
										<strong><?php esc_html_e( 'Caution', 'hookpilot-for-woocommerce' ); ?></strong>
										<p><?php esc_html_e( 'Importing will overwrite all existing hook rules. This action cannot be undone.', 'hookpilot-for-woocommerce' ); ?></p>
									</div>
								</div>
							</div>
						</div>
					</div>

				<?php endif; ?>
			</div><!-- .hkplt-content -->

		</div><!-- .hkplt-main-column -->

		<!-- Sidebar -->
		<aside class="hkplt-sidebar">



			<div class="hkplt-sidebar-box">
				<h3><?php esc_html_e( 'Quick Settings', 'hookpilot-for-woocommerce' ); ?></h3>
				<div class="hkplt-sidebar-setting">
					<span><?php esc_html_e( 'Debug Mode', 'hookpilot-for-woocommerce' ); ?></span>
					<label class="hkplt-toggle hkplt-toggle--sm">
						<input type="checkbox" class="hkplt-debug-sync" id="hkplt-debug-toggle" <?php checked( $debug_mode, 1 ); ?> />
						<span class="hkplt-toggle__slider"></span>
					</label>
				</div>
				<div class="hkplt-sidebar-setting">
					<span><?php esc_html_e( 'Clean on Uninstall', 'hookpilot-for-woocommerce' ); ?></span>
					<label class="hkplt-toggle hkplt-toggle--sm">
						<input type="checkbox" id="hkplt-uninstall-cleanup-toggle" <?php checked( (int) get_option( 'hkplt_uninstall_cleanup', 0 ), 1 ); ?> />
						<span class="hkplt-toggle__slider"></span>
					</label>
				</div>
				<p class="hkplt-field-hint" style="margin-top: 8px; font-size: 11px;">
					<?php esc_html_e( 'If enabled, all data will be removed when deleting the plugin.', 'hookpilot-for-woocommerce' ); ?>
				</p>
				<button id="hkplt-reset-settings-sidebar" class="hkplt-btn hkplt-btn--sm hkplt-btn--danger hkplt-btn--full hkplt-reset-trigger" style="margin-top: 15px;">
					<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Reset All Data', 'hookpilot-for-woocommerce' ); ?>
				</button>
			</div>

			<div class="hkplt-sidebar-box">
				<h3><?php esc_html_e( 'Export Config', 'hookpilot-for-woocommerce' ); ?></h3>
				<p style="font-size:12px;color:var(--hkplt-text-muted);margin-bottom:12px;">
					<?php esc_html_e( 'Generate a PHP config file containing your current hook patterns.', 'hookpilot-for-woocommerce' ); ?>
				</p>
				<button id="hkplt-generate-export" class="hkplt-btn hkplt-btn--outline hkplt-btn--full hkplt-btn--sm">
					<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export as PHP', 'hookpilot-for-woocommerce' ); ?>
				</button>
				<div id="hkplt-export-output" style="display:none; margin-top: 10px;">
					<button id="hkplt-copy-export" class="hkplt-btn hkplt-btn--primary hkplt-btn--full hkplt-btn--sm">
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy to Clipboard', 'hookpilot-for-woocommerce' ); ?>
                    </button>
					<button id="hkplt-download-export" class="hkplt-btn hkplt-btn--outline hkplt-btn--full hkplt-btn--sm" style="margin-top: 5px;">
						<span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'Download File', 'hookpilot-for-woocommerce' ); ?>
					</button>
					<div style="display:none;"><pre id="hkplt-export-code"></pre></div>
				</div>
			</div>


			<div class="hkplt-sidebar-box">
				<h3><?php esc_html_e( 'System Info', 'hookpilot-for-woocommerce' ); ?></h3>
				<ul class="hkplt-sys-info">
					<li><strong>Hookpilot:</strong> <span>v<?php echo esc_html( HKPLT_VERSION ); ?></span></li>
					<li><strong>WC:</strong> <span>v<?php echo esc_html( WC()->version ); ?></span></li>
					<li><strong>PHP:</strong> <span><?php echo esc_html( PHP_VERSION ); ?></span></li>
				</ul>
			</div>

			<div class="hkplt-sidebar-footer">
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> WooCommerce Hook Manager</p>
			</div>
		</aside>

	</div><!-- .hkplt-admin-layout -->
</div><!-- .hkplt-wrap -->
