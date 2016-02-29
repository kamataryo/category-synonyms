<?php
/*
Plugin Name: Category Synonyms
Plugin URI: http://www.github.com/KamataRyo/category-synonyms
Description: define synonymous relationships among terms.
Author: kamataryo
Version: 0.0.1
Author URI: http://www.github.com/KamataRyo/
*/



//Load required files
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'category-synonyms-ui.php';


// settings const and var for global instance
global $categorySynonyms_instance;
define( 'CATEGORY_SYNONYMS_POST_TYPE', 'synonyms_definition' );
define( 'CATEGORY_SYNONYMS_TEXT_DOMAIN', 'category_synonyms' );
define( 'CATEGORY_SYNONYMS_DEFAULT_TAXONOMY', 'category' );
define( 'CATEGORY_SYNONYMS_TAXONOMY_FIELD_KEY', 'category_synonyms_primary_taxonomy_key' );

// instansiate the plugin classes
$categorySynonyms_instance = new CategorySynonyms( CATEGORY_SYNONYMS_POST_TYPE );



class CategorySynonyms {


    public $post_type;

    function __construct( $post_type )
    {
        $this->post_type = $post_type;
        add_action( 'init', array( &$this, 'manager_post_type_init' ) );
    }


    public function manager_post_type_init()
    {
        $labels = array(
            'name'               => _x( 'Synonyms', 'post type general name', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'singular_name'      => _x( 'Synonym', 'post type singular name', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'menu_name'          => _x( 'Synonyms', 'admin menu', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'name_admin_bar'     => _x( 'Synonym', 'add new on admin bar', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'add_new'            => _x( 'Add New', 'Synonym', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'add_new_item'       => __( 'Add New Synonym', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'new_item'           => __( 'New Synonym', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'edit_item'          => __( 'Edit Synonym', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'view_item'          => __( 'View Synonym', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'all_items'          => __( 'All Synonyms', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'search_items'       => __( 'Search Synonyms', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'parent_item_colon'  => __( 'Parent Synonyms:', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'not_found'          => __( 'No synonyms found.', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'not_found_in_trash' => __( 'No synonyms found in Trash.', CATEGORY_SYNONYMS_TEXT_DOMAIN )
        );
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => false,
            'can_export'         => true,
            'rewrite'            => array( 'slug' => 'synonym' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title' )
        );

        register_post_type( $this->post_type, $args );

        //attach all the taxonomies registered.
        foreach ( get_taxonomies() as $tax_name ) {
            register_taxonomy_for_object_type( $tax_name, $this->post_type );
        }
    }

    public function register( $arg )
    {
        if (! array_key_exists( 'terms' , $arg ) ) {
            throw new Error('no terms exception.');
        } else if ( ! is_array( $arg['terms'] ) ) {
            $arg['terms'] = array( $arg['terms'] );
        }

        $default = array(
            'label' => __( 'no synonyms definition label', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'taxonomy' => CATEGORY_SYNONYMS_DEFAULT_TAXONOMY,
        );
        $arg = array_merge( $default, $arg );


        //register terms
        $term_taxonomy_ids = array();
        foreach ( $arg['terms'] as $term ) {

            $exists = term_exists( $term, $arg['taxonomy'] );
            array_push(
                $term_taxonomy_ids,
                $exists ? $exists : wp_insert_term( $term, $arg['taxonomy'] )
            );

		}

        // `tax_input` do not accepts `category` and `post_tag`
        //// transform category name list into category id list
        $post = array(
            'post_title' => $arg['label'],
            'post_type' => $this->post_type,
        );

        $ttids_filtered = Ginq::from( $term_taxonomy_ids )
            ->select( function( $tt ){return $tt['term_taxonomy_id'] ;} )
            ->toList()
        ;

        if ( $arg['taxonomy'] === 'category' ) {
            $post['post_category'] = $ttids_filtered;
        } else if ( $arg['taxonomy'] === 'post_tag' ) {
            $post['tags_input'] = $ttids_filtered;
        } else {
            $post['tax_input'] = array( $arg['taxonomy'] => $ttids_filtered );
        }

        // create a post as synonyms definition list.
        $synonyms_definition_id = wp_insert_post( $post );
        add_post_meta(
            $synonyms_definition_id,
            CATEGORY_SYNONYMS_TAXONOMY_FIELD_KEY,
            $arg['taxonomy']
        );
        return array(
            'synonyms_definition_id' => $synonyms_definition_id, //post_id or Error
            'taxonomy' => $arg['taxonomy'],
            'term_taxonomy_ids' => $term_taxonomy_ids,
        );
    }


    public function unregister( $synonyms_definition_id )
    {
        return get_post_type( $synonyms_definition_id ) === $this->post_type ?
            wp_delete_post( $synonyms_definition_id ) : false;
    }


    public function get_synonyms_definition_by_id( $synonyms_definition_id )
    {
        // simply read synonyms by synonyms_id and return synonyms object.
        $taxonomy = get_post_meta(
            $synonyms_definition_id,
            CATEGORY_SYNONYMS_TAXONOMY_FIELD_KEY,
            true
        );

        $term_taxonomy_ids = wp_get_post_terms( $synonyms_definition_id, $taxonomy, array('fields' => 'tt_ids') );

        return array(
            'term_taxonomy_ids' => $term_taxonomy_ids,
            'taxonomy'          => $taxonomy,
        );
    }


    public function get_synonymous_terms_by( $arg )
    {
        $default = array(
            'field'    => 'name',
            'taxonomy' => CATEGORY_SYNONYMS_DEFAULT_TAXONOMY
        );
        $arg = array_merge( $default, $arg );

        $synonyms_definition_ids = get_posts( array(
            'number_posts' => -1,
            'post_type'    => $this->post_type,
            'post_status'  => 'any',
            'tax_query'    => array(
                array(
                    'taxonomy' => $arg['taxonomy'],
                    'field'    => $arg['field'],
                    'terms'    => $arg['value']
                ),
            ),
            'fields'       => 'ids'
        ) );

        $result = array(
            'term_taxonomy_ids' => array(),
            'taxonomy' => $arg['taxonomy'],
        );

        //flatten term_taonomy_ids
        foreach ($synonyms_definition_ids as $id ) {
            $tt_ids = wp_get_post_terms( $id, $arg['taxonomy'], array('fields' => 'tt_ids' ) );

            foreach ($tt_ids as $tt_id) {

                array_push( $result['term_taxonomy_ids'] , $tt_id );

            }

        }
        $result['term_taxonomy_ids'] = array_unique( $result['term_taxonomy_ids'], SORT_REGULAR );

        return $result;
    }


    function get_all_definitions() {

        $synonyms_definitions = get_posts( array(
            'number_posts' => -1,
            'post_type'    => $this->post_type,
            'post_status'  => 'any',
        ) );

        $result = array();

        foreach ( $synonyms_definitions as $def ) {

            $synonyms = $this->get_synonyms_definition_by_id( $def->ID );
            array_push( $result, array(
                'synonyms_definition_id' => $def->ID,
                'label'                  => $def->post_title,
                'taxonomy'               => $synonyms['taxonomy'],
                'terms'                  => $synonyms['term_taxonomy_ids']
            ) );
        }

        return $result;
    }
}
