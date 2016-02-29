<?php

    // set up UI



    add_action( 'admin_init', 'category_synonyms_admin_init' );
    function category_synonyms_admin_init() {
          // register style sheet
          wp_register_style( 'categorySynonymsStylesheet', plugins_url( 'assets/category-synonyms-ui.css', __FILE__ ) );
      }

    add_action( 'admin_menu', 'category_synonyms_admin_menu' );
    function category_synonyms_admin_menu() {
    	$page = add_options_page(
            __( 'synonyms registration', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            __( 'Category Synonyms', CATEGORY_SYNONYMS_TEXT_DOMAIN ),
            'manage_options',
            CATEGORY_SYNONYMS_TEXT_DOMAIN,
            'describe_category_synonyms_options_ui'
        );
        add_action( 'admin_print_styles-' . $page, 'category_synonyms_admin_styles' );
    }

    function category_synonyms_admin_styles() {
        wp_enqueue_style( 'categorySynonymsStylesheet' );
    }

    function describe_category_synonyms_options_ui() {
    	if ( !current_user_can( 'manage_options' ) )  {
    		wp_die( __( 'You do not have sufficient permissions to access this page.', CATEGORY_SYNONYMS_TEXT_DOMAIN ) );
    	}

        global $categorySynonyms_instance;
        $all_defs = $categorySynonyms_instance->get_all_definitions();

        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html__( 'Synonyms definitions', CATEGORY_SYNONYMS_TEXT_DOMAIN );  ?>
                <a href="***js to add and post new ***" class="page-title-action"><?php echo esc_html__( 'Add New', CATEGORY_SYNONYMS_TEXT_DOMAIN ); ?></a>
            </h1>
            <ul class="subsubsub">
                <li class="all">
                    <?php echo esc_html__( 'All', CATEGORY_SYNONYMS_TEXT_DOMAIN  ); ?>
                    <span class="count">
                        (<?php echo count( $all_defs ); ?>)
                    </span>
                </li>
            </ul>

            <form id="category_synonyms-filter" method="GET">

                <input type="hidden"></input>
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <label for="bulk-action-selector-top" class="screen-reader-text">一括操作を選択</label>
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1">一括操作</option>
                        	<option value="trash">ゴミ箱へ移動</option>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="適用">
                    </div>
                </div>

                <?php if ( count( $all_defs ) > 0  ): ?>
                <table class="wp-list-table widefat fixed striped category-synonyms-ui">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1">すべて選択</label>
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-title column-primary"><?php echo esc_html__('label', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?></th>
                            <th scope="col" class="manage-column column-categories"><?php echo esc_html__('taxonomy', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?></th>
                            <th scope="col" class="manage-column column-terms"><?php echo esc_html__('terms', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody class="the-list">

                        <?php foreach ( $all_defs as $def ): ?>
                        <tr id="synonyms-<?php echo esc_html( $def['synonyms_definition_id'] ); ?>"
                            class="iedit author-self level-0 post-17 type-post status-publish format-standard has-post-thumbnail hentry category-4">
                            <th scope="row" class="check-column">
                                <label class="screen-reader-text" for="cb-select-<?php echo esc_html( $def['synonyms_definition_id'] ); ?>"><?php echo esc_html($def['label']); ?>を選択</label>
    			                <input id="cb-select-<?php echo esc_html( $def['synonyms_definition_id'] ); ?>" type="checkbox" name="synonyms_def[]" value="<?php echo esc_html( $def['synonyms_definition_id'] ); ?>">
    			                <div class="locked-indicator"></div>
    		                </th>
                            <td class="column-title title column-title has-row-actions column-primary page-title">
                                <strong>
                                    <?php echo esc_html( $def['label'] ); ?>
                                </strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">詳細を追加表示</span></button>
                            </td>
                            <td class="column-categories" data-colname="<?php echo esc_html__('taxonomy', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?>">
                                <strong><?php echo esc_html( $def['taxonomy'] ); ?></strong>
                            </td>
                            <td class="column-terms" data-colname="<?php echo esc_html__('terms', CATEGORY_SYNONYMS_TEXT_DOMAIN); ?>">
                                <ul class="term-list">
                                <?php foreach ( $def['terms'] as $term_id ): ?>
                                    <?php $term = get_term_by( 'id', $term_id, $def['taxonomy'] ); ?>
                                    <li>
                                        <strong><?php echo $term->name; ?></strong>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

                <div class="tablenav bottom">
        			<div class="alignleft actions">
            			<label for="bulk-action-selector-bottom" class="screen-reader-text">一括操作を選択</label>
                        <select name="action2" id="bulk-action-selector-bottom">
                            <option value="-1">一括操作</option>
            	            <option value="trash">ゴミ箱へ移動</option>
                        </select>
                        <input type="submit" id="doaction2" class="button action" value="適用">
        		    </div>
    		    </div>

            </form>


            <?php else: ?>
            <p><?php echo esc_html__( 'No Synonyms has been defined..', CATEGORY_SYNONYMS_TEXT_DOMAIN ); ?></p>
            <?php endif; ?>
        </div><!-- .wrap -->

        <?php
    }
