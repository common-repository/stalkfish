<?php
/**
 * Class to record menus events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Menus_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Menus_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'menus';

	/**
	 * Available hooks for current pipe.
	 *
	 * @var array
	 */
	public $hooks = array(
		'wp_create_nav_menu',
		'wp_update_nav_menu',
		'delete_nav_menu',
	);

	/**
	 * Available contexts and their actions for current pipe.
	 *
	 * @var array
	 */
	public $triggers = array(
		'actions' => array(
			'created',
			'updated',
			'deleted',
			'assigned',
			'unassigned'
		)
	);

	/**
	 * Register pipe in the WP Frontend
	 *
	 * @var bool
	 */
	public $register_frontend = false;

	/**
	 * Adds on to pipe triggers.
	 */
	public function __construct() {
		$this->get_pipe_triggers();
	}

	/**
	 * Registers pipe.
	 */
	public function register() {
		parent::register();

		add_action(
			'update_option_theme_mods_' . get_option( 'stylesheet' ),
			array(
				$this,
				'callback_update_option_theme_mods',
			),
			10,
			2
		);
	}

	/**
	 * Get triggers for each pipe/context and their actions.
	 *
	 * @return void
	 */
	public function get_pipe_triggers() {
		// Contexts.
		$menus = wp_get_nav_menus();
		foreach ( $menus as $menu ) {
			$this->triggers['contexts'][] = sanitize_title( $menu->name );
		}
	}

	/**
	 * Unregister pipe actions.
	 */
	public function unregister() {
		parent::unregister();

		remove_action(
			'update_option_theme_mods_' . get_option( 'stylesheet' ),
			array(
				$this,
				'callback_update_option_theme_mods',
			),
			10
		);
	}

	/**
	 * Records new menus.
	 *
	 * @param int   $menu_id Menu ID.
	 * @param array $menu_data Menu data.
	 */
	public function callback_wp_create_nav_menu( $menu_id, $menu_data ) {
		$name = $menu_data['menu-name'];

		$args = array(
			'id'    => $menu_id,
			'title' => $name,
		);

		$link = get_admin_url() . 'nav-menus.php?&menu=' . $menu_id;

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %s: Menu title */
					__(
						'New menu created with the name "%s"',
						'stalkfish'
					),
					array( $args['title'] )
				),
				'meta'    => $args,
				'context' => sanitize_title( $name ),
				'action'  => 'created',
				'link'    => $link
			)
		);
	}


	/**
	 * Records menu modifications.
	 *
	 * @param int   $menu_id Menu ID.
	 * @param array $menu_data Menu data.
	 */
	public function callback_wp_update_nav_menu( $menu_id, $menu_data = array() ) {
		if ( empty( $menu_data ) ) {
			return;
		}

		$name = $menu_data['menu-name'];

		$args = array(
			'id'    => $menu_id,
			'title' => $name,
		);

		$link = get_admin_url() . 'nav-menus.php?&menu=' . $menu_id;

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %s: Menu title */
					__(
						'Menu "%s" updated',
						'stalkfish'
					),
					array( $args['title'] )
				),
				'meta'    => $args,
				'context' => sanitize_title( $name ),
				'action'  => 'updated',
				'link'    => $link,
			)
		);
	}

	/**
	 * Records menu deletion.
	 *
	 * @param object $term Term.
	 * @param int    $tt_id Term ID.
	 * @param object $deleted_term Deleted term.
	 */
	public function callback_delete_nav_menu( $term, $tt_id, $deleted_term ) {
		unset( $tt_id );

		$name    = $deleted_term->name;
		$menu_id = $term;

		$args = array(
			'id'    => $menu_id,
			'title' => $name,
		);


		$link = get_admin_url() . 'nav-menus.php?&menu=' . $menu_id;

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %s: Menu title */
					__(
						'Menu "%s" deleted',
						'stalkfish'
					),
					array( $args['title'] )
				),
				'meta'    => $args,
				'context' => sanitize_title( $name ),
				'action'  => 'deleted',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record assignment to menu locations.
	 *
	 * @param array $prev Previous theme data.
	 * @param array $updated Updated theme data.
	 */
	public function callback_update_option_theme_mods( $prev, $updated ) {
		// Disable if we're switching themes.
		if ( did_action( 'after_switch_theme' ) ) {
			return;
		}

		$key = 'nav_menu_locations';

		if ( ! isset( $updated[ $key ] ) ) {
			return;
		}

		if ( $prev[ $key ] === $updated[ $key ] ) {
			return;
		}

		$locations     = get_registered_nav_menus();
		$prev_value    = (array) $prev[ $key ];
		$updated_value = (array) $updated[ $key ];
		$changed       = array_diff_assoc( $prev_value, $updated_value ) + array_diff_assoc( $updated_value, $prev_value );

		if ( ! $changed ) {
			return;
		}

		foreach ( $changed as $location_id => $menu_id ) {
			$location = $locations[ $location_id ];

			if ( empty( $updated[ $key ][ $location_id ] ) ) {
				$action  = 'unassigned';
				$menu_id = isset( $prev[ $key ][ $location_id ] ) ? $prev[ $key ][ $location_id ] : 0;
				/* translators: %1$s: menu title, %2$s: theme location */
				$message = _x(
					'Menu "%1$s" has been unassigned from "%2$s" location',
					'1: Menu name, 2: Theme location',
					'stalkfish'
				);
			} else {
				$action  = 'assigned';
				$menu_id = isset( $updated[ $key ][ $location_id ] ) ? $updated[ $key ][ $location_id ] : 0;
				/* translators: %1$s: menu title, %2$s: theme location */
				$message = _x(
					'Menu "%1$s" has been assigned to "%2$s" location',
					'1: Menu name, 2: Theme location',
					'stalkfish'
				);
			}

			$menu = get_term( $menu_id, 'nav_menu' );

			// Check if menu is deleted.
			if ( ! $menu || is_wp_error( $menu ) ) {
				continue;
			}

			$name = $menu->name;

			$args = array(
				'id'          => $menu_id,
				'title'       => $name,
				'location'    => $location,
				'location_id' => $location_id,
			);

			$link = get_admin_url() . 'nav-menus.php?&menu=' . $menu_id;

			$this->log(
				array(
					'message' => vsprintf( $message, array( $args['title'], $args['location'] ) ),
					'meta'    => $args,
					'context' => sanitize_title( $name ),
					'action'  => $action,
					'link'    => $link,
				)
			);
		}
	}
}
