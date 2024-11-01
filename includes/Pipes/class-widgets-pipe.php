<?php
/**
 * Class to record widget events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Widgets_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Widgets_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'widgets';

	/**
	 * Available hooks for current pipe.
	 *
	 * @var array
	 */
	public $hooks = array(
		'update_option_sidebars_widgets',
		'updated_option',
	);

	/**
	 * Available contexts and their actions for current pipe.
	 *
	 * @var array
	 */
	public $triggers = array(
		'contexts' => array(
			'wp_inactive_widgets',
			'orphaned_widgets',
		),
		'actions' => array(
			'added',
			'sorted',
			'moved',
			'updated',
			'removed',
			'deactivated',
			'reactivated'
		)
	);

	/**
	 * Stores initial sidebars_widgets option.
	 *
	 * @var array
	 */
	protected $initial_sidebars_widgets = null;

	/**
	 * Adds on to pipe triggers.
	 */
	public function __construct() {
		$this->get_pipe_triggers();
	}

	/**
	 * Get triggers for each pipe/context and their actions.
	 *
	 * @return void
	 */
	public function get_pipe_triggers() {
		global $wp_registered_sidebars;
		// Contexts.
		if ( ! empty( $wp_registered_sidebars ) ) {
			foreach ( $wp_registered_sidebars as $sidebar ) {
				$this->triggers['contexts'][] = sanitize_title( $sidebar['id'] );
			}
		}
	}

	/**
	 * Get translated context labels
	 *
	 * @return array Context label translations
	 */
	public function get_context_labels() {
		global $wp_registered_sidebars;

		$labels = array();

		foreach ( $wp_registered_sidebars as $sidebar ) {
			$labels[ $sidebar['id'] ] = $sidebar['name'];
		}

		$labels['wp_inactive_widgets'] = esc_html__( 'Inactive Widgets', 'stalkfish' );
		$labels['orphaned_widgets']    = esc_html__( 'Orphaned Widgets', 'stalkfish' );

		return $labels;
	}

	/**
	 * Records widgets addition/deletion/reordering/deactivation from sidebars
	 *
	 * @param array $prev Previous sidebars widgets.
	 * @param array $updated Updated sidebars widgets.
	 *
	 * @return void
	 */
	public function callback_update_option_sidebars_widgets( $prev, $updated ) {
		// Check if switching themes.
		if ( did_action( 'after_switch_theme' ) ) {
			return;
		}

		if ( did_action( 'customize_save' ) ) {
			if ( is_null( $this->initial_sidebars_widgets ) ) {
				$this->initial_sidebars_widgets = $prev;
				add_action( 'customize_save_after', array( $this, 'callback_customize_save_after' ) );
			}
		} else {
			$this->record_sidebars_widgets_changes( $prev, $updated );
		}
	}

	/**
	 * Records the changes only when customize_save_after is fired.
	 *
	 * @return void
	 */
	public function callback_customize_save_after() {
		$prev_sidebars_widgets    = $this->initial_sidebars_widgets;
		$updated_sidebars_widgets = get_option( 'sidebars_widgets' );

		$this->record_sidebars_widgets_changes( $prev_sidebars_widgets, $updated_sidebars_widgets );
	}

	/**
	 * Records widget changes.
	 *
	 * @param array $prev Previous sidebar widgets.
	 * @param array $updated Updated sidebar widgets.
	 */
	protected function record_sidebars_widgets_changes( $prev, $updated ) {
		unset( $prev['array_version'] );
		unset( $updated['array_version'] );

		if ( $prev === $updated ) {
			return;
		}

		$this->record_deactivated_widgets( $prev, $updated );
		$this->record_reactivated_widgets( $prev, $updated );
		$this->record_widget_removal( $prev, $updated );
		$this->record_widget_addition( $prev, $updated );
		$this->record_widget_reordering( $prev, $updated );
		$this->record_widget_moved( $prev, $updated );
	}

	/**
	 * Record widget deactivations from sidebars
	 *
	 * @param array $prev Previous sidebars widgets.
	 * @param array $updated Updated sidebars widgets.
	 *
	 * @return void
	 */
	protected function record_deactivated_widgets( $prev, $updated ) {
		$updated_deactivated_widget_ids = array_diff( $updated['wp_inactive_widgets'], $prev['wp_inactive_widgets'] );

		foreach ( $updated_deactivated_widget_ids as $widget_id ) {
			$sidebar_id = '';

			foreach ( $prev as $prev_sidebar_id => $prev_widget_ids ) {
				if ( in_array( $widget_id, $prev_widget_ids, true ) ) {
					$sidebar_id = $prev_sidebar_id;
					break;
				}
			}

			$action       = 'deactivated';
			$name         = $this->get_widget_name( $widget_id );
			$title        = $this->get_widget_title( $widget_id );
			$labels       = $this->get_context_labels();
			$sidebar_name = isset( $labels[ $sidebar_id ] ) ? $labels[ $sidebar_id ] : $sidebar_id;

			if ( $name && $title ) {
				/* translators: %1$s: widget name, %2$s: widget title, %3$s: sidebar name */
				$message = _x( '%1$s widget named "%2$s" from "%3$s" deactivated', '1: Name, 2: Title, 3: Sidebar Name', 'stalkfish' );
			} elseif ( $name ) {
				/* translators: %1$s: widget name, %3$s: sidebar name */
				$message = _x( '%1$s widget from "%3$s" deactivated', '1: Name, 3: Sidebar Name', 'stalkfish' );
			} elseif ( $title ) {
				/* translators: %2$s: widget title, %3$s: sidebar name */
				$message = _x( 'Unknown widget type named "%2$s" from "%3$s" deactivated', '2: Title, 3: Sidebar Name', 'stalkfish' );
			} else {
				/* translators: %4$s: widget ID, %3$s: sidebar name */
				$message = _x( '%4$s widget from "%3$s" deactivated', '4: Widget ID, 3: Sidebar Name', 'stalkfish' );
			}

			$args = compact( 'name', 'title', 'sidebar_name', 'widget_id' );

			$this->log(
				array(
					'message' => vsprintf( $message, $args ),
					'meta'    => $args,
					'context' => 'wp_inactive_widgets',
					'action'  => $action,
					'link'    => get_admin_url() . 'widgets.php',
				)
			);
		}
	}

	/**
	 * Record widget reactivation from sidebars
	 *
	 * @param array $prev Previous sidebars widgets.
	 * @param array $updated Updated sidebars widgets.
	 *
	 * @return void
	 */
	protected function record_reactivated_widgets( $prev, $updated ) {
		$updated_reactivated_widget_ids = array_diff( $prev['wp_inactive_widgets'], $updated['wp_inactive_widgets'] );

		foreach ( $updated_reactivated_widget_ids as $widget_id ) {
			$sidebar_id = '';

			foreach ( $updated as $updated_sidebar_id => $updated_widget_ids ) {
				if ( in_array( $widget_id, $updated_widget_ids, true ) ) {
					$sidebar_id = $updated_sidebar_id;
					break;
				}
			}

			$action = 'reactivated';
			$name   = $this->get_widget_name( $widget_id );
			$title  = $this->get_widget_title( $widget_id );

			if ( $name && $title ) {
				/* translators: %1$s: widget name, %2$s: widget title*/
				$message = _x( '%1$s widget named "%2$s" reactivated', '1: Name, 2: Title', 'stalkfish' );
			} elseif ( $name ) {
				/* translators: %1$s: widget name */
				$message = _x( '%1$s widget reactivated', '1: Name', 'stalkfish' );
			} elseif ( $title ) {
				/* translators: %2$s: widget title */
				$message = _x( 'Unknown widget type named "%2$s" reactivated', '2: Title', 'stalkfish' );
			} else {
				/* translators: %3$s: widget ID */
				$message = _x( '%3$s widget reactivated', '3: Widget ID', 'stalkfish' );
			}

			$args = compact( 'name', 'title', 'widget_id' );

			$this->log(
				array(
					'message' => vsprintf( $message, $args ),
					'meta'    => $args,
					'context' => $sidebar_id,
					'action'  => $action,
					'link'    => get_admin_url() . 'widgets.php',
				)
			);
		}
	}

	/**
	 * Record widget removal from sidebars
	 *
	 * @param array $prev Previous sidebars widgets.
	 * @param array $updated Updated sidebars widgets.
	 *
	 * @return void
	 */
	protected function record_widget_removal( $prev, $updated ) {
		$all_prev_widget_ids    = array_unique( call_user_func_array( 'array_merge', $prev ) );
		$all_updated_widget_ids = array_unique( call_user_func_array( 'array_merge', $updated ) );
		$deleted_widget_ids     = array_diff( $all_prev_widget_ids, $all_updated_widget_ids );

		foreach ( $deleted_widget_ids as $widget_id ) {
			$sidebar_id = '';

			foreach ( $prev as $prev_sidebar_id => $prev_widget_ids ) {
				if ( in_array( $widget_id, $prev_widget_ids, true ) ) {
					$sidebar_id = $prev_sidebar_id;
					break;
				}
			}

			$action       = 'removed';
			$name         = $this->get_widget_name( $widget_id );
			$title        = $this->get_widget_title( $widget_id );
			$labels       = $this->get_context_labels();
			$sidebar_name = isset( $labels[ $sidebar_id ] ) ? $labels[ $sidebar_id ] : $sidebar_id;

			if ( $name && $title ) {
				/* translators: %1$s: widget name, %2$s: widget title, %3$s: sidebar name */
				$message = _x( '%1$s widget named "%2$s" removed from "%3$s"', '1: Name, 2: Title, 3: Sidebar Name', 'stalkfish' );
			} elseif ( $name ) {
				/* translators: %1$s: widget name, %3$s: sidebar name */
				$message = _x( '%1$s widget removed from "%3$s"', '1: Name, 3: Sidebar Name', 'stalkfish' );
			} elseif ( $title ) {
				/* translators: %2$s: widget title, %3$s: sidebar name */
				$message = _x( 'Unknown widget type named "%2$s" removed from "%3$s"', '2: Title, 3: Sidebar Name', 'stalkfish' );
			} else {
				/* translators: %4$s: widget ID, %3$s: sidebar name */
				$message = _x( '%4$s widget removed from "%3$s"', '4: Widget ID, 3: Sidebar Name', 'stalkfish' );
			}

			$args = compact( 'name', 'title', 'sidebar_name', 'widget_id' );

			$this->log(
				array(
					'message' => vsprintf( $message, $args ),
					'meta'    => $args,
					'context' => $sidebar_id,
					'action'  => $action,
					'link'    => get_admin_url() . 'widgets.php',
				)
			);
		}
	}

	/**
	 * Record widget addition/reactivation from sidebars
	 *
	 * @param array $prev Previous sidebars widgets.
	 * @param array $updated Updated sidebars widgets.
	 *
	 * @return void
	 */
	protected function record_widget_addition( $prev, $updated ) {
		$all_prev_widget_ids    = array_unique( call_user_func_array( 'array_merge', $prev ) );
		$all_updated_widget_ids = array_unique( call_user_func_array( 'array_merge', $updated ) );
		$added_widget_ids       = array_diff( $all_updated_widget_ids, $all_prev_widget_ids );

		foreach ( $added_widget_ids as $widget_id ) {
			$sidebar_id = '';

			foreach ( $updated as $updated_sidebar_id => $updated_widget_ids ) {
				if ( in_array( $widget_id, $updated_widget_ids, true ) ) {
					$sidebar_id = $updated_sidebar_id;
					break;
				}
			}

			$action       = 'added';
			$name         = $this->get_widget_name( $widget_id );
			$title        = $this->get_widget_title( $widget_id );
			$labels       = $this->get_context_labels();
			$sidebar_name = isset( $labels[ $sidebar_id ] ) ? $labels[ $sidebar_id ] : $sidebar_id;

			if ( $name && $title ) {
				/* translators: %1$s: widget name, %2$s: widget title, %3$s: sidebar name */
				$message = _x( '%1$s widget named "%2$s" added to "%3$s"', '1: Name, 2: Title, 3: Sidebar Name', 'stalkfish' );
			} elseif ( $name ) {
				/* translators: %1$s: widget name, %3$s: sidebar name */
				$message = _x( '%1$s widget added to "%3$s"', '1: Name, 3: Sidebar Name', 'stalkfish' );
			} elseif ( $title ) {
				/* translators: %2$s: widget title, %3$s: sidebar name */
				$message = _x( 'Unknown widget type named "%2$s" added to "%3$s"', '2: Title, 3: Sidebar Name', 'stalkfish' );
			} else {
				/* translators: %4$s: widget ID, %3$s: sidebar name */
				$message = _x( '%4$s widget added to "%3$s"', '4: Widget ID, 3: Sidebar Name', 'stalkfish' );
			}

			$args = compact( 'name', 'title', 'sidebar_name', 'widget_id' );

			$this->log(
				array(
					'message' => vsprintf( $message, $args ),
					'meta'    => $args,
					'context' => $sidebar_id,
					'action'  => $action,
					'link'    => get_admin_url() . 'widgets.php',
				)
			);
		}
	}

	/**
	 * Record widget reordering/sorting from sidebars.
	 *
	 * @param array $prev Previous sidebars widgets.
	 * @param array $updated Updated sidebars widgets.
	 *
	 * @return void
	 */
	protected function record_widget_reordering( $prev, $updated ) {
		$all_sidebar_ids = array_intersect( array_keys( $prev ), array_keys( $updated ) );

		foreach ( $all_sidebar_ids as $sidebar_id ) {
			if ( $prev[ $sidebar_id ] === $updated[ $sidebar_id ] ) {
				continue;
			}

			// Use intersect to ignore widget additions and removals.
			$all_widget_ids       = array_unique( array_merge( $prev[ $sidebar_id ], $updated[ $sidebar_id ] ) );
			$common_widget_ids    = array_intersect( $prev[ $sidebar_id ], $updated[ $sidebar_id ] );
			$uncommon_widget_ids  = array_diff( $all_widget_ids, $common_widget_ids );
			$updated_widget_ids   = array_values( array_diff( $updated[ $sidebar_id ], $uncommon_widget_ids ) );
			$prev_widget_ids      = array_values( array_diff( $prev[ $sidebar_id ], $uncommon_widget_ids ) );
			$widget_order_changed = ( $updated_widget_ids !== $prev_widget_ids );

			if ( $widget_order_changed ) {
				$labels          = $this->get_context_labels();
				$sidebar_name    = isset( $labels[ $sidebar_id ] ) ? $labels[ $sidebar_id ] : $sidebar_id;
				$prev_widget_ids = $prev[ $sidebar_id ];

				$args = compact( 'sidebar_name', 'prev_widget_ids' );

				$this->log(
					array(
						'message' => vsprintf(
							/* translators: %1$s: sidebar name */
							_x(
								'Reordered widgets in sidebar "%s"',
								'Sidebar name',
								'stalkfish'
							),
							$args
						),
						'meta'    => $args,
						'context' => $sidebar_id,
						'action'  => 'sorted',
						'link'    => get_admin_url() . 'widgets.php',
					)
				);
			}
		}
	}

	/**
	 * Record widget movement to sidebars
	 *
	 * @param array $prev Previous sidebars widgets.
	 * @param array $updated Updated sidebars widgets.
	 *
	 * @return void
	 */
	protected function record_widget_moved( $prev, $updated ) {
		$all_sidebar_ids = array_intersect( array_keys( $prev ), array_keys( $updated ) );

		foreach ( $all_sidebar_ids as $updated_sidebar_id ) {
			if ( $prev[ $updated_sidebar_id ] === $updated[ $updated_sidebar_id ] ) {
				continue;
			}

			$updated_widget_ids = array_diff( $updated[ $updated_sidebar_id ], $prev[ $updated_sidebar_id ] );

			foreach ( $updated_widget_ids as $widget_id ) {
				$prev_sidebar_id = null;
				foreach ( $prev as $sidebar_id => $prev_widget_ids ) {
					if ( in_array( $widget_id, $prev_widget_ids, true ) ) {
						$prev_sidebar_id = $sidebar_id;
						break;
					}
				}

				if ( ! $prev_sidebar_id || 'wp_inactive_widgets' === $prev_sidebar_id || 'wp_inactive_widgets' === $updated_sidebar_id ) {
					continue;
				}

				assert( $prev_sidebar_id !== $updated_sidebar_id );

				$name                 = $this->get_widget_name( $widget_id );
				$title                = $this->get_widget_title( $widget_id );
				$labels               = $this->get_context_labels();
				$prev_sidebar_name    = isset( $labels[ $prev_sidebar_id ] ) ? $labels[ $prev_sidebar_id ] : $prev_sidebar_id;
				$updated_sidebar_name = isset( $labels[ $updated_sidebar_id ] ) ? $labels[ $updated_sidebar_id ] : $updated_sidebar_id;

				if ( $name && $title ) {
					/* translators: %1$s: widget name, %2$s: widget title, %4$s: previous sidebar name, %5$s: updated sidebar name */
					$message = _x( '%1$s widget named "%2$s" moved from "%4$s" to "%5$s"', '1: Name, 2: Title, 4: Previous Sidebar Name, 5: Updated Sidebar Name', 'stalkfish' );
				} elseif ( $name ) {
					/* translators: %1$s: widget name, %4$s: previous sidebar name, %5$s: updated sidebar name */
					$message = _x( '%1$s widget moved from "%4$s" to "%5$s"', '1: Name, 4: Previous Sidebar Name, 5: Updated Sidebar Name', 'stalkfish' );
				} elseif ( $title ) {
					/* translators: %2$s: widget title, %4$s: previous sidebar name, %5$s: updated sidebar name */
					$message = _x( 'Unknown widget type named "%2$s" moved from "%4$s" to "%5$s"', '2: Title, 4: Previous Sidebar Name, 5: Updated Sidebar Name', 'stalkfish' );
				} else {
					/* translators: %3$s: widget ID, %4$s: previous sidebar name, %5$s: updated sidebar name */
					$message = _x( '%3$s widget moved from "%4$s" to "%5$s"', '3: Widget ID, 4: Previous Sidebar Name, 5: Updated Sidebar Name', 'stalkfish' );
				}

				$sidebar_id = $updated_sidebar_id;
				$args       = compact( 'name', 'title', 'widget_id', 'prev_sidebar_name', 'updated_sidebar_name' );

				$this->log(
					array(
						'message' => vsprintf( $message, $args ),
						'meta'    => $args,
						'context' => $sidebar_id,
						'action'  => 'moved',
						'link'    => get_admin_url() . 'widgets.php',
					)
				);
			}
		}
	}

	/**
	 * Record widget modifications.
	 *
	 * @param string $option_name Option key.
	 * @param array  $prev_value Previous value.
	 * @param array  $updated_value Updated value.
	 */
	public function callback_updated_option( $option_name, $prev_value, $updated_value ) {
		if ( ! preg_match( '/^widget_(.+)$/', $option_name, $matches ) || ! is_array( $updated_value ) ) {
			return;
		}

		$is_multi       = ! empty( $updated_value['_multiwidget'] );
		$widget_id_base = $matches[1];

		$updates = array();

		if ( $is_multi ) {
			$widget_id_format = "$widget_id_base-%d";

			unset( $updated_value['_multiwidget'] );
			unset( $prev_value['_multiwidget'] );

			/**
			 * Updated widgets.
			 */
			$updated_widget_numbers = array_intersect( array_keys( $prev_value ), array_keys( $updated_value ) );

			foreach ( $updated_widget_numbers as $widget_number ) {
				$updated_instance = $updated_value[ $widget_number ];
				$prev_instance    = $prev_value[ $widget_number ];

				if ( $prev_instance !== $updated_instance ) {
					$widget_id    = sprintf( $widget_id_format, $widget_number );
					$name         = $this->get_widget_name( $widget_id );
					$title        = ! empty( $updated_instance['title'] ) ? $updated_instance['title'] : null;
					$sidebar_id   = $this->get_widget_sidebar_id( $widget_id );
					$labels       = $this->get_context_labels();
					$sidebar_name = isset( $labels[ $sidebar_id ] ) ? $labels[ $sidebar_id ] : $sidebar_id;

					$updates[] = compact( 'name', 'title', 'widget_id', 'sidebar_id', 'prev_instance', 'sidebar_name' );
				}
			}
		} else {
			$widget_id     = $widget_id_base;
			$name          = $widget_id; // When there aren't names available for single widgets.
			$title         = ! empty( $updated_value['title'] ) ? $updated_value['title'] : null;
			$sidebar_id    = $this->get_widget_sidebar_id( $widget_id );
			$prev_instance = $prev_value;
			$labels        = $this->get_context_labels();
			$sidebar_name  = isset( $labels[ $sidebar_id ] ) ? $labels[ $sidebar_id ] : $sidebar_id;

			$updates[] = compact( 'widget_id', 'title', 'name', 'sidebar_id', 'prev_instance', 'sidebar_name' );
		}

		/**
		 * Record updated actions
		 */
		foreach ( $updates as $update ) {
			if ( $update['name'] && $update['title'] ) {
				/* translators: %1$s: widget name, %2$s: widget title, %3$s: sidebar name */
				$message = _x( '%1$s widget named "%2$s" in "%3$s" updated', '1: Name, 2: Title, 3: Sidebar Name', 'stalkfish' );
			} elseif ( $update['name'] ) {
				/* translators: %1$s: widget name, %3$s: sidebar name */
				$message = _x( '%1$s widget in "%3$s" updated', '1: Name, 3: Sidebar Name', 'stalkfish' );
			} elseif ( $update['title'] ) {
				/* translators: %2$s: widget title, %3$s: sidebar name  */
				$message = _x( 'Unknown widget type named "%2$s" in "%3$s" updated', '2: Title, 3: Sidebar Name', 'stalkfish' );
			} else {
				/* translators: %4$s: widget ID, %3$s: sidebar name */
				$message = _x( '%4$s widget in "%3$s" updated', '4: Widget ID, 3: Sidebar Name', 'stalkfish' );
			}

			$args = array(
				'name'         => $update['name'],
				'title'        => $update['title'],
				'sidebar_name' => $update['sidebar_name'],
				'widget_id'    => $update['widget_id'],
			);

			$this->log(
				array(
					'message' => vsprintf( $message, $args ),
					'meta'    => $args,
					'context' => $update['sidebar_id'],
					'action'  => 'updated',
					'link'    => get_admin_url() . 'widgets.php',
				)
			);
		}
	}

	/**
	 * Get widget title.
	 *
	 * @param string $widget_id Widget instance ID.
	 *
	 * @return string
	 */
	public function get_widget_title( $widget_id ) {
		$instance = $this->get_widget_instance( $widget_id );

		return ! empty( $instance['title'] ) ? $instance['title'] : null;
	}

	/**
	 * Get widget name.
	 *
	 * @param string $widget_id Widget instance ID.
	 *
	 * @return string|null
	 */
	public function get_widget_name( $widget_id ) {
		$widget_obj = $this->get_widget_object( $widget_id );

		return $widget_obj ? $widget_obj->name : null;
	}

	/**
	 * Parses widget instance ID and widget type data.
	 *
	 * @param string $widget_id Widget instance ID.
	 *
	 * @return array|null
	 */
	public function parse_widget_id( $widget_id ) {
		if ( preg_match( '/^(.+)-(\d+)$/', $widget_id, $matches ) ) {
			return array(
				'id_base'       => $matches[1],
				'widget_number' => intval( $matches[2] ),
			);
		} else {
			return null;
		}
	}

	/**
	 * Get widget object.
	 *
	 * @param string $widget_id Widget instance ID.
	 *
	 * @return \WP_Widget|null
	 */
	public function get_widget_object( $widget_id ) {
		global $wp_widget_factory;

		$parsed_widget_id = $this->parse_widget_id( $widget_id );

		if ( ! $parsed_widget_id ) {
			return null;
		}

		$id_base = $parsed_widget_id['id_base'];

		$id_base_to_widget_class_map = array_combine(
			wp_list_pluck( $wp_widget_factory->widgets, 'id_base' ),
			array_keys( $wp_widget_factory->widgets )
		);

		if ( ! isset( $id_base_to_widget_class_map[ $id_base ] ) ) {
			return null;
		}

		return $wp_widget_factory->widgets[ $id_base_to_widget_class_map[ $id_base ] ];
	}

	/**
	 * Get widget instance settings.
	 *
	 * @param string $widget_id Widget ID.
	 *
	 * @return array|null Widget instance
	 */
	public function get_widget_instance( $widget_id ) {
		$instance         = null;
		$parsed_widget_id = $this->parse_widget_id( $widget_id );
		$widget_obj       = $this->get_widget_object( $widget_id );

		if ( $widget_obj && $parsed_widget_id ) {
			$settings     = $widget_obj->get_settings();
			$multi_number = $parsed_widget_id['widget_number'];

			if ( isset( $settings[ $multi_number ] ) && ! empty( $settings[ $multi_number ]['title'] ) ) {
				$instance = $settings[ $multi_number ];
			}
		} else {
			$potential_instance = get_option( "widget_{$widget_id}" );

			if ( ! empty( $potential_instance ) && ! empty( $potential_instance['title'] ) ) {
				$instance = $potential_instance;
			}
		}

		return $instance;
	}

	/**
	 * Get the sidebar of a certain widget.
	 *
	 * @param string $widget_id Widget id.
	 *
	 * @return string Sidebar id
	 */
	public function get_widget_sidebar_id( $widget_id ) {
		$sidebars_widgets = get_option( 'sidebars_widgets', array() );

		unset( $sidebars_widgets['array_version'] );

		foreach ( $sidebars_widgets as $sidebar_id => $widget_ids ) {
			if ( in_array( $widget_id, $widget_ids, true ) ) {
				return $sidebar_id;
			}
		}

		return 'orphaned_widgets';
	}
}
