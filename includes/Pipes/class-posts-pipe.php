<?php
/**
 * Class to record post events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Posts_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Posts_Pipe extends Pipe {

	/**
	 * Event name
	 *
	 * @var string
	 */
	public $name = 'posts';

	/**
	 * Previous post.
	 *
	 * @var stdClass
	 */
	protected $prev_post = null;

	/**
	 * Previous permalink.
	 *
	 * @var string
	 */
	protected $prev_link = null;

	/**
	 * Previous post is marked as sticky.
	 *
	 * @var boolean
	 */
	protected $prev_sticky = null;

	/**
	 * Previous post status.
	 *
	 * @var string
	 */
	protected $prev_status = null;

	/**
	 * Previous path to page template.
	 *
	 * @var string
	 */
	protected $prev_template = null;

	/**
	 * Previous categories.
	 *
	 * @var array
	 */
	protected $prev_cats = null;

	/**
	 * Previous tags.
	 *
	 * @var array
	 */
	protected $prev_tags = null;

	/**
	 * Previous post Meta.
	 *
	 * @var string
	 */
	protected $prev_meta = null;

	/**
	 * Available hooks.
	 *
	 * @var array
	 */
	public $hooks = array(
		/**
		 * Post.
		 */
		'pre_post_update',
		'save_post',
		'set_object_terms',
		'delete_post',
		'wp_trash_post',
		'untrash_post',
		'future_to_publish',
		/**
		 * Metadata.
		 */
		'add_post_metadata',
		'updated_post_meta',
		'delete_post_metadata',
	);

	/**
	 * Available contexts and their actions for current pipe.
	 *
	 * @var array
	 */
	public $triggers = array(
		'actions' => array(
			'created',
			'published',
			'modified',
			'deleted',
			'restored',
		)
	);

	/**
	 * Ignored CPTs
	 *
	 * @var array
	 */
	public $ignored_cpts = array(
		'attachment',
		'revision',
		'nav_menu_item',
		'customize_changeset',
		'custom_css',
	);

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
		// Contexts.
		$post_types = get_post_types() ;
		foreach ( $post_types as $post_type ) {
			// Skip ignored custom post types.
			if ( in_array( $post_type, $this->ignored_cpts, true ) ) {
				continue;
			}

			$this->triggers['contexts'][] = $post_type;
		}
	}

	/**
	 * Get before post edit data to compare changes.
	 *
	 * @param int $post_id Post ID.
	 */
	public function callback_pre_post_update( $post_id ) {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );

		if ( ! empty( $post ) && $post instanceof \WP_Post ) {
			$this->prev_post     = $post;
			$this->prev_link     = get_permalink( $post_id );
			$this->prev_status   = $post->post_status;
			$this->prev_sticky   = in_array( $post_id, get_option( 'sticky_posts' ), true );
			$this->prev_template = $this->get_page_template( $this->prev_post );
			$this->prev_cats     = $this->get_post_categories( $this->prev_post );
			$this->prev_tags     = $this->get_post_tags( $this->prev_post );
			$this->prev_meta     = get_post_meta( $post_id );
		}
	}

	/**
	 * Get the page template file.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return string Template path for the page.
	 */
	protected function get_page_template( $post ) {
		if ( ! isset( $post->ID ) ) {
			return '';
		}

		$template  = get_page_template_slug( $post->ID );
		$templates = array();

		if ( $template && 0 === validate_file( $template ) ) {
			$templates[] = $template;
		}

		if ( $post->post_name ) {
			$templates[] = "page-$post->post_name.php";
		}

		if ( $post->ID ) {
			$templates[] = "page-$post->ID.php";
		}

		$templates[] = 'page.php';

		return get_query_template( 'page', $templates );
	}

	/**
	 * Get the post categories.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array A list of categories
	 */
	protected function get_post_categories( $post ) {
		return ! isset( $post->ID ) ? array() : wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
	}

	/**
	 * Get the post tags.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array A list of tags
	 */
	protected function get_post_tags( $post ) {
		return ! isset( $post->ID ) ? array() : wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
	}

	/**
	 *  Record all the post changes.
	 *
	 * @param integer  $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @param boolean  $update Post update status.
	 */
	public function callback_save_post( $post_id, $post, $update ) {
		// Exit if post type is empty, revision or trash.
		if ( empty( $post->post_type ) || 'revision' === $post->post_type || 'trash' === $post->post_type ) {
			return;
		}

		// Ignore updates from ignored custom post types.
		if ( in_array( $post->post_type, $this->ignored_cpts, true ) ) {
			return;
		}

		// Ignored events.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			if ( $this->prev_post && 'auto-draft' === $this->prev_post->post_status && 'draft' === $post->post_status ) {
				$this->record_post_creation( $this->prev_post, $post );
			}

			return;
		}

		/**
		 * Filter for passing the following requests only -
		 * 1. Rest request from Gutenberg.
		 * 2. Classic editor request.
		 * 3. Quick edit ajax request.
		 */
		if ( ! defined( 'REST_REQUEST' ) && ! defined( 'DOING_AJAX' ) ) {
			// Gutenberg's second post or classic editor's request.
			if ( ! isset( $_REQUEST['classic-editor'] ) ) { // phpcs:ignore
				$editor_replace = get_option( 'classic-editor-replace', 'classic' );
				$allow_users    = get_option( 'classic-editor-allow-users', 'disallow' );

				// Exit if it is Gutenberg's second request.
				if ( 'block' === $editor_replace && 'disallow' === $allow_users ) {
					return;
				}
				if ( 'allow' === $allow_users ) {
					return;
				}
			}
		}

		if ( $update ) {
			$status_event = $this->record_post_status_change( $this->prev_post, $post );

			if ( 'published' !== $status_event && 'auto-draft' !== $this->prev_post->post_status ) {

				// Count post events.
				$changes = 0;

				$changes = $this->record_post_author_change( $this->prev_post, $post ) + $this->record_post_parent_change( $this->prev_post, $post )
						+ $this->record_post_visibility_change( $this->prev_post, $post )
						+ $this->record_post_date_change( $this->prev_post, $post )
						+ $this->record_post_permalink_change( $this->prev_link, get_permalink( $post->ID ), $post )
						+ $this->record_post_comments_pings( $this->prev_post, $post );

				// Stop logging if status change event occurred.
				$changes = $status_event ? true : $changes;

				if ( '1' === $changes ) {
					remove_action( 'save_post', array( $this, 'callback' ), 10, 99 );
				}

				$this->record_post_modifications( $post->ID, $this->prev_post, $post, $changes );
			}
		} else {
			// Record post creation if not update.
			$this->record_post_creation( $this->prev_post, $post );
		}
	}

	/**
	 * Record post deletion.
	 *
	 * @param int $post_id Post id.
	 */
	public function callback_delete_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! in_array( $post->post_type, $this->ignored_cpts, true ) ) {

			if ( $this->is_auto_draft( $post->post_title ) ) {
				return;
			}

			$args = $this->get_post_event_data( $post );

			$message = vsprintf(
				/* translators: %1$s: Post Type Singular, %2$s: Post ID */
				_x(
					'%1$s #%2$s was permanently deleted',
					'1: Post Type Singular, 2: Post ID',
					'stalkfish'
				),
				array( $args['post_singular_label'], $post->ID )
			);
			$link = get_admin_url() . 'edit.php?post_type=' . $args['post_type'];

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'deleted',
					'meta'    => $args,
					'link'    => $link,
				)
			);
		}
	}

	/**
	 * Whether is current post auto draft or not.
	 *
	 * @param string $post_title Post title.
	 */
	private function is_auto_draft( $post_title ) {
		$ignore = 0;
		if ( ( 'auto-draft' === $post_title || 'Auto Draft' === $post_title ) ) {
			$ignore = ! stalkfish_get_global_settings( 'wp-backend', false );
		}

		return $ignore;
	}

	/**
	 * Record post trashing.
	 *
	 * @param int $post_id Post ID.
	 */
	public function callback_wp_trash_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! in_array( $post->post_type, $this->ignored_cpts, true ) ) {
			$args = $this->get_post_event_data( $post );

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID */
				_x(
					'%1$s #%2$s moved to trash',
					'1: Post Type Singular, 2: Post ID',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'] )
			);
			$link = get_admin_url() . 'post.php?post=' . $args['id'] . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'deleted',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			remove_action( 'save_post', array( $this, 'callback' ), 10, 99 );
		}
	}

	/**
	 * Record post restore.
	 *
	 * @param int $post_id Post ID.
	 */
	public function callback_untrash_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! in_array( $post->post_type, $this->ignored_cpts, true ) ) {
			$args = $this->get_post_event_data( $post );

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID */
				_x(
					'%1$s #%2$s was restored',
					'1: Post Type Singular, 2: Post ID',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'] )
			);
			$link    = get_admin_url() . 'post.php?post=' . $args['id'] . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'restored',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			remove_action( 'save_post', array( $this, 'callback' ), 10, 99 );
		}
	}

	/**
	 * Record post future publishing.
	 *
	 * @param int $post_id Post ID.
	 */
	public function callback_future_to_publish( $post_id ) {
		$post = get_post( $post_id );

		if ( ! in_array( $post->post_type, $this->ignored_cpts, true ) ) {
			$args = $this->get_post_event_data( $post );

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID */
				_x(
					'%1$s #%2$s was published',
					'1: Post Type Singular, 2: Post ID',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'] )
			);
			$link = get_admin_url() . 'post.php?post=' . $args['id'] . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'published',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			remove_action( 'save_post', array( $this, 'callback' ), 10, 99 );
		}
	}

	/**
	 * Record status change.
	 *
	 * @param \WP_Post $prev_post Previous post.
	 * @param \WP_Post $updated_post Updated post.
	 *
	 * @return integer
	 */
	protected function record_post_status_change( $prev_post, $updated_post ) {
		if ( $prev_post->post_status !== $updated_post->post_status && 'trash' !== $updated_post->post_status ) {
			$action                 = 'modified';
			$args                   = $this->get_post_event_data( $updated_post );
			$args['prev_status']    = $prev_post->post_status;
			$args['updated_status'] = $updated_post->post_status;

			if ( 'auto-draft' === $prev_post->post_status && 'draft' === $updated_post->post_status ) {
				$action  = 'created';
				$message = vsprintf(
					/* translators: %1$s: Post Type Singular, %2$s: Post ID */
					_x(
						'%1$s #%2$s was created',
						'1: Post Type Singular, 2: Post ID',
						'stalkfish'
					),
					array( $args['post_singular_label'], $updated_post->ID )
				);
			} elseif ( 'publish' === $updated_post->post_status ) {
				$action  = 'published';
				$message = vsprintf(
					/* translators: %1$s: Post Type Singular, %2$s: Post ID */
					_x(
						'%1$s #%2$s was published',
						'1: Post Type Singular, 2: Post ID',
						'stalkfish'
					),
					array( $args['post_singular_label'], $updated_post->ID )
				);
			} elseif ( 'pending' === $updated_post->post_status ) {
				$message = vsprintf(
					/* translators: %1$s: Post Type Singular, %2$s: Post ID */
					_x(
						'%1$s #%2$s was set to pending',
						'1: Post Type Singular, 2: Post ID',
						'stalkfish'
					),
					array( $args['post_singular_label'], $updated_post->ID )
				);
			} elseif ( 'future' === $updated_post->post_status ) {
				$message = vsprintf(
					/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Scheduled post date */
					_x(
						'%1$s #%2$s was scheduled to be published on %3$s',
						'1: Post Type Singular, 2: Post ID, 3: Scheduled Post Date',
						'stalkfish'
					),
					array( $args['post_singular_label'], $updated_post->ID, $updated_post->post_date )
				);

			} else {
				$message = vsprintf(
					/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Post status */
					_x(
						'%1$s #%2$s status was set to %3$s',
						'1: Post Type Singular, 2: Post ID, 3: Updated post status',
						'stalkfish'
					),
					array( $args['post_singular_label'], $updated_post->ID, $updated_post->post_status )
				);
			}

			$link    = get_admin_url() . 'post.php?post=' . $updated_post->ID . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => $action,
					'meta'    => $args,
					'link'    => $link,
				)
			);

			return $action;
		}
	}

	/**
	 * Records post creation events.
	 *
	 * @param \WP_Post $prev_post Previous post object.
	 * @param \WP_Post $updated_post Updated post object.
	 */
	protected function record_post_creation( $prev_post, $updated_post ) {
		if ( ! empty( $updated_post ) && $updated_post instanceof \WP_Post ) {
			$status          = '';
			$message         = '';
			$action          = 'modified';
			$args = $this->get_post_event_data( $updated_post );

			switch ( $updated_post->post_status ) {
				case 'publish':
					$status = 'publish';
					/* translators: %1$s: Post Type Singular, %2$s: Post ID */
					$message = _x(
							'%1$s #%2$s was published',
							'1: Post Type Singular, 2: Post ID',
							'stalkfish'
						);
					$action  = 'published';
					break;

				case 'draft':
					$status = 'draft';
					/* translators: %1$s: Post Type Singular, %2$s: Post ID */
					$message = _x(
							'%1$s #%2$s was created',
							'1: Post Type Singular, 2: Post ID',
							'stalkfish'
						);
					$action  = 'created';
					break;
				case 'future':
					$status = 'future';
					/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Scheduled post date */
					$message = _x(
							'%1$s #%2$s was scheduled to be published on %3$s',
							'1: Post Type Singular, 2: Post ID, 3: Scheduled Post Date',
							'stalkfish'
						);
					break;
				case 'pending':
					$status = 'pending';
					/* translators: %1$s: Post Type Singular, %2$s: Post ID */
					$message = _x(
						'%1$s #%2$s was set to pending',
						'1: Post Type Singular, 2: Post ID',
						'stalkfish'
					);
					break;
				default:
					break;
			}
			if ( $status ) {
				$message = vsprintf( $message, array( $args['post_singular_label'], $args['id'], $args['post_date'] ) );
				$link    = get_admin_url() . 'post.php?post=' . $updated_post->ID . '&action=edit';

				// Let's keep the meta clean.
				unset( $args['post_singular_label'] );

				$this->log(
					array(
						'message' => $message,
						'context' => $args['post_type'],
						'action'  => $action,
						'meta'    => $args,
						'link'    => $link,
					)
				);
			}
		}
	}


	/**
	 * Record post author change.
	 *
	 * @param \WP_Post $prev_post Previous post.
	 * @param \WP_Post $updated_post Updated post.
	 */
	protected function record_post_author_change( $prev_post, $updated_post ) {
		if ( $prev_post->post_author !== $updated_post->post_author ) {
			$prev_author = get_userdata( $prev_post->post_author );
			$prev_author = ( is_object( $prev_author ) ) ? $prev_author->user_login : 'N/A';

			$updated_author = get_userdata( $updated_post->post_author );
			$updated_author = ( is_object( $updated_author ) ) ? $updated_author->user_login : 'N/A';

			$args                   = $this->get_post_event_data( $updated_post );
			$args['prev_author']    = $prev_author;
			$args['updated_author'] = $updated_author;

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Previous Post author, %4$s: Updated Post author */
				_x(
					'%1$s #%2$s author was changed from %3$s to %4$s',
					'1: Post Type Singular, 2: Post ID, 3: Previous Post Author, 4: Updated post author',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'], $prev_author, $updated_author )
			);
			$link    = get_admin_url() . 'post.php?post=' . $updated_post->ID . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			return 1;
		}
	}

	/**
	 * Record post parent change.
	 *
	 * @param \WP_Post $prev_post Previous post.
	 * @param \WP_Post $updated_post Updated post.
	 */
	protected function record_post_parent_change( $prev_post, $updated_post ) {
		if ( $prev_post->post_parent !== $updated_post->post_parent && 'page' === $updated_post->post_type ) {

			$args                        = $this->get_post_event_data( $updated_post );
			$args['prev_parent']         = $prev_post->post_parent;
			$args['updated_parent']      = $updated_post->post_parent;
			$args['prev_parent_name']    = $prev_post->post_parent ? get_the_title( $prev_post->post_parent ) : 'no parent';
			$args['updated_parent_name'] = $updated_post->post_parent ? get_the_title( $updated_post->post_parent ) : 'no parent';

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Post Parent */
				_x(
					'%1$s #%2$s parent was changed to #%3$s',
					'1: Post Type Singular, 2: Post ID, 3: Post parent',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'], $args['updated_parent'] )
			);
			$link    = get_admin_url() . 'post.php?post=' . $updated_post->ID . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			return 1;
		}
	}

	/**
	 * Record post visibility change.
	 *
	 * @param \WP_Post $prev_post Previous post.
	 * @param \WP_Post $updated_post Updated post.
	 */
	protected function record_post_visibility_change( $prev_post, $updated_post ) {
		$prev_visibility    = '';
		$updated_visibility = '';

		if ( $prev_post->post_password ) {
			$prev_visibility = __( 'Passord Protected', 'stalkfish' );
		} elseif ( 'private' === $prev_post->post_status ) {
			$prev_visibility = __( 'Private', 'stalkfish' );
		} else {
			$prev_visibility = __( 'Public', 'stalkfish' );
		}

		if ( $updated_post->post_password ) {
			$updated_visibility = __( 'Passord Protected', 'stalkfish' );
		} elseif ( 'private' === $updated_post->post_status ) {
			$updated_visibility = __( 'Private', 'stalkfish' );
		} else {
			$updated_visibility = __( 'Public', 'stalkfish' );
		}

		if ( $prev_visibility && $updated_visibility && ( $prev_visibility !== $updated_visibility ) ) {
			$args                       = $this->get_post_event_data( $prev_post );
			$args['prev_visibility']    = $prev_visibility;
			$args['updated_visibility'] = $updated_visibility;

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Update post visibility */
				_x(
					'%1$s #%2$s visibility was changed to %3$s',
					'1: Post Type Singular, 2: Post ID, 3: Updated post visibility',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'], $args['updated_visibility'] )
			);
			$link    = get_admin_url() . 'post.php?post=' . $updated_post->ID . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			return 1;
		}
	}

	/**
	 * Record post date change.
	 *
	 * @param \WP_Post $prev_post Previous post.
	 * @param \WP_Post $updated_post Updated post.
	 */
	protected function record_post_date_change( $prev_post, $updated_post ) {
		$from = strtotime( $prev_post->post_date );
		$to   = strtotime( $updated_post->post_date );

		if ( 'pending' === $prev_post->post_status || 'future' === $updated_post->post_status ) {
			return 0;
		}

		// Exit if is a re-save on draft.
		if ( $this->is_draft_resave( $prev_post, $updated_post ) ) {
			return 0;
		}

		if ( $from !== $to ) {
			$args                 = $this->get_post_event_data( $prev_post );
			$args['prev_date']    = $prev_post->post_date;
			$args['updated_date'] = $updated_post->post_date;

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Updated post date */
				_x(
					'%1$s #%2$s date was changed to %3$s',
					'1: Post Type Singular, 2: Post ID, 3: Updated post date',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'], $args['updated_date'] )
			);
			$link    = get_admin_url() . 'post.php?post=' . $updated_post->ID . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			return 1;
		}
	}

	/**
	 * Record post permalink change.
	 *
	 * @param string   $prev_link Prev permalink.
	 * @param string   $updated_link Updated permalink.
	 * @param \WP_Post $post Post object.
	 */
	protected function record_post_permalink_change( $prev_link, $updated_link, $post ) {
		if ( in_array( $post->post_status, array( 'draft', 'pending', 'trash' ), true ) ) {
			$prev_link    = $this->prev_post->post_name;
			$updated_link = $post->post_name;
		}

		if ( $prev_link !== $updated_link ) {
			$args                = $this->get_post_event_data( $post );
			$args['prev_url']    = $prev_link;
			$args['updated_url'] = $updated_link;

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Updated post URL */
				_x(
					'%1$s #%2$s URL was changed to "%3$s"',
					'1: Post Type Singular, 2: Post ID, 3: Updated post URL',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'], $args['updated_url'] )
			);
			$link    = get_admin_url() . 'post.php?post=' . $args['id'] . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			return 1;
		}

		return 0;
	}

	/**
	 * Record post comments/trackbacks and pingbacks.
	 *
	 * @param \WP_Post $prev_post Previous post.
	 * @param \WP_Post $updated_post Updated post.
	 */
	private function record_post_comments_pings( $prev_post, $updated_post ) {
		$result = 0;
		$link   = get_admin_url() . 'post.php?post=' . $updated_post->ID . '&action=edit';

		// Comments.
		if ( $prev_post->comment_status !== $updated_post->comment_status ) {
			$args                   = $this->get_post_event_data( $updated_post );
			$args['comment_status'] = 'open' === $updated_post->comment_status ? 'enabled' : 'disabled';

			$message = vsprintf(
				/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Updated post comment status */
				_x(
					'%1$s #%2$s comments was %3$s',
					'1: Post Type Singular, 2: Post ID, 3: Updated post comments status',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'], $args['comment_status'] )
			);

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			$result = 1;
		}

		// Trackbacks and Pingbacks.
		if ( $prev_post->ping_status !== $updated_post->ping_status ) {
			$args                     = $this->get_post_event_data( $updated_post );
			$args['pingbacks_status'] = 'open' === $updated_post->ping_status ? 'enabled' : 'disabled';

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Updated post pingbacks status */
				_x(
					'%1$s #%2$s pingbacks was %3$s',
					'1: Post Type Singular, 2: Post ID, 3: Updated post pingbacks status',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'], $args['pingbacks_status'] )
			);

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			$result = 1;
		}

		return $result;
	}

	/**
	 * Record post modifications/updates.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $prev_post Previous post.
	 * @param \WP_Post $updated_post Updated post.
	 * @param int      $modified If the post has been modified.
	 */
	public function record_post_modifications( $post_id, $prev_post, $updated_post, $modified ) {
		if ( $this->record_post_title_change( $prev_post, $updated_post ) || 'trash' === $updated_post->post_status ) {
			return;
		}

		$is_content_changed = $prev_post->post_content !== $updated_post->post_content;

		// Don't track if the content hasn't changed and is a draft re-save.
		if ( ! $is_content_changed && $this->is_draft_resave( $prev_post, $updated_post ) ) {
			return;
		}
		if ( $prev_post->post_modified !== $updated_post->post_modified ) {
			$message         = '';
			$args = $this->get_post_event_data( $prev_post );

			if ( $is_content_changed ) {
				$message = vsprintf(
					/* translators: %1$s: Post Type Singular, %2$s: Post ID */
					_x(
						'%1$s #%2$s content was modified',
						'1: Post Type Singular, 2: Post ID',
						'stalkfish'
					),
					array( $args['post_singular_label'], $updated_post->ID )
				);
			}

			if ( ! $modified ) {
				$message = vsprintf(
					/* translators: %1$s: Post Type Singular, %2$s: Post ID */
					_x(
						'%1$s #%2$s was modified',
						'1: Post Type Singular, 2: Post ID',
						'stalkfish'
					),
					array( $args['post_singular_label'], $updated_post->ID )
				);
			}

			if ( $message ) {
				$args['revision_link'] = $this->get_post_revision_link( $post_id, $prev_post );
				// Whether excerpt changed.
				$prev_excerpt = $prev_post->post_excerpt;
				$excerpt      = get_post_field( 'post_excerpt', $post_id );

				if ( empty( $prev_excerpt ) && ! empty( $excerpt ) ) {
					$message = vsprintf(
						/* translators: %1$s: Post Type Singular, %2$s: Post ID */
						_x(
							'%1$s #%2$s excerpt was added',
							'1: Post Type Singular, 2: Post ID',
							'stalkfish'
						),
						array( $args['post_singular_label'], $updated_post->ID )
					);
					$args['event_type'] = 'added';
				} elseif ( ! empty( $prev_excerpt ) && empty( $excerpt ) ) {
					$message = vsprintf(
						/* translators: %1$s: Post Type Singular, %2$s: Post ID */
						_x(
							'%1$s #%2$s excerpt was removed',
							'1: Post Type Singular, 2: Post ID',
							'stalkfish'
						),
						array( $args['post_singular_label'], $updated_post->ID )
					);
					$args['event_type'] = 'removed';
				} elseif ( $prev_excerpt !== $excerpt ) {
					$message = vsprintf(
						/* translators: %1$s: Post Type Singular, %2$s: Post ID */
						_x(
							'%1$s #%2$s excerpt was modified',
							'1: Post Type Singular, 2: Post ID',
							'stalkfish'
						),
						array( $args['post_singular_label'], $updated_post->ID )
					);
					$args['event_type'] = 'modified';
				}

				if ( $prev_excerpt !== $excerpt ) {
					$args['prev_excerpt']    = ( $prev_excerpt ) ? $prev_excerpt : '';
					$args['updated_excerpt'] = ( $excerpt ) ? $excerpt : '';
				}

				$link   = get_admin_url() . 'post.php?post=' . $updated_post->ID . '&action=edit';

				// Let's keep the meta clean.
				unset( $args['post_singular_label'] );

				$this->log(
					array(
						'message' => $message,
						'context' => $args['post_type'],
						'action'  => 'modified',
						'meta'    => $args,
						'link'    => $link,
					)
				);
			}
		}
	}

	/**
	 * Record post title change.
	 *
	 * @param \WP_Post $prev_post Previous post.
	 * @param \WP_Post $updated_post Updated post.
	 */
	private function record_post_title_change( $prev_post, $updated_post ) {
		if ( $prev_post->post_title !== $updated_post->post_title ) {
			$args                  = $this->get_post_event_data( $updated_post );
			$args['prev_title']    = $prev_post->post_title;
			$args['updated_title'] = $updated_post->post_title;

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Previous post title, %4$s: Updated post title */
				_x(
					'%1$s #%2$s title was changed from "%3$s" to "%4$s".',
					'1: Post Type Singular, 2: Post ID, 3: Previous post title, 4: Updated post title',
					'stalkfish'
				),
				array( $args['post_singular_label'], $updated_post->ID, $prev_post->post_title, $updated_post->post_title )
			);
			$link   = get_admin_url() . 'post.php?post=' . $updated_post->ID . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);

			return 1;
		}

		return 0;
	}

	/**
	 * Record post term changes via Gutenberg.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $terms List of terms.
	 * @param array  $tt_ids List of taxonomy term ids.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function callback_set_object_terms( $post_id, $terms, $tt_ids, $taxonomy ) {
		$post = get_post( $post_id );

		if ( is_wp_error( $post ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		if ( 'post_tag' === $taxonomy ) {
			// Trigger tags change hook.
			$this->record_post_tags_change( $this->prev_tags, $this->get_post_tags( $post ), $post );
		} else {
			// Trigger categories change hook.
			$this->record_post_categories_change( $this->prev_cats, $this->get_post_categories( $post ), $post );
		}

	}

	/**
	 * Record post tags change.
	 *
	 * @param array    $prev_tags Previous tags.
	 * @param array    $updated_tags Updated tags.
	 * @param \WP_Post $post Post object.
	 */
	protected function record_post_tags_change( $prev_tags, $updated_tags, $post ) {
		if ( ! $prev_tags ) {
			$prev_tags = array();
		}

		// If no changes present.
		$changes = array_intersect( $prev_tags, $updated_tags );
		if ( count( $changes ) === count( $prev_tags ) && count( $prev_tags ) === count( $updated_tags ) ) {
			return;
		}

		// Added tags.
		$added_tags = array_diff( (array) $updated_tags, (array) $prev_tags );
		if ( ! empty( $added_tags ) ) {
			$this->post_tags_change_action( 'added', $post, $added_tags, $updated_tags );
		}

		// Removed tags.
		$removed_tags = array_diff( (array) $prev_tags, (array) $updated_tags );
		if ( ! empty( $removed_tags ) ) {
			$this->post_tags_change_action( 'removed', $post, $removed_tags, $updated_tags );
		}
	}

	/**
	 * Post tags change action.
	 *
	 * @param string   $event_type Event action added or removed.
	 * @param \WP_Post $post Post object.
	 * @param array    $changed_tags Changed tags.
	 * @param array    $updated_tags List of updated tags.
	 */
	private function post_tags_change_action( $event_type, $post, $changed_tags, $updated_tags ) {
		$args                 = $this->get_post_event_data( $post );
		$args['prev_tags']    = $this->prev_tags;
		$args['updated_tags'] = $updated_tags;
		$changed_tags                    = ! empty( $changed_tags ) ? implode( ', ', $changed_tags ) : __( 'No tags', 'stalkfish' );

		/* translators: %1$s: Changed Tags, %2$s: Post Type Singular, %3$s: Post ID */
		$message = _x(
			'Tag(s) "%1$s" were added to %2$s #%3$s',
			'1: Tags, 2: Post Type Singular, 3: Post ID',
			'stalkfish'
		);

		if ( 'removed' === $event_type ) {
			/* translators: %1$s: Changed Tags, %2$s: Post Type Singular, %3$s: Post ID */
			$message = _x(
				'Tag(s) "%1$s" were removed from %2$s #%3$s',
				'1: Tags, 2: Post Type Singular, 3: Post ID',
				'stalkfish'
			);
		}

		$message = vsprintf(
			$message,
			array( $changed_tags,  $args['post_singular_label'], $post->ID )
		);
		$link   = get_admin_url() . 'post.php?post=' . $post->ID . '&action=edit';

		// Let's keep the meta clean.
		unset( $args['post_singular_label'] );

		$this->log(
			array(
				'message' => $message,
				'context' => $args['post_type'],
				'action'  => 'modified',
				'meta'    => $args,
				'link'    => $link,
			)
		);
	}

	/**
	 * Record categories change.
	 *
	 * @param array    $prev_cats Previous categories.
	 * @param array    $updated_cats Updated categories.
	 * @param \WP_Post $post Post object.
	 */
	protected function record_post_categories_change( $prev_cats, $updated_cats, $post ) {
		$prev_cats    = implode( ', ', (array) $prev_cats );
		$updated_cats = implode( ', ', (array) $updated_cats );

		if ( $prev_cats !== $updated_cats && 'page' !== $post->post_type ) {
			$args                       = $this->get_post_event_data( $post );
			$args['prev_categories']    = $prev_cats;
			$args['updated_categories'] = $updated_cats;

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Previous post categories, %4$s: Updated post categories */
				_x(
					'%1$s #%2$s categories changed from "%3$s" to "%4$s"',
					'1: Post Type Singular, 2: Post ID, 3: Previous post categories, 4: Updated post categories',
					'stalkfish'
				),
				array( $args['post_singular_label'], $post->ID, $prev_cats, $updated_cats )
			);
			$link   = get_admin_url() . 'post.php?post=' . $post->ID . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);
		}
	}

	/**
	 * Get post revision link.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 */
	private function get_post_revision_link( $post_id, $post ) {
		$revisions = wp_get_post_revisions( $post_id );
		if ( ! empty( $revisions ) ) {
			$revision = array_shift( $revisions );

			return $this->make_revision_link( $revision->ID );
		}
	}

	/**
	 * Builds revision link.
	 *
	 * @param int $revision_id Revision ID.
	 */
	private function make_revision_link( $revision_id ) {
		return ! empty( $revision_id ) ? add_query_arg( 'revision', $revision_id, admin_url( 'revision.php' ) ) : null;
	}

	/**
	 * Checks whether a post is a re-save on a draft.
	 *
	 * @param \WP_Post $prev_post Previous post.
	 * @param |WP_Post $updated_post Updated post.
	 *
	 * @return bool
	 */
	private function is_draft_resave( $prev_post, $updated_post ) {
		if ( 'draft' === $prev_post->post_status && $prev_post->post_status === $updated_post->post_status && $prev_post->post_date_gmt === $updated_post->post_date_gmt && preg_match( '/^[0\-\ \:]+$/', $prev_post->post_date_gmt ) ) {
			return true;
		}
	}

	/**
	 * Get post edit link.
	 *
	 * @param \WP_Post $post post.
	 *
	 * @return array $editor_link Name and value link.
	 */
	private function get_editor_link( $post ) {
		$value       = get_edit_post_link( $post->ID );
		$editor_link = array(
			'name'  => 'edit_link',
			'value' => $value,
		);

		return $editor_link;
	}

	/**
	 * Get category edit link.
	 *
	 * @param int    $tag_id Tag ID.
	 * @param string $taxonomy Taxonomy.
	 *
	 * @return string|null Edit link.
	 */
	public function get_taxonomy_edit_link( $tag_id, $taxonomy = 'post_tag' ) {
		$tag_args = array(
			'taxonomy' => $taxonomy,
			'tag_ID'   => $tag_id,
		);

		return ! empty( $tag_id ) ? add_query_arg( $tag_args, admin_url( 'term.php' ) ) : null;
	}

	/**
	 * Gets the singular post type label
	 *
	 * @param string $post_type_slug  Post type slug.
	 *
	 * @return string Singular post type label
	 */
	public function get_post_type_singular( $post_type_slug ) {
		$name = esc_html__( 'Post', 'stalkfish' );

		if ( post_type_exists( $post_type_slug ) ) {
			$post_type = get_post_type_object( $post_type_slug );
			$name      = $post_type->labels->singular_name;
		}

		return $name;
	}

	/**
	 * Return Post Event Data.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array
	 */
	public function get_post_event_data( $post ) {
		if ( ! empty( $post ) && $post instanceof \WP_Post ) {
			$editor_link = $this->get_editor_link( $post );

			$post_data = array(
				'id'                  => $post->ID,
				'url'                 => get_permalink( $post->ID ),
				'post_date'           => $post->post_date,
				'post_type'           => $post->post_type,
				'post_title'          => $post->post_title,
				'post_status'         => $post->post_status,
				'post_singular_label' => $this->get_post_type_singular( $post->post_type ),
				$editor_link['name']  => $editor_link['value'],
			);

			return $post_data;
		}

		return array();
	}

	/**
	 * Record post meta additions.
	 *
	 * @param int    $meta_id ID of updated meta.
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return int $meta_id  ID of updated meta.
	 */
	public function callback_add_post_metadata( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( ! $post_id ) {
			return $meta_id;
		}

		return $this->record_meta_changes( $meta_id, $post_id, $meta_key, $meta_value );
	}

	/**
	 * Record post meta changes.
	 *
	 * @param int    $meta_id ID of updated meta.
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return int $meta_id  ID of updated meta.
	 */
	public function callback_updated_post_meta( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( ! $post_id ) {
			return $meta_id;
		}

		return $this->record_meta_changes( $meta_id, $post_id, $meta_key, $meta_value );
	}

	/**
	 * Handles post meta entries.
	 *
	 * @param int    $meta_id ID of updated meta.
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return int $meta_id  ID of updated meta.
	 */
	public function record_meta_changes( $meta_id, $post_id, $meta_key, $meta_value ) {
		switch ( $meta_key ) {
			case '_wp_page_template':
				$this->record_page_template_change( $post_id, $meta_value );
				break;
			case '_thumbnail_id':
				$this->record_post_thumbnail_change( $post_id, $meta_value );
				break;
			default:
				return $meta_id;
		}

		return $meta_id;
	}

	/**
	 * Record post metadata deletions.
	 *
	 * @param bool|null $delete Is the given meta deletable.
	 * @param int       $meta_id ID of updated meta.
	 * @param int       $post_id Post ID.
	 * @param string    $meta_key Meta key.
	 * @param mixed     $meta_value Meta value.
	 *
	 * @return bool|null $delete  Is the given meta deletable.
	 */
	public function callback_delete_post_metadata( $delete, $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( ! $post_id ) {
			return $delete;
		}

		switch ( $meta_key ) {
			case '_wp_page_template':
				$this->record_page_template_change( $post_id, $meta_value );
				break;
			case '_thumbnail_id':
				$this->record_post_thumbnail_change( $post_id, $meta_value );
				break;
			default:
				return $delete;
		}

		return $delete;
	}

	/**
	 * Record page template change.
	 *
	 * @param int   $post_id Post ID.
	 * @param mixed $meta_value Meta value.
	 *
	 * @return void
	 */
	public function record_page_template_change( $post_id, $meta_value ) {
		$prev_template    = ( $this->prev_template && 'page' !== basename( $this->prev_template, '.php' ) ) ? ucwords(
			str_replace(
				array(
					'-',
					'_',
				),
				' ',
				basename( $this->prev_template, '.php' )
			)
		) : __( 'Default', 'stalkfish' );
		$updated_template = ( $meta_value ) ? ucwords(
			str_replace(
				array(
					'-',
					'_',
				),
				' ',
				basename( $meta_value )
			)
		) : __( 'Default', 'stalkfish' );

		if ( $prev_template !== $updated_template ) {
			$post                                = get_post( $post_id );
			$args                     = $this->get_post_event_data( $post );
			$args['prev_template']    = $prev_template;
			$args['updated_template'] = $updated_template;

			$message = vsprintf(
			/* translators: %1$s: Post Type Singular, %2$s: Post ID, %3$s: Updated page template */
				_x(
					'%1$s #%2$s template was changed to %3$s',
					'1: Post Type Singular, 2: Post ID, 3: Updated template',
					'stalkfish'
				),
				array( $args['post_singular_label'], $args['id'], $args['updated_template'] )
			);
			$link   = get_admin_url() . 'post.php?post=' . $args['id'] . '&action=edit';

			// Let's keep the meta clean.
			unset( $args['post_singular_label'] );

			$this->log(
				array(
					'message' => $message,
					'context' => $args['post_type'],
					'action'  => 'modified',
					'meta'    => $args,
					'link'    => $link,
				)
			);
		}
	}

	/**
	 * Record post thumbnail changes.
	 *
	 * @param int   $post_id Post ID.
	 * @param mixed $meta_value Meta value.
	 *
	 * @return void
	 */
	public function record_post_thumbnail_change( $post_id, $meta_value ) {
		$prev_featured_image    = ( isset( $this->prev_meta['_thumbnail_id'][0] ) ) ? wp_get_attachment_metadata( $this->prev_meta['_thumbnail_id'][0] ) : false;
		$updated_featured_image = wp_get_attachment_metadata( $meta_value );

		if ( empty( $updated_featured_image['file'] ) && empty( $prev_featured_image['file'] ) ) {
			return;
		}

		/* translators: %1$s: Post Type Singular, %2$s: Post ID */
		$message = _x(
			'%1$s #%2$s featured image was modified',
			'1: Post Type Singular, 2: Post ID',
			'stalkfish'
		);

		if ( empty( $prev_featured_image['file'] ) && ! empty( $updated_featured_image['file'] ) ) {
			/* translators: %1$s: Post Type Singular, %2$s: Post ID */
			$message = _x(
				'%1$s #%2$s featured image was added',
				'1: Post Type Singular, 2: Post ID',
				'stalkfish'
			);
		} elseif ( ! empty( $prev_featured_image['file'] ) && empty( $updated_featured_image['file'] ) ) {
			/* translators: %1$s: Post Type Singular, %2$s: Post ID */
			$message = _x(
				'%1$s #%2$s featured image was removed',
				'1: Post Type Singular, 2: Post ID',
				'stalkfish'
			);
		}

		$prev_image    = is_array( $prev_featured_image ) && array_key_exists( 'file', $prev_featured_image ) ? $prev_featured_image['file'] : __( 'No previous image', 'stalkfish' );
		$updated_image = is_array( $updated_featured_image ) && array_key_exists( 'file', $updated_featured_image ) ? $updated_featured_image['file'] : __( 'No image', 'stalkfish' );

		$post                             = get_post( $post_id );
		$args                  = $this->get_post_event_data( $post );
		$args['prev_image']    = $prev_image;
		$args['updated_image'] = $updated_image;

		$message = vsprintf(
			$message,
			array( $args['post_singular_label'], $args['id'] )
		);
		$link   = get_admin_url() . 'post.php?post=' . $post_id . '&action=edit';

		// Let's keep the meta clean.
		unset( $args['post_singular_label'] );

		$this->log(
			array(
				'message' => $message,
				'context' => $args['post_type'],
				'action'  => 'modified',
				'meta'    => $args,
				'link'    => $link,
			)
		);
	}
}
