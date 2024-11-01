<?php
/**
 * Abstract class for extending Event Pipes.
 *
 * @package Stalkfish
 */

namespace Stalkfish\Pipes;

/**
 * Class Pipe
 *
 * @package Stalkfish\Pipes
 */
abstract class Pipe {
	/**
	 * Pipe name.
	 *
	 * @var string
	 */
	public $name = null;

	/**
	 * Available hooks for current pipe.
	 *
	 * @var array
	 */
	public $hooks = array();

	/**
	 * Available contexts and their actions for current pipe.
	 *
	 * @var array
	 */
	public $triggers = array();

	/**
	 * Whether pipe registered in Admin?
	 *
	 * @var bool
	 */
	public $register_admin = true;

	/**
	 * Whether pipe registered in Frontend?
	 *
	 * @var bool
	 */
	public $register_frontend = true;

	/**
	 * Pipe status.
	 *
	 * @var bool
	 */
	public $is_registered = false;

	/**
	 * Previous pipe entry in the same request.
	 *
	 * @var int
	 */
	public $prev = null;

	/**
	 * Checks whether the pipe is currently registered.
	 *
	 * @return bool
	 */
	public function is_registered() {
		return $this->is_registered;
	}

	/**
	 * Registers pipe context.
	 */
	public function register() {
		if ( $this->is_registered() ) {
			return;
		}

		foreach ( $this->hooks as $hook ) {
			add_action( $hook, array( $this, 'callback' ), 10, 99 );
		}

		$this->is_registered = true;
	}

	/**
	 * Deregister event hooks.
	 */
	public function deregister() {
		if ( ! $this->is_registered() ) {
			return;
		}

		foreach ( $this->hooks as $hook ) {
			remove_action( $hook, array( $this, 'callback' ), 10, 99 );
		}

		$this->is_registered = false;
	}

	/**
	 * Looks for a class method with the convention: "callback_{action name}"
	 */
	public function callback() {
		$action   = current_filter();
		$callback = array( $this, 'callback_' . preg_replace( '/[^a-z0-9_\-]/', '_', $action ) );

		// Call the real function.
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, func_get_args() );
		}
	}

	/**
	 * Log handler
	 *
	 * @param array $body      Pipe data.
	 *
	 * @return bool
	 */
	public function log( $body ) {
		$pipe = $this->name;

		$data = apply_filters(
			'stalkfish_log_data',
			compact( 'pipe', 'body' )
		);

		if ( ! $data ) {
			return false;
		} else {
			$pipe = $data['pipe'];
			$body = $data['body'];
		}

		return call_user_func_array( array( stalkfish_get_instance()->logpipe, 'pipe' ), compact( 'pipe', 'body' ) );
	}

	/**
	 * Compare two values and return changed keys if they are arrays
	 *
	 * @param  mixed    $old_value Value before change.
	 * @param  mixed    $new_value Value after change.
	 * @param  bool|int $deep      Get array children changes keys as well, not just parents.
	 *
	 * @return array
	 */
	public function get_changed_keys( $old_value, $new_value, $deep = false ) {
		if ( ! is_array( $old_value ) && ! is_array( $new_value ) ) {
			return array();
		}

		if ( ! is_array( $old_value ) ) {
			return array_keys( $new_value );
		}

		if ( ! is_array( $new_value ) ) {
			return array_keys( $old_value );
		}

		$diff = array_udiff_assoc(
			$old_value,
			$new_value,
			function( $value1, $value2 ) {
				// Compare potentially complex nested arrays.
				return wp_json_encode( $value1 ) !== wp_json_encode( $value2 );
			}
		);

		$result = array_keys( $diff );

		// Find unexisting keys in old or new value.
		$common_keys     = array_keys( array_intersect_key( $old_value, $new_value ) );
		$unique_keys_old = array_values( array_diff( array_keys( $old_value ), $common_keys ) );
		$unique_keys_new = array_values( array_diff( array_keys( $new_value ), $common_keys ) );

		$result = array_merge( $result, $unique_keys_old, $unique_keys_new );

		// Remove numeric indexes.
		$result = array_filter(
			$result,
			function( $value ) {
				// @codingStandardsIgnoreStart
				// check if is not valid number (is_int, is_numeric and ctype_digit are not enough)
				return (string) (int) $value !== (string) $value;
				// @codingStandardsIgnoreEnd
			}
		);

		$result = array_values( array_unique( $result ) );

		if ( false === $deep ) {
			return $result; // Return an numerical based array with changed TOP PARENT keys only.
		}

		$result = array_fill_keys( $result, null );

		foreach ( $result as $key => $val ) {
			if ( in_array( $key, $unique_keys_old, true ) ) {
				$result[ $key ] = false; // Removed.
			} elseif ( in_array( $key, $unique_keys_new, true ) ) {
				$result[ $key ] = true; // Added.
			} elseif ( $deep ) { // Changed, find what changed, only if we're allowed to explore a new level.
				if ( is_array( $old_value[ $key ] ) && is_array( $new_value[ $key ] ) ) {
					$inner  = array();
					$parent = $key;
					$deep--;
					$changed = $this->get_changed_keys( $old_value[ $key ], $new_value[ $key ], $deep );
					foreach ( $changed as $child => $change ) {
						$inner[ $parent . '::' . $child ] = $change;
					}
					$result[ $key ] = 0; // Changed parent which has a changed children.
					$result         = array_merge( $result, $inner );
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if the pipe dependencies are satisfied or not
	 *
	 * @return bool
	 */
	public function is_dependency_satisfied() {
		return true;
	}

}
