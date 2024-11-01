<?php
/**
 * Stalkfish Settings Class
 *
 * @package Stalkfish
 */

namespace Stalkfish\Settings;

use Stalkfish\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin_Settings Class.
 */
class Admin_Settings {

	const OPTION_KEY = 'sf_options';
	/**
	 * Setting pages.
	 *
	 * @var array
	 */
	private static $settings = array();

	/**
	 * Error messages.
	 *
	 * @var array
	 */
	private static $errors = array();

	/**
	 * Update messages.
	 *
	 * @var array
	 */
	private static $messages = array();

	/**
	 * Include the settings page classes.
	 */
	public static function get_settings_pages() {
		if ( empty( self::$settings ) ) {
			$settings = array();

			$settings[] = include 'class-general.php';
			$settings[] = include 'class-exclude.php';

			self::$settings = apply_filters( 'sf_get_settings_pages', $settings );
		}

		return self::$settings;
	}

	/**
	 * Save the settings.
	 */
	public static function save() {
		global $current_tab;

		check_admin_referer( 'sf-settings' );

		// Trigger actions.
		do_action( 'sf_settings_save_' . $current_tab );
		do_action( 'sf_update_options_' . $current_tab );
		do_action( 'sf_update_options' );

		self::add_message( __( 'Your settings have been saved.', 'stalkfish' ) );

		// Clear any unwanted data and flush rules.
		update_option( 'sf_queue_flush_rewrite_rules', 'yes' );

		do_action( 'sf_settings_saved' );
	}

	/**
	 * Add a message.
	 *
	 * @param string $text Message.
	 */
	public static function add_message( $text ) {
		self::$messages[] = $text;
	}

	/**
	 * Add an error.
	 *
	 * @param string $text Message.
	 */
	public static function add_error( $text ) {
		self::$errors[] = $text;
	}

	/**
	 * Output messages + errors.
	 */
	public static function show_messages() {
		if ( count( self::$errors ) > 0 ) {
			foreach ( self::$errors as $error ) {
				echo '<div id="message" class="error inline"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
			}
		} elseif ( count( self::$messages ) > 0 ) {
			foreach ( self::$messages as $message ) {
				echo '<div id="message" class="updated inline"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
			}
		}
	}

	/**
	 * Settings page.
	 *
	 * Handles the display of the main Stalkfish settings page in admin.
	 */
	public static function output() {
		global $current_section, $current_tab;

		do_action( 'sf_settings_start' );

		// Select2.
		wp_enqueue_script( 'sf-select2', STALKFISH_ASSETS_URL . '/js/select2/select2.full.js', array( 'jquery' ), '4.1.0-rc.0', true );
		wp_enqueue_style( 'sf-select2', STALKFISH_ASSETS_URL . '/css/select2/select2.css', array(), '4.1.0-rc.0' );

		wp_enqueue_style( 'sf_settings', STALKFISH_ASSETS_URL . '/css/settings.css', array(), filemtime( STALKFISH_PLUGIN_DIR_PATH . 'assets/css/settings.css' ) );

		wp_enqueue_script( 'wp-util' );
		wp_enqueue_script(
			'sf_settings',
			STALKFISH_ASSETS_URL . '/js/settings.js',
			array(
				'jquery',
				'wp-i18n',
				'wp-util',
				'sf-select2'
			),
			filemtime( STALKFISH_PLUGIN_DIR_PATH . 'assets/js/settings.js' ),
			true
		);

		// Get tabs for the settings page.
		$tabs = apply_filters( 'sf_settings_tabs_array', array() );

		include dirname( __FILE__ ) . '/views/html-settings.php';
	}

