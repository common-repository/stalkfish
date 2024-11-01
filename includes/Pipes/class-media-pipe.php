<?php
/**
 * Class to record media events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Media_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Media_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'media';

	/**
	 * Stores attachment data being updated.
	 *
	 * @var array
	 */
	protected $prev_attachment = array();

	/**
	 * Holds attachment alt meta if it was updated.
	 *
	 * @var array
	 */
	protected $alt_meta = array();

	/**
	 * Holds status if only alt was updated.
	 *
	 * @var bool
	 */
	protected $only_alt_update = false;

	/**
	 * Available hooks for current pipe.
	 *
	 * @var array
	 */
	public $hooks = array(
		'pre_post_update',
		'update_post_meta',
		'add_attachment',
		'edit_attachment',
		'delete_attachment',
		'wp_save_image_editor_file',
		'wp_save_image_file',
	);

	/**
	 * Available contexts and their actions for current pipe.
	 *
	 * @var array
	 */
	public $triggers = array(
		'contexts' => array(
			'document',
			'image',
			'audio',
			'video',
			'spreadsheet',
			'interactive',
			'text',
			'archive',
			'code'
		),
		'actions' => array(
			'attached',
			'uploaded',
			'added',
			'edited',
			'updated',
			'removed',
			'deleted'
		)
	);

	/**
	 * Get translated context labels
	 *
	 * @return array Context label translations
	 */
	public function get_context_labels() {
		return array(
			'image'       => esc_html__( 'Image', 'stalkfish' ),
			'audio'       => esc_html__( 'Audio', 'stalkfish' ),
			'video'       => esc_html__( 'Video', 'stalkfish' ),
			'document'    => esc_html__( 'Document', 'stalkfish' ),
			'spreadsheet' => esc_html__( 'Spreadsheet', 'stalkfish' ),
			'interactive' => esc_html__( 'Interactive', 'stalkfish' ),
			'text'        => esc_html__( 'Text', 'stalkfish' ),
			'archive'     => esc_html__( 'Archive', 'stalkfish' ),
			'code'        => esc_html__( 'Code', 'stalkfish' ),
		);
	}

	/**
	 * Get the file type for an attachment which corresponds with a context label
	 *
	 * @param string $file_uri URI of the attachment.
	 *
	 * @return string A file type which corresponds with a context label
	 */
	public function get_attachment_type( $file_uri ) {
		$extension      = pathinfo( $file_uri, PATHINFO_EXTENSION );
		$extension_type = wp_ext2type( $extension );

		if ( empty( $extension_type ) ) {
			$extension_type = 'document';
		}

		$context_labels = $this->get_context_labels();

		if ( ! isset( $context_labels[ $extension_type ] ) ) {
			$extension_type = 'document';
		}

		return $extension_type;
	}

	/**
	 * Catches alt meta changes and stores its values for logging
	 * in edit_attachment callback.
	 *
	 * @param int    $meta_id Alt meta id.
	 * @param int    $object_id Attachment post id.
	 * @param string $meta_key Alt meta key.
	 * @param string $_meta_value Updated alt meta key.
	 *
	 * @return void
	 */
	public function callback_update_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
		// Since it logs indefinitely, we simply exit if so.
		if ( '_edit_lock' === $meta_key ) {
			return;
		}

		$post = get_post( $object_id );
		if ( ( ! empty( $post ) && $post instanceof \WP_Post ) && 'attachment' === $post->post_type ) {
			if ( '_wp_attachment_image_alt' === $meta_key ) {
				$prev_meta_value = get_post_meta( $object_id, '_wp_attachment_image_alt', true ) ?? '';

				$this->alt_meta        = array(
					'post_id' => $object_id,
					'prev'    => $prev_meta_value,
					'updated' => $_meta_value,
				);
				$this->only_alt_update = true;
			}

			if ( '_edit_last' === $meta_key ) {
				$this->only_alt_update = false;
			}
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
			$this->prev_attachment = $post;
		}
	}

	/**
	 * Record attachment uploads.
	 *
	 * @param int $post_id Post ID.
	 */
	public function callback_add_attachment( $post_id ) {
		$post = get_post( $post_id );
		if ( $post->post_parent ) {
			/* translators: %1$s: attachment title, %2$s: post title */
			$message = _x(
				'Attached file "%1$s" to "%2$s"',
				'1: Attachment title, 2: Parent post title',
				'stalkfish'
			);
		} else {
			/* translators: %s: attachment title */
			$message = esc_html__( 'Uploaded file "%1$s" to Media library', 'stalkfish' );
		}

		$name         = $post->post_title;
		$url          = $post->guid;
		$parent_id    = $post->post_parent;
		$parent       = get_post( $parent_id );
		$parent_title = $parent instanceof \WP_Post ? $parent->post_title : __( 'Unidentifiable post', 'stalkfish' );
		$link         = get_admin_url() . 'upload.php?item=' . $post_id;

		$args = array(
			'id'           => $post_id,
			'title'        => $name,
			'parent_id'    => $parent_id,
			'parent_title' => $parent_title,
			'url'          => $url,
		);

		$this->log(
			array(
				'message' => vsprintf( $message, array( $args['title'], $args['parent_title'] ) ),
				'meta'    => $args,
				'context' => $this->get_attachment_type( $post->guid ),
				'action'  => $post->post_parent ? 'attached' : 'uploaded',
				'link'    => $link,
			)
		);
	}

	/**
	 * Records attachment alt meta changes.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $prev Previous alt attribute.
	 * @param string $updated Updated alt attribute.
	 *
	 * @return void
	 */
	public function record_attachment_alt_changes( $post_id, $prev, $updated ) {
		$post = get_post( $post_id );
		$name = $post->post_title;
		$url  = $post->guid;

		$args = array(
			'id'          => $post->ID,
			'title'       => $name,
			'url'         => $url,
			'updated_alt' => $updated,
		);

		$link         = get_admin_url() . 'upload.php?item=' . $post_id;

		if ( ! empty( $prev ) && empty( $updated ) ) {
			$args['prev_alt'] = $prev;

			$action  = 'removed';
			$message = vsprintf(
			/* translators: %1$s: Attachment title */
				__( 'Attachment "%1$s" alt was removed', 'stalkfish' ),
				array( $name )
			);
		} elseif ( ! empty( $prev ) && $prev !== $updated ) {
			$args['prev_alt'] = $prev;

			$action  = 'updated';
			$message = vsprintf(
			/* translators: %1$s: Attachment title, %2$s: Updated alt */
				__( 'Attachment "%1$s" alt was updated to "%2$s"', 'stalkfish' ),
				array( $name, $updated )
			);
		} else {
			$action  = 'added';
			$message = vsprintf(
			/* translators: %1$s: Attachment title, %2$s: Updated alt */
				__( 'Attachment "%1$s": alt "%2$s" was added', 'stalkfish' ),
				array( $name, $updated )
			);
		}

		$this->log(
			array(
				'message' => $message,
				'meta'    => $args,
				'context' => $this->get_attachment_type( $post->guid ),
				'action'  => $action,
				'link'    => $link,
			)
		);
	}

	/**
	 * Records attachment's modifications.
	 *
	 * @param int $post_id Post ID.
	 */
	public function callback_edit_attachment( $post_id ) {
		/**
		 * Here we are doing 2 things.
		 * 1. Avoid duplicate events caused by the media editor since it triggers two of media pipe actions
		 * 2. This action triggers after the alt meta is updated hence avoids invalid entries.
		 */
		if ( $this->only_alt_update ) {
			$this->record_attachment_alt_changes( $this->alt_meta['post_id'], $this->alt_meta['prev'], $this->alt_meta['updated'] );
			$this->only_alt_update = false;
			return;
		}

		$post = get_post( $post_id );

		$name = $post->post_title;
		$url  = $post->guid;
		$args = array(
			'id'    => $post_id,
			'title' => $name,
			'url'   => $url,
		);
		$link = get_admin_url() . 'upload.php?item=' . $post_id;

		if ( $this->prev_attachment->post_excerpt !== $post->post_excerpt ) {
			$args['updated_caption'] = $post->post_excerpt;
		}

		if ( $this->prev_attachment->post_content !== $post->post_content ) {
			$args['updated_description'] = $post->post_content;
		}

		if ( in_array( 'post_id', $this->alt_meta, true ) && $this->alt_meta['prev'] !== $this->alt_meta['updated'] ) {
			$args['updated_alt'] = $this->alt_meta['updated'];
		}

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %s: Attachment title */
					__( 'Attachment "%s" updated', 'stalkfish' ),
					array( $name )
				),
				'meta'    => $args,
				'context' => $this->get_attachment_type( $post->guid ),
				'action'  => 'updated',
				'link'    => $link,
			)
		);
	}

	/**
	 * Records attachment deletion.
	 *
	 * @param int $post_id Post ID.
	 */
	public function callback_delete_attachment( $post_id ) {
		$post      = get_post( $post_id );
		$parent_id = $post->post_parent ? $post->post_parent : __( 'Unidentifiable post', 'stalkfish' );
		$name      = $post->post_title;

		$args = array(
			'id'        => $post_id,
			'title'     => $name,
			'parent_id' => $parent_id,
		);
		if ( is_numeric( $parent_id ) ) {
			$link = get_admin_url() . 'upload.php?parent_post_id=' . $parent_id;
		} else {
			$link = '';
		}

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %s: attachment title */
					__( 'Deleted "%s"', 'stalkfish' ),
					array( $args['title'] )
				),
				'meta'    => $args,
				'context' => $this->get_attachment_type( $post->guid ),
				'action'  => 'deleted',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record changes made in the image editor
	 *
	 * @param string $dummy Unused.
	 * @param string $filename Filename.
	 * @param string $image Unused.
	 * @param string $mime_type Unused.
	 * @param int    $post_id Post ID.
	 */
	public function callback_wp_save_image_editor_file( $dummy, $filename, $image, $mime_type, $post_id ) {
		unset( $dummy );
		unset( $image );
		unset( $mime_type );

		$name = basename( $filename );
		$post = get_post( $post_id );

		$args = array(
			'id'       => $post_id,
			'title'    => $name,
			'filename' => $filename,
			'url'      => $post->guid,
		);
		$link = get_admin_url() . 'upload.php?item=' . $post_id;

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %s: an attachment title */
					__( 'Edited image "%s"', 'stalkfish' ),
					array( $args['title'] )
				),
				'meta'    => $args,
				'context' => $this->get_attachment_type( $post->guid ),
				'action'  => 'edited',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record updates made in the image editor upon saving.
	 *
	 * @param string $dummy Unused.
	 * @param string $filename Filename.
	 * @param string $image Unused.
	 * @param string $mime_type Unused.
	 * @param int    $post_id Post ID.
	 */
	public function callback_wp_save_image_file( $dummy, $filename, $image, $mime_type, $post_id ) {
		return $this->callback_wp_save_image_editor_file( $dummy, $filename, $image, $mime_type, $post_id );
	}
}
