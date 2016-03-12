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
            <ul class="subsubsub">
                <li class="all">
                    <?php echo esc_html__( 'All' ); ?>
                    <span class="count">
                        (<?php echo count( $all_defs ); ?>)
                    </span>
                </li>
            </ul>


            <input type="hidden"></input>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php echo esc_html__( 'Select bulk action' ); ?></label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php echo esc_html__( 'Bulk Actions' ); ?></option>
                    	<option value="trash"><?php echo esc_html__( 'Move to Trash' ); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php echo esc_html__( 'Apply' ); ?>">
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
                    <?php #tr要素はテンプレートパーツ化する。ここでの出力と、ajaxでクライアント側に返すために用いる ?>
                    <tr id="synonyms-<?php echo esc_html( $def['synonyms_definition_id'] ); ?>">
                        <th scope="row" class="check-column">
                            <label class="screen-reader-text" for="cb-select-<?php echo esc_html( $def['synonyms_definition_id'] ); ?>"><?php printf( esc_html__('Select %s'), $def['label'] ); ?></label>
			                <input id="cb-select-<?php echo esc_html( $def['synonyms_definition_id'] ); ?>" type="checkbox" name="synonyms_def[]" value="<?php echo esc_html( $def['synonyms_definition_id'] ); ?>">
			                <div class="locked-indicator"></div>
		                </th>
                        <td class="column-title title column-title has-row-actions column-primary page-title">
                            <strong>
                                <?php echo esc_html( $def['label'] ); ?>
                            </strong>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php echo esc_html__( 'Show more details' ); ?></span></button>
                        </td>
                        <td class="column-categories" data-colname="<?php echo esc_html__('taxonomy', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?>">
                            <span class="click2input"><?php echo esc_html( $def['taxonomy'] ); ?></span>
                            <input type="text" name="clicked2input-taxonomy" class="clicked2input" value="<?php echo esc_html( $def['taxonomy'] ); ?>">
                        </td>
                        <td class="column-terms" data-colname="<?php echo esc_html__('terms', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?>">
                            <ul class="term-list click2inputs">
                            <?php $terms = array();  ?>
                            <?php foreach ( $def['terms'] as $term_id ): ?>
                                <?php $term = get_term_by( 'id', $term_id, $def['taxonomy'] ); ?>
                                <li>
                                    <span><?php echo $term->name; ?></span>
                                </li>
                            <?php array_push( $terms, $term->name ); ?>
                            <?php endforeach; ?>
                            </ul>
                            <input type="text" name="clicked2input-terms" class="clicked2inputs" value="<?php echo esc_html( implode( ',', $terms ) ); ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>

            <div class="tablenav bottom">
    			<div class="alignleft actions">
        			<label for="bulk-action-selector-bottom" class="screen-reader-text"><?php echo esc_html__( 'Select bulk action' ); ?></label>
                    <select name="action2" id="bulk-action-selector-bottom">
                        <option value="-1"><?php echo esc_html__( 'Bulk Actions' ); ?></option>
        	            <option value="trash"><?php echo esc_html__( 'Move to Trash' ); ?></option>
                    </select>
                    <input type="submit" id="doaction2" class="button action" value="<?php echo esc_html__( 'Apply' ); ?>">
    		    </div>
		    </div>



            <?php else: ?>
            <p><?php echo esc_html__( 'No Synonyms has been defined..', CATEGORY_SYNONYMS_TEXT_DOMAIN ); ?></p>
            <?php endif; ?>
        </div><!-- .wrap -->

        <?php
    }


    // set up template-tags

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