	/**
	 * Get a setting from the settings API.
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public static function get_option( $option_name = false, $default = '' ) {
		$options = get_option( self::OPTION_KEY );

		if ( ! $option_name ) {
			return $options;
		}
		// Array value.
		if ( strstr( $option_name, '[' ) ) {
			parse_str( $option_name, $option_array );

			// Option name is first key.
			$option_name = current( array_keys( $option_array ) );

			// Get value.
			if ( empty( $options[ $option_name ] ) ) {
				$options[ $option_name ] = '';
			}
			$option_values = $options[ $option_name ];

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}
		} else {
			// Single value.
			if ( ! isset( $options[ $option_name ] ) || ( isset( $options[ $option_name ] ) && '' === $options[ $option_name ] ) ) {
				$options[ $option_name ] = null;
			}

			$option_value = $options[ $option_name ];
		}

		if ( ! is_null( $option_value ) && ! is_array( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return ( null === $option_value ) ? $default : $option_value;
	}

	/**
	 * Output admin fields.
	 *
	 * Loops though the Stalkfish options array and outputs each field.
	 *
	 * @param array[] $options Opens array to output.
	 */
	public static function output_fields( $options ) {
		foreach ( $options as $value ) {
			if ( ! isset( $value['type'] ) ) {
				continue;
			}
			if ( ! isset( $value['id'] ) ) {
				$value['id'] = '';
			}
			if ( ! isset( $value['title'] ) ) {
				$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
			}
			if ( ! isset( $value['class'] ) ) {
				$value['class'] = '';
			}
			if ( ! isset( $value['to'] ) ) {
				$value['to'] = '';
			}
			if ( ! isset( $value['default'] ) ) {
				$value['default'] = '';
			}
			if ( ! isset( $value['desc'] ) ) {
				$value['desc'] = '';
			}
			if ( ! isset( $value['desc_tip'] ) ) {
				$value['desc_tip'] = false;
			}
			if ( ! isset( $value['placeholder'] ) ) {
				$value['placeholder'] = '';
			}
			if ( ! isset( $value['suffix'] ) ) {
				$value['suffix'] = '';
			}
			if ( ! isset( $value['switch'] ) ) {
				$value['switch'] = false;
			}
			if ( ! isset( $value['value'] ) ) {
				$value['value'] = self::get_option( $value['id'], $value['default'] );
			}

			// Custom attribute handling.
			$custom_attributes = array();

			if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
				foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}

			// Description handling.
			$field_description = self::get_field_description( $value );
			$description       = $field_description['description'];
			$tooltip_html      = $field_description['tooltip_html'];

			// Switch based on type.
			switch ( $value['type'] ) {

				// Section Titles.
				case 'title':
					if ( ! empty( $value['title'] ) ) {
						echo '<h2 class="title ' . esc_attr( $value['class'] ) . '">' . esc_html( $value['title'] ) . '</h2>';
					}
					if ( ! empty( $value['desc'] ) ) {
						echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
						echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
						echo '</div>';
					}
					echo '<table class="form-table">' . "\n\n";
					if ( ! empty( $value['id'] ) ) {
						do_action( 'sf_settings_' . sanitize_title( $value['id'] ) );
					}
					break;

				// Section Ends.
				case 'sectionend':
					if ( ! empty( $value['id'] ) ) {
						do_action( 'sf_settings_' . sanitize_title( $value['id'] ) . '_end' );
					}
					echo '</table>';
					if ( ! empty( $value['id'] ) ) {
						do_action( 'sf_settings_' . sanitize_title( $value['id'] ) . '_after' );
					}
					break;
				// Standard text inputs'.
				case 'text':
					$option_value = $value['value'];

					?>
					<tr>
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
								<?php
								echo wp_kses_post( $tooltip_html );
								?>
							</label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="<?php echo esc_attr( $value['type'] ); ?>"
									value="<?php echo esc_attr( $option_value ); ?>"
									class="regular-text <?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php
									echo esc_attr( implode( ' ', $custom_attributes ) );
									?>
							/><?php echo esc_html( $value['suffix'] ); ?> <?php
							echo wp_kses_post( $description );
							?>
						</td>
					</tr>
					<?php
					break;
				case 'action':
					$option_value = $value['value'];
					echo '<table class="form-table sf-action">' . "\n\n";
					if ( ! empty( $value['id'] ) ) {
						do_action( 'sf_settings_' . sanitize_title( $value['id'] ) );
					}

					if ( ! empty( $option_value ) ) {
						?>
						<tr id="<?php echo esc_attr( $value['id'] ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							</th>
							<td class="forminwp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<?php if ( ! empty( $value['desc'] ) ) : ?>
									<span class="description"><?php echo esc_html( $value['desc'] ); ?></span>
								<?php endif; ?>
								<?php wp_nonce_field( 'sf_action_nonce', 'sf_action_nonce' ); ?>
								<input type="submit" id="sf-mock-data"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									name="<?php echo esc_attr( $value['id'] ); ?>"
									value="<?php echo esc_attr( $option_value ); ?>"/>
							</td>
						</tr>
						<?php
					}
					break;
				// Radio inputs.
				case 'radio':
					$option_value = $value['value'];
					?>
					<tr>
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?>
								<?php
								echo wp_kses_post( $tooltip_html );
								?>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<fieldset>
								<?php echo esc_html( $description ); ?>
								<ul>
									<?php
									foreach ( $value['options'] as $key => $val ) {
										?>
										<li>
											<label><input
														name="<?php echo esc_attr( $value['id'] ); ?>"
														value="<?php echo esc_attr( $key ); ?>"
														type="radio"
														class="<?php echo esc_attr( $value['class'] ); ?>"
														<?php echo esc_attr( implode( ' ', $custom_attributes ) ); ?>
														<?php checked( $key, $option_value ); ?>
												/> <?php echo esc_html( $val ); ?></label>
										</li>
										<?php
									}
									?>
								</ul>
							</fieldset>
						</td>
					</tr>
					<?php
					break;
				// Checkbox input.
				case 'checkbox':
					$option_value     = $value['value'];
					$visibility_class = array();

					if ( ! isset( $value['hide_if_checked'] ) ) {
						$value['hide_if_checked'] = false;
					}
					if ( ! isset( $value['show_if_checked'] ) ) {
						$value['show_if_checked'] = false;
					}
					if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
						$visibility_class[] = 'hidden_option';
					}
					if ( 'option' === $value['hide_if_checked'] ) {
						$visibility_class[] = 'hide_options_if_checked';
					}
					if ( 'option' === $value['show_if_checked'] ) {
						$visibility_class[] = 'show_options_if_checked';
					}

					if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
						?>
							<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
								<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
								<td class="forminp forminp-checkbox">
									<fieldset>
						<?php
					} else {
						?>
							<fieldset class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
						<?php
					}

