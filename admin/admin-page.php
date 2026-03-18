<?php
/**
 * Admin Page Template
 *
 * Single template file that renders all sub-page views based on
 * the $view variable set by WHM_Admin_Page render callbacks.
 *
 * @package WHM
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Capability guard.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'hook-manager-for-woocommerce' ) );
}

// Default view.
$view = isset( $view ) ? $view : 'dashboard';

// Load settings for views that need them.
$settings   = get_option( WHM_OPTION_KEY, array() );
if ( ! is_array( $settings ) ) {
	$settings = array();
}
$debug_mode = (int) get_option( 'whm_debug_mode', 0 );

// Inspector data.
$inspector = new WHM_Hook_Inspector();
$hook_list = $inspector->get_wc_hook_list();

// Export manager.
$export_manager = new WHM_Export_Manager();

// Helper: human-readable status label.
function whm_status_label( $status ) {
	$map = array(
		'disable'        => __( 'Disable Callback', 'hook-manager-for-woocommerce' ),
		'priority'       => __( 'Change Priority', 'hook-manager-for-woocommerce' ),
		'wrapper'        => __( 'HTML Wrapper', 'hook-manager-for-woocommerce' ),
		'custom_content' => __( 'Custom Content', 'hook-manager-for-woocommerce' ),
		'shortcode'      => __( 'Shortcode', 'hook-manager-for-woocommerce' ),
		'active'         => __( 'Active', 'hook-manager-for-woocommerce' ),
		'inactive'       => __( 'Inactive', 'hook-manager-for-woocommerce' ),
	);
	return isset( $map[ $status ] ) ? $map[ $status ] : ucfirst( str_replace( '_', ' ', $status ) );
}
?>

<div class="wrap whm-wrap">
	<?php /* WordPress notices are injected after the first h1. This hidden H1 catches them. */ ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'WooCommerce Hook Manager', 'hook-manager-for-woocommerce' ); ?></h1>

	<div class="whm-admin-layout">
		
		<!-- Main Column -->
		<div class="whm-main-column">
			<div class="whm-header-premium">
				<div class="whm-header-content">
					<div class="whm-header-info">
						<div class="whm-logo-group">
							<div class="whm-icon-box">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10,1C8.9,1,8,1.9,8,3s0.9,2,2,2s2-0.9,2-2S11.1,1,10,1z M10,4.2C9.3,4.2,8.8,3.7,8.8,3S9.3,1.8,10,1.8S11.2,2.3,11.2,3S10.7,4.2,10,4.2z M10.5,5v9.5c0,1.4-1.1,2.5-2.5,2.5S5.5,15.9,5.5,14.5V12h1.5v2.5c0,0.6,0.4,1,1,1s1-0.4,1-1V5H10.5z M4.5,12l2.5-3l1.5,3H4.5z"/></svg>
							</div>
							<h2 class="whm-logo-title"><?php esc_html_e( 'Hook Manager', 'hook-manager-for-woocommerce' ); ?></h2>
							<span class="whm-version-badge">v<?php echo esc_html( WHM_VERSION ); ?></span>
						</div>
						<p class="whm-header-tagline"><?php esc_html_e( 'Visually manage WooCommerce hooks without editing any files.', 'hook-manager-for-woocommerce' ); ?></p>
					</div>
					<div class="whm-header-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=whm-add-hook' ) ); ?>" class="whm-btn whm-btn--primary">
							<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'New Rule', 'hook-manager-for-woocommerce' ); ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Navigation Tabs -->
			<nav class="whm-nav-tabs">
				<?php
				$tabs = array(
					'dashboard'  => array( 'label' => __( 'Dashboard', 'hook-manager-for-woocommerce' ), 'slug' => 'whm-dashboard', 'icon' => 'dashboard' ),
					'inspector'  => array( 'label' => __( 'Hook Inspector', 'hook-manager-for-woocommerce' ), 'slug' => 'whm-inspector', 'icon' => 'search' ),
					'manager'    => array( 'label' => __( 'Hook Manager', 'hook-manager-for-woocommerce' ), 'slug' => 'whm-manager', 'icon' => 'sort' ),
					'add_hook'   => array( 'label' => __( '+ Add Rule', 'hook-manager-for-woocommerce' ), 'slug' => 'whm-add-hook', 'icon' => 'edit' ),
					'import_export' => array( 'label' => __( 'Import/Export', 'hook-manager-for-woocommerce' ), 'slug' => 'whm-import-export', 'icon' => 'migrate' ),
				);
				foreach ( $tabs as $key => $tab ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $tab['slug'] ) ); ?>"
					   class="whm-tab <?php echo ( $view === $key ) ? 'whm-tab--active' : ''; ?>">
						<span class="dashicons dashicons-<?php echo esc_attr( $tab['icon'] ); ?>"></span>
						<?php echo esc_html( $tab['label'] ); ?>
						<?php if ( 'manager' === $key && ! empty( $settings ) ) : ?>
							<span class="whm-tab-count"><?php echo count( $settings ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="whm-content">
				<div id="whm-notice" class="whm-notice" style="display:none;"></div>

				<?php
				/* ================================================================
				 * VIEW: DASHBOARD
				 * ============================================================== */
				if ( 'dashboard' === $view ) : ?>
					<div class="whm-dashboard">
						<div class="whm-welcome-box">
							<div class="whm-welcome-text">
								<h2><?php esc_html_e( 'Welcome back!', 'hook-manager-for-woocommerce' ); ?></h2>
								<p><?php esc_html_e( 'You\'re currently monitoring active WooCommerce hooks. Use the inspector to find exact locations or manage rules in real-time.', 'hook-manager-for-woocommerce' ); ?></p>
								<div class="whm-welcome-actions">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=whm-inspector' ) ); ?>" class="whm-btn whm-btn--primary"><?php esc_html_e( 'Get Started', 'hook-manager-for-woocommerce' ); ?></a>
								</div>
							</div>
							<div class="whm-welcome-image">
								<span class="dashicons dashicons-shield"></span>
							</div>
						</div>

						<div class="whm-cards">
							<div class="whm-card">
								<span class="dashicons dashicons-search"></span>
								<h3><?php esc_html_e( 'Hook Inspector', 'hook-manager-for-woocommerce' ); ?></h3>
								<p><?php esc_html_e( 'Find exact hook locations and registered callbacks on your active WooCommerce pages.', 'hook-manager-for-woocommerce' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=whm-inspector' ) ); ?>" class="whm-btn whm-btn--outline whm-btn--full"><?php esc_html_e( 'Inspect Hooks', 'hook-manager-for-woocommerce' ); ?></a>
							</div>
							<div class="whm-card">
								<span class="dashicons dashicons-admin-settings"></span>
								<h3><?php esc_html_e( 'Hook Manager', 'hook-manager-for-woocommerce' ); ?></h3>
								<p><?php esc_html_e( 'Review your active rules, modify priorities, or delete existing modifications.', 'hook-manager-for-woocommerce' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=whm-manager' ) ); ?>" class="whm-btn whm-btn--outline whm-btn--full"><?php esc_html_e( 'Manage Rules', 'hook-manager-for-woocommerce' ); ?></a>
							</div>
							<div class="whm-card">
								<span class="dashicons dashicons-welcome-add-page"></span>
								<h3><?php esc_html_e( 'Add Custom Content', 'hook-manager-for-woocommerce' ); ?></h3>
								<p><?php esc_html_e( 'Insert your own HTML, shortcodes, or text into any WooCommerce action hook.', 'hook-manager-for-woocommerce' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=whm-add-hook' ) ); ?>" class="whm-btn whm-btn--outline whm-btn--full"><?php esc_html_e( 'Create Rule', 'hook-manager-for-woocommerce' ); ?></a>
							</div>
						</div>
					</div>

				<?php
				/* ================================================================
				 * VIEW: HOOK INSPECTOR
				 * ============================================================== */
				elseif ( 'inspector' === $view ) : ?>
					<div class="whm-inspector">
						<h2><?php esc_html_e( 'Hook Inspector', 'hook-manager-for-woocommerce' ); ?></h2>
						<p><?php esc_html_e( 'Displays registered callbacks for known WooCommerce hooks.', 'hook-manager-for-woocommerce' ); ?></p>

						<div class="whm-toolbar">
							<input type="text" id="whm-inspector-search" class="whm-input" placeholder="<?php esc_attr_e( 'Filter by hook name…', 'hook-manager-for-woocommerce' ); ?>" />
							<button id="whm-load-inspector" class="whm-btn whm-btn--primary"><?php esc_html_e( 'Load Hooks', 'hook-manager-for-woocommerce' ); ?></button>
						</div>

						<div id="whm-inspector-table-wrap">
							<p class="whm-placeholder"><?php esc_html_e( 'Click "Load Hooks" to inspect registered callbacks.', 'hook-manager-for-woocommerce' ); ?></p>
						</div>
					</div>

				<?php
				/* ================================================================
				 * VIEW: HOOK MANAGER
				 * ============================================================== */
				elseif ( 'manager' === $view ) : ?>
					<div class="whm-manager" id="whm-manager-view">
						<div class="whm-section-header" style="margin-bottom: 32px;">
							<h2><?php esc_html_e( 'Hook Manager', 'hook-manager-for-woocommerce' ); ?></h2>
							<p><?php esc_html_e( 'Review, edit, or delete existing hook rules. Drag to reorder priorities.', 'hook-manager-for-woocommerce' ); ?></p>
						</div>

						<div class="whm-rules-container">
							<div class="whm-rules-table-header">
								<span><?php esc_html_e( 'Rule Details', 'hook-manager-for-woocommerce' ); ?></span>
								<span><?php esc_html_e( 'Target Hook', 'hook-manager-for-woocommerce' ); ?></span>
								<span><?php esc_html_e( 'Priority', 'hook-manager-for-woocommerce' ); ?></span>
								<span style="text-align: right;"><?php esc_html_e( 'Actions', 'hook-manager-for-woocommerce' ); ?></span>
							</div>

							<?php if ( empty( $settings ) ) : ?>
								<div class="whm-empty-state" style="padding: 60px;">
									<span class="dashicons dashicons-info-outline"></span>
									<p><?php esc_html_e( 'No hook rules configured yet.', 'hook-manager-for-woocommerce' ); ?></p>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=whm-add-hook' ) ); ?>" class="whm-btn whm-btn--primary"><?php esc_html_e( '+ Add Rule', 'hook-manager-for-woocommerce' ); ?></a>
								</div>
							<?php else : ?>
								<div class="whm-rules-grid" id="whm-rules-grid">
									<?php foreach ( $settings as $index => $s ) :
										$status    = isset( $s['status'] ) ? $s['status'] : 'active';
										$hook_name = isset( $s['hook_name'] ) ? $s['hook_name'] : '';
										$priority  = isset( $s['priority'] ) ? (int) $s['priority'] : 10;
										$is_active = ( $status !== 'inactive' );
									?>
										<div class="whm-rule-card" data-id="<?php echo (int) $index; ?>">
											<div class="whm-rule-title-cell">
												<strong><?php echo esc_html( !empty($s['rule_title']) ? $s['rule_title'] : whm_status_label( $status ) ); ?></strong>
												<span class="whm-badge whm-badge--<?php echo esc_attr( $status ); ?>" style="font-size: 10px; padding: 2px 8px;">
													<?php echo esc_html( whm_status_label( $status ) ); ?>
												</span>
											</div>

											<div class="whm-rule-hook-cell">
												<code><?php echo esc_html( $hook_name ); ?></code>
											</div>

											<div class="whm-rule-priority-cell">
												<span class="pill"><?php echo (int) $priority; ?></span>
											</div>



											<div class="whm-rule-actions-cell">
												<button class="whm-btn whm-btn--sm whm-btn--outline whm-btn-edit" data-id="<?php echo (int) $index; ?>" title="<?php esc_attr_e( 'Edit', 'hook-manager-for-woocommerce' ); ?>">
													<span class="dashicons dashicons-edit"></span>
												</button>
												<button class="whm-btn whm-btn--sm whm-btn--danger whm-btn-delete" data-id="<?php echo (int) $index; ?>" title="<?php esc_attr_e( 'Delete', 'hook-manager-for-woocommerce' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</button>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<div id="whm-edit-modal" class="whm-modal" style="display:none;" aria-modal="true" role="dialog">
						<div class="whm-modal__backdrop"></div>
						<div class="whm-modal__box">
							<div class="whm-modal__head">
								<h3><?php esc_html_e( 'Edit Hook Rule', 'hook-manager-for-woocommerce' ); ?></h3>
								<button class="whm-modal__close">&times;</button>
							</div>
							<div class="whm-modal__body">
								<form id="whm-edit-form" class="whm-form whm-form--modal">
									<input type="hidden" id="whm-edit-id" name="setting[id]" value="" />

									<div class="whm-form-row">
										<label><?php esc_html_e( 'Hook Name', 'hook-manager-for-woocommerce' ); ?></label>
										<select id="whm-edit-hook-name" name="setting[hook_name]" class="whm-select whm-hook-select-edit" required>
											<option value=""><?php esc_html_e( '— Select a Hook —', 'hook-manager-for-woocommerce' ); ?></option>
											<?php foreach ( $hook_list as $h ) : ?>
												<option value="<?php echo esc_attr( $h ); ?>"><?php echo esc_html( $h ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>

									<div class="whm-form-row">
										<label><?php esc_html_e( 'Rule Type', 'hook-manager-for-woocommerce' ); ?></label>
										<select id="whm-edit-rule-type" name="setting[status]" class="whm-select whm-edit-type-select">
											<option value="disable"><?php esc_html_e( 'Disable Callback', 'hook-manager-for-woocommerce' ); ?></option>
											<option value="priority"><?php esc_html_e( 'Change Priority', 'hook-manager-for-woocommerce' ); ?></option>
											<option value="wrapper"><?php esc_html_e( 'Add HTML Wrapper', 'hook-manager-for-woocommerce' ); ?></option>
											<option value="custom_content"><?php esc_html_e( 'Insert Custom Content', 'hook-manager-for-woocommerce' ); ?></option>
											<option value="shortcode"><?php esc_html_e( 'Insert Shortcode', 'hook-manager-for-woocommerce' ); ?></option>
										</select>
									</div>

									<!-- Disable / Priority shared callback field -->
									<div class="whm-edit-section whm-section-box" id="edit-section-callback">
										<p class="whm-section-box__head"><?php esc_html_e( 'Callback', 'hook-manager-for-woocommerce' ); ?></p>
										<div class="whm-form-row" style="margin-bottom:0">
											<label><?php esc_html_e( 'Callback Name', 'hook-manager-for-woocommerce' ); ?></label>
											<input type="text" id="whm-edit-callback" name="setting[callback_name]" class="whm-input" placeholder="function_name or Class::method" />
											<p class="whm-field-hint"><?php esc_html_e( 'Enter the exact callback name to disable or re-prioritise.', 'hook-manager-for-woocommerce' ); ?></p>
										</div>
									</div>

									<!-- Priority section -->
									<div class="whm-edit-section whm-section-box" id="edit-section-priority" style="display:none">
										<p class="whm-section-box__head"><?php esc_html_e( 'Priority Settings', 'hook-manager-for-woocommerce' ); ?></p>
										<div class="whm-priority-grid">
											<div class="whm-form-row" style="margin-bottom:0">
												<label><?php esc_html_e( 'Current Priority', 'hook-manager-for-woocommerce' ); ?></label>
												<input type="number" id="whm-edit-old-priority" name="setting[old_priority]" class="whm-input" value="10" min="0" max="9999" />
											</div>
											<div class="whm-form-row" style="margin-bottom:0">
												<label><?php esc_html_e( 'New Priority', 'hook-manager-for-woocommerce' ); ?></label>
												<input type="number" id="whm-edit-priority-val" name="setting[priority]" class="whm-input whm-priority-input" value="10" min="0" max="9999" />
												<div class="whm-priority-presets">
													<?php foreach ( array( 1, 5, 10, 20, 100, 999 ) as $p ) : ?>
													<button type="button" class="whm-preset-btn" data-target="whm-edit-priority-val" data-val="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></button>
													<?php endforeach; ?>
												</div>
											</div>
										</div>
									</div>

									<!-- Wrapper section -->
									<div class="whm-edit-section whm-section-box" id="edit-section-wrapper" style="display:none">
										<p class="whm-section-box__head"><?php esc_html_e( 'HTML Wrapper', 'hook-manager-for-woocommerce' ); ?></p>
										<div class="whm-form-row">
											<label><?php esc_html_e( 'HTML Tag', 'hook-manager-for-woocommerce' ); ?></label>
											<div class="whm-tag-picker">
												<?php foreach ( array( 'div','span','section','article','aside','header','footer','p','nav' ) as $tag ) : ?>
												<button type="button" class="whm-tag-btn <?php echo $tag === 'div' ? 'is-active' : ''; ?>" data-tag="<?php echo esc_attr( $tag ); ?>">&lt;<?php echo esc_html( $tag ); ?>&gt;</button>
												<?php endforeach; ?>
											</div>
											<input type="hidden" id="whm-edit-wrapper-tag" name="setting[wrapper_tag]" value="div" />
										</div>
										<div class="whm-priority-grid">
											<div class="whm-form-row">
												<label><?php esc_html_e( 'CSS Class(es)', 'hook-manager-for-woocommerce' ); ?></label>
												<input type="text" id="whm-edit-wrapper-class" name="setting[wrapper_class]" class="whm-input" placeholder="my-class" />
											</div>
											<div class="whm-form-row">
												<label><?php esc_html_e( 'Extra Attributes', 'hook-manager-for-woocommerce' ); ?></label>
												<input type="text" id="whm-edit-wrapper-attrs" name="setting[wrapper_attrs]" class="whm-input" placeholder='data-id="1"' />
											</div>
										</div>
										<div class="whm-form-row" style="margin-bottom:0">
											<label><?php esc_html_e( 'Priority', 'hook-manager-for-woocommerce' ); ?></label>
											<input type="number" name="setting[priority]" class="whm-input whm-input--small whm-priority-input" value="10" min="0" max="9999" />
										</div>
									</div>

									<!-- Custom Content -->
									<div class="whm-edit-section whm-section-box" id="edit-section-content" style="display:none">
										<p class="whm-section-box__head"><?php esc_html_e( 'Custom Content', 'hook-manager-for-woocommerce' ); ?></p>
										<textarea id="whm-edit-content" name="setting[custom_content]" class="whm-textarea" rows="7" placeholder="<?php esc_attr_e( 'Enter custom HTML...', 'hook-manager-for-woocommerce' ); ?>"></textarea>
										<p class="whm-field-hint"><?php esc_html_e( 'Full WYSIWYG is available on the Add Rule page. HTML is preserved here.', 'hook-manager-for-woocommerce' ); ?></p>
										<div class="whm-form-row" style="margin-top:10px;margin-bottom:0">
											<label><?php esc_html_e( 'Output Priority', 'hook-manager-for-woocommerce' ); ?></label>
											<input type="number" name="setting[priority]" class="whm-input whm-input--small whm-priority-input" value="10" min="0" max="9999" />
										</div>
									</div>

									<!-- Shortcode -->
									<div class="whm-edit-section whm-section-box" id="edit-section-shortcode" style="display:none">
										<p class="whm-section-box__head"><?php esc_html_e( 'Shortcode', 'hook-manager-for-woocommerce' ); ?></p>
										<div class="whm-form-row">
											<label><?php esc_html_e( 'Shortcode', 'hook-manager-for-woocommerce' ); ?></label>
											<input type="text" id="whm-edit-shortcode" name="setting[shortcode_content]" class="whm-input" placeholder="[my_shortcode]" />
										</div>
										<div class="whm-form-row" style="margin-bottom:0">
											<label><?php esc_html_e( 'Output Priority', 'hook-manager-for-woocommerce' ); ?></label>
											<input type="number" id="whm-edit-priority-shortcode" name="setting[priority]" class="whm-input whm-input--small whm-priority-input" value="10" min="0" max="9999" />
										</div>
									</div>

								</form>
							</div>
							<div class="whm-modal__foot">
								<button type="button" class="whm-modal__close whm-btn whm-btn--outline"><?php esc_html_e( 'Cancel', 'hook-manager-for-woocommerce' ); ?></button>
								<button type="button" id="whm-edit-save" class="whm-btn whm-btn--primary">
									<?php esc_html_e( 'Update Rule', 'hook-manager-for-woocommerce' ); ?>
								</button>
								<span class="whm-spinner spinner"></span>
							</div>
						</div>
					</div>

				<?php
				/* ================================================================
				 * VIEW: ADD CUSTOM HOOK
				 * ============================================================== */
				elseif ( 'add_hook' === $view ) : ?>
					<div class="whm-add-hook">
						<div class="whm-section-header" style="margin-bottom: 32px;">
							<h2><?php esc_html_e( 'Create New Rule', 'hook-manager-for-woocommerce' ); ?></h2>
							<p><?php esc_html_e( 'Modify how WooCommerce behaves with custom hook rules.', 'hook-manager-for-woocommerce' ); ?></p>
						</div>

						<form id="whm-add-hook-form" class="whm-form">
							<?php wp_nonce_field( 'whm_nonce', 'whm_nonce_field' ); ?>

							<div class="whm-form-row">
								<label><?php esc_html_e( 'Rule Label', 'hook-manager-for-woocommerce' ); ?> <span class="whm-text-muted" style="font-weight: 400; font-size: 12px;"><?php esc_html_e( '(Internal name for your reference)', 'hook-manager-for-woocommerce' ); ?></span></label>
								<input type="text" name="setting[rule_title]" class="whm-input" placeholder="<?php esc_attr_e( 'e.g., Custom Upsell Banner', 'hook-manager-for-woocommerce' ); ?>" />
							</div>

							<div class="whm-form-row">
								<label><?php esc_html_e( 'Select Hook', 'hook-manager-for-woocommerce' ); ?> <span class="required">*</span></label>
								<select id="whm-hook-name" name="setting[hook_name]" class="whm-select whm-hook-select" required>
									<option value=""><?php esc_html_e( '— Select a hook —', 'hook-manager-for-woocommerce' ); ?></option>
									<?php foreach ( $hook_list as $h ) : ?>
										<option value="<?php echo esc_attr( $h ); ?>"><?php echo esc_html( $h ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="whm-form-row">
								<label><?php esc_html_e( 'Rule Type', 'hook-manager-for-woocommerce' ); ?></label>
								<div class="whm-type-cards">
									<?php
									$types = array(
										'disable'        => array( '&#x1F6AB;', __( 'Disable Callback', 'hook-manager-for-woocommerce' ),      __( 'Remove a callback from this hook', 'hook-manager-for-woocommerce' ) ),
										'priority'       => array( '&#x26A1;', __( 'Change Priority', 'hook-manager-for-woocommerce' ),        __( 'Move a callback to a new priority', 'hook-manager-for-woocommerce' ) ),
										'wrapper'        => array( '&#x1F4E6;', __( 'Add HTML Wrapper', 'hook-manager-for-woocommerce' ),       __( 'Wrap hook output in an HTML element', 'hook-manager-for-woocommerce' ) ),
										'custom_content' => array( '&#x270F;&#xFE0F;', __( 'Insert Custom Content', 'hook-manager-for-woocommerce' ), __( 'Output custom HTML at this hook', 'hook-manager-for-woocommerce' ) ),
										'shortcode'      => array( '&#x1F516;', __( 'Insert Shortcode', 'hook-manager-for-woocommerce' ),       __( 'Run a shortcode at this hook', 'hook-manager-for-woocommerce' ) ),
									);
									foreach ( $types as $val => $info ) : ?>
									<label class="whm-type-card <?php echo $val === 'disable' ? 'is-active' : ''; ?>">
										<input type="radio" name="setting[status]" value="<?php echo esc_attr( $val ); ?>" class="whm-rule-type-radio" <?php echo $val === 'disable' ? 'checked' : ''; ?>>
										<span class="whm-type-card__icon"><?php echo wp_kses_post( $info[0] ); ?></span>
										<strong><?php echo esc_html( $info[1] ); ?></strong>
										<small><?php echo esc_html( $info[2] ); ?></small>
									</label>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- DISABLE CALLBACK -->
							<div class="whm-rule-section whm-section-box" id="section-callback">
								<h3 class="whm-section-box__head"><?php esc_html_e( 'Select Callback to Disable', 'hook-manager-for-woocommerce' ); ?></h3>
								<div class="whm-form-row" style="margin-bottom:0">
									<label><?php esc_html_e( 'Registered Callbacks', 'hook-manager-for-woocommerce' ); ?></label>
									<div style="display:flex;gap:12px;align-items:center">
										<select id="whm-callback-select" name="setting[callback_name]" class="whm-select" style="flex:1">
											<option value=""><?php esc_html_e( '— Select a hook first —', 'hook-manager-for-woocommerce' ); ?></option>
										</select>
										<span id="whm-cb-spinner" style="display:none;font-size:18px">&#x23F3;</span>
									</div>
									<p class="whm-field-hint"><?php esc_html_e( 'Callbacks load automatically when you select a hook.', 'hook-manager-for-woocommerce' ); ?></p>
								</div>
							</div>

							<!-- CHANGE PRIORITY -->
							<div class="whm-rule-section whm-section-box" id="section-priority" style="display:none">
								<h3 class="whm-section-box__head"><?php esc_html_e( 'Change Priority', 'hook-manager-for-woocommerce' ); ?></h3>
								<div class="whm-form-row">
									<label><?php esc_html_e( 'Callback', 'hook-manager-for-woocommerce' ); ?></label>
									<select id="whm-priority-callback-select" name="setting[callback_name]" class="whm-select">
										<option value=""><?php esc_html_e( '— Select a hook first —', 'hook-manager-for-woocommerce' ); ?></option>
									</select>
								</div>
								<div class="whm-priority-grid">
									<div class="whm-form-row" style="margin-bottom:0">
										<label><?php esc_html_e( 'Current Priority', 'hook-manager-for-woocommerce' ); ?></label>
										<input type="number" id="whm-old-priority" name="setting[old_priority]" class="whm-input" value="10" min="0" max="9999" />
										<p class="whm-field-hint"><?php esc_html_e( 'Auto-filled when callback is picked', 'hook-manager-for-woocommerce' ); ?></p>
									</div>
									<div class="whm-form-row" style="margin-bottom:0">
										<label><?php esc_html_e( 'New Priority', 'hook-manager-for-woocommerce' ); ?></label>
										<input type="number" id="whm-new-priority" name="setting[priority]" class="whm-input" value="10" min="0" max="9999" />
										<div class="whm-priority-presets">
											<?php foreach ( array( 1, 5, 10, 20, 100, 999 ) as $p ) : ?>
											<button type="button" class="whm-preset-btn" data-target="whm-new-priority" data-val="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></button>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</div>

							<!-- HTML WRAPPER -->
							<div class="whm-rule-section whm-section-box" id="section-wrapper" style="display:none">
								<h3 class="whm-section-box__head"><?php esc_html_e( 'HTML Wrapper', 'hook-manager-for-woocommerce' ); ?></h3>
								<div class="whm-form-row">
									<label><?php esc_html_e( 'HTML Tag', 'hook-manager-for-woocommerce' ); ?></label>
									<div class="whm-tag-picker">
										<?php foreach ( array( 'div','span','section','article','aside','header','footer','p','nav' ) as $tag ) : ?>
										<button type="button" class="whm-tag-btn <?php echo $tag === 'div' ? 'is-active' : ''; ?>" data-tag="<?php echo esc_attr( $tag ); ?>">&lt;<?php echo esc_html( $tag ); ?>&gt;</button>
										<?php endforeach; ?>
									</div>
									<input type="hidden" id="whm-wrapper-tag" name="setting[wrapper_tag]" value="div" />
								</div>
								<div class="whm-priority-grid">
									<div class="whm-form-row">
										<label><?php esc_html_e( 'CSS Class(es)', 'hook-manager-for-woocommerce' ); ?></label>
										<input type="text" id="whm-wrapper-class" name="setting[wrapper_class]" class="whm-input" placeholder="my-wrapper card" />
									</div>
									<div class="whm-form-row">
										<label><?php esc_html_e( 'ID', 'hook-manager-for-woocommerce' ); ?></label>
										<input type="text" name="setting[wrapper_id]" class="whm-input" placeholder="section-id" />
									</div>
								</div>
								<div class="whm-form-row">
									<label><?php esc_html_e( 'Extra Attributes', 'hook-manager-for-woocommerce' ); ?></label>
									<input type="text" id="whm-wrapper-attrs" name="setting[wrapper_attrs]" class="whm-input" placeholder='data-id="123" aria-label="My section"' />
								</div>
								<div class="whm-wrapper-preview">
									<span><?php esc_html_e( 'Preview:', 'hook-manager-for-woocommerce' ); ?></span>
									<code id="whm-wrapper-preview-code">&lt;div&gt; &hellip; &lt;/div&gt;</code>
								</div>
								<div class="whm-form-row" style="margin-top:12px;margin-bottom:0">
									<label><?php esc_html_e( 'Priority', 'hook-manager-for-woocommerce' ); ?></label>
									<input type="number" name="setting[priority]" class="whm-input whm-input--small whm-priority-input" value="10" min="0" max="9999" />
								</div>
							</div>

							<!-- CUSTOM CONTENT -->
							<div class="whm-rule-section whm-section-box" id="section-content" style="display:none">
								<h3 class="whm-section-box__head"><?php esc_html_e( 'Custom Content (WYSIWYG)', 'hook-manager-for-woocommerce' ); ?></h3>
								<?php wp_editor( '', 'whm_custom_content', array(
									'textarea_name' => 'setting[custom_content]',
									'textarea_rows' => 10,
									'teeny'         => false,
									'media_buttons' => true,
								) ); ?>
								<div class="whm-form-row" style="margin-top:14px;margin-bottom:0">
									<label><?php esc_html_e( 'Output Priority', 'hook-manager-for-woocommerce' ); ?></label>
									<input type="number" name="setting[priority]" class="whm-input whm-input--small whm-priority-input" value="10" min="0" max="9999" />
								</div>
							</div>

							<!-- SHORTCODE -->
							<div class="whm-rule-section whm-section-box" id="section-shortcode" style="display:none">
								<h3 class="whm-section-box__head"><?php esc_html_e( 'Shortcode', 'hook-manager-for-woocommerce' ); ?></h3>
								<div class="whm-form-row">
									<label><?php esc_html_e( 'Shortcode', 'hook-manager-for-woocommerce' ); ?></label>
									<input type="text" name="setting[shortcode_content]" class="whm-input" placeholder="[my_shortcode]" />
								</div>
								<div class="whm-form-row" style="margin-bottom:0">
									<label><?php esc_html_e( 'Output Priority', 'hook-manager-for-woocommerce' ); ?></label>
									<input type="number" name="setting[priority]" class="whm-input whm-input--small whm-priority-input" value="10" min="0" max="9999" />
								</div>
							</div>

							<div class="whm-form-actions">
								<button type="submit" class="whm-btn whm-btn--primary" id="whm-save-hook">
									<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Rule', 'hook-manager-for-woocommerce' ); ?>
								</button>
								<span class="whm-spinner spinner"></span>
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
					<div class="whm-import-export">
						<div class="whm-section-header" style="margin-bottom: 32px;">
							<h2><?php esc_html_e( 'Import / Export Settings', 'hook-manager-for-woocommerce' ); ?></h2>
							<p><?php esc_html_e( 'Transfer your hook rules between different sites or environments using JSON.', 'hook-manager-for-woocommerce' ); ?></p>
						</div>

						<div class="whm-ie-grid">
							<!-- Export Section -->
							<div class="whm-ie-section">
								<div class="whm-ie-header">
									<div class="whm-ie-icon whm-ie-icon--export">
										<span class="dashicons dashicons-upload"></span>
									</div>
									<div>
										<h3><?php esc_html_e( 'Export Rules', 'hook-manager-for-woocommerce' ); ?></h3>
										<p><?php esc_html_e( 'Copy this JSON to import on another site.', 'hook-manager-for-woocommerce' ); ?></p>
									</div>
								</div>
								<textarea id="whm-export-json" class="whm-textarea" rows="12" readonly><?php echo esc_textarea( wp_json_encode( $settings, JSON_PRETTY_PRINT ) ); ?></textarea>
								<div class="whm-ie-actions">
									<button type="button" id="whm-copy-json" class="whm-btn whm-btn--primary whm-btn--full">
										<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy to Clipboard', 'hook-manager-for-woocommerce' ); ?>
									</button>
								</div>
								<p class="whm-ie-hint">
									<span class="dashicons dashicons-info-outline"></span>
									<?php
									printf(
										/* translators: %d: number of rules */
										esc_html__( 'Currently exporting %d rule(s).', 'hook-manager-for-woocommerce' ),
										count( $settings )
									);
									?>
								</p>
							</div>

							<!-- Import Section -->
							<div class="whm-ie-section">
								<div class="whm-ie-header">
									<div class="whm-ie-icon whm-ie-icon--import">
										<span class="dashicons dashicons-download"></span>
									</div>
									<div>
										<h3><?php esc_html_e( 'Import Rules', 'hook-manager-for-woocommerce' ); ?></h3>
										<p><?php esc_html_e( 'Paste a previously exported JSON string.', 'hook-manager-for-woocommerce' ); ?></p>
									</div>
								</div>
								<textarea id="whm-import-json" class="whm-textarea" rows="12" placeholder='[{"hook_name":"woocommerce_after_main_content","status":"custom_content","priority":10}]'></textarea>
								<div class="whm-ie-actions">
									<button type="button" id="whm-process-import" class="whm-btn whm-btn--primary whm-btn--full">
										<span class="dashicons dashicons-database-import"></span> <?php esc_html_e( 'Import Rules', 'hook-manager-for-woocommerce' ); ?>
									</button>
								</div>
								<div class="whm-ie-warning">
									<span class="dashicons dashicons-warning"></span>
									<div>
										<strong><?php esc_html_e( 'Caution', 'hook-manager-for-woocommerce' ); ?></strong>
										<p><?php esc_html_e( 'Importing will overwrite all existing hook rules. This action cannot be undone.', 'hook-manager-for-woocommerce' ); ?></p>
									</div>
								</div>
							</div>
						</div>
					</div>

				<?php endif; ?>
			</div><!-- .whm-content -->

		</div><!-- .whm-main-column -->

		<!-- Sidebar -->
		<aside class="whm-sidebar">



			<div class="whm-sidebar-box">
				<h3><?php esc_html_e( 'Quick Settings', 'hook-manager-for-woocommerce' ); ?></h3>
				<div class="whm-sidebar-setting">
					<span><?php esc_html_e( 'Debug Mode', 'hook-manager-for-woocommerce' ); ?></span>
					<label class="whm-toggle whm-toggle--sm">
						<input type="checkbox" class="whm-debug-sync" id="whm-debug-toggle" <?php checked( $debug_mode, 1 ); ?> />
						<span class="whm-toggle__slider"></span>
					</label>
				</div>
				<div class="whm-sidebar-setting">
					<span><?php esc_html_e( 'Clean on Uninstall', 'hook-manager-for-woocommerce' ); ?></span>
					<label class="whm-toggle whm-toggle--sm">
						<input type="checkbox" id="whm-uninstall-cleanup-toggle" <?php checked( (int) get_option( 'whm_uninstall_cleanup', 0 ), 1 ); ?> />
						<span class="whm-toggle__slider"></span>
					</label>
				</div>
				<p class="whm-field-hint" style="margin-top: 8px; font-size: 11px;">
					<?php esc_html_e( 'If enabled, all data will be removed when deleting the plugin.', 'hook-manager-for-woocommerce' ); ?>
				</p>
				<button id="whm-reset-settings-sidebar" class="whm-btn whm-btn--sm whm-btn--danger whm-btn--full whm-reset-trigger" style="margin-top: 15px;">
					<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Reset All Data', 'hook-manager-for-woocommerce' ); ?>
				</button>
			</div>

			<div class="whm-sidebar-box">
				<h3><?php esc_html_e( 'Export Config', 'hook-manager-for-woocommerce' ); ?></h3>
				<p style="font-size:12px;color:var(--whm-text-muted);margin-bottom:12px;">
					<?php esc_html_e( 'Generate a PHP config file containing your current hook patterns.', 'hook-manager-for-woocommerce' ); ?>
				</p>
				<button id="whm-generate-export" class="whm-btn whm-btn--outline whm-btn--full whm-btn--sm">
					<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export as PHP', 'hook-manager-for-woocommerce' ); ?>
				</button>
				<div id="whm-export-output" style="display:none; margin-top: 10px;">
					<button id="whm-copy-export" class="whm-btn whm-btn--primary whm-btn--full whm-btn--sm">
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy to Clipboard', 'hook-manager-for-woocommerce' ); ?>
                    </button>
					<button id="whm-download-export" class="whm-btn whm-btn--outline whm-btn--full whm-btn--sm" style="margin-top: 5px;">
						<span class="dashicons dashicons-media-text"></span> <?php esc_html_e( 'Download File', 'hook-manager-for-woocommerce' ); ?>
					</button>
					<div style="display:none;"><pre id="whm-export-code"></pre></div>
				</div>
			</div>


			<div class="whm-sidebar-box">
				<h3><?php esc_html_e( 'System Info', 'hook-manager-for-woocommerce' ); ?></h3>
				<ul class="whm-sys-info">
					<li><strong>WHM:</strong> <span>v<?php echo esc_html( WHM_VERSION ); ?></span></li>
					<li><strong>WC:</strong> <span>v<?php echo esc_html( WC()->version ); ?></span></li>
					<li><strong>PHP:</strong> <span><?php echo esc_html( PHP_VERSION ); ?></span></li>
				</ul>
			</div>

			<div class="whm-sidebar-footer">
				<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> WooCommerce Hook Manager</p>
			</div>
		</aside>

	</div><!-- .whm-admin-layout -->
</div><!-- .whm-wrap -->
