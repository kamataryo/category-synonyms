<?php

    // set up UI

    function category_synonyms_admin_init()
    {
          // register style sheet
          wp_register_style( 'categorySynonymsStylesheet', plugins_url( 'assets/category-synonyms-ui.css', __FILE__ ) );
          wp_register_script( 'categorySynonymsScript', plugins_url( 'assets/category-synonyms-ui.js', __FILE__ ), array( 'jquery' ) );
    }
    add_action( 'admin_init', 'category_synonyms_admin_init' );


    function category_synonyms_admin_menu()
    {
    	$page = add_options_page(
            __( 'synonyms registration', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            __( 'Category Synonyms', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'manage_options',
            CATEGORY_SYNONYMS_TEXT_DOMAIN,
            'describe_category_synonyms_options_ui'
        );
        add_action( 'admin_print_styles-' . $page, 'category_synonyms_admin_styles' );
        add_action( 'admin_print_scripts-' . $page , 'category_synonyms_admin_scripts' );
    }
    add_action( 'admin_menu', 'category_synonyms_admin_menu' );


    function category_synonyms_admin_styles()
    {
        wp_enqueue_style( 'categorySynonymsStylesheet' );
    }

    function category_synonyms_admin_scripts()
    {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'categorySynonymsScript' );
        wp_localize_script( 'categorySynonymsScript','ajax' , array(
            'Endpoints' => admin_url( 'admin-ajax.php' ),
        ) );
    }

    //define ajax action
    function ajax_add_new_def()
    {
        global $categorySynonyms_instance;
        $cs = $categorySynonyms_instance;
        $def_id = $cs->register( array(
            'terms' => array(),
            'taxonomy' => 'category'
        ) )['synonyms_definition_id'];
        $all_defs = $cs->get_all_definitions();
        foreach ( $all_defs as $def ) {
            if ( $def['synonyms_definition_id'] === $def_id ) {
                admin_synonym_def_tr( $def );
                die();
            }
        }
    }
    add_action( 'wp_ajax_category_synonyms_add_new', 'ajax_add_new_def' );


    function ajax_delete_defs()
    {
        global $categorySynonyms_instance;
        $cs = $categorySynonyms_instance;
        $result = array();
        foreach ($_POST['ids'] as $id) {
            $result[$id] = $cs->unregister( $id ) ? true : false;
        }
        wp_send_json_success( $result );
    }
    add_action( 'wp_ajax_category_syonyms_delete_defs', 'ajax_delete_defs' );


    function ajax_update_def()
    {
        global $categorySynonyms_instance;
        $cs = $categorySynonyms_instance;
        $cs->update( $_POST['id'], $_POST['updates'] );
        wp_send_json_success();
    }
    add_action( 'wp_ajax_category_synonyms_update_def', 'ajax_update_def' );


    function describe_category_synonyms_options_ui()
    {
    	if ( !current_user_can( 'manage_options' ) )  {
    		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    	}

        global $categorySynonyms_instance;
        $all_defs = $categorySynonyms_instance->get_all_definitions();

        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html__( 'Synonyms definitions', CATEGORY_SYNONYMS_TEXT_DOMAIN );  ?>
                <a href="#" class="page-title-action click2add"><?php echo esc_html__( 'Add New Synonyms', CATEGORY_SYNONYMS_TEXT_DOMAIN ); ?></a>
            </h1>

            <input type="hidden"></input>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php echo esc_html__( 'Select bulk action' ); ?></label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php echo esc_html__( 'Bulk Actions' ); ?></option>
                    	<option value="delete"><?php echo esc_html__( 'Delete' ); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php echo esc_html__( 'Apply' ); ?>" data-onprocess="false">
                </div>
            </div>

            <?php if ( count( $all_defs ) > 0  ): ?>
            <table class="wp-list-table widefat fixed striped category-synonyms-ui">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1"><?php echo esc_html__( 'Select all' ); ?></label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" class="manage-column column-title column-primary"><?php echo esc_html__('labels', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?></th>
                        <th scope="col" class="manage-column column-categories"><?php echo esc_html__( 'taxonomies', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?></th>
                        <th scope="col" class="manage-column column-terms"><?php echo esc_html__('terms', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody class="the-list">

                    <?php foreach ( $all_defs as $def ): ?>
                    <?php admin_synonym_def_tr( $def ); ?>
                    <?php endforeach; ?>

                </tbody>
            </table>


            <?php else: ?>
            <p><?php echo esc_html__( 'No Synonyms has been defined..', CATEGORY_SYNONYMS_TEXT_DOMAIN ); ?></p>
            <?php endif; ?>
        </div><!-- .wrap -->

        <?php
    }


    // set up template-tags

    function admin_synonym_def_tr( $def )
    {
    ?>
        <tr id="synonyms-<?php echo esc_html( $def['synonyms_definition_id'] ); ?>" data-id="<?php echo esc_html( $def['synonyms_definition_id'] ); ?>">
            <th scope="row" class="check-column">
                <label class="screen-reader-text" for="cb-select-<?php echo esc_html( $def['synonyms_definition_id'] ); ?>"><?php printf( esc_html__('Select %s'), $def['label'] ); ?></label>
                <input id="cb-select-<?php echo esc_html( $def['synonyms_definition_id'] ); ?>" type="checkbox" name="synonyms_def[]" value="<?php echo esc_html( $def['synonyms_definition_id'] ); ?>">
                <div class="locked-indicator"></div>
            </th>

            <td class="column-title title column-title has-row-actions column-primary page-title">
                <span class="click2input"><?php echo esc_html( $def['label'] ); ?></span>
                <input type="text" name="clicked2input-label" class="clicked2input" value="<?php echo esc_html( $def['label'] ); ?>" data-updatable="label">
                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php echo esc_html__( 'Show more details' ); ?></span></button>
            </td>

            <td class="column-categories" data-colname="<?php echo esc_html__('taxonomy', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?>">
                <span class="click2input"><?php echo esc_html( $def['taxonomy'] ); ?></span>
                <select name="clicked2input-taxonomy"  class="clicked2input"  value="<?php echo esc_html( $def['taxonomy'] ); ?>" data-updatable="taxonomy">
                <?php foreach ( get_taxonomies() as $taxonomy ): ?>
                    <?php if ($taxonomy === 'nav_menu' || $taxonomy === 'post_format' || $taxonomy ==='link_category' ): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <?php $selected = $def['taxonomy'] === $taxonomy ? ' selected="selected"' : ''; ?>
                    <option value="<?php echo esc_html( $taxonomy ); ?>"<?php echo $selected; ?>><?php echo esc_html( $taxonomy ); ?></option>
                <?php endforeach;  ?>
                </select>
            </td>

            <td class="column-terms" data-colname="<?php echo esc_html__('terms', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?>">
                <ul class="term-list click2inputs">
                <?php $terms = array();  ?>
                <?php if ( count( $def['terms'] ) === 0 ): ?>
                    <li>+</li>
                <?php else: ?>
                    <?php foreach ( $def['terms'] as $term_id ): ?>
                    <?php $term = get_term_by( 'id', $term_id, $def['taxonomy'] ); ?>
                    <li>
                        <span><?php echo $term->name; ?></span>
                    </li>
                    <?php array_push( $terms, $term->name ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                </ul>
                <input type="text" name="clicked2input-terms" class="clicked2inputs" value="<?php echo esc_html( implode( ',', $terms ) ); ?>" data-updatable="terms">
            </td>
        </tr>
    <?php
    }


    function get_the_term_list_synonymously( $id, $taxonomy, $before, $sep, $after )
    {
        global $categorySynonyms_instance;
        $terms = get_the_terms( $id, $taxonomy );
        $ids_result = array();

        foreach ( $terms as $term ) {
            $ids =  $categorySynonyms_instance->get_synonymous_terms_by( array(
                'field'    => 'id',
                'value'    => $term->term_id,
                'taxonomy' => $taxonomy
            ) )['term_taxonomy_ids'];
            foreach ( $ids as $id ) {
                array_push( $ids_result, '<a href="' . get_term_link( $id, $taxonomy  ) . '">' .get_term_by( 'id', $id, $taxonomy )->name . '</a>' );
            }
        }

        sort( $ids_result );
        return $before . implode( $sep, $ids_result ) . $after;
    }

    function get_term_link_synonymously( $name, $taxonomy )
    {
        global $categorySynonyms_instance;
        $term_ids = $categorySynonyms_instance->get_synonymous_terms_by( array(
            'field'    => 'name',
            'value'    => $name,
            'taxonomy' => $taxonomy
        ) )['term_taxonomy_ids'];

        $terms = array( $name );
        foreach ( $term_ids as $term_id ) {
            $value = get_term_by( 'id', $term_id, $taxonomy )->name;
            if ($value !== $name) {
                array_push( $terms, $value );
            }
        }

        return get_home_url() . '/' . $taxonomy . '/' . implode(',', $terms);
    }
