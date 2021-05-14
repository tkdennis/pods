<?php
/**
 * Name: Migrate: Import from the Custom Post Type UI plugin
 *
 * Menu Name: Migrate CPT UI
 *
 * Description: Import Custom Post Types and Taxonomies from Custom Post Type UI (<a href="http://webdevstudios.com/plugin/custom-post-type-ui/">http://webdevstudios.com/plugin/custom-post-type-ui/</a>)
 *
 * Category: Migration
 *
 * Version: 1.0
 *
 * Plugin: pods-migrate-custom-post-type-ui/pods-migrate-custom-post-type-ui.php
 *
 * @package    Pods\Components
 * @subpackage Migrate-Cptui
 */

if ( class_exists( 'Pods_Migrate_CPTUI' ) ) {
	return;
}

/**
 * Class Pods_Migrate_CPTUI
 */
class Pods_Migrate_CPTUI extends PodsComponent {

	/** @var array
	 *
	 *  Support option names for multiple versions, list from newest to oldest
	 */
	private $post_option_name_list = array(
		'cptui_post_types',
		'cpt_custom_post_types',
	);

	/** @var array
	 *
	 *  Support option names for multiple versions, list from newest to oldest
	 */
	private $taxonomy_option_name_list = array(
		'cptui_taxonomies',
		'cpt_custom_tax_types',
	);

	private $api = null;

	private $post_option_name = null;

	private $taxonomy_option_name = null;

	private $post_types = array();

	private $taxonomies = array();

	/**
	 * {@inheritdoc}
	 */
	public function init() {

		$this->post_option_name = $this->get_option_name( $this->post_option_name_list );
		if ( ! is_null( $this->post_option_name ) ) {
			$this->post_types = (array) get_option( $this->post_option_name, array() );
		}
		$this->taxonomy_option_name = $this->get_option_name( $this->taxonomy_option_name_list );
		if ( ! is_null( $this->taxonomy_option_name ) ) {
			$this->taxonomies = (array) get_option( $this->taxonomy_option_name, array() );
		}
	}

	/**
	 * Enqueue styles
	 *
	 * @since 2.0.0
	 */
	public function admin_assets() {

		wp_enqueue_style( 'pods-wizard' );
	}

	/**
	 * Show the Admin
	 *
	 * @param $options
	 * @param $component
	 */
	public function admin( $options, $component ) {

		$post_types = (array) $this->post_types;
		$taxonomies = (array) $this->taxonomies;

		$method = 'migrate';
		// ajax_migrate
		pods_view( PODS_DIR . 'components/Migrate-CPTUI/ui/wizard.php', compact( array_keys( get_defined_vars() ) ) );
	}

	/**
	 * Handle the Migration AJAX
	 *
	 * @param $params
	 */
	public function ajax_migrate( $params ) {

		$post_types = (array) $this->post_types;
		$taxonomies = (array) $this->taxonomies;

		$migrate_post_types = array();

		if ( isset( $params->post_type ) && ! empty( $params->post_type ) ) {
			foreach ( $params->post_type as $post_type => $checked ) {
				if ( true === (boolean) $checked ) {
					$migrate_post_types[] = $post_type;
				}
			}
		}

		$migrate_taxonomies = array();

		if ( isset( $params->taxonomy ) && ! empty( $params->taxonomy ) ) {
			foreach ( $params->taxonomy as $taxonomy => $checked ) {
				if ( true === (boolean) $checked ) {
					$migrate_taxonomies[] = $taxonomy;
				}
			}
		}

		foreach ( $post_types as $k => $post_type ) {
			if ( ! in_array( pods_v( 'name', $post_type ), $migrate_post_types, true ) ) {
				continue;
			}

			$id = $this->migrate_post_type( $post_type );

			if ( 0 < $id ) {
				unset( $post_types[ $k ] );
			}
		}

		foreach ( $taxonomies as $k => $taxonomy ) {
			if ( ! in_array( pods_v( 'name', $taxonomy ), $migrate_taxonomies, true ) ) {
				continue;
			}

			$id = $this->migrate_taxonomy( $taxonomy );

			if ( 0 < $id ) {
				unset( $taxonomies[ $k ] );
			}
		}

		if ( 1 === (int) pods_v( 'cleanup', $params, 0 ) ) {
			if ( ! empty( $post_types ) ) {
				if ( ! is_null( $this->post_option_name ) ) {
					update_option( $this->post_option_name, $post_types );
				}
			} else {
				if ( ! is_null( $this->post_option_name ) ) {
					delete_option( $this->post_option_name );
				}
			}

			if ( ! empty( $taxonomies ) ) {
				if ( ! is_null( $this->taxonomy_option_name ) ) {
					update_option( $this->taxonomy_option_name, $taxonomies );
				}
			} else {
				if ( ! is_null( $this->taxonomy_option_name ) ) {
					delete_option( $this->taxonomy_option_name );
				}
			}
		}//end if
	}

