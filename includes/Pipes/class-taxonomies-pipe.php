<?php
/**
 * Class to record taxonomy events.
 *
 * @package Stalkfish\Pipes
 */

namespace Stalkfish\Pipes;

/**
 * Class Taxonomies_Pipe
 *
 * @package Stalkfish\Pipes
 */
class Taxonomies_Pipe extends Pipe {
	/**
	 * Pipe name
	 *
	 * @var string
	 */
	public $name = 'taxonomies';

	/**
	 * Available hooks for current pipe.
	 *
	 * @var array
	 */
	public $hooks = array(
		'created_term',
		'delete_term',
		'edit_term',
		'edited_term',
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
		)
	);

	/**
	 * Register pipe in the WP Frontend
	 *
	 * @var bool
	 */
	public $register_frontend = false;

	/**
	 * Store taxonomy labels.
	 *
	 * @var array
	 */
	public $context_labels;

	/**
	 * Store term values before update
	 *
	 * @var Object
	 */
	public $term_before_update;

	/**
	 * Adds on to pipe triggers.
	 */
	public function __construct() {
		$this->get_pipe_triggers();
	}

	/**
	 * Register all context hooks.
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		$this->get_context_labels();
	}

	/**
	 * Get triggers for each pipe/context and their actions.
	 *
	 * @return void
	 */
	public function get_pipe_triggers() {
		global $wp_taxonomies;

		// Contexts.
		foreach ( $wp_taxonomies as $tax_id => $tax ) {
			if ( in_array( $tax_id, $this->get_excluded_taxonomies(), true ) ) {
				continue;
			}
			$this->triggers['contexts'][] = $tax_id;
		}
	}

	/**
	 * Get translated context labels
	 *
	 * @return array Context label translations
	 */
	public function get_context_labels() {
		global $wp_taxonomies;

		$labels = wp_list_pluck( $wp_taxonomies, 'labels' );

		$this->context_labels = wp_list_pluck( $labels, 'singular_name' );

		add_action( 'registered_taxonomy', array( $this, 'registered_taxonomy' ), 10, 3 );

		return $this->context_labels;
	}

	/**
	 * Store registration of taxonomies after inital loading for labels.
	 *
	 * @param string       $taxonomy Taxonomy slug.
	 * @param array|string $object_type Object type or array of object types.
	 * @param array|string $args Array or string of taxonomy registration arguments.
	 */
	public function registered_taxonomy( $taxonomy, $object_type, $args ) {
		unset( $object_type );

		$taxonomy_obj = (object) $args;
		$label        = get_taxonomy_labels( $taxonomy_obj )->singular_name;

		$this->context_labels[ $taxonomy ] = $label;
	}

	/**
	 * Record term creation.
	 *
	 * @param integer $term_id Term ID.
	 * @param integer $tt_id Taxonomy term ID.
	 * @param string  $taxonomy Taxonomy name.
	 */
	public function callback_created_term( $term_id, $tt_id, $taxonomy ) {
		if ( in_array( $taxonomy, $this->get_excluded_taxonomies(), true ) ) {
			return;
		}

		$term           = get_term( $term_id, $taxonomy );
		$taxonomy_label = strtolower( $this->context_labels[ $taxonomy ] );

		$args = array(
			'id'             => $term_id,
			'name'           => $term->name,
			'taxonomy'       => $taxonomy,
			'taxonomy_label' => $taxonomy_label,
			'parent'         => $term->parent,
		);

		$link = get_admin_url() . 'term.php?taxonomy=' . $taxonomy . '&tag_ID=' . $term_id;

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: term name, %2$s: taxonomy singular label */
					_x(
						'Created "%1$s" %2$s',
						'1: Term name, 2: Taxonomy singular label',
						'stalkfish'
					),
					array( $args['name'], $args['taxonomy_label'] )
				),
				'meta'    => $args,
				'context' => $taxonomy,
				'action'  => 'created',
				'link'    => $link,
			)
		);
	}

	/**
	 * Record term deletion.
	 *
	 * @param integer $term_id Term ID.
	 * @param integer $tt_id Taxonomy term ID.
	 * @param string  $taxonomy Taxonomy name.
	 * @param object  $deleted_term Deleted term object.
	 */
	public function callback_delete_term( $term_id, $tt_id, $taxonomy, $deleted_term ) {
		if ( in_array( $taxonomy, $this->get_excluded_taxonomies(), true ) ) {
			return;
		}

		$taxonomy_label = strtolower( $this->context_labels[ $taxonomy ] );

		$args = array(
			'id'             => $term_id,
			'name'           => $deleted_term->name,
			'taxonomy'       => $taxonomy,
			'taxonomy_label' => $taxonomy_label,
			'parent'         => $deleted_term->parent,
		);

		$link = get_admin_url() . 'edit-tags.php?taxonomy=' . $taxonomy;

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: term name, %2$s: taxonomy singular label */
					_x(
						'Deleted "%1$s" %2$s',
						'1: Term name, 2: Taxonomy singular label',
						'stalkfish'
					),
					array( $args['name'], $args['taxonomy_label'] )
				),
				'meta'    => $args,
				'context' => $taxonomy,
				'action'  => 'deleted',
				'link'    => $link,
			)
		);
	}

	/**
	 * Store term updates for later callback to record.
	 *
	 * @param integer $term_id Term ID.
	 * @param integer $tt_id Taxonomy term ID.
	 * @param string  $taxonomy Taxonomy name.
	 */
	public function callback_edit_term( $term_id, $tt_id, $taxonomy ) {
		unset( $tt_id );
		$this->term_before_update = get_term( $term_id, $taxonomy );
	}

	/**
	 * Record term updates.
	 *
	 * @param integer $term_id Term ID.
	 * @param integer $tt_id Taxonomy term ID.
	 * @param string  $taxonomy Taxonomy name.
	 */
	public function callback_edited_term( $term_id, $tt_id, $taxonomy ) {
		if ( in_array( $taxonomy, $this->get_excluded_taxonomies(), true ) ) {
			return;
		}

		$term = $this->term_before_update;

		if ( ! $term ) {
			$term = get_term( $term_id, $taxonomy );
		}

		$taxonomy_label = strtolower( $this->context_labels[ $taxonomy ] );

		$args = array(
			'id'             => $term_id,
			'name'           => $term->name,
			'taxonomy'       => $taxonomy,
			'taxonomy_label' => $taxonomy_label,
			'parent'         => $term->parent,
		);

		$link = get_admin_url() . 'term.php?taxonomy=' . $taxonomy . '&tag_ID=' . $term_id;

		$this->log(
			array(
				'message' => vsprintf(
				/* translators: %1$s: term name, %2$s: taxonomy singular label */
					_x(
						'Updated "%1$s" %2$s',
						'1: Term name, 2: Taxonomy singular label',
						'stalkfish'
					),
					array( $args['name'], $args['taxonomy_label'] )
				),
				'meta'    => $args,
				'context' => $taxonomy,
				'action'  => 'updated',
				'link'    => $link,
			)
		);
	}

	/**
	 * Get excluded taxonomies.
	 *
	 * @return array List of excluded taxonomies
	 */
	public function get_excluded_taxonomies() {
		return apply_filters(
			'stalkfish_taxonomies_exclude_taxonomies',
			array(
				'nav_menu',
				'post_format',
				'link_category',
				'wp_theme',
				'wp_template_part_area',
			)
		);
	}
}
