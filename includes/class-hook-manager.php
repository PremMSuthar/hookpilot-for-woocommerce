<?php
/**
 * Hook Manager
 *
 * Reads the saved hook settings from the database and applies them
 * to the WordPress hook system: removes callbacks, re-adds with new
 * priority, wraps hook output, injects custom content, and enables
 * the frontend debug overlay.
 *
 * @package WHM
 */

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WHM_Hook_Manager
 */
class WHM_Hook_Manager {

	/**
	 * Raw settings array loaded from the DB.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Constructor – loads settings once.
	 */
	public function __construct() {
		$this->settings = get_option( WHM_OPTION_KEY, array() );
		if ( ! is_array( $this->settings ) ) {
			$this->settings = array();
		}
	}

	/**
	 * Register all WordPress hooks managed by this class.
	 */
	public function register_hooks() {
		// Apply hook changes once all plugins have loaded.
		add_action( 'wp', array( $this, 'apply_hook_settings' ) );

		// Frontend debug overlay (only for admins when debug mode is on).
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_debug_overlay' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_whm_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_whm_update_setting', array( $this, 'ajax_update_setting' ) );
		add_action( 'wp_ajax_whm_delete_setting', array( $this, 'ajax_delete_setting' ) );
		add_action( 'wp_ajax_whm_toggle_debug', array( $this, 'ajax_toggle_debug' ) );
		add_action( 'wp_ajax_whm_toggle_uninstall_cleanup', array( $this, 'ajax_toggle_uninstall_cleanup' ) );
		add_action( 'wp_ajax_whm_import_json', array( $this, 'ajax_import_json' ) );
		add_action( 'wp_ajax_whm_get_hook_callbacks', array( $this, 'ajax_get_hook_callbacks' ) );
	}

	/**
	 * Apply every saved hook rule to the WordPress hook system.
	 */
	public function apply_hook_settings() {
		foreach ( $this->settings as $setting ) {
			if ( empty( $setting['hook_name'] ) ) {
				continue;
			}

			$status    = isset( $setting['status'] ) ? (string) $setting['status'] : 'active';
			$hook_name = (string) $setting['hook_name'];

			// Skip inactive rules.
			if ( 'inactive' === $status ) {
				continue;
			}

			// --- Disable a specific callback ---
			if ( 'disable' === $status && ! empty( $setting['callback_name'] ) ) {
				$this->remove_callback( $hook_name, $setting['callback_name'], $setting );
				continue;
			}

			// --- Change priority ---
			if ( 'priority' === $status && ! empty( $setting['callback_name'] ) ) {
				$old_priority = isset( $setting['old_priority'] ) ? (int) $setting['old_priority'] : 10;
				$new_priority = isset( $setting['priority'] ) ? (int) $setting['priority'] : 10;
				$this->change_priority( $hook_name, $setting['callback_name'], $old_priority, $new_priority );
				continue;
			}

			// --- Wrapper (dynamic tag, classes, attributes) ---
			if ( 'wrapper' === $status ) {
				$priority = isset( $setting['priority'] ) ? (int) $setting['priority'] : 10;
				$this->add_wrapper( $hook_name, $setting, $priority );
				continue;
			}

			// --- Custom HTML content ---
			if ( 'custom_content' === $status && ! empty( $setting['custom_content'] ) ) {
				$priority       = isset( $setting['priority'] ) ? (int) $setting['priority'] : 10;
				$custom_content = $setting['custom_content'];
				$this->add_custom_content( $hook_name, $custom_content, $priority );
				continue;
			}

			// --- Shortcode content ---
			if ( 'shortcode' === $status && ! empty( $setting['shortcode_content'] ) ) {
				$priority          = isset( $setting['priority'] ) ? (int) $setting['priority'] : 10;
				$shortcode_content = $setting['shortcode_content'];
				$this->add_shortcode_content( $hook_name, $shortcode_content, $priority );
				continue;
			}
		}
	}

	/**
	 * Remove (disable) a named callback from a hook.
	 *
	 * @param  string $hook_name     Hook name.
	 * @param  string $callback_name Callback string (function or Class::method).
	 * @param  array  $setting       The full setting row for priority hint.
	 */
	private function remove_callback( $hook_name, $callback_name, $setting ) {
		$priority = isset( $setting['priority'] ) ? (int) $setting['priority'] : false;

		if ( strpos( $callback_name, '::' ) !== false ) {
			list( $class, $method ) = explode( '::', $callback_name, 2 );
			// Remove from any known object instance.
			if ( has_action( $hook_name ) ) {
				global $wp_filter;
				if ( isset( $wp_filter[ $hook_name ] ) ) {
					foreach ( $wp_filter[ $hook_name ]->callbacks as $prio => $fns ) {
						if ( false !== $priority && (int) $prio !== $priority ) {
							continue;
						}
						foreach ( $fns as $fn_data ) {
							$cb = $fn_data['function'];
							if ( is_array( $cb ) && is_object( $cb[0] ) && $cb[1] === $method && get_class( $cb[0] ) === $class ) {
								remove_action( $hook_name, $cb, $prio );
							}
						}
					}
				}
			}
		} else {
			$remove_prio = false !== $priority ? $priority : 10;
			remove_action( $hook_name, $callback_name, $remove_prio );
		}
	}

	/**
	 * Change the priority of a named callback on a hook.
	 *
	 * @param  string $hook_name     Hook name.
	 * @param  string $callback_name Callback identifier.
	 * @param  int    $old_priority  Current priority.
	 * @param  int    $new_priority  Desired new priority.
	 */
	private function change_priority( $hook_name, $callback_name, $old_priority, $new_priority ) {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $hook_name ] ) ) {
			return;
		}

		if ( strpos( $callback_name, '::' ) !== false ) {
			list( $class, $method ) = explode( '::', $callback_name, 2 );
			if ( isset( $wp_filter[ $hook_name ]->callbacks[ $old_priority ] ) ) {
				foreach ( $wp_filter[ $hook_name ]->callbacks[ $old_priority ] as $fn_data ) {
					$cb = $fn_data['function'];
					if ( is_array( $cb ) && is_object( $cb[0] ) && get_class( $cb[0] ) === $class && $cb[1] === $method ) {
						$accepted_args = $fn_data['accepted_args'];
						remove_action( $hook_name, $cb, $old_priority );
						add_action( $hook_name, $cb, $new_priority, $accepted_args );
						return;
					}
				}
			}
		} else {
			if ( has_action( $hook_name, $callback_name ) ) {
				remove_action( $hook_name, $callback_name, $old_priority );
				add_action( $hook_name, $callback_name, $new_priority );
			}
		}
	}

	/**
	 * Wrap a hook with HTML before and after it fires.
	 * Supports legacy manual HTML and new dynamic tag/class/attr selection.
	 *
	 * @param  string $hook_name Hook name.
	 * @param  array  $setting   The full setting row.
	 * @param  int    $priority  Priority.
	 */
	private function add_wrapper( $hook_name, $setting, $priority ) {
		$tag   = ! empty( $setting['wrapper_tag'] ) ? sanitize_key( $setting['wrapper_tag'] ) : 'div';
		$class = ! empty( $setting['wrapper_class'] ) ? sanitize_text_field( $setting['wrapper_class'] ) : '';
		$attrs = ! empty( $setting['wrapper_attrs'] ) ? sanitize_text_field( $setting['wrapper_attrs'] ) : '';

		// If manual wrapper start is provided, it takes precedence (legacy support).
		if ( ! empty( $setting['wrapper_start'] ) ) {
			$before = $setting['wrapper_start'];
			$after  = ! empty( $setting['wrapper_end'] ) ? $setting['wrapper_end'] : '</div>';
		} else {
			$before = '<' . $tag . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . ( $attrs ? ' ' . $attrs : '' ) . '>';
			$after  = '</' . $tag . '>';
		}

		add_action(
			$hook_name,
			static function () use ( $before ) {
				echo wp_kses_post( $before );
			},
			( $priority - 1 )
		);

		add_action(
			$hook_name,
			static function () use ( $after ) {
				echo wp_kses_post( $after );
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Add an arbitrary HTML content output to a hook.
	 *
	 * @param  string $hook_name     Hook name.
	 * @param  string $content       HTML content.
	 * @param  int    $priority      Priority.
	 */
	private function add_custom_content( $hook_name, $content, $priority ) {
		add_action(
			$hook_name,
			static function () use ( $content ) {
				echo wp_kses_post( $content );
			},
			$priority
		);
	}

	/**
	 * Add a shortcode output to a hook.
	 *
	 * @param  string $hook_name Hook name.
	 * @param  string $content   Shortcode string.
	 * @param  int    $priority  Priority.
	 */
	private function add_shortcode_content( $hook_name, $content, $priority ) {
		add_action(
			$hook_name,
			static function () use ( $content ) {
				echo do_shortcode( $content );
			},
			$priority
		);
	}

	/**
	 * Enqueue the frontend debug overlay assets if debug mode is on.
	 */
	public function maybe_enqueue_debug_overlay() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! get_option( 'whm_debug_mode', 0 ) ) {
			return;
		}

		wp_enqueue_style(
			'whm-debug-overlay',
			WHM_PLUGIN_URL . 'public/hook-debug-overlay.css',
			array(),
			WHM_VERSION
		);

		wp_enqueue_script(
			'whm-debug-overlay',
			WHM_PLUGIN_URL . 'public/hook-debug-overlay.js',
			array( 'jquery' ),
			WHM_VERSION,
			true
		);

		// Pass hook inspector data and active plugin rules to JS.
		$inspector = new WHM_Hook_Inspector();
		$hook_data = $inspector->get_all_hook_data();

		// Build a clean list of active rules to display in the debug panel.
		$raw_settings = get_option( WHM_OPTION_KEY, array() );
		$active_rules = array();
		if ( is_array( $raw_settings ) ) {
			foreach ( $raw_settings as $rule ) {
				if ( empty( $rule['hook_name'] ) ) {
					continue;
				}
				// Skip rules the admin has explicitly deactivated.
				if ( isset( $rule['status'] ) && 'inactive' === $rule['status'] ) {
					continue;
				}
				$active_rules[] = array(
					'id'            => isset( $rule['id'] ) ? (int) $rule['id'] : 0,
					'hook_name'     => sanitize_text_field( $rule['hook_name'] ),
					'status'        => isset( $rule['status'] ) ? sanitize_key( $rule['status'] ) : 'active',
					'callback_name' => isset( $rule['callback_name'] ) ? sanitize_text_field( $rule['callback_name'] ) : '',
					'priority'      => isset( $rule['priority'] ) ? (int) $rule['priority'] : 10,
					'old_priority'  => isset( $rule['old_priority'] ) ? (int) $rule['old_priority'] : 10,
					'wrapper_tag'   => isset( $rule['wrapper_tag'] ) ? sanitize_key( $rule['wrapper_tag'] ) : '',
					'wrapper_class' => isset( $rule['wrapper_class'] ) ? sanitize_text_field( $rule['wrapper_class'] ) : '',
				);
			}
		}

		wp_localize_script(
			'whm-debug-overlay',
			'whmDebugData',
			array(
				'hooks'   => $hook_data,
				'rules'   => $active_rules,
				'strings' => array(
					'panel_title'  => esc_html__( 'Active WHM Rules', 'woocommerce-hook-manager' ),
					'rule'         => esc_html__( 'rule', 'woocommerce-hook-manager' ),
					'rules'        => esc_html__( 'rules', 'woocommerce-hook-manager' ),
					'no_rules'     => esc_html__( 'No active rules configured.', 'woocommerce-hook-manager' ),
					'disabled'     => esc_html__( 'Disabled', 'woocommerce-hook-manager' ),
					'priority'     => esc_html__( 'Priority', 'woocommerce-hook-manager' ),
					'wrapper'      => esc_html__( 'Wrapper', 'woocommerce-hook-manager' ),
					'custom_html'  => esc_html__( 'Custom HTML', 'woocommerce-hook-manager' ),
					'shortcode'    => esc_html__( 'Shortcode', 'woocommerce-hook-manager' ),
					'active'       => esc_html__( 'Active', 'woocommerce-hook-manager' ),
				),
			)
		);

	}

	/**
	 * AJAX: Save a hook setting.
	 */
	public function ajax_save_settings() {
		check_ajax_referer( 'whm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'woocommerce-hook-manager' ) ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification -- already verified above.
		$raw = isset( $_POST['setting'] ) ? wp_unslash( $_POST['setting'] ) : array();
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		// phpcs:enable

		$setting = $this->sanitize_setting( $raw );

		if ( empty( $setting['hook_name'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Hook name is required.', 'woocommerce-hook-manager' ) ) );
		}

		$settings = get_option( WHM_OPTION_KEY, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Update existing or append.
		$id   = isset( $setting['id'] ) ? (int) $setting['id'] : - 1;
		$saved = false;

		if ( $id >= 0 && isset( $settings[ $id ] ) ) {
			$settings[ $id ] = $setting;
			$saved           = true;
		}

		if ( ! $saved ) {
			$setting['id'] = count( $settings );
			$settings[]    = $setting;
		}

		update_option( WHM_OPTION_KEY, $settings );

		wp_send_json_success( array( 'message' => esc_html__( 'Setting saved.', 'woocommerce-hook-manager' ) ) );
	}

	/**
	 * AJAX: Update (edit) an existing hook setting by index.
	 */
	public function ajax_update_setting() {
		check_ajax_referer( 'whm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'woocommerce-hook-manager' ) ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification -- already verified.
		$raw = isset( $_POST['setting'] ) ? wp_unslash( $_POST['setting'] ) : array();
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		$id  = isset( $raw['id'] ) ? (int) $raw['id'] : -1;
		// phpcs:enable

		if ( $id < 0 ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid rule ID.', 'woocommerce-hook-manager' ) ) );
		}

		$settings = get_option( WHM_OPTION_KEY, array() );
		if ( ! is_array( $settings ) || ! isset( $settings[ $id ] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Rule not found.', 'woocommerce-hook-manager' ) ) );
		}

		$setting = $this->sanitize_setting( $raw );

		if ( empty( $setting['hook_name'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Hook name is required.', 'woocommerce-hook-manager' ) ) );
		}

		$setting['id']   = $id;
		$settings[ $id ] = $setting;
		update_option( WHM_OPTION_KEY, $settings );

		wp_send_json_success(
			array(
				'message'  => esc_html__( 'Rule updated.', 'woocommerce-hook-manager' ),
				'setting'  => $setting,
				'settings' => array_values( $settings ),
			)
		);
	}

	/**
	 * AJAX: Delete a hook setting by index.
	 */
	public function ajax_delete_setting() {
		check_ajax_referer( 'whm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'woocommerce-hook-manager' ) ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification -- already verified.
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : - 1;
		// phpcs:enable

		$settings = get_option( WHM_OPTION_KEY, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		if ( ! isset( $settings[ $id ] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Setting not found.', 'woocommerce-hook-manager' ) ) );
		}

		unset( $settings[ $id ] );
		$settings = array_values( $settings ); // Re-index.
		update_option( WHM_OPTION_KEY, $settings );

		wp_send_json_success( array( 'message' => esc_html__( 'Setting deleted.', 'woocommerce-hook-manager' ) ) );
	}

	/**
	 * AJAX: Toggle the frontend debug mode flag.
	 */
	public function ajax_toggle_debug() {
		check_ajax_referer( 'whm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'woocommerce-hook-manager' ) ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification -- already verified.
		$enabled = isset( $_POST['enabled'] ) ? (int) $_POST['enabled'] : 0;
		// phpcs:enable

		update_option( 'whm_debug_mode', $enabled ? 1 : 0 );

		wp_send_json_success( array( 'debug_mode' => $enabled ) );
	}

	/**
	 * AJAX: Toggle the uninstall cleanup flag.
	 */
	public function ajax_toggle_uninstall_cleanup() {
		check_ajax_referer( 'whm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'woocommerce-hook-manager' ) ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification
		$enabled = isset( $_POST['enabled'] ) ? (int) $_POST['enabled'] : 0;
		// phpcs:enable

		update_option( 'whm_uninstall_cleanup', $enabled ? 1 : 0 );

		wp_send_json_success( array( 'uninstall_cleanup' => $enabled ) );
	}

	/**
	 * AJAX: Import settings from a JSON string.
	 */
	public function ajax_import_json() {
		check_ajax_referer( 'whm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Insufficient permissions.', 'woocommerce-hook-manager' ) ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification
		$json = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : '';
		// phpcs:enable

		if ( empty( $json ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No JSON data provided.', 'woocommerce-hook-manager' ) ) );
		}

		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid JSON format.', 'woocommerce-hook-manager' ) ) );
		}

		$sanitized_settings = array();
		foreach ( $data as $row ) {
			if ( empty( $row['hook_name'] ) ) {
				continue;
			}
			$sanitized_settings[] = $this->sanitize_setting( $row );
		}

		// Update indices to be sequential based on the new array order.
		foreach ( $sanitized_settings as $index => &$setting ) {
			$setting['id'] = $index;
		}

		update_option( WHM_OPTION_KEY, $sanitized_settings );

		wp_send_json_success( array(
			'message' => esc_html__( 'Settings imported successfully.', 'woocommerce-hook-manager' ),
			'count'   => count( $sanitized_settings ),
		) );
	}


	/**
	 * Sanitize a raw setting array from POST data.
	 *
	 * @param  array $raw Raw POST data.
	 * @return array      Sanitized setting row.
	 */
	private function sanitize_setting( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		return array(
			'id'                => isset( $raw['id'] ) ? (int) $raw['id'] : - 1,
			'rule_title'        => isset( $raw['rule_title'] ) ? sanitize_text_field( wp_unslash( $raw['rule_title'] ) ) : '',
			'hook_name'         => isset( $raw['hook_name'] ) ? sanitize_text_field( wp_unslash( $raw['hook_name'] ) ) : '',
			'callback_name'     => isset( $raw['callback_name'] ) ? sanitize_text_field( wp_unslash( $raw['callback_name'] ) ) : '',
			'priority'          => isset( $raw['priority'] ) ? (int) $raw['priority'] : 10,
			'old_priority'      => isset( $raw['old_priority'] ) ? (int) $raw['old_priority'] : 10,
			'wrapper_start'     => isset( $raw['wrapper_start'] ) ? wp_kses_post( wp_unslash( $raw['wrapper_start'] ) ) : '',
			'wrapper_end'       => isset( $raw['wrapper_end'] ) ? wp_kses_post( wp_unslash( $raw['wrapper_end'] ) ) : '',
			'wrapper_tag'       => isset( $raw['wrapper_tag'] ) ? sanitize_key( $raw['wrapper_tag'] ) : 'div',
			'wrapper_class'     => isset( $raw['wrapper_class'] ) ? sanitize_text_field( wp_unslash( $raw['wrapper_class'] ) ) : '',
			'wrapper_attrs'     => isset( $raw['wrapper_attrs'] ) ? sanitize_text_field( wp_unslash( $raw['wrapper_attrs'] ) ) : '',
			'custom_content'    => isset( $raw['custom_content'] ) ? wp_kses_post( wp_unslash( $raw['custom_content'] ) ) : '',
			'shortcode_content' => isset( $raw['shortcode_content'] ) ? sanitize_text_field( wp_unslash( $raw['shortcode_content'] ) ) : '',
			'status'            => isset( $raw['status'] ) ? sanitize_key( $raw['status'] ) : 'active',
		);
	}

	/**
	 * Retrieve all saved settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * AJAX: Return registered callbacks for a specific hook.
	 * Used to populate the Callback select when editing rules.
	 */
	public function ajax_get_hook_callbacks() {
		check_ajax_referer( 'whm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification
		$hook = isset( $_POST['hook_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hook_name'] ) ) : '';
		// phpcs:enable

		if ( empty( $hook ) ) {
			wp_send_json_error( array( 'message' => 'No hook name provided.' ) );
		}

		global $wp_filter;

		$callbacks = array();

		if ( isset( $wp_filter[ $hook ] ) ) {
			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $items ) {
				foreach ( $items as $item ) {
					$cb   = $item['function'];
					$name = '';

					if ( is_string( $cb ) ) {
						$name = $cb;
					} elseif ( is_array( $cb ) && count( $cb ) === 2 ) {
						$obj  = $cb[0];
						$meth = $cb[1];
						$name = ( is_object( $obj ) ? get_class( $obj ) : (string) $obj ) . '::' . $meth;
					} elseif ( $cb instanceof Closure ) {
						$name = '{closure}';
					}

					// Try to resolve source file.
					$source = '';
					try {
						if ( is_string( $cb ) && function_exists( $cb ) ) {
							$ref    = new ReflectionFunction( $cb );
							$source = basename( $ref->getFileName() );
						} elseif ( is_array( $cb ) ) {
							$ref    = new ReflectionMethod( $cb[0], $cb[1] );
							$source = basename( $ref->getFileName() );
						}
					} catch ( Exception $e ) {
						// Ignore reflection errors.
					}

					$callbacks[] = array(
						'callback' => $name,
						'priority' => (int) $priority,
						'source'   => $source,
					);
				}
			}
		}

		// Sort by priority.
		usort( $callbacks, function( $a, $b ) {
			return $a['priority'] - $b['priority'];
		} );

		wp_send_json_success( array( 'callbacks' => $callbacks ) );
	}
}
