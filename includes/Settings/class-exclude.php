<?php
/**
 * Stalkfish Exclude Settings
 *
 * @package Stalkfish
 */

namespace Stalkfish\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Excludes.
 */
class Exclude extends Settings_Page {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'exclude';
		$this->label = __( 'Exclude', 'stalkfish' );

		parent::__construct();

		add_action( 'wp_ajax_sf_get_actions', array( $this, 'get_actions' ) );
		add_action( 'wp_ajax_sf_get_users', array( $this, 'get_users' ) );
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array();

		return apply_filters( 'sf_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'sf_exclude_settings',
			array(
				array(
					'type' => 'title',
					'id'   => 'sf_exclude_rules',
				),
				array(
					'desc'    => sprintf(
					/* translators: %s: Stalkfish Documentation link */
						__( 'Add rules to ignore specific site events or activities from being recorded by Stalkfish. %s.', 'stalkfish' ),
						'<a href="https://stalkfish.com/docs/excludes" target="_blank">' . __( 'Get help', 'stalkfish' ) . '</a>'
					),
					'id'      => 'sf_exclude_rules',
					'default' => get_option( 'sf_exclude_rules' ),
					'type'    => 'rules',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'sf_exclude_rules',
				),
			)
		);

		return apply_filters( 'sf_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		Admin_Settings::save_fields( $settings );
		if ( $current_section ) {
			do_action( 'sf_update_options_' . $this->id . '_' . $current_section );
		}
	}

	/**
	 * Update actions dropdown options based on the pipe selected.
	 */
	public function get_actions() {
		$pipe_or_context    = (string) stalkfish_filter_input( INPUT_POST, 'pipe' );
		$pipes              = stalkfish_get_instance()->pipes->triggers;
		$actions            = array();
		if ( ! empty( $pipe_or_context ) ) {
			if ( isset( $pipes[ $pipe_or_context ] ) ) {
				foreach( $pipes[ $pipe_or_context ]['actions'] as $key => $value ) {
					$actions[ $value ] = $value;
				}
			}
		} else {
			$actions = array();
		}
		ksort( $actions );
		wp_send_json_success( $actions );
	}

	/**
	 * Ajax callback function to search users, used on exclude setting page
	 *
	 * @uses \WP_User_Query
	 */
	public function get_users() {
		if ( ! defined( 'DOING_AJAX' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_ajax_referer( 'sf_get_users', 'nonce' );

		$response = (object) array(
			'status'  => false,
			'message' => esc_html__( 'There was an error in the request', 'stalkfish' ),
		);

		$search = '';
		$input  = stalkfish_filter_input( INPUT_POST, 'find' );

		if ( ! isset( $input['term'] ) ) {
			$search = wp_unslash( $_POST['find'] );
			$search = ! empty( $search ) && isset( $search['term'] ) ? $search['term'] : '';
		}

		$request = (object) array(
			'find' => $search,
		);

		add_filter(
			'user_search_columns',
			array(
				$this,
				'add_display_name_search_columns',
			),
			10,
			3
		);

		$users = new \WP_User_Query(
			array(
				'search'         => "*{$request->find}*",
				'search_columns' => array(
					'user_login',
					'user_nicename',
					'user_email',
					'user_url',
				),
				'orderby'        => 'display_name',
				'number'         => 50,
			)
		);

		remove_filter(
			'user_search_columns',
			array(
				$this,
				'add_display_name_search_columns',
			),
			10
		);

		if ( 0 === $users->get_total() ) {
			wp_send_json_error( $response );
		}
		$users_array = $users->results;

		if ( is_multisite() && is_super_admin() ) {
			$super_admins = get_super_admins();
			foreach ( $super_admins as $admin ) {
				$user          = get_user_by( 'login', $admin );
				$users_array[] = $user;
			}
		}

		$response->status        = true;
		$response->message       = '';
		$response->roles         = stalkfish_get_all_roles();
		$response->users         = array();
		$users_added_to_response = array();

		foreach ( $users_array as $key => $user ) {
			// exclude duplications.
			if ( array_key_exists( $user->ID, $users_added_to_response ) ) {
				continue;
			} else {
				$users_added_to_response[ $user->ID ] = true;
			}

			$args = array(
				'id'   => $user->ID,
				'text' => $user->display_name,
			);

			$args['tooltip'] = esc_attr(
				sprintf(
				/* translators: %1$d: User ID, %2$s: Username, %3$s: Email, %4$s: User role */
					__( 'ID: %1$d\nUser: %2$s\nEmail: %3$s\nRole: %4$s', 'stalkfish' ),
					$user->ID,
					$user->user_login,
					$user->user_email,
					ucwords( stalkfish_get_current_user_roles( $user->roles ) )
				)
			);

			$response->users[] = $args;
		}

		usort(
			$response->users,
			function ( $a, $b ) {
				return strcmp( $a['text'], $b['text'] );
			}
		);

		if ( empty( $search ) || preg_match( '/wp|cli|system|unknown/i', $search ) ) {
			$response->users[] = array(
				'id'      => '0',
				'text'    => 'System',
				'tooltip' => esc_html__( 'Actions performed by the system when a user is not logged in (e.g. Auto site upgrader, Auto plugin actions)', 'stalkfish' ),
			);
		}

		wp_send_json_success( $response );
	}

	/**
	 * Filter the columns to search in a WP_User_Query search.
	 *
	 * @param array          $search_columns Array of column names to be searched.
	 * @param string         $search Text being searched.
	 * @param \WP_User_Query $query current WP_User_Query instance.
	 *
	 * @return array
	 */
	public function add_display_name_search_columns( $search_columns, $search, $query ) {
		unset( $search );
		unset( $query );

		$search_columns[] = 'display_name';

		return $search_columns;
	}
}

return new Exclude();