	/**
	 *
	 *
	 * @since 2.0.0
	 *
	 * @param $post_type
	 *
	 * @return bool|int|mixed
	 */
	private function migrate_post_type( $post_type ) {
		$supports = [];

		if ( isset( $post_type['supports'] ) && is_array( $post_type['supports'] ) ) {
			// New style.
			$supports = $post_type['supports'];
		} elseif ( isset( $post_type[0] ) && is_array( $post_type[0] ) ) {
			// Old style.
			$supports = $post_type[0];
		}

		$taxonomies = [];

		if ( isset( $post_type['taxonomies'] ) && is_array( $post_type['taxonomies'] ) ) {
			// New style.
			$taxonomies = $post_type['taxonomies'];
		} elseif ( isset( $post_type[1] ) && is_array( $post_type[1] ) ) {
			// Old style.
			$taxonomies = $post_type[1];
		}

		$labels = [];

		if ( isset( $post_type['labels'] ) && is_array( $post_type['labels'] ) ) {
			// New style.
			$labels = $post_type['labels'];
		} elseif ( isset( $post_type[2] ) && is_array( $post_type[2] ) ) {
			// Old style.
			$labels = $post_type[2];
		}

		$params = array(
			'type'                     => 'post_type',
			'storage'                  => 'meta',
			'object'                   => '',
			'name'                     => pods_v( 'name', $post_type ),
			'label'                    => pods_v( 'label', $post_type ),
			'label_singular'           => pods_v( 'singular_label', $post_type ),
			'description'              => pods_v( 'description', $post_type ),
			'public'                   => pods_v( 'public', $post_type ),
			'show_ui'                  => (int) pods_v( 'show_ui', $post_type ),
			'has_archive'              => (int) pods_v( 'has_archive', $post_type ),
			'exclude_from_search'      => (int) pods_v( 'exclude_from_search', $post_type ),
			'capability_type'          => pods_v( 'capability_type', $post_type ),
			// --!! Needs sanity checking?
			'hierarchical'             => (int) pods_v( 'hierarchical', $post_type ),
			'rewrite'                  => (int) pods_v( 'rewrite', $post_type ),
			'rewrite_custom_slug'      => pods_v( 'rewrite_slug', $post_type ),
			'query_var'                => (int) pods_v( 'query_var', $post_type ),
			'menu_position'            => (int) pods_v( 'menu_position', $post_type ),
			'show_in_menu'             => (int) pods_v( 'show_in_menu', $post_type ),
			'menu_string'              => pods_v( 'show_in_menu_string', $post_type ),

			// 'supports' argument to register_post_type()
			'supports_title'           => in_array( 'title', $supports, true ),
			'supports_editor'          => in_array( 'editor', $supports, true ),
			'supports_excerpt'         => in_array( 'excerpt', $supports, true ),
			'supports_trackbacks'      => in_array( 'trackbacks', $supports, true ),
			'supports_custom_fields'   => in_array( 'custom-fields', $supports, true ),
			'supports_comments'        => in_array( 'comments', $supports, true ),
			'supports_revisions'       => in_array( 'revisions', $supports, true ),
			'supports_thumbnail'       => in_array( 'thumbnail', $supports, true ),
			'supports_author'          => in_array( 'author', $supports, true ),
			'supports_page_attributes' => in_array( 'page-attributes', $supports, true ),
			'supports_post_formats'    => in_array( 'post-formats', $supports, true ),
			'supports_custom'          => pods_v( 'custom_supports', $post_type ),

			// 'labels' argument to register_post_type()
			'menu_name'                => pods_v( 'menu_name', $labels ),
			'label_add_new'            => pods_v( 'add_new', $labels ),
			'label_add_new_item'       => pods_v( 'add_new_item', $labels ),
			'label_edit'               => pods_v( 'edit', $labels ),
			'label_edit_item'          => pods_v( 'edit_item', $labels ),
			'label_new_item'           => pods_v( 'new_item', $labels ),
			'label_view'               => pods_v( 'view', $labels ),
			'label_view_item'          => pods_v( 'view_item', $labels ),
			'label_search_items'       => pods_v( 'search_items', $labels ),
			'label_not_found'          => pods_v( 'not_found', $labels ),
			'label_not_found_in_trash' => pods_v( 'not_found_in_trash', $labels ),
			'label_parent'             => pods_v( 'parent', $labels ),
		);

		// Migrate built-in taxonomies
		$builtin = $taxonomies;

		foreach ( $builtin as $taxonomy_name ) {
			$params[ 'built_in_taxonomies_' . $taxonomy_name ] = 1;
		}

		if ( ! is_object( $this->api ) ) {
			$this->api = pods_api();
		}

		$pod = $this->api->load_pod( array( 'name' => pods_clean_name( $params['name'] ) ), false );

		if ( ! empty( $pod ) ) {
			return pods_error( sprintf( __( 'Pod with the name %s already exists', 'pods' ), pods_clean_name( $params['name'] ) ) );
		}

		$id = (int) $this->api->save_pod( $params );

		if ( empty( $id ) ) {
			return false;
		}

		$pod = $this->api->load_pod( array( 'id' => $id ), false );

		if ( empty( $pod ) ) {
			return false;
		}

		if ( $pod['name'] != $params['name'] ) {
			$this->api->rename_wp_object_type( $params['type '], $params['name'], $pod['name'] );
		}

		return $id;
	}

