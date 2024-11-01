<?php
/**
 * Class to record installer events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Installer_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Installer_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'installer';

	/**
	 * List of Available Plugins.
	 *
	 * @var array
	 */
	protected $ex_plugins = array();

	/**
	 * List of Available Themes.
	 *
	 * @since 1.0.6
	 * @var array
	 */
	protected $ex_themes = array();

	/**
	 * Available hooks.
	 *
	 * @var array
	 */
	public $hooks = array(
		/**
		 *  List of plugins.
		 */
		'admin_init',
		/**
		 * Plugins & Theme installation.
		 */
		'upgrader_process_complete',
		/**
		 * Plugins & theme activation/deactivation.
		 */
		'activate_plugin',
		'deactivate_plugin',
		'switch_theme',
		/**
		 * Plugins & theme deletion.
		 */
		'delete_site_transient_update_themes',
		'admin_init',
		'shutdown',
		/**
		 * Core update.
		 */
		'_core_updated_successfully',
	);

	/**
	 * Available contexts and their actions for current pipe.
	 *
	 * @var array
	 */
	public $triggers = array(
		'contexts' => array(
			'themes',
			'plugins',
			'core',
		),
		'actions'  => array(
			'installed',
			'updated',
			'deleted',
			'activated',
			'deactivated',
			'failed',
			'downgraded',
		),
	);

	/**
	 * Register pipe in the WP Frontend
	 *
	 * @var bool
	 */
	public $register_frontend = false;

	/**
	 * Wrapper method for calling get_plugins()
	 *
	 * @return array
	 */
	public function get_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugins();
	}

	/**
	 * Wrapper method for getting themes data.
	 *
	 * @since 1.0.6
	 * @return array
	 */
	public function get_themes() {
		$_themes = array();
		$themes  = wp_get_themes();

		// The data is dynamic if straight through wp_get_themes()
		// but stays static if accessed through wp_get_theme().
		foreach ( $themes as $theme_slug => $theme_data ) {
			$_themes[ $theme_slug ] = wp_get_theme( $theme_slug );
		}

		return $_themes;
	}

	/**
	 * Captures available plugins/themes when admin page is loaded.
	 *
	 * @since 1.0.6
	 * @return void
	 */
	public function callback_admin_init() {
		$this->ex_plugins = $this->get_plugins();
		$this->ex_themes  = $this->get_themes();
	}

	/**
	 * Record plugin installations.
	 *
	 * @param \WP_Upgrader $upgrader Upgrader object.
	 * @param array        $extra Extra plugin data.
	 *
	 * @return bool
	 */
	public function callback_upgrader_process_complete( $upgrader, $extra ) {
		$logs = array();

		// This would have failed down the road anyway.
		if ( ! isset( $extra['type'] ) ) {
			return false;
		}

		$type   = $extra['type'];
		$action = $extra['action'];

		if ( ! in_array( $type, array( 'plugin', 'theme' ), true ) ) {
			return false;
		}

		if ( 'install' === $action ) {
			if ( 'plugin' === $type ) {
				$path = $upgrader->plugin_info();

				if ( ! $path ) {
					return false;
				}

				$data    = get_plugin_data( $upgrader->skin->result['local_destination'] . '/' . $path );
				$slug    = $upgrader->result['destination_name'];
				$name    = $data['Name'];
				$version = $data['Version'];
			} else { // theme.
				$theme_info = $upgrader->theme_info();

				if ( ! $theme_info && ! $theme_info instanceof \WP_Theme ) {
					return false;
				}

				wp_clean_themes_cache();

				$slug    = $theme_info->get_stylesheet();
				$theme   = wp_get_theme( $slug );
				$name    = $theme->name;
				$version = $theme->version;
			}

			$action = 'installed';
			/* translators: %1$s: Plugin/theme type, %2$s: Plugin/theme name, %3$s: Plugin/theme version */
			$message = _x(
				'Installed %1$s %2$s v%3$s',
				'Plugin/theme installation. 1: Type (plugin/theme), 2: Plugin/theme name, 3: Plugin/theme version',
				'stalkfish'
			);

			$logs[] = compact( 'slug', 'name', 'version', 'message', 'action' );
		} elseif ( 'update' === $action ) {
			$action = 'updated';
			/* translators: %1$s: Plugin/Theme type, %2$s: Plugin/Theme name, %3$s: Plugin/Theme version */
			$message = _x(
				'Updated %1$s %2$s to v%3$s',
				'Plugin/theme update. 1: Type (plugin/theme), 2: Plugin/theme name, 3: Plugin/theme version',
				'stalkfish'
			);

			if ( 'plugin' === $type ) {
				if ( isset( $extra['bulk'] ) && true === $extra['bulk'] ) {
					$slugs = $extra['plugins'];
				} else {
					$slugs = array( $upgrader->skin->plugin );
				}

				$_plugins = $this->ex_plugins;

				foreach ( $slugs as $slug ) {
					$plugin_data  = get_plugin_data( WP_PLUGIN_DIR . '/' . $slug );
					$name         = $plugin_data['Name'];
					$version      = $plugin_data['Version'];
					$prev_version = $_plugins[ $slug ]['Version'];

					$logs[] = compact( 'slug', 'name', 'prev_version', 'version', 'message', 'action' );
				}
			} else { // theme.
				if ( isset( $extra['bulk'] ) && true === $extra['bulk'] ) {
					$slugs = $extra['themes'];
				} else {
					$slugs = array( $upgrader->skin->theme );
				}

				$_themes = $this->ex_themes;

				foreach ( $slugs as $slug ) {
					$theme        = $_themes[ $slug ];
					$stylesheet   = $theme['Stylesheet Dir'] . '/style.css';
					$theme_data   = get_file_data(
						$stylesheet,
						array(
							'Version' => 'Version',
						)
					);
					$name         = $theme['Name'];
					$prev_version = $theme['Version'] ?? null;
					$version      = $theme_data['Version'];

					$logs[] = compact( 'slug', 'name', 'prev_version', 'version', 'message', 'action' );
				}
			}
		} else {
			return false;
		}

		foreach ( $logs as $log ) {
			$name         = $log['name'] ?? null;
			$version      = $log['version'] ?? null;
			$slug         = $log['slug'] ?? null;
			$prev_version = $log['prev_version'] ?? null;
			$message      = $log['message'] ?? null;
			$action       = $log['action'] ?? null;

			if ( version_compare( $version, $prev_version, '==' ) ) {
				$action = 'failed';
				/* translators: %1$s: Plugin/Theme type, %2$s: Plugin/Theme name, %3$s: Plugin/Theme version */
				$message = _x(
					'Update failed for %1$s %2$s',
					'Plugin/theme update. 1: Type (plugin/theme), 2: Plugin/theme name',
					'stalkfish'
				);
			} elseif ( version_compare( $version, $prev_version, '<' ) ) {
				$action = 'downgraded';
				/* translators: %1$s: Plugin/Theme type, %2$s: Plugin/Theme name, %3$s: Plugin/Theme version */
				$message = _x(
					'Downgraded %1$s %2$s to v%3$s',
					'Plugin/theme update. 1: Type (plugin/theme), 2: Plugin/theme name, 3: Plugin/theme version',
					'stalkfish'
				);
			}

			$args = compact( 'type', 'name', 'version', 'slug', 'prev_version' );
			$link = get_admin_url() . $type . 's.php';

			$body = array(
				'message' => vsprintf( $message, $args ),
				'meta'    => $args,
				'context' => $type . 's',
				'action'  => $action,
				'link'    => $link,
			);
			$this->log(
				$body
			);
		}

		return true;
	}

	/**
	 * Record plugin activations.
	 *
	 * @param string $slug Plugin slug name.
	 * @param bool   $network_wide Whether Activated across the multi-site.
	 */
	public function callback_activate_plugin( $slug, $network_wide ) {
		$_plugins     = $this->get_plugins();
		$name         = $_plugins[ $slug ]['Name'];
		$version      = $_plugins[ $slug ]['Version'];
		$network_wide = $network_wide ? esc_html__( 'network wide', 'stalkfish' ) : null;

		$args = compact( 'name', 'network_wide', 'version' );
		$link = get_admin_url() . 'plugins.php';

		$body = array(
			'message' => vsprintf(
				/* translators: %1$s: Plugin name, %2$s: Network Wide */
				_x(
					'Activated plugin %1$s %2$s',
					'1: Plugin name, 2: Single site or network wide',
					'stalkfish'
				),
				$args
			),
			'meta'    => $args,
			'context' => 'plugins',
			'action'  => 'activated',
			'link'    => $link,
		);
		$this->log(
			$body
		);
	}

	/**
	 * Record plugin deactivations.
	 *
	 * @param string $slug Plugin slug name.
	 * @param bool   $network_wide Whether deactivated across the multi-site.
	 */
	public function callback_deactivate_plugin( $slug, $network_wide ) {
		$_plugins     = $this->get_plugins();
		$name         = $_plugins[ $slug ]['Name'];
		$version      = $_plugins[ $slug ]['Version'];
		$network_wide = $network_wide ? esc_html__( 'network wide', 'stalkfish' ) : null;
		$link         = get_admin_url() . 'plugins.php';

		$args = compact( 'name', 'network_wide', 'version', 'slug' );

		$body = array(
			'message' => vsprintf(
				/* translators: %1$s: Plugin name, %2$s: Network wide */
				_x(
					'Deactivated plugin %1$s %2$s',
					'1: Plugin name, 2: Single site or network wide',
					'stalkfish'
				),
				$args
			),
			'meta'    => $args,
			'context' => 'plugins',
			'action'  => 'deactivated',
			'link'    => $link,
		);
		$this->log(
			$body
		);
	}

	/**
	 * Record theme activations.
	 *
	 * @param string $name Theme name.
	 * @param string $theme Unused.
	 */
	public function callback_switch_theme( $name, $theme ) {
		unset( $theme );
		$args = compact( 'name' );
		$link = get_admin_url() . 'plugins.php';

		$body = array(
			'message' => vsprintf(
				/* translators: %1$s: Theme name */
				_x(
					'Activated theme %s',
					'1: Theme name',
					'stalkfish'
				),
				$args
			),
			'meta'    => $args,
			'context' => 'themes',
			'action'  => 'activated',
			'link'    => $link,
		);

		$this->log(
			$body
		);
	}

	/**
	 * Record theme deletion.
	 */
	public function callback_delete_site_transient_update_themes() {
		/**
		 * This is used as a hack to determine a theme was deleted.
		 */
		$backtrace         = debug_backtrace(); // @codingStandardsIgnoreLine
		$delete_theme_call = null;

		foreach ( $backtrace as $call ) {
			if ( isset( $call['function'] ) && 'delete_theme' === $call['function'] ) {
				$delete_theme_call = $call;
				break;
			}
		}

		if ( empty( $delete_theme_call ) ) {
			return;
		}

		$name = $delete_theme_call['args'][0];
		$args = compact( 'name' );
		$link = get_admin_url() . 'themes.php';
		$body = array(
			'message' => vsprintf(
				/* translators: %1$s: Theme name */
				_x(
					'Deleted theme %s',
					'1: Theme name',
					'stalkfish'
				),
				$args
			),
			'meta'    => $args,
			'context' => 'themes',
			'action'  => 'deleted',
			'link'    => $link,
		);

		$this->log(
			$body
		);
	}

	/**
	 * Record plugins uninstallations.
	 */
	public function callback_shutdown() {
		$has_permission = ( current_user_can( 'install_plugins' ) || current_user_can( 'activate_plugins' ) ||
							current_user_can( 'delete_plugins' ) || current_user_can( 'update_plugins' ) || current_user_can( 'install_themes' ) );
		if ( ! $has_permission ) {
			return;
		}
		// Filter global arrays for security.
		$post_array  = filter_input_array( INPUT_POST );
		$get_array   = filter_input_array( INPUT_GET );
		$script_name = isset( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : false;

		$action = '';
		if ( isset( $get_array['action'] ) && '-1' !== $get_array['action'] ) {
			$action = $get_array['action'];
		} elseif ( isset( $post_array['action'] ) && '-1' !== $post_array['action'] ) {
			$action = $post_array['action'];
		}

		if ( isset( $get_array['action2'] ) && '-1' !== $get_array['action2'] ) {
			$action = $get_array['action2'];
		} elseif ( isset( $post_array['action2'] ) && '-1' !== $post_array['action2'] ) {
			$action = $post_array['action2'];
		}

		$actype = '';
		if ( ! empty( $script_name ) ) {
			$actype = basename( $script_name, '.php' );
		}

		$is_plugins = 'plugins' === $actype;
		$link       = get_admin_url() . 'plugins.php';

		// Uninstall plugin.
		if ( in_array( $action, array( 'delete-plugin' ), true ) ) {
			if ( isset( $post_array['plugin'] ) ) {
				$plugin_file = WP_PLUGIN_DIR . '/' . $post_array['plugin'];
				$name        = basename( $plugin_file, '.php' );
				$name        = str_replace( array( '_', '-', '  ' ), ' ', $name );
				$name        = ucwords( $name );
				$plugin_data = $this->ex_plugins[ $post_array['plugin'] ];
				$version     = $plugin_data['Version'];
				$slug        = $post_array['plugin'];
				$args        = compact( 'name', 'version', 'slug' );

				$body = array(
					'message' => vsprintf(
						/* translators: %1$s: Plugin name */
						_x(
							'Deleted plugin %s',
							'1: Plugin name',
							'stalkfish'
						),
						$args
					),
					'meta'    => $args,
					'context' => 'plugins',
					'action'  => 'deleted',
					'link'    => $link,
				);

				$this->log(
					$body
				);
			}
		}

		// Uninstall multiple plugins
		// TODO: Multiple delete action has verification issues for active plugins which will not get deleted.
		if ( $is_plugins && in_array( $action, array( 'delete-selected' ), true ) ) {
			foreach ( $post_array['checked'] as $plugin_file ) {
				$name        = basename( $plugin_file, '.php' );
				$name        = str_replace( array( '_', '-', '  ' ), ' ', $name );
				$name        = ucwords( $name );
				$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_file;
				$plugin_data = get_plugin_data( $plugin_file, false, true );
				$version     = $plugin_data['Version'];
				$slug        = $plugin_file;

				$args = compact( 'name', 'version', 'slug' );

				$body = array(
					'message' => vsprintf(
						/* translators: %1$s: Plugin name */
						_x(
							'Deleted plugin %s',
							'1: Plugin name',
							'stalkfish'
						),
						$args
					),
					'meta'    => $args,
					'context' => 'plugins',
					'action'  => 'deleted',
					'link'    => $link,
				);

				$this->log(
					$body
				);
			}
		}

		return false;
	}

	/**
	 * Record WordPress core upgrades
	 *
	 * @param string $new_version Version WordPress core has be upgraded to.
	 *
	 * @return void
	 */
	public function callback__core_updated_successfully( $new_version ) {
		global $pagenow, $wp_version;

		$prev_version = $wp_version;
		$auto_updated = ( 'update-core.php' !== $pagenow );

		if ( $auto_updated ) {
			/* translators: %s: WP Version number */
			$message = esc_html__( 'WordPress auto-updated to %s', 'stalkfish' );
		} else {
			/* translators: %s: WP Version number */
			$message = esc_html__( 'WordPress updated to %s', 'stalkfish' );
		}

		$args = compact( 'new_version', 'prev_version', 'auto_updated' );

		$body = array(
			'message' => vsprintf( $message, $args ),
			'meta'    => $args,
			'context' => 'core',
			'action'  => 'updated',
		);

		$this->log(
			$body
		);
	}

}
