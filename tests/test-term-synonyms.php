<?php

class TermSynonymsTest extends WP_UnitTestCase {
	const the_TEXTDOMAIN = 'term_synonyms';
	private $termSynonyms;
	private $post_type;

	function __construct() {
		global $termSynonyms_instance;
		$this->termSynonyms = $termSynonyms_instance;
		$this->post_type = $termSynonyms_instance->post_type;
	}


	function test_if_module_Ginq_loaded() {
		$the_array = array( 1,2,3,4,5,6,7,8,9,10 );
		$result = Ginq::from( $the_array )
			->where( function( $x ) {return  $x % 3 === 0; } )
			->select( function( $x ) {return $x; } )
			->toList();
		$this->assertEquals( $result, array( 3, 6, 9 ) );
	}

	function test_custom_post_type_registration() {
		// [appearance] it should be that the singleton instance exists.
		$this->assertNotNull( $this->termSynonyms );

		// [behavior] it should be that the custom post type have been registered.
		$this->assertTrue( post_type_exists( $this->post_type ) );
	}


	function test_if_custom_taxonomy_registered_for_custom_post_type() {
		// [appearance] it should be that the singleton instance exists.
		// it have been tested already.

		// [behavior] it should be that the custop post type have been linked with all the default taxonomies.
		$this->assertEquals(
			json_encode( array_keys( get_taxonomies() ) ),
			json_encode( get_object_taxonomies( $this->post_type ) )
		);
	}


	function test_for_synonyms_registration_and_unregistration() {
		//provisioning
		$ts = $this->termSynonyms;
		$synonyms = array(
			"category" => array( 'white', 'bianco', 'weiss', 'abyad', '白' ),
			"post_tag" => array( '#FFF', '#ffffff', 'rgb(255,255,255)' )
		);
		// `synonyms_id` is `post_id`
		$synonyms_id = $ts->register( $synonyms, 'synonym label in test to identifiy the synonym group' );


		// describe register test

		// [appearance] it should that the function returns effective post_id;
		$this->assertNotFalse( $synonyms_id );
		$this->assertInternalType ('integer', $synonyms_id );

		// [behavior] it should be that terms have been generated after registration
		foreach ( $synonyms as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				$result = term_exists( $term, $taxonomy );
				// negative result of `term_exists` is 0 or null
				$this->assertNotFalse ( $result );
				$this->assertNotNull ( $result );
			}
		}

		// [behavior] it should be that a custom post have been generated.
		$this->assertNotFalse( get_post_status( $synonyms_id ) );
		$this->assertEquals( get_post_type( $synonyms_id ), $this->post_type );


		// [behavior] it should be that the post have been attached all the terms
		foreach ( $synonyms as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				$this->assertTrue ( has_term( $term, $taxonomy, $synonyms_id ) );
			}
		}


		// describe unregister test
		$result = $ts->unregister( $synonyms_id );

		// [appearance] it should be that the function returns copied deleted post object.
		$this->assertNotFalse( $result );

		// [behavior] it should be that the post have been deleted.
		$this->assertFalse( get_post_status( $synonyms_id ) );
	}




	function test_if_synonyms_list_is_readable() {
		//provisioning
		$ts = $this->termSynonyms;
		$synonyms = array(
			"category" => array( 'wine', 'ワイン' ),
			"post_tag" => array('liquor made from grape', 'red and white', 'even rose' )
		);


		// `synonyms_id` is `post_id`
		$synonyms_id = $ts->register( $synonyms, 'synonym label in test to read the synonym group' );
		$result = $ts->read( $synonyms_id );

		$taxonomies_expected = array_keys( $synonyms );
		$taxonomies_actual = array_keys( $result );
		sort( $taxonomies_expected );
		sort( $taxonomies_actual );

		// [appearance] it should read() returns $synonyms object.
		$this->assertEquals(
			json_encode( $taxonomies_expected ),
			json_encode( $taxonomies_actual )
		);
		// check nested values in synonym object.
		foreach ( $taxonomies_expected as $taxonomy ) {
			$terms_expected = $synonyms[ $taxonomy ];
			$terms_actual = $result[ $taxonomy ];
			sort( $terms_expected );
			sort( $terms_actual );
			$this->assertEquals(
				json_encode( ( $terms_expected ) ),
				json_encode( ( $terms_actual ) )
			);
		}

		// clean up for next test.
		$ts->unregister( $synonyms_id );
	}



	function test_of_function_synonymsOf() {
		//provisioning
		$ts = $this->termSynonyms;
		$synonyms1 = array(
			'category' => array( 'mocha' ),
			'post_tag' => array( 'qafa', 'the bitter drink', 'my favorite' )
		);
		$synonyms2 = array(
			'category' => array( 'coffee' ),
			'post_tag' => array( 'qafa' )
		);
		// this is 2nd level of adjacency, which does not affect.
		$synonyms3 = array(
			'category' => array( 'coffee' ),
			'post_tag' => array( 'the cafeine' )
		);
		$synonym_id1 = $ts->register( $synonyms1, 'synonym label1 in test for synonymsOf' );
		$synonym_id2 = $ts->register( $synonyms2, 'synonym label2 in test for synonymsOf' );

		$expected = array(
			'category' => array( 'mocha', 'coffee' ),
			'post_tag' => array( 'qafa', 'the bitter drink', 'my favorite' )
		);

		$actual = $ts->synonymsOf( 'qafa', 'post_tag' );
		sort( $expected['category'] );
		sort( $expected['post_tag'] );
		sort( $actual['category'] );
		sort( $actual['post_tag'] );


		// [appearance] it should be that function `synonymsOf` returns unioned array.
		$this->assertEquals(
			json_encode( ( $expected ) ),
			json_encode( ( $actual ) )
		);


		//clean up
		$ts->unregister( $synonym_id1 );
		$ts->unregister( $synonym_id2 );
	}

	function test_get_posts_intercepted() {
		//provisioning
		$ts = $this->termSynonyms;
		$synonyms = array(
			'category' => array( 'tea', 'chai' )
		);
		$synonym_id = $ts->register( $synonyms, 'synonym label in test for query check' );

		wp_insert_post( array(
			'post_type' => 'post',
            'post_title' => 'what I love',
            'post_category' => array( get_cat_ID( 'tea' ) ),
		) );
		wp_insert_post( array(
			'post_type' => 'post',
			'post_title' => 'my favorite',
			'post_category' => array( get_cat_ID( 'chai' ) ),
		) );
		wp_insert_post( array(
			'post_type' => 'post',
			'post_title' => 'only title',
		) );

		$obtained_posts = get_posts( array(
			'number_posts' => -1,
			'post_type'    => 'post',
			'post_status'  => 'any',
			'tax_query'    => array(
				array(
					'taxonomy' => 'category',
					'field'    => 'name',
					'terms'    => 'tea',
				),
			),
		) );

		// [appearance] it should be that 2 posts have been obtained.
		$this->assertEquals( count( $obtained_posts ), 2 );
	}

}