					if ( ! empty( $value['title'] ) ) {
						?>
							<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
						<?php
					}

					?>
						<label for="<?php echo esc_attr( $value['id'] ); ?>">
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="checkbox"
								class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
								value="1"
								<?php checked( $option_value, true ); ?>
								<?php echo implode( ' ', $custom_attributes ); // phpcs:ignore ?>
							/> <?php echo $description; // phpcs:ignore ?>
							<?php if ( $value['switch'] ) { ?>
								<span><?php esc_html_e( 'Toggle', 'stalkfish' ); ?></span>
							<?php } ?>
						</label> <?php echo $tooltip_html; // phpcs:ignore ?>
					<?php

					if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
						?>
									</fieldset>
								</td>
							</tr>
						<?php
					} else {
						?>
							</fieldset>
						<?php
					}
					break;
				case 'rules':
					$option_value = $value['value'];

					$exclude_rows = array();
					// Account for when no rules have been added yet.
					if ( ! is_array( $option_value ) ) {
						$option_value = array();
					}

					// Prepend an empty row.
					$option_value['rows'] = ( isset( $option_value['rows'] ) ? $option_value['rows'] : array() ) + array( 'helper' => '' );
					?>
					<p><?php echo wp_kses_post( $description ); ?></p>
					<div class="tablenav top">
						<input type="button" class="button" id="exclude_new_rule" value="&#43; <?php echo esc_html__( 'Add New Rule', 'stalkfish' ) ?>" />
					</div>
					<table class="wp-list-table widefat fixed sf-exclude-list">
						<thead>
							<tr>
								<td scope="col" class="manage-column column-cb check-column"><input class="cb-select" type="checkbox" /></td>
								<th scope="col" class="manage-column"><?php echo esc_html__( 'Pipe or Context', 'stalkfish' ) ?></th>
								<th scope="col" class="manage-column"><?php echo esc_html__( 'Action', 'stalkfish' ) ?></th>
								<th scope="col" class="manage-column"><?php echo esc_html__( 'Author or Role', 'stalkfish' ) ?></th>
								<th scope="col" class="actions-column manage-column"><span class="hidden"><?php esc_html__( 'Filters', 'stalkfish' ) ?></span></th>
							</tr>
						</thead>
						<tbody>
							<tr class="no-items hidden"><td class="colspanchange" colspan="5"><?php echo esc_html__( 'No rules found.', 'stalkfish' ) ?></td></tr>

							<?php
							foreach ( $option_value['rows'] as $opt_key => $opt_value ) {
								// Prepare values.
								$pipe           = isset( $option_value['pipe'][ $opt_key ] ) ? $option_value['pipe'][ $opt_key ] : '';
								$context        = isset( $option_value['context'][ $opt_key ] ) ? $option_value['context'][ $opt_key ] : '';
								$action         = isset( $option_value['action'][ $opt_key ] ) ? $option_value['action'][ $opt_key ] : '';
								$author_or_role = isset( $option_value['author_or_role'][ $opt_key ] ) ? $option_value['author_or_role'][ $opt_key ] : '';

								// Author or Role dropdown menu.
								$author_or_role_values   = array();
								$author_or_role_selected = array();

								foreach ( stalkfish_get_all_roles() as $role_id => $role ) {
									$args  = array(
										'value' => $role_id,
										'text'  => $role,
									);
									$count = isset( $users['avail_roles'][ $role_id ] ) ? $users['avail_roles'][ $role_id ] : 0;

									if ( ! empty( $count ) ) {
										/* translators: %d: Number of users */
										$args['user_count'] = sprintf( _n( '%d user', '%d users', absint( $count ), 'stalkfish' ), absint( $count ) );
									}

									if ( $role_id === $author_or_role ) {
										$author_or_role_selected['value'] = $role_id;
										$author_or_role_selected['text']  = $role;
									}

									$author_or_role_values[] = $args;
								}

								if ( empty( $author_or_role_selected ) && is_numeric( $author_or_role ) ) {
									$user                    = new \WP_User( $author_or_role );
									$display_name            = ( 0 === $user->ID ) ? esc_html__( 'N/A', 'stalkfish' ) : $user->display_name;
									$author_or_role_selected = array(
										'value' => $user->ID,
										'text'  => $display_name,
									);
									$author_or_role_values[] = $author_or_role_selected;
								}

								$author_or_role_input = self::get_select2_field(
									'select2',
									array(
										'name'    => esc_attr( sprintf( '%1$s[%2$s][]', esc_attr( $value['id'] ), 'author_or_role' ) ),
										'options' => $author_or_role_values,
										'classes' => 'author_or_role',
										'data'    => array(
											'placeholder'   => esc_html__( 'Any Author or Role', 'stalkfish' ),
											'nonce'         => esc_attr( wp_create_nonce( 'sf_get_users' ) ),
											'selected-id'   => isset( $author_or_role_selected['value'] ) ? esc_attr( $author_or_role_selected['value'] ) : '',
											'selected-text' => isset( $author_or_role_selected['text'] ) ? esc_attr( $author_or_role_selected['text'] ) : '',
										),
									)
								);

								// Context dropdown menu.
								$context_values = array();

								$triggers = stalkfish_get_instance()->pipes->triggers;

								foreach ( $triggers as $context_id => $context_data ) {
									if ( is_array( $context_data ) ) {
										$child_values = array();
										if ( isset( $context_data['contexts'] ) && is_array( $context_data['contexts'] ) ) {
											$child_values = array();
											foreach ( $context_data['contexts'] as $child_id ) {
												$child_values[] = array(
													'value'  => $context_id . '-' . $child_id,
													'text'   => $child_id,
													'parent' => $context_id,
												);
											}
										}
										$context_values[] = array(
											'value'    => $context_id,
											'text'     => ucfirst( $context_id ),
											'children' => $child_values,
										);
									} else {
										$context_values[] = array(
											'value' => $context_id,
											'text'  => $context_data,
										);
									}
								}

								$pipe_or_context_input = self::get_select2_field(
										'select2',
										array(
											'name'    => esc_attr( sprintf( '%1$s[%2$s][]', esc_attr( $value['id'] ), 'pipe_or_context' ) ),
											'options' => $context_values,
											'classes' => 'pipe_or_context',
											'data'    => array(
												'group'       => 'pipe',
												'placeholder' => __( 'Any Context', 'stalkfish' ),
											),
										)
								);

								$pipe_input = self::get_select2_field(
									'hidden',
									array(
										'name'    => esc_attr( sprintf( '%1$s[%2$s][]', esc_attr( $value['id'] ), 'pipe' ) ),
										'value'   => $pipe,
										'classes' => 'pipe',
									)
								);

								$context_input = self::get_select2_field(
									'hidden',
									array(
										'name'    => esc_attr( sprintf( '%1$s[%2$s][]', esc_attr( $value['id'] ), 'context' ) ),
										'value'   => $context,
										'classes' => 'context',
									)
								);

								// Action dropdown menu.
								$action_values = array(
									array(
										'value' => 'all',
										'text'  => 'Any Action',
									)
								);

								$action_input = self::get_select2_field(
									'select2',
									array(
										'name'    => esc_attr( sprintf( '%1$s[%2$s][]', esc_attr( $value['id'] ), 'action' ) ),
										'value'   => $action,
										'options' => $action_values,
										'classes' => 'action',
										'data'    => array(
											'placeholder' => __( 'Any Action', 'stalkfish' ),
										),
									)
								);


								// Hidden helper input.
								$helper_input = sprintf(
									'<input type="hidden" name="%1$s[%2$s][]" value="" />',
									esc_attr( $value['id'] ),
									'rows'
								);

								$exclude_rows[] = sprintf(
										'<tr class="%1$s %2$s">
												<th scope="row" class="check-column">%3$s %4$s</th>
												<td>%5$s %6$s %7$s</td>
												<td>%8$s</td>
												<td>%9$s</td>
												<th scope="row" class="actions-column">%10$s</th>
											</tr>',
										( 0 !== (int) $opt_key % 2 ) ? 'alternate' : '',
										( 'helper' === (string) $opt_key ) ? 'hidden helper' : '',
										'<input class="cb-select" type="checkbox" />',
										$helper_input,
										$pipe_or_context_input,
										$pipe_input,
										$context_input,
										$action_input,
										$author_or_role_input,
										'<a href="#" class="exclude_rules_remove_rule_row">Delete</a>'
								);
							}

							echo implode( '', $exclude_rows );
							?>
						</tbody>
						<tfoot>
							<tr>
								<td scope="col" class="manage-column column-cb check-column"><input class="cb-select" type="checkbox" /></td>
								<th scope="col" class="manage-column"><?php echo esc_html__( 'Pipe or Context', 'stalkfish' ) ?></th>
								<th scope="col" class="manage-column"><?php echo esc_html__( 'Action', 'stalkfish' ) ?></th>
								<th scope="col" class="manage-column"><?php echo esc_html__( 'Author or Role', 'stalkfish' ) ?></th>
								<th scope="col" class="actions-column manage-column"><span class="hidden"><?php esc_html__( 'Filters', 'stalkfish' ) ?></span></th>
							</tr>
						</tfoot>
					</table>
					<div class="tablenav top">
						<input type="button" class="button" id="exclude_remove_rules" value="<?php echo esc_html__( 'Delete Selected Rules', 'stalkfish' ) ?>" />
					</div>
					<?php
					break;

				// Default: run an action.
				default:
					do_action( 'sf_admin_field_' . $value['type'], $value );
					break;
			}
		}
	}

	/**
	 * Helper function to get the formatted description and tip HTML for a
	 * given form field. Plugins can call this when implementing their own custom
	 * settings types.
	 *
	 * @param array $value The form field value array.
	 *
	 * @return array The description and tip as a 2 element array.
	 */
	public static function get_field_description( $value ) {
		$description  = '';
		$tooltip_html = '';

		if ( true === $value['desc_tip'] ) {
			$tooltip_html = $value['desc'];
		} elseif ( ! empty( $value['desc_tip'] ) ) {
			$description  = $value['desc'];
			$tooltip_html = $value['desc_tip'];
		} elseif ( ! empty( $value['desc'] ) ) {
			$description = $value['desc'];
		}

		if ( $description && in_array( $value['type'], array( 'checkbox' ), true ) ) {
			$description = wp_kses_post( $description );
		} elseif ( $description ) {
			$description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
		}

		if ( $tooltip_html && in_array( $value['type'], array( 'checkbox' ), true ) ) {
			$tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
		} elseif ( $tooltip_html ) {
			$tooltip_html = wp_kses_post( $tooltip_html );
		}

		return array(
			'description'  => $description,
			'tooltip_html' => $tooltip_html,
		);
	}

	/**
	 * Renders Select2 & helper field.
	 *
	 * @param array  $args The options for the field type.
	 *
	 * @return string
	 */
	public static function get_select2_field( $field_type, $args ) {
		$args = wp_parse_args(
				$args,
				array(
						'name'        => '',
						'value'       => '',
						'options'     => array(),
						'description' => '',
						'classes'     => '',
						'data'        => array(),
						'multiple'    => false,
				)
		);

		if ( 'hidden' !== $field_type ) {
			$values = array();
			$multiple = ( $args['multiple'] ) ? 'multiple ' : '';

			$output = sprintf(
					'<select name="%1$s" id="%1$s" class="select2-select %2$s" %3$s%4$s>',
					esc_attr( $args['name'] ),
					esc_attr( $args['classes'] ),
					self::prepare_data_attributes_string( $args['data'] ),
					$multiple
			);

			if ( array_key_exists( 'placeholder', $args['data'] ) && ! $multiple ) {
				$output .= '<option value=""></option>';
			}

			foreach ( $args['options'] as $parent ) {
				$parent = wp_parse_args(
						$parent,
						array(
								'value'    => '',
								'text'     => '',
								'children' => array(),
						)
				);
				if ( empty( $parent['value'] ) ) {
					continue;
				}
				if ( is_array( $args['value'] ) ) {
					$selected = selected( in_array( $parent['value'], $args['value'], true ), true, false );
				} else {
					$selected = selected( $args['value'], $parent['value'], false );
				}
				$output   .= sprintf(
						'<option class="parent" value="%1$s" %3$s>%2$s</option>',
						$parent['value'],
						$parent['text'],
						$selected
				);
				$values[] = $parent['value'];
				if ( ! empty( $parent['children'] ) ) {
					foreach ( $parent['children'] as $child ) {
						$output   .= sprintf(
								'<option class="child" value="%1$s" %3$s>%2$s</option>',
								$child['value'],
								$child['text'],
								selected( $args['value'], $child['value'], false )
						);
						$values[] = $child['value'];
					}
					$output .= '</optgroup>';
				}
			}

			$selected_values = explode( ',', $args['value'] );
			foreach ( $selected_values as $selected_value ) {
				if ( ! empty( $selected_value ) && ! in_array( $selected_value, array_map( 'strval', $values ), true ) ) {
					$output .= sprintf(
							'<option value="%1$s" %2$s>%1$s</option>',
							$selected_value,
							selected( true, true, false )
					);
				}
			}

			$output .= '</select>';
		} else {
			$output = sprintf(
					'<input type="hidden" name="%1$s" id="%1$s" class="%2$s" value="%3$s" />',
					esc_attr( $args['name'] ),
					esc_attr( $args['classes'] ),
					esc_attr( $args['value'] )
			);
		}

		return $output;
	}

	/**
	 * Prepares string with HTML data attributes
	 *
	 * @param array $data List of key/value data pairs to prepare.
	 * @return string
	 */
	public static function prepare_data_attributes_string( $data ) {
		$output = '';
		foreach ( $data as $key => $value ) {
			$key     = 'data-' . esc_attr( $key );
			$output .= $key . '="' . esc_attr( $value ) . '" ';
		}
		return $output;
	}

	/**
	 * Save admin fields.
	 *
	 * Loops though the Stalkfish options array and outputs each field.
	 *
	 * @param array $options Options array to output.
	 * @param array $data Optional. Data to use for saving. Defaults to $_POST.
	 *
	 * @return bool
	 */
	public static function save_fields( $options, $data = null ) {
		if ( is_null( $data ) ) {
			$data = $_POST; // phpcs:ignore
		}
		if ( empty( $data ) ) {
			return false;
		}

		// Options to update will be stored here and saved later.
		$update_options   = array();
		$autoload_options = array();

		// Loop options and get values to save.
		foreach ( $options as $option ) {
			if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) || ( isset( $option['is_option'] ) && false === $option['is_option'] ) ) {
				continue;
			}

			// Get posted value.
			if ( strstr( $option['id'], '[' ) ) {
				parse_str( $option['id'], $option_name_array );
				$option_name  = current( array_keys( $option_name_array ) );
				$setting_name = key( $option_name_array[ $option_name ] );
				$raw_value    = isset( $data[ $option_name ][ $setting_name ] ) ? wp_unslash( $data[ $option_name ][ $setting_name ] ) : null;
			} else {
				$option_name  = $option['id'];
				$setting_name = '';
				$raw_value    = isset( $data[ $option['id'] ] ) ? wp_unslash( $data[ $option['id'] ] ) : null;
			}

			// Format the value based on option type.
			switch ( $option['type'] ) {
				case 'checkbox':
					$value = '1' === $raw_value || true === $raw_value ? true : false;
					break;
				case 'rules':
					$value = is_array( $raw_value ) ? $raw_value : array();
					break;
				default:
					$value = sf_clean( $raw_value );
					break;
			}

			/**
			 * Sanitize the value of an option.
			 */
			$value = apply_filters( 'sf_admin_settings_sanitize_option', $value, $option, $raw_value );

			/**
			 * Sanitize the value of an option by option name.
			 */
			$value = apply_filters( "sf_admin_settings_sanitize_option_$option_name", $value, $option, $raw_value );

			if ( is_null( $value ) ) {
				continue;
			}

			// Check if option is an array and handle that differently to single values.
			if ( $option_name && $setting_name ) {
				if ( ! isset( $update_options[ $option_name ] ) ) {
					$update_options[ $option_name ] = get_option( $option_name, array() );
				}
				if ( ! is_array( $update_options[ $option_name ] ) ) {
					$update_options[ $option_name ] = array();
				}
				$update_options[ $option_name ][ $setting_name ] = $value;
			} else {
				$update_options[ $option_name ] = $value;
			}

			$autoload_options[ $option_name ] = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;

			/**
			 * Fire an action before saved.
			 */
			do_action( 'sf_update_option', $option );
		}

		// Save all options in our array.
		foreach ( $update_options as $name => $value ) {
			Options::get_instance()->set( $name, $value );
		}

		return true;
	}
}
