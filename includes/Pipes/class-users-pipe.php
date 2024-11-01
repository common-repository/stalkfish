<?php
/**
 * Class to record user events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Users_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Users_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'users';

	/**
	 * Stores user object prior to delete.
	 *
	 * @var WP_User
	 */
	protected $user_pre_deleted = array();

	/**
	 * Stores user meta data being updated.
	 *
	 * @var array
	 */
	protected $prev_user_meta = array();

	/**
	 * Stores current user object.
	 *
	 * @var WP_User
	 */
	protected $current_user = null;

	/**
	 * Available hooks.
	 *
	 * @var array
	 */
	public $hooks = array(
		/**
		 * Register or add user data.
		 */
		'user_register',
		/**
		 * Update user data.
		 */
		'update_user_meta',
		'updated_user_meta',
		'profile_update',
		'retrieve_password',
		/**
		 * Login and logout.
		 */
		'set_auth_cookie',
		'clear_auth_cookie',
		'wp_logout',
		/**
		 * Delete user.
		 */
		'delete_user',
		'deleted_user',
		/**
		 * Set user role.
		 */
		'set_user_role',
	);

	/**
	 * Available contexts and their actions for current pipe.
	 *
	 * @var array
	 */
	public $triggers = array(
		'contexts' => array(
			'users',
			'profiles',
			'sessions',
		),
		'actions' => array(
			'created',
			'updated',
			'forgot-password',
			'login',
			'logout',
			'deleted'
		)
	);

	/**
	 * Record user registration.
	 *
	 * @param int $user_id Newly registered user ID.
	 */
	public function callback_user_register( $user_id ) {
		$current_user    = wp_get_current_user();
		$registered_user = get_user_by( 'id', $user_id );

		if ( ! $current_user->ID ) {
			$message = esc_html__( 'New user registered', 'stalkfish' );
		} else {
			/* translators: %2$s: Username, %3$s: User role */
			$message = _x(
				'New user account created with the username "%2$s" (%3$s)',
				'1: Username, 2: User role',
				'stalkfish'
			);
		}

		$args = array(
			'id'             => $user_id,
			'username'       => $registered_user->user_login,
			'role'           => stalkfish_get_current_user_roles( $registered_user->roles ),
			'email'          => $registered_user->user_email,
			'edit_user_link' => add_query_arg( 'user_id', $user_id, admin_url( 'user-edit.php' ) ),
		);

		$link = get_admin_url() . 'user-edit.php?user_id=' . $user_id;

		$this->log(
			array(
				'message' => vsprintf( $message, $args ),
				'context' => 'users',
				'action'  => 'created',
				'meta'    => $args,
				'link'    => $link,
			)
		);
	}

	/**
	 * Record profile update
	 *
	 * @param int      $user_id Registered user ID.
	 * @param \WP_User $prev_user Registered user object.
	 */
	public function callback_profile_update( $user_id, $prev_user ) {
		// Get updated user data.
		$updated_user = get_userdata( $user_id );
		$link         = get_admin_url() . 'user-edit.php?user_id=' . $user_id;

		// Log display name has been changed.
		if ( $prev_user->display_name !== $updated_user->display_name ) {
			$message = vsprintf(
				/* translators: %1$s: Username, %2$s: Updated Display name */
				__( '%1$s\'s display name was changed to %2$s', 'stalkfish' ),
				array(
					$prev_user->user_login,
					$updated_user->display_name,
				)
			);
			$args = array(
				'id'                   => $user_id,
				'username'             => $updated_user->user_login,
				'role'                 => stalkfish_get_current_user_roles( $updated_user->roles ),
				'prev_display_name'    => $prev_user->display_name,
				'updated_display_name' => $updated_user->display_name,
			);

			$this->log(
				array(
					'message' => $message,
					'context' => 'profiles',
					'action'  => 'updated',
					'meta'    => $args,
					'link'    => $link,
				)
			);
		}

		// Log email has been changed.
		if ( $prev_user->user_email !== $updated_user->user_email ) {
			$message = vsprintf(
				/* translators: %1$s: Username, %2$s: Updated email */
				__( '%1$s\'s email was changed to %2$s', 'stalkfish' ),
				array(
					$prev_user->user_login,
					$updated_user->user_email,
				)
			);
			$args = array(
				'id'             => $user_id,
				'username'       => $updated_user->user_login,
				'role'           => stalkfish_get_current_user_roles( $updated_user->roles ),
				'prev_email'     => $prev_user->user_email,
				'updated_email'  => $updated_user->user_email,
			);
			$this->log(
				array(
					'message' => $message,
					'context' => 'profiles',
					'action'  => 'updated',
					'meta'    => $args,
					'link'    => $link,
				)
			);
		}

		// Log password has been changed.
		if ( $prev_user->user_pass !== $updated_user->user_pass ) {
			$message = vsprintf(
				/* translators: %1$s: Username */
				__( '%s\'s password was changed', 'stalkfish' ),
				array( $prev_user->user_login )
			);
			$args = array(
				'id'             => $user_id,
				'username'       => $updated_user->user_login,
				'role'           => stalkfish_get_current_user_roles( $updated_user->roles ),
			);

			$this->log(
				array(
					'message' => $message,
					'context' => 'profiles',
					'action'  => 'updated',
					'meta'    => $args,
					'link'    => $link,
				)
			);
		}

		// Log url/website has been changed.
		if ( $prev_user->user_url !== $updated_user->user_url ) {
			$message = vsprintf(
				/* translators: %1$s: Username, %2$s: Updated url  */
				__( '%1$s\'s website was changed to %2$s', 'stalkfish' ),
				array(
					$prev_user->user_login,
					$updated_user->user_url,
				)
			);
			$args = array(
				'id'             => $user_id,
				'username'       => $updated_user->user_login,
				'role'           => stalkfish_get_current_user_roles( $updated_user->roles ),
				'prev_url'       => $prev_user->user_url,
				'updated_url'    => $updated_user->user_url,
			);
			$this->log(
				array(
					'message' => $message,
					'context' => 'profiles',
					'action'  => 'updated',
					'meta'    => $args,
					'link'    => $link,
				)
			);
		}
	}

	/**
	 * Holds user meta being updated.
	 *
	 * @param int    $user_id - User ID.
	 * @param string $meta_key - Meta key.
	 * @param mixed  $meta_value - Metadata value.
	 */
	public function callback_update_user_meta( $user_id, $meta_key, $meta_value ) {
		// Store old user meta array.
		$this->prev_user_meta[ $user_id ] = (object) array(
			'key' => get_metadata_by_mid( 'user', $user_id ),
			'val' => get_metadata( 'user', $meta_key, $meta_value, true ),
		);
	}

	/**
	 * Update a profile field name value.
	 *
	 * @param int    $meta_id - Meta ID.
	 * @param int    $object_id - Object ID.
	 * @param string $meta_key - Meta key.
	 * @param mixed  $meta_value - Meta value.
	 */
	public function callback_updated_user_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( in_array( $meta_key, array( 'last_update', 'wp_user_level', 'wp_capabilities', 'session_tokens' ), true ) ) {
			return;
		}

		$username_meta = array( 'first_name', 'last_name', 'nickname' ); // User profile name related meta.
		$user          = get_user_by( 'ID', $object_id ); // Get user.
		$message       = '';

		if ( isset( $this->prev_user_meta[ $meta_id ] ) && ! in_array( $meta_key, $username_meta, true ) ) {

			// Check if a custom field change has happened.
			if ( $this->prev_user_meta[ $meta_id ]->val !== $meta_value ) {
				$message = vsprintf(
					/* translators: %1$s: Username, %2$s: Custom field key */
					__( '%1$s\'s %2$s was updated', 'stalkfish' ),
					array(
						$user->user_login,
						$meta_key,
					)
				);

				$args = array(
					'id'                => $user->ID,
					'username'          => $user->user_login,
					'role'              => stalkfish_get_current_user_roles( $user->roles ),
					'custom_field_name'  => $meta_key,
					'updated_value'     => $meta_value,
					'prev_value'        => $this->prev_user_meta[ $meta_id ]->val,
				);
			}

			// Remove old meta update data.
			unset( $this->prev_user_meta[ $meta_id ] );

		} elseif ( isset( $this->prev_user_meta[ $meta_id ] ) && in_array( $meta_key, $username_meta, true ) ) {
			// Detect the alert based on meta key.
			switch ( $meta_key ) {
				case 'first_name':
					$message = vsprintf(
						/* translators: %1$s: Username, %2$s: First name */
						__( '%1$s\'s first name was changed to %2$s', 'stalkfish' ),
						array(
							$user->user_login,
							$meta_value,
						)
					);
					break;
				case 'last_name':
					$message = vsprintf(
						/* translators: %1$s: Username, %2$s: Last name */
						__( '%1$s\'s last name was changed to %2$s', 'stalkfish' ),
						array(
							$user->user_login,
							$meta_value,
						)
					);
					break;
				case 'nickname':
				default:
					$message = vsprintf(
						/* translators: %1$s: Username, %2$s: Custom field key, %3$s: Field value */
						__( '%1$s\'s custom field %2$s was changed to %3$s', 'stalkfish' ),
						array(
							$user->user_login,
							$meta_key,
							$meta_value,
						)
					);
					break;
			}
			$args = array(
				'id'             => $user->ID,
				'username'       => $user->user_login,
				'role'           => stalkfish_get_current_user_roles( $user->roles ),
				'field'          => $meta_key,
				'prev_value'     => $this->prev_user_meta[ $meta_id ]->val,
				'updated_value'  => $meta_value,
			);
		}

		$link         = get_admin_url() . 'user-edit.php?user_id=' . $user->ID;

		$this->log(
			array(
				'message' => $message,
				'context' => 'profiles',
				'action'  => 'updated',
				'meta'    => isset( $args ) && is_array( $args ) ? $args : array(),
				'link'    => $link,
			)
		);
	}

	/**
	 * Record role update.
	 *
	 * @param int    $user_id User ID.
	 * @param string $updated_role User role.
	 * @param array  $prev_roles Previous user roles.
	 * @param bool   $use_posted_data If selected user role.
	 */
	public function callback_set_user_role( $user_id, $updated_role, $prev_roles, $use_posted_data = false ) {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$roles_to_process = ( $use_posted_data ) ? $updated_role : $user->roles;

		$prev_roles    = array_map( '\stalkfish_filter_role_names', $prev_roles );
		$updated_roles = array_map( '\stalkfish_filter_role_names', $roles_to_process );

		// Get roles as strings to compare.
		$prev_roles_string    = is_array( $prev_roles ) ? implode( ', ', $prev_roles ) : '';
		$updated_roles_string = is_array( $updated_roles ) ? implode( ', ', $updated_roles ) : '';

		// Since a user role can't be empty unless it's a new user creation event, we simply exit if like so.
		if ( ! $prev_roles_string ) {
			return;
		}

		// Alert if roles are changed.
		if ( $prev_roles_string !== $updated_roles_string ) {
			$args = array(
				'id'             => $user_id,
				'username'       => $user->user_login,
				'prev_role'      => is_array( $prev_roles ) ? reset( $prev_roles ) : '',
				'updated_role'   => is_array( $updated_roles ) ? reset( $updated_roles ) : '',
			);

			$link         = get_admin_url() . 'user-edit.php?user_id=' . $user_id;

			$this->log(
				array(
					'message' => vsprintf(
					/* translators: %2$s: Username, %3$s: Previous Role, %4$s: Updated Role */
						_x(
							'%2$s\'s role was changed from %3$s to %4$s',
							'1: Username, 2: Previous role, 3: Updated role',
							'stalkfish'
						),
						$args
					),
					'context' => 'profiles',
					'action'  => 'updated',
					'meta'    => $args,
					'link'    => $link,
				)
			);
		}

	}

	/**
	 * Record user requests to retrieve passwords
	 *
	 * @param string $user_login User login.
	 */
	public function callback_retrieve_password( $user_login ) {
		if ( stalkfish_filter_input( $user_login, FILTER_VALIDATE_EMAIL ) ) {
			$user = get_user_by( 'email', $user_login );
		} else {
			$user = get_user_by( 'login', $user_login );
		}

		$link         = get_admin_url() . 'user-edit.php?user_id=' . $user->ID;

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: Username */
					__( '%s\'s password was requested to be reset', 'stalkfish' ),
					array( $user_login )
				),
				'context' => 'sessions',
				'action'  => 'forgot-password',
				'meta'    => array(
					'id'             => $user->ID,
					'role'           => stalkfish_get_current_user_roles( $user->roles ),
					'username'       => $user_login,
					'edit_user_link' => add_query_arg( 'user_id', $user->ID, admin_url( 'user-edit.php' ) ),
				),
				'link' => $link,
			)
		);
	}

	/**
	 * Record user login
	 *
	 * @param string $auth_cookie Authenticated cookie.
	 * @param int    $expire Unused.
	 * @param int    $expiration Unused.
	 * @param int    $user_id Unused.
	 */
	public function callback_set_auth_cookie( $auth_cookie, $expire, $expiration, $user_id ) {

		$user = get_user_by( 'ID', $user_id );

		if ( ! is_a( $user, '\WP_User' ) ) {
			return;
		}

		$username = $user->data->user_login;

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %s: Username */
					__( 'User "%s" logged in', 'stalkfish' ),
					compact( 'username' )
				),
				'context' => 'sessions',
				'action'  => 'login',
				'meta'    => array(),
				'user'    => $user_id,
			)
		);
	}

	/**
	 * Gets and sets the current user.
	 */
	public function callback_clear_auth_cookie() {
		$this->current_user = wp_get_current_user();
	}

	/**
	 * Record user logout.
	 */
	public function callback_wp_logout() {
		if ( $this->current_user->ID ) {
			$username = $this->current_user->user_login;

			$link         = get_admin_url() . 'user-edit.php?user_id=' . $this->current_user->ID;

			$this->log(
				array(
					'message' => vsprintf(
					/* translators: %s: Username */
						__( 'User "%s" logged out', 'stalkfish' ),
						compact( 'username' )
					),
					'context' => 'sessions',
					'action'  => 'logout',
					'meta'    => array(),
					'user'    => $this->current_user->ID,
					'link'    => $link,
				)
			);
		}
	}

	/**
	 * Store user's id to be deleted.
	 *
	 * @param int $user_id User ID that maybe deleted.
	 */
	public function callback_delete_user( $user_id ) {
		if ( ! isset( $this->user_pre_deleted[ $user_id ] ) ) {
			$this->user_pre_deleted[ $user_id ] = get_user_by( 'id', $user_id );
		}
	}

	/**
	 * Record deleted user.
	 *
	 * @param int $user_id Deleted user ID.
	 */
	public function callback_deleted_user( $user_id ) {
		$args = array();
		if ( isset( $this->user_pre_deleted[ $user_id ] ) ) {
			/* translators: %1$s: Username, %2$s: User role */
			$message      = _x(
				'%1$s\'s account was deleted (%2$s)',
				'1: Username, 2: User role',
				'stalkfish'
			);
			$user_login   = $this->user_pre_deleted[ $user_id ]->user_login;
			$deleted_user = $this->user_pre_deleted[ $user_id ];

			$role = ! empty( $deleted_user->roles ) && is_array( $deleted_user->roles ) ? reset( $deleted_user->roles ) : '';

			$args = array(
				'id'       => $user_id,
				'username' => $user_login,
				'role'     => $role,
				'email'    => $deleted_user->user_email,
			);

			unset( $this->user_pre_deleted[ $user_id ] );
		} else {
			/* translators: %1$s: Username */
			$message      = esc_html__( 'User account "%s" was deleted', 'stalkfish' );
			$user_login   = $user_id;
			$deleted_user = $user_id;
			$roles        = stalkfish_get_user_role_labels( $deleted_user );

			$args = array(
				'id'       => $user_id,
				'username' => $user_login,
				'role'     => is_array( $roles ) ? reset( $roles ) : '',
			);
		}

		$link         = get_admin_url() . 'users.php';

		$this->log(
			array(
				'message' => vsprintf(
					$message,
					array( $args['username'], $args['role'] )
				),
				'context' => 'users',
				'action'  => 'deleted',
				'meta'    => $args,
				'link'    => $link,
			)
		);
	}

}
