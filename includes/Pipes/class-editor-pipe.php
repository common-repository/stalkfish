<?php
/**
 * Class to record editor events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Editor_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Editor_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'editor';

	/**
	 * Available hooks for current pipe.
	 *
	 * @var array
	 */
	public $hooks = array(
		'admin_init',
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
		),
		'actions' => array(
			'modified'
		)
	);

	/**
	 * Register pipe in the frontend
	 *
	 * @var bool
	 */
	public $register_frontend = false;

	/**
	 * Get theme/plugin data wrapper.
	 *
	 * @param string $dir Theme/Plugin directory name.
	 * @param string $type Type for which data to fetch for.
	 *
	 * @return array|void Theme/Plugin data.
	 */
	public function get_theme_plugin_data( $dir, $type = 'plugin' ) {
		if ( 'plugin' !== $type ) {
			$theme = wp_get_theme( $dir );

			return $theme->exists() ? $theme : array();
		}

		if ( ! function_exists( '\get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugin_data( $dir );
	}

	/**
	 * Record file modifications via theme/plugin editor.
	 *
	 * @return void
	 */
	public function callback_admin_init() {
		// @codingStandardsIgnoreStart
		$nonce   = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;
		$file    = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : false;
		$action  = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : false;
		$referer = isset( $_POST['_wp_http_referer'] ) ? sanitize_text_field( wp_unslash( $_POST['_wp_http_referer'] ) ) : false;
		$referer = remove_query_arg( array( 'file', 'theme', 'plugin' ), $referer );
		$referer = basename( $referer, '.php' );
		// @codingStandardsIgnoreEnd

		if ( 'edit-theme-plugin-file' === $action ) {
			if ( 'plugin-editor' === $referer && wp_verify_nonce( $nonce, 'edit-plugin_' . $file ) ) {
				$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : false;

				$data = $this->get_theme_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				$args = array(
					'file'    => $file,
					'name'    => $data['Name'],
					'version' => $data['Version'],
					'network' => $data['Network'],
					'slug'    => $plugin,
				);

				$link = get_admin_url() . 'plugin-editor.php?file=' . $file . '&plugin=' . $plugin;

				/* translators: %1$s: File Name, %2$s: Plugin name */
				$message = __( 'Modified the file "%1$s" in plugin %2$s', 'stalkfish' );

				$this->log(
					array(
						'message' => vsprintf( $message, $args ),
						'meta'    => $args,
						'context' => 'plugins',
						'action'  => 'modified',
						'link'    => $link,
					)
				);
			} elseif ( 'theme-editor' === $referer ) {
				$theme = isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : false;

				if ( ! wp_verify_nonce( $nonce, 'edit-theme_' . $theme . '_' . $file ) ) {
					return;
				}
				$data = $this->get_theme_plugin_data( $theme, 'theme' );
				$args = array(
					'file'    => $file,
					'name'    => $data['Name'],
					'version' => $data['Version'],
					'slug'    => $theme,
				);

				$link = get_admin_url() . 'theme-editor.php?file=' . $file . '&theme=' . $theme;

				/* translators: %1$s: File name, %2$s: Theme name */
				$message = __( 'Modified the file "%1$s" in theme %2$s', 'stalkfish' );

				$this->log(
					array(
						'message' => vsprintf( $message, $args ),
						'meta'    => $args,
						'context' => 'themes',
						'action'  => 'modified',
						'link'    => $link,
					)
				);
			}
		}
	}
}
