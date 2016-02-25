<?php
class TermSynonyms {

    const TEXTDOMAIN = 'term_synonyms';
    public $post_type;

    function __construct( $post_type ) {
        $this->post_type = $post_type;
        add_action( 'init'         , array( &$this, 'manager_post_type_init' ) );
        add_action( 'pre_get_posts', array( &$this, 'add_synonym_terms_pre_get_posts' ) );
    }


    public function manager_post_type_init() {
        $labels = array(
            'name'               => _x( 'Synonyms', 'post type general name', self::TEXTDOMAIN ),
            'singular_name'      => _x( 'Synonym', 'post type singular name', self::TEXTDOMAIN ),
            'menu_name'          => _x( 'Synonyms', 'admin menu', self::TEXTDOMAIN ),
            'name_admin_bar'     => _x( 'Synonym', 'add new on admin bar', self::TEXTDOMAIN ),
            'add_new'            => _x( 'Add New', 'Synonym', self::TEXTDOMAIN ),
            'add_new_item'       => __( 'Add New Synonym', self::TEXTDOMAIN ),
            'new_item'           => __( 'New Synonym', self::TEXTDOMAIN ),
            'edit_item'          => __( 'Edit Synonym', self::TEXTDOMAIN ),
            'view_item'          => __( 'View Synonym', self::TEXTDOMAIN ),
            'all_items'          => __( 'All Synonyms', self::TEXTDOMAIN ),
            'search_items'       => __( 'Search Synonyms', self::TEXTDOMAIN ),
            'parent_item_colon'  => __( 'Parent Synonyms:', self::TEXTDOMAIN ),
            'not_found'          => __( 'No synonyms found.', self::TEXTDOMAIN ),
            'not_found_in_trash' => __( 'No synonyms found in Trash.', self::TEXTDOMAIN )
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
        $default_taxonomies = array_values( get_taxonomies() );
        foreach ( $default_taxonomies as $taxonomy ) {
            register_taxonomy_for_object_type( $taxonomy, $this->post_type );
        }
    }


    public function add_synonym_terms_pre_get_posts( $query ) {
        if( ! $query->is_admin() && $query->is_category() ){
            if ($query->query[ 'post_type']  === $this->post_type) {
                return;
            }

            # ここでしたいこと
            # tax_queryを改変して、全てのシノニムを追加したい

            $queries = $query->tax_query->queries;
            foreach ($queries as $query) {
                $taxonomy = $query[ 'taxonomy' ];
                foreach ( $query[ 'terms' ] as $term ) {
                    $synonyms_list = $this->synonymsOf( $term, $taxonomy );
                    foreach ($synonyms_list as $taxonomy => $synonyms ) {
                        // $query->tax_query->queries[ $taxonomy ] = array_merge($query->tax_query->queries[ $taxonomy ], $synonyms );
                    }
                }
            }
        }
    }


    public function register( $synonyms, $label ) {
        //register terms
        foreach ( $synonyms as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
                if ( ! term_exists( $term, $taxonomy ) ) {
                    wp_insert_term( $term, $taxonomy );
                }
			}
		}

        // `tax_input` do not accepts `category` and `post_tag`

        // transform category name list into category id list
        $post_category = array();
        if ( array_key_exists( 'category', $synonyms ) ) {
            foreach ( $synonyms['category'] as $cat_name ) {
                array_push( $post_category, get_cat_ID( $cat_name ) );
            }
            unset( $synonyms['category'] );
        }

        // simply unset if contains tag list
        $tags_input = array();
        if ( array_key_exists( 'post_tag', $synonyms ) ) {
            $tags_input = $synonyms['post_tag'];
            unset( $synonyms['post_tag'] );
        }


        // create a post as synonym list.
        $post = array(
            'post_title' => $label,
            'post_type' => $this->post_type,
            'post_category' => $post_category,
            'tags_input' => $tags_input,
            'tax_input' => $synonyms
        );
        return wp_insert_post( $post ); //post_id or false
    }


    public function unregister( $synonyms_id ) {
        // post exists and post_type_matches
        $result = false;
        if (get_post_type( $synonyms_id ) === $this->post_type ) {
            $result = wp_delete_post( $synonyms_id );
        }
        return $result;
    }

    public function read( $synonyms_id ) {
        // simply read synonyms by synonyms_id and return synonyms object.
        $result = array();
        foreach ( array_values( get_taxonomies() ) as $taxonomy ) {
            $tax_objects = wp_get_post_terms( $synonyms_id, $taxonomy );
            if (count( $tax_objects ) > 0 ) {
                $result[ $taxonomy ] = array();
                foreach ( $tax_objects as $tax_object ) {
                    array_push( $result[ $taxonomy ], $tax_object->name);
                }
            }
        }
        return $result;
    }


    public function synonymsOf( $term_name, $taxonomy ) {


        $synonym_list_ids = get_posts( array(
            'number_posts' => -1,
            'post_type'    => $this->post_type,
            'post_status'  => 'any',
            'tax_query'    => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'name',
                    'terms'    => $term_name
                ),
            ),
            'fields'       => 'ids'
        ) );



        // parse the post information and get synonym object
        $result = array();
        foreach ( array_values( get_taxonomies() ) as $taxonomy ) {
            foreach ( $synonym_list_ids as $post_id ) {
                $terms = wp_get_post_terms( $post_id, $taxonomy, array('fields' => 'names') );
                if ( array_key_exists( $taxonomy, $result ) ) {
                    $result[ $taxonomy ] = array_merge(
                        $result[ $taxonomy ],
                        $terms
                    );
                } else {
                    $result[ $taxonomy ] = $terms;
                }
            }
            if (array_key_exists( $taxonomy, $result ) ) {
                if ( count( $result[ $taxonomy ] ) === 0 ) {
                    unset( $result[ $taxonomy ] );
                } else {
                    $result[ $taxonomy ] = array_unique( $result[ $taxonomy ] );
                }
            }
        }

        return $result;
    }

}
