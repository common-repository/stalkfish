<?php
/**
 * Class to record comment events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Comments_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Comments_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'comments';

	/**
	 * Available hooks for current pipe.
	 *
	 * @var array
	 */
	public $hooks = array(
		/**
		 * Post comments.
		 */
		'wp_insert_comment',
		/**
		 * Edit comments.
		 */
		'edit_comment',
		'transition_comment_status',
		/**
		 * Delete/trash comments.
		 */
		'before_delete_post',
		'deleted_post',
		'delete_comment',
		'trash_comment',
		'untrash_comment',
		/**
		 * Spam comments.
		 */
		'spam_comment',
		'unspam_comment',
		/**
		 * Additional events.
		 */
		'comment_duplicate_trigger',
		'comment_flood_trigger',
	);

	/**
	 * Available contexts and their actions for current pipe.
	 *
	 * @var array
	 */
	public $triggers = array(
		'contexts' => array(
			'comments',
		),
		'actions' => array(
			'flood',
			'replied',
			'created',
			'edited',
			'spammed',
			'deleted',
			'trashed',
			'untrashed',
			'unspammed',
			'duplicate',
		)
	);

	/**
	 * Store the post ID during post deletion.
	 *
	 * @var int
	 */
	protected $delete_post = 0;

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
		$post_types = get_post_types_by_support( array( 'comments' ) );
		foreach ( $post_types as $post_type ) {
			$this->triggers['contexts'][] = $post_type;
		}
	}

	/**
	 * Get the comment type label for a given comment ID
	 *
	 * @param int $comment_id ID of the comment.
	 *
	 * @return string The comment type label
	 */
	public function get_comment_type_label( $comment_id ) {
		$comment_type = get_comment_type( $comment_id );

		if ( empty( $comment_type ) ) {
			$comment_type = 'comment';
		}

		/**
		 * Filter through translated comment type labels
		 *
		 * @param array Comment type label translations
		 */
		$comment_type_labels = apply_filters(
			'stalkfish_comments_comment_type_labels',
			array(
				'comment'   => esc_html__( 'Comment', 'stalkfish' ),
				'trackback' => esc_html__( 'Trackback', 'stalkfish' ),
				'pingback'  => esc_html__( 'Pingback', 'stalkfish' ),
			)
		);

		$label = isset( $comment_type_labels[ $comment_type ] ) ? $comment_type_labels[ $comment_type ] : $comment_type;

		return $label;
	}

	/**
	 * Record comment flood blocks.
	 *
	 * @param string $time_lastcomment Time of last comment before block.
	 * @param string $time_newcomment Time of first comment after block.
	 */
	public function callback_comment_flood_trigger( $time_lastcomment, $time_newcomment ) {
		$req_user_login = get_option( 'comment_registration' );

		if ( $req_user_login ) {
			$user      = wp_get_current_user();
			$user_id   = $user->ID;
			$user_name = $user->display_name;
			$link = get_admin_url() . 'user-edit.php?user_id=' . $user_id;
		} else {
			$user_name = esc_html__( 'a logged out user', 'stalkfish' );
			$link = get_admin_url() . 'edit-comments.php';
		}

		$args = array(
			'id'               => $user_id ?? null,
			'username'         => $user_name,
			'time_lastcomment' => $time_lastcomment,
			'time_newcomment'  => $time_newcomment,
		);

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: username */
					__(
						'Detected & prevented comment flooding by %1$s',
						'stalkfish'
					),
					array( $args['username'] )
				),
				'meta'    => $args,
				'context' => 'comments',
				'action'  => 'flood',
				'link'    => $link
			)
		);
	}

	/**
	 * Get comment data from comment object.
	 *
	 * @param \WP_Comment $comment Comment object.
	 *
	 * @return array An array of comment data.
	 */
	public function get_comment_data( $comment ) {
		$post_id      = $comment->comment_post_ID;
		$post         = get_post( $post_id );
		$post_title   = $post ? "\"$post->post_title\"" : esc_html__( 'a post', 'stalkfish' );
		$comment_type = mb_strtolower( $this->get_comment_type_label( $comment->comment_ID ) );
		$username     = $this->get_comment_author( $comment, 'name' );
		$user_id      = (int) $this->get_comment_author( $comment );

		$data = array(
			'id'         => $comment->comment_ID,
			'type'       => $comment_type,
			'post_id'    => $post_id,
			'post_title' => $post_title,
		);

		// In some plugin actions user fields remain empty and non-existent, if so skip it.
		if ( ! empty( $username ) ) {
			$data['username'] = $username;
		}

		if ( ! empty( $user_id ) && $user_id ) {
			$data['user_id'] = $user_id;
		}

		return $data;
	}

	/**
	 * Record comment creation.
	 *
	 * @param int         $comment_id Comment ID.
	 * @param \WP_Comment $comment Comment object.
	 */
	public function callback_wp_insert_comment( $comment_id, $comment ) {
		if ( in_array( $comment->comment_type, $this->get_ignored_comment_types(), true ) ) {
			return;
		}

		$comment_data           = $this->get_comment_data( $comment );
		$post_type              = get_post_type( $comment_data['post_id'] );
		$comment_data['status'] = ( '1' === $comment->comment_approved ) ? esc_html__( 'approved', 'stalkfish' ) : esc_html__( 'pending approval', 'stalkfish' );
		$is_spam                = false;
		$link                   = get_admin_url() . 'comment.php?action=editcomment&c=' . $comment_id;

		if ( class_exists( 'Akismet' ) && \Akismet::matches_last_comment( $comment ) ) {
			$ak_last_comment = \Akismet::get_last_comment();
			if ( 'true' === $ak_last_comment['akismet_result'] ) {
				$is_spam                = true;
				$comment_data['status'] = esc_html__( 'marked as spam by Akismet', 'stalkfish' );
			}
		}
		if ( $comment->comment_parent ) {
			$parent_user_name                  = get_comment_author( $comment->comment_parent );
			$comment_data['reply_to_username'] = $parent_user_name;

			$this->log(
				array(
					'message' => vsprintf(
					/* translators: %1$s: parent comment's author, %2$s: comment author, %3$s: post title, %4$s: comment status, %5$s: comment type */
						_x(
							'Reply to %1$s\'s %5$s by %2$s on %3$s %4$s',
							"1: Parent comment's author, 2: Comment author, 3: Post title, 4: Comment status, 5: Comment type",
							'stalkfish'
						),
						array(
							$comment_data['reply_to_username'],
							$comment_data['username'],
							$comment_data['post_title'],
							$comment_data['status'],
							$comment_data['type'],
						)
					),
					'meta'    => $comment_data,
					'context' => $post_type,
					'action'  => 'replied',
					'link'    => $link,
				)
			);
		} else {
			$this->log(
				array(
					'message' => vsprintf(
					/* translators: %1$s: comment author, %2$s: post title, %3$s: comment status, %4$s: and comment type */
						_x(
							'New %4$s by %1$s on %2$s %3$s',
							'1: Comment author, 2: Post title 3: Comment status, 4: Comment type',
							'stalkfish'
						),
						array(
							$comment_data['username'] ?? __( 'Guest', 'stalkfish' ),
							$comment_data['post_title'],
							$comment_data['status'],
							$comment_data['type'],
						)
					),
					'meta'    => $comment_data,
					'context' => $post_type,
					'action'  => $is_spam ? 'spammed' : 'created',
					'link'    => $link,
				)
			);
		}
	}

	/**
	 * Record comment updates.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function callback_edit_comment( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( in_array( $comment->comment_type, $this->get_ignored_comment_types(), true ) ) {
			return;
		}

		$comment_data = $this->get_comment_data( $comment );
		$post_type    = get_post_type( $comment_data['post_id'] );
		$link         = get_admin_url() . 'comment.php?action=editcomment&c=' . $comment_id;

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: comment author, %2$s: post title, %3$s: comment type */
					_x(
						'Edited %1$s\'s %3$s on %2$s',
						'1: Comment author, 2: Post title, 3: Comment type',
						'stalkfish'
					),
					array( $comment_data['username'], $comment_data['post_title'], $comment_data['type'] )
				),
				'meta'    => $comment_data,
				'context' => $post_type,
				'action'  => 'edited',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record comment status updates.
	 *
	 * @param string      $updated_status Updated comment status.
	 * @param string      $prev_status Previous comment status.
	 * @param \WP_Comment $comment Comment object.
	 */
	public function callback_transition_comment_status( $updated_status, $prev_status, $comment ) {
		if ( in_array( $comment->comment_type, $this->get_ignored_comment_types(), true ) ) {
			return;
		}

		if ( 'approved' !== $updated_status && 'unapproved' !== $updated_status || 'trash' === $prev_status || 'spam' === $prev_status ) {
			return;
		}

		$comment_data                   = $this->get_comment_data( $comment );
		$post_type                      = get_post_type( $comment_data['post_id'] );
		$comment_data['prev_status']    = $prev_status;
		$comment_data['updated_status'] = $updated_status;
		$link                           = get_admin_url() . 'comment.php?action=editcomment&c=' . $comment_data['id'];

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %1$s: Username, %2$s: Comment type, %3$s: Post Title, %4$s: Previous Status, %5$s: Updated Status */
					_x(
						'Status for %1$s\'s %2$s on %3$s has been updated from %4$s to %5$s',
						'1: Comment author, 2: Comment type, 3: Post title, 4: Comment previous status, 5: Comment updated status',
						'stalkfish'
					),
					array(
						$comment_data['username'],
						$comment_data['type'],
						$comment_data['post_title'],
						$comment_data['prev_status'],
						$comment_data['updated_status'],
					)
				),
				'meta'    => $comment_data,
				'context' => $post_type,
				'action'  => $updated_status,
				'link'    => $link,
			)
		);
	}

	/**
	 * Store the post ID during deletion
	 *
	 * @param int $post_id Post ID.
	 */
	public function callback_before_delete_post( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->delete_post = $post_id;
	}

	/**
	 * Reset the stored post ID after deletion
	 *
	 * @param int $post_id Post ID.
	 */
	public function callback_deleted_post( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->delete_post = 0;
	}

	/**
	 * Record comment deletion.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function callback_delete_comment( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( in_array( $comment->comment_type, $this->get_ignored_comment_types(), true ) ) {
			return;
		}

		$comment_data = $this->get_comment_data( $comment );
		$post_type    = get_post_type( $comment_data['post_id'] );
		$link         = get_admin_url() . 'edit-comments.php';

		if ( $this->delete_post === $comment_data['post_id'] ) {
			return;
		}

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %1$s: Username, %2$s: Comment type, %3$s: Post Title */
					_x(
						'Deleted %1$s\'s %2$s on %3$s permanently',
						'1: Comment author, 2: Comment type, 3: Post title',
						'stalkfish'
					),
					array( $comment_data['username'], $comment_data['type'], $comment_data['post_title'] )
				),
				'meta'    => $comment_data,
				'context' => $post_type,
				'action'  => 'deleted',
				'link'    => $link,
			)
		);
	}


	/**
	 * Record comment trashing.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function callback_trash_comment( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( in_array( $comment->comment_type, $this->get_ignored_comment_types(), true ) ) {
			return;
		}

		$comment_data = $this->get_comment_data( $comment );
		$post_type    = get_post_type( $comment_data['post_id'] );
		$link         = get_admin_url() . 'comment.php?action=editcomment&c=' . $comment_id;

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %1$s: Username, %2$s: Comment type, %3$s: Post Title */
					_x(
						'Trashed %1$s\'s %2$s on %3$s',
						'1: Comment author, 2: Comment type, 3: Post title',
						'stalkfish'
					),
					array( $comment_data['username'], $comment_data['type'], $comment_data['post_title'] )
				),
				'meta'    => $comment_data,
				'context' => $post_type,
				'action'  => 'trashed',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record comment un-trashing.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function callback_untrash_comment( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( in_array( $comment->comment_type, $this->get_ignored_comment_types(), true ) ) {
			return;
		}

		$comment_data = $this->get_comment_data( $comment );
		$post_type    = get_post_type( $comment_data['post_id'] );
		$link         = get_admin_url() . 'comment.php?action=editcomment&c=' . $comment_id;

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %1$s: Username, %2$s: Comment type, %3$s: Post Title */
					_x(
						'Restored %1$s\'s %3$s on %2$s',
						'1: Comment author, 2: Comment type, 3: Post title',
						'stalkfish'
					),
					array( $comment_data['username'], $comment_data['type'], $comment_data['post_title'] )
				),
				'meta'    => $comment_data,
				'context' => $post_type,
				'action'  => 'untrashed',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record comment marked as spam.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function callback_spam_comment( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( in_array( $comment->comment_type, $this->get_ignored_comment_types(), true ) ) {
			return;
		}

		$comment_data = $this->get_comment_data( $comment );
		$post_type    = get_post_type( $comment_data['post_id'] );
		$link         = get_admin_url() . 'comment.php?action=editcomment&c=' . $comment_id;

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %1$s: Username, %2$s: Comment type, %3$s: Post Title */
					_x(
						'Marked %1$s\'s %2$s on %3$s as spam',
						'1: Comment author, 2: Comment type, 3: Post title',
						'stalkfish'
					),
					array( $comment_data['username'], $comment_data['type'], $comment_data['post_title'] )
				),
				'meta'    => $comment_data,
				'context' => $post_type,
				'action'  => 'spammed',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record comment un-marked as spam.
	 *
	 * @param int $comment_id Comment ID.
	 */
	public function callback_unspam_comment( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( in_array( $comment->comment_type, $this->get_ignored_comment_types(), true ) ) {
			return;
		}

		$comment_data = $this->get_comment_data( $comment );
		$post_type    = get_post_type( $comment_data['post_id'] );
		$link         = get_admin_url() . 'comment.php?action=editcomment&c=' . $comment_id;

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %1$s: Username, %2$s: Comment type, %3$s: Post Title */
					_x(
						'Unmarked %1$s\'s %2$s on %3$s as spam',
						'1: Comment author, 2: Comment type, 3: Post title',
						'stalkfish'
					),
					array( $comment_data['username'], $comment_data['type'], $comment_data['post_title'] )
				),
				'meta'    => $comment_data,
				'context' => $post_type,
				'action'  => 'unspammed',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record duplicate commenting attempts.
	 */
	public function callback_comment_duplicate_trigger() {
		global $wpdb;
		if ( ! empty( $wpdb->last_result ) ) {
			return;
		}

		$comment_id = $wpdb->last_result[0]->comment_ID;
		$comment    = get_comment( $comment_id );

		if ( in_array( $comment->comment_type, $this->get_ignored_comment_types(), true ) ) {
			return;
		}

		$comment_data = $this->get_comment_data( $comment );
		$post_type    = get_post_type( $comment_data['post_id'] );
		$link         = get_admin_url() . 'user-edit.php?user_id=' . $comment_data['user_id'];

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %1$s: Username, %2$s: Comment type, %3$s: Post Title */
					_x(
						'Duplicate %2$s by %1$s prevented on %3$s',
						'1: Comment author, 2: Comment type, 3: Post title',
						'stalkfish'
					),
					array( $comment_data['username'], $comment_data['type'], $comment_data['post_title'] )
				),
				'meta'    => $comment_data,
				'context' => $post_type,
				'action'  => 'duplicate',
				'link'    => $link,
			)
		);
	}

	/**
	 * Get the comment author and returns the specified field.
	 *
	 * @param object|int $comment A comment object or comment ID.
	 * @param string     $field What field you want to return.
	 *
	 * @return int|string $output User ID or user display name
	 */
	public function get_comment_author( $comment, $field = 'id' ) {
		$comment = is_object( $comment ) ? $comment : get_comment( absint( $comment ) );

		$req_name_email = get_option( 'require_name_email' );
		$req_user_login = get_option( 'comment_registration' );

		$user_id   = 0;
		$user_name = esc_html__( 'Guest', 'stalkfish' );

		$output = '';

		if ( $req_name_email && isset( $comment->comment_author_email ) && isset( $comment->comment_author ) ) {
			$user      = get_user_by( 'email', $comment->comment_author_email );
			$user_id   = isset( $user->ID ) ? $user->ID : 0;
			$user_name = isset( $user->display_name ) ? $user->display_name : $comment->comment_author;
		}

		if ( $req_user_login ) {
			$user      = wp_get_current_user();
			$user_id   = $user->ID;
			$user_name = $user->display_name;
		}

		if ( 'id' === $field ) {
			$output = $user_id;
		} elseif ( 'name' === $field ) {
			$output = $user_name;
		}

		return $output;
	}

	/**
	 * Get ignored comment types.
	 *
	 * @return  array  List of ignored comment types
	 */
	public function get_ignored_comment_types() {
		return apply_filters(
			'stalkfish_comments_exclude_comment_types',
			array()
		);
	}
}
