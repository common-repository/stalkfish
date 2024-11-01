<?php
/**
 * Sends logged data to remote server.
 *
 * @package Stalkfish
 */

namespace Stalkfish;

use Stalkfish\Options;

/**
 * Class Logpipe.
 *
 * @package Stalkfish
 */
class Logpipe {

	/**
	 * Current visitor IP address
	 *
	 * @var string
	 */
	private $ip_address;

	/**
	 * Logpipe constructor
	 */
	public function __construct() {

		$this->ip_address = stalkfish_get_current_ip();

		add_action( 'stalkfish_enqueue_request', array( $this, 'enqueue_request' ), 10, 1 );
	}

	/**
	 * Gets current event pipe runtime data.
	 *
	 * @return array Runtime data
	 */
	public function get_runtime_data() {
		return array(
			'runtime' => array(
				'wp_version'     => get_bloginfo( 'version' ),
				'php_version'    => (float) phpversion(),
				'plugin_version' => STALKFISH_VERSION,
				'environment'    => function_exists( '\wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			),
		);
	}

	/**
	 * Gets meta for the user triggering the event.
	 *
	 * @param array $event_body Event data.
	 *
	 * @return array|string
	 */
	public function get_nexus_user_meta( $event_body ) {
		// Get the current user id.
		$user_id = $event_body['user'] ?? wp_get_current_user()->ID;

		// If user switching plugin class exists and filter is set to disable then try get the old user.
		if ( apply_filters( 'stalkfish_disable_user_switching_plugin_tracking', false ) && class_exists( 'user_switching' ) ) {
			$old_user = user_switching::get_old_user();
			if ( isset( $old_user->ID ) ) {
				// looks like this is a switched user so setup original user
				// values for use when logging.
				$user_id = $old_user->ID;
			}
		}

		$user     = get_user_by( 'ID', $user_id );
		$username = $user->user_login ?? '';

		// Get current user roles.
		if ( isset( $old_user ) && false !== $old_user ) {
			// Switched user so setup original user roles and values for later user.
			$role = reset( $old_user->roles );
			if ( function_exists( 'is_super_admin' ) && is_super_admin() ) {
				$role = 'superadmin';
			}
		} else {
			// Not a switched user so get the current user roles.
			$role = $user instanceof \WP_User ? stalkfish_get_current_user_roles( $user->roles ) : '';
		}

		if ( $user instanceof \WP_User ) {
			return array(
				'initiator' => 'user',
				'user'      => array(
					'id'         => $user->ID,
					'username'   => $username,
					'role'       => $role,
					'avatar_url' => get_avatar_url( $user->ID ),
				),
			);
		} elseif ( 'Plugin' === $username ) {
			return array( 'initiator' => 'plugin' );

		} elseif ( 'Plugins' === $username ) {
			return array( 'initiator' => 'plugins' );

		} elseif ( 'Website Visitor' === $username || 'Unregistered user' === $username ) {
			return array( 'initiator' => 'unregistered-user' );
		} else {
			return array( 'initiator' => 'system' );
		}
	}

	/**
	 * Checks whether current event is ignored.
	 *
	 * @param array  $data Event data in question.
	 *
	 * @return bool
	 */
	public function is_event_ignored( $data ) {
		$ignore_event = false;
		$event = array(
			'pipe'      => $data['pipe'],
			'context'   => $data['context'],
			'action'    => $data['action'],
		);

		if ( isset( $data['initiator'] ) && 'user' === $data['initiator'] ) {
			$event['author'] = $data['user']['username'];
			$event['role']   = $data['user']['role'];
		}

		// Load exclude rules from settings.
		$excludes = Options::get_instance()->get( 'sf_exclude_rules' );

		foreach ( $this->filter_exclude_rules_by_rows( $excludes ) as $exclude_event ) {
			$exclude = array(
				'pipe'      => ! empty( $exclude_event['pipe'] ) ? $exclude_event['pipe'] : null,
				'context'   => ! empty( $exclude_event['context'] ) ? $exclude_event['context'] : null,
				'action'    => ! empty( $exclude_event['action'] ) ? $exclude_event['action'] : null,
				'author'    => is_numeric( $exclude_event['author_or_role'] ) ? absint( $exclude_event['author_or_role'] ) : null,
				'role'      => ( ! empty( $exclude_event['author_or_role'] ) && ! is_numeric( $exclude_event['author_or_role'] ) ) ? $exclude_event['author_or_role'] : null,
			);

			$exclude_triggers = array_filter( $exclude, 'strlen' );

			if ( $this->event_matches_keys( $event, $exclude_triggers ) ) {
				$ignore_event = true;
				break;
			}
		}

		return $ignore_event;
	}

	/**
	 * Filter exclude rules by row ids, since currently we have to store them like this at exclude settings.
	 *
	 * @param array $rules List of rules indexed by rule ID.
	 *
	 * @return array
	 */
	public function filter_exclude_rules_by_rows( $rules ) {
		$excludes = array();

		$rule_keys = array(
			'rows',
			'pipe',
			'context',
			'action',
			'author_or_role'
		);

		if ( empty( $rules['rows'] ) ) {
			return array();
		}

		foreach ( array_keys( $rules['rows'] ) as $row_id ) {
			$excludes[ $row_id ] = array();

			foreach ( $rule_keys as $rule_key ) {
				if ( isset( $rules[ $rule_key ][ $row_id ] ) ) {
					$excludes[ $row_id ][ $rule_key ] = $rules[ $rule_key ][ $row_id ];
				} else {
					$excludes[ $row_id ][ $rule_key ] = null;
				}
			}
		}

		return $excludes;
	}

	/**
	 * Checks for an event for met excluded triggers.
	 *
	 * @param array $event Event data.
	 * @param array $exclude_triggers Event exclude triggers.
	 *
	 * @return boolean
	 */
	public function event_matches_keys( $event, $exclude_triggers ) {
		$matches_needed = count( $exclude_triggers );
		$matches_found  = 0;

		foreach ( $exclude_triggers as $exclude_key => $exclude_value ) {
			if ( ! isset( $event[ $exclude_key ] ) || is_null( $exclude_value ) ) {
				continue;
			}

			if ( $event[ $exclude_key ] === $exclude_value ) {
				$matches_found++;
			}
		}

		return $matches_found === $matches_needed;
	}

	/**
	 * Filters through the event data for empty/null values
	 * and adds a fallback if any.
	 *
	 * @param array $data Data to be filtered.
	 *
	 * @return array Filtered data.
	 */
	public function event_fallback_filters( $data ) {
		// Text to be added as wildcard.
		$fallback_text = 'unknown';

		$required_keys = array(
			'pipe',
			'context',
			'action',
			'message',
			'meta',
		);

		// Loops through the required keys through the event data and adds fallback for empty values or unset keys.
		foreach ( $required_keys as $key ) {
			if ( 'meta' === $key && ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) ) {
				$chars_count = $this->count_array_chars( $data[ $key ] );

				if ( $chars_count > 4000 ) {
					$data[ $key ] = array(
						'message' => __( 'Values omitted as it exceeded more than 4000 characters', 'stalkfish' ),
					);

					continue;
				}
			}

			// Move on to the next key if all is well.
			if ( isset( $data[ $key ] ) && ( ! empty( $data[ $key ] ) || is_array( $data[ $key ] ) ) ) {
				continue;
			}

			// Sets a fallback text when a key has empty value.
			if ( isset( $data[ $key ] ) && ( ! is_array( $data[ $key ] && '' === $data[ $key ] ) ) ) {
				$data[ $key ] = $fallback_text;
				continue;
			}

			// Sets up the key if it isn't set in the data already.
			if ( ! isset( $data[ $key ] ) ) {
				if ( 'meta' === $key ) {
					$data[ $key ] = array();
					continue;
				}

				$data[ $key ] = $fallback_text;
			}
		}

		// Returns the filtered data.
		return $data;
	}

	/**
	 * Counts characters in an array including keys.
	 *
	 * @param array $array
	 *
	 * @return int Count of characters.
	 */
	public function count_array_chars(array $array)
	{
		$char_count = 0;
		array_walk_recursive($array, function($val, $key) use (&$char_count)
		{
			$char_count += strlen($val) + strlen($key);
		});
		return $char_count;
	}

	/**
	 * Sends data to remote.
	 *
	 * @param string $pipe Pipe triggering the record.
	 * @param array  $body Data to be recorded.
	 * @param bool   $immediate Force data to be sent immediately.
	 *
	 * @return bool Response code
	 */
	public function pipe( $pipe, $body, $immediate = false ) {
		$data = array_merge(
			array(
				'pipe'       => (string) $pipe,
				'ip_address' => (string) $this->ip_address,
				'date'       => (string) current_time( 'mysql', true ),
			),
			$body,
			$this->get_nexus_user_meta( $body ),
			$this->get_runtime_data()
		);

		// Checks for null/empty args and adds fallback
		$data = $this->event_fallback_filters( $data );

		// Ignores if current event is in the list of excluded events.
		if ( $this->is_event_ignored( $data ) ) {
			return false;
		}

		$request_type = (string) Options::get_instance()->get('sf_request_type' );

		if ( 'immediate' === $request_type || $immediate ) {
			return $this->enqueue_request( $data );
		}
		return as_enqueue_async_action( 'stalkfish_enqueue_request', array( 'data' => $data ) );
	}

	/**
	 * To enqueue into async post.
	 *
	 * @param array $data Request data.
	 *
	 * @throws \Exception
	 */
	public function enqueue_request( $data ) {
		$response = wp_safe_remote_post(
			STALKFISH_APP_URL . '/api/events',
			array(
				'method'      => 'POST',
				'headers'     => array(
					'Authorization' => 'Bearer ' . Options::get_instance()->get( 'sf_app_api_key' ),
				),
				'body'        => $data,
				'httpversion' => '1.1',
				'sslverify'   => false
			)
		);

		$response_code = wp_remote_retrieve_response_code( $response );

		$allowed_codes = array(
			426,
			500
		);

		if ( empty( $response_code ) || in_array( (int) $response_code, $allowed_codes ) ) {
			as_schedule_single_action( time() + 60, 'stalkfish_enqueue_request', array( 'data' => $data ) );
		}

		return $response_code;
	}
}