	/**
	 *
	 *
	 * @since 2.0.0
	 *
	 * @param $taxonomy
	 *
	 * @return bool|int|mixed
	 */
	private function migrate_taxonomy( $taxonomy ) {
		$labels = [];

		if ( isset( $taxonomy['labels'] ) && is_array( $taxonomy['labels'] ) ) {
			// New style.
			$labels = $taxonomy['labels'];
		} elseif ( isset( $taxonomy[0] ) && is_array( $taxonomy[0] ) ) {
			// Old style.
			$labels = $taxonomy[0];
		}

		$post_types = [];

		if ( isset( $taxonomy['object_types'] ) && is_array( $taxonomy['object_types'] ) ) {
			// New style.
			$post_types = $taxonomy['object_types'];
		} elseif ( isset( $taxonomy[0] ) && is_array( $taxonomy[0] ) ) {
			// Old style.
			$post_types = $taxonomy[0];
		}

		$params = array(
			'type'                             => 'taxonomy',
			'storage'                          => 'meta',
			'object'                           => '',
			'name'                             => pods_v( 'name', $taxonomy ),
			'label'                            => pods_v( 'label', $taxonomy ),
			'label_singular'                   => pods_v( 'singular_label', $taxonomy ),
			'public'                           => 1,
			'show_ui'                          => (int) pods_v( 'show_ui', $taxonomy ),
			'hierarchical'                     => (int) pods_v( 'hierarchical', $taxonomy ),
			'query_var'                        => (int) pods_v( 'query_var', $taxonomy ),
			'rewrite'                          => (int) pods_v( 'rewrite', $taxonomy ),
			'rewrite_custom_slug'              => pods_v( 'rewrite_slug', $taxonomy ),
			'label_search_items'               => pods_v( 'search_items', $labels ),
			'label_popular_items'              => pods_v( 'popular_items', $labels ),
			'label_all_items'                  => pods_v( 'all_items', $labels ),
			'label_parent'                     => pods_v( 'parent_item', $labels ),
			'label_parent_item_colon'          => pods_v( 'parent_item_colon', $labels ),
			'label_edit'                       => pods_v( 'edit_item', $labels ),
			'label_update_item'                => pods_v( 'update_item', $labels ),
			'label_add_new'                    => pods_v( 'add_new_item', $labels ),
			'label_new_item'                   => pods_v( 'new_item_name', $labels ),
			'label_separate_items_with_commas' => pods_v( 'separate_items_with_commas', $labels ),
			'label_add_or_remove_items'        => pods_v( 'add_or_remove_items', $labels ),
			'label_choose_from_the_most_used'  => pods_v( 'choose_from_most_used', $labels ),
		);

		// Migrate attach-to
		$attach = $post_types;

		foreach ( $attach as $type_name ) {
			$params[ 'built_in_post_types_' . $type_name ] = 1;
		}

		if ( ! is_object( $this->api ) ) {
			$this->api = pods_api();
		}

		$pod = $this->api->load_pod( array( 'name' => pods_clean_name( $params['name'] ) ), false );

		if ( ! empty( $pod ) ) {
			return pods_error( sprintf( __( 'Pod with the name %s already exists', 'pods' ), pods_clean_name( $params['name'] ) ) );
		}

		$id = (int) $this->api->save_pod( $params );

		if ( empty( $id ) ) {
			return false;
		}

		$pod = $this->api->load_pod( array( 'id' => $id ), false );

		if ( empty( $pod ) ) {
			return false;
		}

		if ( $pod['name'] != $params['name'] ) {
			$this->api->rename_wp_object_type( $params['type '], $params['name'], $pod['name'] );
		}

		return $id;
	}

	/**
	 *
	 * @since 2.0.0
	 */
	public function clean() {
		if ( ! is_null( $this->post_option_name ) ) {
			delete_option( $this->post_option_name );
		}

		if ( ! is_null( $this->taxonomy_option_name ) ) {
			delete_option( $this->taxonomy_option_name );
		}

	}

	/**
	 * @param array $option_name_list List of possible option names.
	 *
	 * @return null|string The first found option name, or NULL if none were found
	 */
	private function get_option_name( $option_name_list ) {

		$option_name_list = (array) $option_name_list;

		foreach ( $option_name_list as $this_option_name ) {
			if ( null !== get_option( $this_option_name, null ) ) {
				return $this_option_name;
			}
		}

		return null;

	}

}
