<?php
/**
 * Loads event pipes for core and third party hooks.
 *
 * @package Stalkfish
 */

namespace Stalkfish;

/**
 * Class Pipes
 *
 * @package Stalkfish
 */
class Pipes {
	/**
	 * Registered pipes.
	 *
	 * @var array
	 */
	public $pipes = array();

	/**
	 * Available pipe context and actions.
	 *
	 * @var array
	 */
	public $triggers = array();

	/**
	 * Pipes Constructor
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * Registers event pipes.
	 */
	public function register() {
		$pipes = array(
			/**
			 * WP Events
			 */
			'Users',
			'Posts',
			'Installer',
			'Settings',
			'Editor',
			'Media',
			'Menus',
			'Widgets',
			'Taxonomies',
			'Comments',
			// 'Multisite', // Multisite log needs to be tested and improved further.
		);

		$instances = array();
		foreach ( $pipes as $pipe ) {
			$pipe_class = sprintf( '\Stalkfish\Pipes\%s_Pipe', $pipe );

			if ( ! class_exists( $pipe_class ) ) {
				continue;
			}

			// Register a new instance.
			$instance = new $pipe_class();

			if ( ! is_subclass_of( $instance, 'Stalkfish\Pipes\Pipe' ) ) {
				continue;
			}

			/**
			 * Conditionally attach multisite triggers.
			 */
			if ( 'multisite' !== $instance->name ) {
				$this->triggers[ $instance->name ] = $instance->triggers;
			}

			if ( is_multisite() && 'multisite' === $instance->name ) {
				$this->triggers[ $instance->name ] = $instance->triggers;
			}

			// Whether admin event pipes are allowed to be recorded.
			if ( is_admin() && ! $instance->register_admin ) {
				continue;
			}

			// Whether frontend event pipes are allowed to be recorded.
			if ( ! is_admin() && ! $instance->register_frontend ) {
				continue;
			}

			if ( $instance->is_dependency_satisfied() ) {
				$instances[ $instance->name ] = $instance;
			}
		}

		/**
		 * Attach additional event pipes via classes.
		 */
		$this->pipes = apply_filters( 'stalkfish_pipes', $instances );

		if ( empty( $this->pipes ) ) {
			return;
		}

		foreach ( $this->pipes as $pipe ) {
			$pipe->register();
		}

	}
}
