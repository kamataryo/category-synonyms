<?php

class TermSynonymsTest extends WP_UnitTestCase {

	private $termSynonyms;
	private $post_type;


	function __construct()
	{
		global $termSynonyms_instance;
		$this->termSynonyms = $termSynonyms_instance;
		$this->post_type = $termSynonyms_instance->post_type;
	}


	//for my training
	function test_if_module_Ginq_loaded()
	{
		$result = Ginq::from( array( 1,2,3,4,5,6,7,8,9 ) )
			->where( function( $x ){return  $x % 3 === 0; } )
			->select( function( $x ){return $x; } )
			->toList();

		$this->assertEquals( $result, array( 3, 6, 9 ) );
	}


	function test_custom_post_type_registration()
	{
		// [appearance] it should be that the singleton instance exists.
		$this->assertNotNull( $this->termSynonyms );

		// [behavior] it should be that the custom post type have been registered.
		$this->assertTrue( post_type_exists( $this->post_type ) );
	}


	function test_if_custom_taxonomy_registered_for_custom_post_type()
	{
		// [behavior] it should be that the custop post type have been linked with all the default taxonomies.
		$this->assertEquals(
			array_keys( get_taxonomies() ),
			get_object_taxonomies( $this->post_type )
		);

	}


	function test_for_synonyms_registration_and_unregistration()
	{
		//provisioning
		$ts = $this->termSynonyms;

		$synonymous_terms = array( 'white', 'bianco', 'weiss', 'abyad', '白' );
		$tax_name = 'category';

		// `synonyms_id` is `post_id`
		$registration_info = $ts->register( array(
			'label'    => 'synonym label in test to identifiy the synonym group',
			'terms'    => $synonymous_terms,
			'taxonomy' => $tax_name,
		) );


		// describe registration test

		// [appearance] it should be that register returns 3element array of synonyms_definition_id, concerned taxonomy name and array of registerd term_taxonomy_ids.;
		$this->assertEquals(
			array_keys( $registration_info ),
			array( 'synonyms_definition_id', 'taxonomy', 'term_taxonomy_ids' )
		);

		// [appearance] it should be that syonnyms_id is integer if success
		$this->assertInternalType ('integer', $registration_info['synonyms_definition_id'] );
		$this->assertNotEquals( 0, $registration_info['synonyms_definition_id'] );

		// [appearance] taxonomy name should be returened directly.
		$this->assertEquals($tax_name, $registration_info['taxonomy']);
		// [appearance] also the taxnomy name should be set as custom field.
		$this->assertEquals($tax_name, get_post_meta(
			$registration_info['synonyms_definition_id'],
			TERM_SYNONYMS_TAXONOMY_FIELD_KEY,
			true
		) );

		// check if same amount of ids has been returened.
		foreach ( $registration_info['term_taxonomy_ids'] as $index => $combind_term_taxonomy_id ) {

			$this->assertInternalType( 'integer', $combind_term_taxonomy_id['term_id'] );
			$this->assertInternalType( 'integer', $combind_term_taxonomy_id['term_taxonomy_id'] );

			$this->assertEquals(
				$synonymous_terms[$index],
				get_term_by( 'id', $combind_term_taxonomy_id['term_taxonomy_id'], $tax_name )->name
			);

		}

		// [behavior] it should be that terms have been generated after registration
		foreach ( $synonymous_terms as $term ){

			// negative result of `term_exists` is 0 or null
			$this->assertNotFalse ( term_exists( $term, $tax_name ) );

			$this->assertNotNull ( term_exists( $term, $tax_name ) );

		}

		// [behavior] it should be that a custom post have been generated.
		$this->assertNotFalse( get_post_status( $registration_info['synonyms_definition_id'] ) );

		$this->assertEquals( get_post_type( $registration_info['synonyms_definition_id'] ), $this->post_type );


		// [behavior] it should be that the post have been attached all the terms
		foreach ( $synonymous_terms as $term ) {

			$this->assertTrue ( has_term( $term, $tax_name, $registration_info['synonyms_definition_id'] ) );

		}


		// describe unregister test
		$unregistration_info = $ts->unregister( $registration_info['synonyms_definition_id'] );

		// [appearance] it should be that the function returns copied deleted post object.
		$this->assertNotFalse( $unregistration_info );

		// [behavior] it should be that the post have been deleted.
		$this->assertFalse( get_post_status( $unregistration_info->ID ) );
	}


	function test_of_get_synonyms_definition_by_id()
	{
		//provisioning
		$ts = $this->termSynonyms;
		$synonymous_terms = array( 'wine', 'ワイン' );
		$tax_name = 'category';

		$registration_info = $ts->register( array(
			'label' => 'synonym label in test to read the synonym group',
			'taxonomy' => $tax_name,
			'terms' => $synonymous_terms,
		) );

		$result = $ts->get_synonyms_definition_by_id( $registration_info['synonyms_definition_id'] );

		$this->assertEquals( count( $result['term_taxonomy_ids'] ), count( $synonymous_terms ) );
		$this->assertEquals( $result['taxonomy'], $tax_name );

		// clean up for next test.
		$ts->unregister( $registration_info['synonyms_definition_id'] );
	}


	function test_of_get_synonymous_terms_by()
	{
		//provisioning
		$ts = $this->termSynonyms;
		$args = array(
			array(
				'taxonomy' => 'category',
				'terms'    => array( 'mocha', 'coffee' ),
			),
			array(
				'taxonomy' => 'category',
				'terms' => array( 'coffee', 'qafa' ),
			),
		);
		$infos = array();
		foreach ( $args as $arg ) {
			array_push( $infos, $ts->register( $arg ) );
		}


		$result = $ts->get_synonymous_terms_by( array(
			'field'    => 'name',
			'value'    => 'coffee',
			'taxonomy' => 'category',
		) );

		var_dump($result['term_taxonomy_ids']);
		$this->assertEquals( count( $result['term_taxonomy_ids'] ), 3 );
		$this->assertEquals( $result['taxonomy'], 'category' );



		//clean up
		foreach ( $infos as $info ) {
			$ts->unregister( $info['synonyms_definition_id'] );
		}
	}


	// function test_get_posts_intercepted()
	// {
	// 	//provisioning
	// 	$ts = $this->termSynonyms;
	// 	$synonyms = array(
	// 		'category' => array( 'tea', 'chai' )
	// 	);
	// 	$synonym_id = $ts->register( $synonyms, 'synonym label in test for query check' );
	//
	// 	$posts = array(
	// 		array(
	// 			'post_type' => 'post',
	//             'post_title' => 'what I love',
	//             'post_category' => array( get_cat_ID( 'tea' ) ),
	// 		),
	// 		array(
	// 			'post_type' => 'post',
	// 			'post_title' => 'my favorite',
	// 			'post_category' => array( get_cat_ID( 'chai' ) ),
	// 		),
	// 		array(
	// 			'post_type' => 'post',
	// 			'post_title' => 'only title',
	// 		)
	// 	);
	//
	// 	foreach ($posts as $post) {
	// 		wp_insert_post( $post );
	// 	}
	//
	//
	// 	$obtained_posts = get_posts( array(
	// 		'number_posts' => -1,
	// 		'post_type'    => 'post',
	// 		'post_status'  => 'any',
	// 		'tax_query'    => array(
	// 			array(
	// 				'taxonomy' => 'category',
	// 				'field'    => 'name',
	// 				'terms'    => 'tea',
	// 			),
	// 		),
	// 	) );
	//
	// 	// [appearance] it should be that 2 posts have been obtained.
	// 	$this->assertEquals( count( $obtained_posts ), 2 );
	// }

}
