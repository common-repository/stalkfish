<?php
/**
 * Class to record settings events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Settings_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Settings_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'settings';

	/**
	 * Labels used for WordPress Settings
	 *
	 * @var array
	 */
	public $labels = array();

	/**
	 * Available hooks.
	 *
	 * @var array
	 */
	public $hooks = array(
		'allowed_options',
		'update_option',
		'update_site_option',
		'update_option_permalink_structure',
		'update_option_category_base',
		'update_option_tag_base',
	);

	/**
	 * Available contexts and their actions for current pipe.
	 *
	 * @var array
	 */
	public $triggers = array(
		'contexts' => array(
			'settings',
			'theme_mods',
			'sf_options',
		),
		'actions'  => array(
			'updated',
		),
	);

	/**
	 * Register pipe in the WP Frontend
	 *
	 * @var bool
	 */
	public $register_frontend = false;

	/**
	 * Register all context hooks
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		$this->labels = array(
			// General.
			'options-general'    => array(
				'blogname'           => esc_html__( 'Site Title', 'stalkfish' ),
				'blogdescription'    => esc_html__( 'Tagline', 'stalkfish' ),
				'gmt_offset'         => esc_html__( 'Timezone', 'stalkfish' ),
				'admin_email'        => esc_html__( 'E-mail Address', 'stalkfish' ),
				'new_admin_email'    => esc_html__( 'E-mail Address', 'stalkfish' ),
				'siteurl'            => esc_html__( 'WordPress Address (URL)', 'stalkfish' ),
				'home'               => esc_html__( 'Site Address (URL)', 'stalkfish' ),
				'users_can_register' => esc_html__( 'Membership', 'stalkfish' ),
				'default_role'       => esc_html__( 'New User Default Role', 'stalkfish' ),
				'timezone_string'    => esc_html__( 'Timezone', 'stalkfish' ),
				'date_format'        => esc_html__( 'Date Format', 'stalkfish' ),
				'time_format'        => esc_html__( 'Time Format', 'stalkfish' ),
				'start_of_week'      => esc_html__( 'Week Starts On', 'stalkfish' ),
			),
			// Writing.
			'options-writing'    => array(
				'use_smilies'            => esc_html__( 'Formatting', 'stalkfish' ),
				'use_balanceTags'        => esc_html__( 'Formatting', 'stalkfish' ),
				'default_category'       => esc_html__( 'Default Post Category', 'stalkfish' ),
				'default_post_format'    => esc_html__( 'Default Post Format', 'stalkfish' ),
				'mailserver_url'         => esc_html__( 'Mail Server', 'stalkfish' ),
				'mailserver_login'       => esc_html__( 'Login Name', 'stalkfish' ),
				'mailserver_pass'        => esc_html__( 'Password', 'stalkfish' ),
				'default_email_category' => esc_html__( 'Default Mail Category', 'stalkfish' ),
				'default_link_category'  => esc_html__( 'Default Link Category', 'stalkfish' ),
				'ping_sites'             => esc_html__( 'Update Services', 'stalkfish' ),
			),
			// Reading.
			'options-reading'    => array(
				'show_on_front'   => esc_html__( 'Home page displays', 'stalkfish' ),
				'page_on_front'   => esc_html__( 'Front page displays', 'stalkfish' ),
				'page_for_posts'  => esc_html__( 'Blog posts page displays', 'stalkfish' ),
				'posts_per_page'  => esc_html__( 'Blog pages show at most', 'stalkfish' ),
				'posts_per_rss'   => esc_html__( 'Syndication feeds show the most recent', 'stalkfish' ),
				'rss_use_excerpt' => esc_html__( 'For each article in a feed, show', 'stalkfish' ),
				'blog_public'     => esc_html__( 'Search Engine Visibility', 'stalkfish' ),
			),
			// Discussion.
			'options-discussion' => array(
				'default_pingback_flag'        => esc_html__( 'Default article settings', 'stalkfish' ),
				'default_ping_status'          => esc_html__( 'Default article settings', 'stalkfish' ),
				'default_comment_status'       => esc_html__( 'Default article settings', 'stalkfish' ),
				'require_name_email'           => esc_html__( 'Other comment settings', 'stalkfish' ),
				'comment_registration'         => esc_html__( 'Other comment settings', 'stalkfish' ),
				'close_comments_for_old_posts' => esc_html__( 'Other comment settings', 'stalkfish' ),
				'close_comments_days_old'      => esc_html__( 'Other comment settings', 'stalkfish' ),
				'thread_comments'              => esc_html__( 'Other comment settings', 'stalkfish' ),
				'thread_comments_depth'        => esc_html__( 'Other comment settings', 'stalkfish' ),
				'page_comments'                => esc_html__( 'Other comment settings', 'stalkfish' ),
				'comments_per_page'            => esc_html__( 'Other comment settings', 'stalkfish' ),
				'default_comments_page'        => esc_html__( 'Other comment settings', 'stalkfish' ),
				'comment_order'                => esc_html__( 'Other comment settings', 'stalkfish' ),
				'comments_notify'              => esc_html__( 'E-mail me whenever', 'stalkfish' ),
				'moderation_notify'            => esc_html__( 'E-mail me whenever', 'stalkfish' ),
				'comment_moderation'           => esc_html__( 'Before a comment appears', 'stalkfish' ),
				'comment_whitelist'            => esc_html__( 'Before a comment appears', 'stalkfish' ),
				'comment_max_links'            => esc_html__( 'Comment Moderation', 'stalkfish' ),
				'moderation_keys'              => esc_html__( 'Comment Moderation', 'stalkfish' ),
				'blacklist_keys'               => esc_html__( 'Comment Blacklist', 'stalkfish' ),
				'show_avatars'                 => esc_html__( 'Show Avatars', 'stalkfish' ),
				'avatar_rating'                => esc_html__( 'Maximum Rating', 'stalkfish' ),
				'avatar_default'               => esc_html__( 'Default Avatar', 'stalkfish' ),
			),
			// Media.
			'options-media'      => array(
				'thumbnail_size_w'              => esc_html__( 'Thumbnail size', 'stalkfish' ),
				'thumbnail_size_h'              => esc_html__( 'Thumbnail size', 'stalkfish' ),
				'thumbnail_crop'                => esc_html__( 'Thumbnail size', 'stalkfish' ),
				'medium_size_w'                 => esc_html__( 'Medium size', 'stalkfish' ),
				'medium_size_h'                 => esc_html__( 'Medium size', 'stalkfish' ),
				'large_size_w'                  => esc_html__( 'Large size', 'stalkfish' ),
				'large_size_h'                  => esc_html__( 'Large size', 'stalkfish' ),
				'uploads_use_yearmonth_folders' => esc_html__( 'Uploading Files', 'stalkfish' ),
			),
			// Permalinks.
			'options-permalink'  => array(
				'permalink_structure' => esc_html__( 'Permalink', 'stalkfish' ),
				'category_base'       => esc_html__( 'Category base', 'stalkfish' ),
				'tag_base'            => esc_html__( 'Tag base', 'stalkfish' ),
			),
			// Network.
			'options-network'    => array(
				'registrationnotification'    => esc_html__( 'Registration notification', 'stalkfish' ),
				'registration'                => esc_html__( 'Allow new registrations', 'stalkfish' ),
				'add_new_users'               => esc_html__( 'Add New Users', 'stalkfish' ),
				'menu_items'                  => esc_html__( 'Enable administration menus', 'stalkfish' ),
				'upload_space_check_disabled' => esc_html__( 'Site upload space check', 'stalkfish' ),
				'blog_upload_space'           => esc_html__( 'Site upload space', 'stalkfish' ),
				'upload_filetypes'            => esc_html__( 'Upload file types', 'stalkfish' ),
				'site_name'                   => esc_html__( 'Network Title', 'stalkfish' ),
				'first_post'                  => esc_html__( 'First Post', 'stalkfish' ),
				'first_page'                  => esc_html__( 'First Page', 'stalkfish' ),
				'first_comment'               => esc_html__( 'First Comment', 'stalkfish' ),
				'first_comment_url'           => esc_html__( 'First Comment URL', 'stalkfish' ),
				'first_comment_author'        => esc_html__( 'First Comment Author', 'stalkfish' ),
				'welcome_email'               => esc_html__( 'Welcome Email', 'stalkfish' ),
				'welcome_user_email'          => esc_html__( 'Welcome User Email', 'stalkfish' ),
				'fileupload_maxk'             => esc_html__( 'Max upload file size', 'stalkfish' ),
				'global_terms_enabled'        => esc_html__( 'Terms Enabled', 'stalkfish' ),
				'illegal_names'               => esc_html__( 'Banned Names', 'stalkfish' ),
				'limited_email_domains'       => esc_html__( 'Limited Email Registrations', 'stalkfish' ),
				'banned_email_domains'        => esc_html__( 'Banned Email Domains', 'stalkfish' ),
				'WPLANG'                      => esc_html__( 'Network Language', 'stalkfish' ),
				'blog_count'                  => esc_html__( 'Blog Count', 'stalkfish' ),
				'admin_email'                 => esc_html__( 'Admin Email', 'stalkfish' ),
				'new_admin_email'             => esc_html__( 'New Admin Email', 'stalkfish' ),
				'allowedthemes'               => esc_html__( 'Network Allowed Themes', 'stalkfish' ),
			),
			// Theme Mods.
			'theme_mods'         => array(
				// Custom Background.
				'background_image'        => esc_html__( 'Background Image', 'stalkfish' ),
				'background_position_x'   => esc_html__( 'Background Position', 'stalkfish' ),
				'background_repeat'       => esc_html__( 'Background Repeat', 'stalkfish' ),
				'background_attachment'   => esc_html__( 'Background Attachment', 'stalkfish' ),
				'background_color'        => esc_html__( 'Background Color', 'stalkfish' ),
				// Custom Header.
				'header_image'            => esc_html__( 'Header Image', 'stalkfish' ),
				'header_textcolor'        => esc_html__( 'Text Color', 'stalkfish' ),
				'header_background_color' => esc_html__( 'Header and Sidebar Background Color', 'stalkfish' ),
				// Featured Content.
				'featured_content_layout' => esc_html__( 'Layout', 'stalkfish' ),
				// Custom Sidebar.
				'sidebar_textcolor'       => esc_html__( 'Header and Sidebar Text Color', 'stalkfish' ),
				// Custom Colors.
				'color_scheme'            => esc_html__( 'Color Scheme', 'stalkfish' ),
				'main_text_color'         => esc_html__( 'Main Text Color', 'stalkfish' ),
				'secondary_text_color'    => esc_html__( 'Secondary Text Color', 'stalkfish' ),
				'link_color'              => esc_html__( 'Link Color', 'stalkfish' ),
				'page_background_color'   => esc_html__( 'Page Background Color', 'stalkfish' ),
			),
			// Stalkfish.
			'sf_options'         => array(
				'sf_app_api_key'  => esc_html__( 'Stalkfish API Key', 'stalkfish' ),
				'sf_request_type' => esc_html__( 'Stalkfish How to send data', 'stalkfish' ),
			),
			'options-users'      => array(
				'user_count' => esc_html__( 'User Count', 'stalkfish' ),
			),
		);

		// These option labels are special and need to change based on multisite context.
		if ( is_network_admin() ) {
			$this->labels['admin_email']     = esc_html__( 'Network Admin Email', 'stalkfish' );
			$this->labels['new_admin_email'] = esc_html__( 'Network Admin Email', 'stalkfish' );
		}

		/**
		 * To filter through settings labels at any point of time.
		 *
		 * @param array $labels Settings labels
		 *
		 * @return array  Updated array of settings labels
		 */
		apply_filters( 'stalkfish_settings_labels', $this->labels );

		add_action(
			sprintf( 'update_option_theme_mods_%s', get_option( 'stylesheet' ) ),
			array(
				$this,
				'record_theme_mods',
			),
			10,
			2
		);
	}

	/**
	 * Check if the option is ignored.
	 *
	 * @param string $option_name Option name.
	 *
	 * @return bool Whether the option is ignored or not
	 */
	public function is_option_ignored( $option_name ) {
		if ( 0 === strpos( $option_name, '_transient_' ) || 0 === strpos( $option_name, '_site_transient_' ) ) {
			return true;
		}

		if ( '$' === substr( $option_name, - 1 ) ) {
			return true;
		}

		$default_ignored = array(
			'image_default_link_type',
			'medium_large_size_w',
			'medium_large_size_h',
			'rewrite_rules',
		);

		/**
		 * Filters the boolean output for is_option_ignored().
		 *
		 * @param boolean $is_ignored True if ignored, otherwise false.
		 * @param string $option_name Current option name.
		 * @param array $default_ignored Default options to ignore.
		 *
		 * @return boolean
		 */
		return apply_filters(
			'stalkfish_is_option_ignored',
			in_array( $option_name, $default_ignored, true ),
			$option_name,
			$default_ignored
		);
	}

	/**
	 * Check if the option key is ignored.
	 *
	 * @param string $option_name Option name.
	 * @param string $key Option key.
	 *
	 * @return bool Whether option key is ignored or not
	 */
	public function is_key_ignored( $option_name, $key ) {
		$ignored = array(
			'theme_mods' => array(
				'background_image_thumb',
				'header_image_data',
				'nav_menu_locations',
			),
		);

		if ( isset( $ignored[ $option_name ] ) ) {
			return in_array( $key, $ignored[ $option_name ], true );
		}

		return false;
	}

	/**
	 * Check if array keys in the option should be recorded separately.
	 *
	 * @param mixed $value Option value.
	 *
	 * @return bool Whether the option should be treated as a group
	 */
	public function is_option_group( $value ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		if ( 0 === count( array_filter( array_keys( $value ), 'is_string' ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize values such as arrays or objects
	 *
	 * @param mixed $value Raw input value.
	 *
	 * @return string
	 */
	public function sanitize_value( $value ) {
		if ( is_array( $value ) ) {
			return '';
		} elseif ( is_object( $value ) && ! in_array( '__toString', get_class_methods( $value ), true ) ) {
			return '';
		}

		return strval( $value );
	}

	/**
	 * Return context by option name and key
	 *
	 * @param string $option_name Option name.
	 * @param string $key Option key.
	 *
	 * @return string Context slug
	 */
	public function get_context_by_key( $option_name, $key ) {
		$contexts = array(
			'theme_mods' => array(
				'custom_background' => array(
					'background_image',
					'background_position_x',
					'background_repeat',
					'background_attachment',
					'background_color',
				),
				'custom_header'     => array(
					'header_image',
					'header_textcolor',
				),
			),
			'sf_options' => array(
				'stalkfish' => array(
					'sf_app_api_key',
					'sf_request_type',
				),
			),
		);

		if ( isset( $contexts[ $option_name ] ) ) {
			foreach ( $contexts[ $option_name ] as $context => $keys ) {
				if ( in_array( $key, $keys, true ) ) {
					return $context;
				}
			}
		}

		return false;
	}

	/**
	 * Return translated labels for all serialized settings fields.
	 *
	 * @param string $option_name Option name.
	 * @param string $field_key Field key.
	 *
	 * @return string Field key translation or key itself if not found
	 */
	public function get_serialized_field_label( $option_name, $field_key ) {
		if ( isset( $this->labels[ $option_name ] ) && isset( $this->labels[ $option_name ][ $field_key ] ) ) {
			return $this->labels[ $option_name ][ $field_key ];
		}

		return $field_key;
	}

	/**
	 * Return translated labels for settings fields.
	 *
	 * @param string $context Context key.
	 * @param string $field_key Field key.
	 *
	 * @return array Field label translations
	 */
	public function get_field_label( $context, $field_key ) {
		if ( isset( $this->labels[ $context ][ $field_key ] ) ) {
			return $this->labels[ $context ][ $field_key ];
		}

		return $field_key;
	}

	/**
	 * Record settings update.
	 *
	 * @param string $option Option name.
	 * @param mixed  $prev_value Option previous value.
	 * @param mixed  $value Option updated value.
	 * @param string $via Update source.
	 */
	public function callback_updated_option( $option, $prev_value, $value, $via = null ) {
		global $whitelist_options, $new_whitelist_options;

		if ( $this->is_option_ignored( $option ) ) {
			return;
		}

		$options = array_merge(
			(array) $whitelist_options,
			(array) $new_whitelist_options,
			array(
				'general' => array_keys( $this->labels['options-general'] ),
			),
			array(
				'writing' => array_keys( $this->labels['options-writing'] ),
			),
			array(
				'reading' => array_keys( $this->labels['options-reading'] ),
			),
			array(
				'discussion' => array_keys( $this->labels['options-discussion'] ),
			),
			array(
				'media' => array_keys( $this->labels['options-media'] ),
			),
			array(
				'permalink' => array_keys( $this->labels['options-permalink'] ),
			),
			array(
				'network' => array_keys( $this->labels['options-network'] ),
			),
			array(
				'users' => array_keys( $this->labels['options-users'] ),
			)
		);

		foreach ( $options as $key => $opts ) {
			if ( in_array( $option, $opts, true ) ) {
				$context = $key;
				break;
			}
		}

		if ( ! isset( $context ) ) {
			$context = 'settings';
		}

		$changed_options = array();

		if ( $this->is_option_group( $value ) ) {
			foreach ( $this->get_changed_keys( $prev_value, $value ) as $field_key ) {
				if ( ! $this->is_key_ignored( $option, $field_key ) ) {
					$key_context      = $this->get_context_by_key( $option, $field_key );
					$label_parent_key = $option;
					$label_field_key  = $field_key;

					$changed_options[] = array(
						'label'      => $this->get_serialized_field_label( $label_parent_key, $label_field_key ),
						'option'     => $option,
						'option_key' => $field_key,
						'prev_value' => isset( $prev_value[ $field_key ] ) ? $this->sanitize_value( $prev_value[ $field_key ] ) : null,
						'value'      => isset( $value[ $field_key ] ) ? $this->sanitize_value( $value[ $field_key ] ) : null,
					);
					$context           = ( false !== $key_context ? $key_context : $context );
				}
			}
		} else {
			$changed_options[] = array(
				'label'      => $this->get_field_label( 'options-' . $context, $option ),
				'option'     => $option,
				'prev_value' => $this->sanitize_value( $prev_value ),
				'value'      => $this->sanitize_value( $value ),
			);
		}

		foreach ( $changed_options as $args ) {
			$args['via'] = $via;

			if ( 'theme_mods' === $option ) {
				$context = 'customizer';
			}

			switch ( $context ) {
				case 'general':
				case 'writing':
				case 'reading':
				case 'discussion':
				case 'media':
				case 'permalink':
					$link = get_admin_url() . 'options-' . $context . '.php';
					break;
				case 'stalkfish':
					$link = get_admin_url() . 'options-general.php?page=sf-settings';
					break;
				case 'customizer':
					$link = get_admin_url() . 'customize.php';
					break;
				case 'network':
					$link = get_admin_url() . 'network/settings.php';
					break;
				default:
					$link = get_admin_url() . 'options-general.php';
					break;
			}

			$this->log(
				array(
					'message' => vsprintf( /* translators: %s: setting name */
						__( '"%s" setting was updated', 'stalkfish' ),
						$args
					),
					'meta'    => $args,
					'context' => $context,
					'action'  => 'updated',
					'link'    => $link,
				)
			);
		}
	}

	/**
	 * Recorder to run only on options.php
	 *
	 * @param array $options Options.
	 *
	 * @return array
	 */
	public function callback_allowed_options( $options ) {
		add_action( 'updated_option', array( $this, 'callback' ), 10, 3 );

		return $options;
	}

	/**
	 * Recorder to run only for WP CLI, Customizer, and listed settings only.
	 *
	 * @param string $option Option name.
	 * @param mixed  $prev_value Option previous value.
	 * @param mixed  $value Option updated value.
	 */
	public function callback_update_option( $option, $prev_value, $value ) {
		if ( ( defined( '\WP_CLI' ) && \WP_CLI || did_action( 'customize_save' ) ) ) {
			foreach ( $this->labels as $key ) {
				if ( array_key_exists( $option, $key ) ) {
					$via = did_action( 'customize_save' ) ? 'customizer' : 'cli';
					$this->callback_updated_option( $option, $prev_value, $value, $via );
					break;
				}
			}
		} elseif ( 'sf_options' === $option && array_key_exists( $option, $this->labels ) ) {
			$this->callback_updated_option( $option, $prev_value, $value );
		}
	}

	/**
	 * Recorder to run only on network/settings.php
	 *
	 * @param string $option Option name.
	 * @param mixed  $value Option updated value.
	 * @param mixed  $prev_value Option previous value.
	 */
	public function callback_update_site_option( $option, $value, $prev_value ) {
		$this->callback_updated_option( $option, $prev_value, $value );
	}

	/**
	 * Recorder to run only on options-permalink.php for permalink structure.
	 *
	 * @param mixed $prev_value Option previous value.
	 * @param mixed $value Option updated value.
	 */
	public function callback_update_option_permalink_structure( $prev_value, $value ) {
		$this->callback_updated_option( 'permalink_structure', $prev_value, $value );
	}

	/**
	 * Recorder to run only on options-permalink.php for category base.
	 *
	 * @param mixed $prev_value Option previous value.
	 * @param mixed $value Option updated value.
	 */
	public function callback_update_option_category_base( $prev_value, $value ) {
		$this->callback_updated_option( 'category_base', $prev_value, $value );
	}

	/**
	 * Recorder to run only on options-permalink.php page for tag base.
	 *
	 * @param mixed $old_value Option old value.
	 * @param mixed $value Option new value.
	 */
	public function callback_update_option_tag_base( $old_value, $value ) {
		$this->callback_updated_option( 'tag_base', $old_value, $value );
	}

	/**
	 * Records theme modifications.
	 *
	 * @param mixed $prev_value Previous setting value.
	 * @param mixed $updated_value Updated setting value.
	 */
	public function record_theme_mods( $prev_value, $updated_value ) {
		$this->callback_updated_option( 'theme_mods', $prev_value, $updated_value );
	}

}
