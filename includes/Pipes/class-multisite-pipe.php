<?php
/**
 * Class to record multisite events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Multisite_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Multisite_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'multisite';

	/**
	 * Available hooks for current pipe.
	 *
	 * @var array
	 */
	public $hooks = array(
		/**
		 * Site creation.
		 */
		'wp_initialize_site',
		'wpmu_activate_blog',
		/**
		 * Site users.
		 */
		'add_user_to_blog',
		'remove_user_from_blog',
		/**
		 * Site modifications.
		 */
		'wp_delete_site',
		'make_spam_blog',
		'make_ham_blog',
		'mature_blog',
		'unmature_blog',
		'archive_blog',
		'unarchive_blog',
		'make_delete_blog',
		'make_undelete_blog',
		'update_blog_public',
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
			'trashed',
			'restored',
			'archived',
			'unarchived'
		)
	);

	/**
	 * Register pipe at Frontend
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
	 * Get triggers for each pipe/context and their actions.
	 *
	 * @return void
	 */
	public function get_pipe_triggers() {
		// Contexts.
		if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
			$sites                   = get_sites();
			foreach ( $sites as $site ) {
				$this->triggers['contexts'][] = sanitize_title( $site->blogname );
			}
		}
	}

	/**
	 * Record site creation.
	 *
	 * @param \WP_Site $new_site New site object.
	 * @param array    $args Arguments for the initialization.
	 */
	public function callback_wp_initialize_site( $new_site, $args ) {
		$blogname = ! empty( $args['title'] ) ? $args['title'] : $new_site->blogname;

		$args = array(
			'id'    => $new_site->blog_id,
			'title' => ! empty( $blogname ) ? $blogname : 'Site %d',
			'url'   => $new_site->siteurl,
		);

		$link = network_site_url( '/wp-admin/network/' ) . 'site-info.php?id=' . $args['id'];

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %1$s: Site title */
					_x(
						'New site "%s" created',
						'1. Site title',
						'stalkfish'
					),
					array( $args['title'] )
				),
				'meta'    => $args,
				'context' => sanitize_title( $blogname ),
				'action'  => 'created',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record site deletion.
	 *
	 * @param \WP_Site $ex_site Deleted site object.
	 */
	public function callback_wp_delete_site( $ex_site ) {
		$args = array(
			'id'    => $ex_site->blog_id,
			'title' => $ex_site->blogname,
			'url'   => $ex_site->siteurl,
		);

		$link = network_site_url( '/wp-admin/network/' ) . 'sites.php';

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: Site title */
					_x(
						'Deleted "%s" site',
						'1. Site title',
						'stalkfish'
					),
					array( $args['title'] )
				),
				'meta'    => $args,
				'context' => sanitize_title( $ex_site->blogname ),
				'action'  => 'deleted',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record site registered.
	 *
	 * @param int $blog_id Blog ID.
	 * @param int $user_id User ID.
	 */
	public function callback_wpmu_activate_blog( $blog_id, $user_id ) {
		$blog = get_site( $blog_id );

		$args = array(
			'id'      => $blog->blog_id,
			'title'   => $blog->blogname,
			'url'     => $blog->siteurl,
			'user_id' => $user_id,
		);

		$link = network_site_url( '/wp-admin/network/' ) . 'site-info.php?id=' . $args['id'];

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: Site title */
					_x(
						'Registered "%s" site',
						'1. Site title',
						'stalkfish'
					),
					array( $args['title'] )
				),
				'meta'    => $args,
				'context' => sanitize_title( $blog->blogname ),
				'action'  => 'created',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record user added to a site.
	 *
	 * @param int    $user_id User ID.
	 * @param string $role User role.
	 * @param int    $blog_id Blog ID.
	 */
	public function callback_add_user_to_blog( $user_id, $role, $blog_id ) {
		$blog = get_site( $blog_id );
		$user = get_user_by( 'id', $user_id );

		if ( ! is_a( $user, 'WP_User' ) ) {
			return;
		}

		$args = array(
			'id'        => $blog->blog_id,
			'title'     => $blog->blogname,
			'url'       => $blog->siteurl,
			'user_id'   => $user_id,
			'username'  => $user->user_login,
			'user_role' => $role,
		);

		$link = network_site_url( '/wp-admin/network/' ) . 'site-users.php?id=' . $blog_id;

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: Username, %2$s: Site title, %3$s: User role */
					_x(
						'User "%1$s" added to the "%2$s" site as %3$s',
						'1. User name, 2. Site title, 3. User Role',
						'stalkfish'
					),
					array( $args['username'], $args['title'], $args['user_role'] )
				),
				'meta'    => $args,
				'context' => sanitize_title( $blog->blogname ),
				'action'  => 'updated',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record user removed from a site.
	 *
	 * @param int $user_id User ID.
	 * @param int $blog_id Blog ID.
	 */
	public function callback_remove_user_from_blog( $user_id, $blog_id ) {
		$blog = get_site( $blog_id );
		$user = get_user_by( 'id', $user_id );

		if ( ! is_a( $user, 'WP_User' ) ) {
			return;
		}

		$args = array(
			'id'       => $blog->blog_id,
			'title'    => $blog->blogname,
			'url'      => $blog->siteurl,
			'user_id'  => $user_id,
			'username' => $user->user_login,
		);

		$link = network_site_url( '/wp-admin/network/' ) . 'site-users.php?id=' . $blog_id;

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: Username, %2$s: Site title, %3$s: User role */
					_x(
						'User "%1$s" removed from the "%2$s" site',
						'1. User name, 2. Site title, 3. User Role',
						'stalkfish'
					),
					array( $args['username'], $args['title'], $args['user_role'] )
				),
				'meta'    => $args,
				'context' => sanitize_title( $blog->blogname ),
				'action'  => 'updated',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record site status update.
	 *
	 * @param int    $blog_id Blog ID.
	 * @param string $status Blog Status.
	 * @param string $action Action.
	 */
	public function record_update_site_status( $blog_id, $status, $action ) {
		$blog = get_site( $blog_id );

		$args = array(
			'id'     => $blog->blog_id,
			'title'  => $blog->blogname,
			'url'    => $blog->siteurl,
			'status' => $status,
		);

		$link = network_site_url( '/wp-admin/network/' ) . 'site-info.php?id=' . $args['id'];

		$this->log(
			array(
				'message' => vsprintf(
					/* translators: %1$s: Username, %2$s: Site status */
					_x(
						'Site "%1$s" was %2$s',
						'1. Site title, 2. Site status',
						'stalkfish'
					),
					array( $args['title'], $args['status'] )
				),
				'meta'    => $args,
				'context' => sanitize_title( $blog->blogname ),
				'action'  => $action,
				'link'    => $link,
			)
		);
	}

	/**
	 * Record site set to public/private.
	 *
	 * @param int    $blog_id Blog ID.
	 * @param string $value Status flag.
	 */
	public function callback_update_blog_public( $blog_id, $value ) {
		if ( absint( $value ) ) {
			$status = esc_html__( 'set to public', 'stalkfish' );
		} else {
			$status = esc_html__( 'set to private', 'stalkfish' );
		}

		$this->record_update_site_status( $blog_id, $status, 'updated' );
	}

	/**
	 * Blog marked as deleted
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function callback_make_delete_blog( $blog_id ) {
		$this->record_update_site_status( $blog_id, esc_html__( 'trashed', 'stalkfish' ), 'trashed' );
	}

	/**
	 * Record site set to un-deleted.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function callback_make_undelete_blog( $blog_id ) {
		$this->record_update_site_status( $blog_id, esc_html__( 'restored', 'stalkfish' ), 'restored' );
	}

	/**
	 * Record site marked as spam.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function callback_make_spam_blog( $blog_id ) {
		$this->record_update_site_status( $blog_id, esc_html__( 'marked as spam', 'stalkfish' ), 'updated' );
	}

	/**
	 * Record site removed from spam.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function callback_make_ham_blog( $blog_id ) {
		$this->record_update_site_status( $blog_id, esc_html__( 'removed from spam', 'stalkfish' ), 'updated' );
	}

	/**
	 * Record site set to mature.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function callback_mature_blog( $blog_id ) {
		$this->record_update_site_status( $blog_id, esc_html__( 'set to mature', 'stalkfish' ), 'updated' );
	}

	/**
	 * Record site removed from mature
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function callback_unmature_blog( $blog_id ) {
		$this->record_update_site_status( $blog_id, esc_html__( 'removed from mature', 'stalkfish' ), 'updated' );
	}

	/**
	 * Record site archive.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function callback_archive_blog( $blog_id ) {
		$this->record_update_site_status( $blog_id, esc_html__( 'archived', 'stalkfish' ), 'archived' );
	}

	/**
	 * Record site un-archive.
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function callback_unarchive_blog( $blog_id ) {
		$this->record_update_site_status( $blog_id, esc_html__( 'unarchived', 'stalkfish' ), 'unarchived' );
	}
}
