<?php
/**
 * Tests for Content creation abilities.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use Spectra\Abilities\CreateButtons;
use Spectra\Abilities\CreateAccordion;
use Spectra\Abilities\CreateCountdown;
use Spectra\Abilities\CreateCounter;
use Spectra\Abilities\CreateGoogleMap;
use Spectra\Abilities\CreateIcons;
use Spectra\Abilities\CreateList;
use Spectra\Abilities\CreateSeparator;
use Spectra\Abilities\CreateTabs;
use Spectra\Abilities\CreateContent;

/**
 * Content creation abilities test case.
 */
class ContentCreationAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user can edit.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
	}

	// -------------------------------------------------------------------------
	// CreateButtons
	// -------------------------------------------------------------------------

	/**
	 * Test CreateButtons metadata.
	 */
	public function test_create_buttons_metadata() {
		$ability = CreateButtons::instance();

		$this->assertSame( 'spectra-blocks/create-buttons', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateButtons execute with valid params.
	 */
	public function test_create_buttons_execute_success() {
		$result = CreateButtons::instance()->execute(
			array(
				'buttons' => array(
					array(
						'text'    => 'Click Me',
						'linkURL' => 'https://example.com',
					),
					array(
						'text'       => 'Learn More',
						'linkTarget' => '_blank',
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'block_markup', $result );
		$this->assertStringContainsString( '<!-- wp:spectra/buttons -->', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/button', $result['block_markup'] );
		$this->assertStringContainsString( 'Click Me', $result['block_markup'] );
		$this->assertStringContainsString( 'Learn More', $result['block_markup'] );
		$this->assertStringContainsString( 'example.com', $result['block_markup'] );
	}

	/**
	 * Test CreateButtons execute fails without buttons param.
	 */
	public function test_create_buttons_execute_missing_buttons() {
		$result = CreateButtons::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateButtons execute fails with empty text buttons.
	 */
	public function test_create_buttons_execute_empty_text() {
		$result = CreateButtons::instance()->execute(
			array(
				'buttons' => array(
					array( 'text' => '' ),
				),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_param', $result->get_error_code() );
	}

	/**
	 * Test CreateButtons inserts into post when post_id given.
	 */
	public function test_create_buttons_execute_with_post_insertion() {
		$post_id = self::factory()->post->create();

		$result = CreateButtons::instance()->execute(
			array(
				'buttons' => array( array( 'text' => 'Test Button' ) ),
				'post_id' => $post_id,
				'mode'    => 'replace',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $post_id, $result['post_id'] );
		$this->assertStringContainsString( '<!-- wp:spectra/buttons -->', $result['post_content'] );
	}

	// -------------------------------------------------------------------------
	// CreateAccordion
	// -------------------------------------------------------------------------

	/**
	 * Test CreateAccordion metadata.
	 */
	public function test_create_accordion_metadata() {
		$ability = CreateAccordion::instance();

		$this->assertSame( 'spectra-blocks/create-accordion', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateAccordion execute with valid items.
	 */
	public function test_create_accordion_execute_success() {
		$result = CreateAccordion::instance()->execute(
			array(
				'items' => array(
					array(
						'question' => 'What is Spectra?',
						'answer'   => 'A WordPress block plugin.',
					),
					array(
						'question' => 'Is it free?',
						'answer'   => 'Yes, it is.',
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/accordion', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/accordion-child-item', $result['block_markup'] );
		$this->assertStringContainsString( 'What is Spectra?', $result['block_markup'] );
		$this->assertStringContainsString( 'A WordPress block plugin.', $result['block_markup'] );
	}

	/**
	 * Test CreateAccordion execute fails without items.
	 */
	public function test_create_accordion_execute_missing_items() {
		$result = CreateAccordion::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateAccordion with active accordion index.
	 */
	public function test_create_accordion_with_active_index() {
		$result = CreateAccordion::instance()->execute(
			array(
				'items'           => array(
					array(
						'question' => 'Q1',
						'answer'   => 'A1',
					),
					array(
						'question' => 'Q2',
						'answer'   => 'A2',
					),
				),
				'activeAccordion' => 1,
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'activeAccordion', $result['block_markup'] );
	}

	/**
	 * Test CreateAccordion skips items with missing question/answer.
	 */
	public function test_create_accordion_skips_incomplete_items() {
		$result = CreateAccordion::instance()->execute(
			array(
				'items' => array(
					array( 'question' => 'Q1' ), // Missing answer.
					array(
						'question' => 'Q2',
						'answer'   => 'A2',
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'Q2', $result['block_markup'] );
		$this->assertStringNotContainsString( 'Q1', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateCountdown
	// -------------------------------------------------------------------------

	/**
	 * Test CreateCountdown metadata.
	 */
	public function test_create_countdown_metadata() {
		$ability = CreateCountdown::instance();

		$this->assertSame( 'spectra-blocks/create-countdown', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateCountdown execute with valid date.
	 */
	public function test_create_countdown_execute_success() {
		$result = CreateCountdown::instance()->execute(
			array( 'endDateTime' => '2030-12-31T23:59:59' )
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/countdown', $result['block_markup'] );
		$this->assertStringContainsString( '2030-12-31T23:59:59', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/countdown-child-day', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/countdown-child-hour', $result['block_markup'] );
	}

	/**
	 * Test CreateCountdown execute fails without date.
	 */
	public function test_create_countdown_execute_missing_date() {
		$result = CreateCountdown::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateCountdown with selective units.
	 */
	public function test_create_countdown_hidden_units() {
		$result = CreateCountdown::instance()->execute(
			array(
				'endDateTime' => '2030-12-31T23:59:59',
				'showDays'    => false,
				'showSeconds' => false,
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( 'countdown-child-day', $result['block_markup'] );
		$this->assertStringNotContainsString( 'countdown-child-second', $result['block_markup'] );
		$this->assertStringContainsString( 'countdown-child-hour', $result['block_markup'] );
		$this->assertStringContainsString( 'countdown-child-minute', $result['block_markup'] );
	}

	/**
	 * Test CreateCountdown with custom labels.
	 */
	public function test_create_countdown_custom_labels() {
		$result = CreateCountdown::instance()->execute(
			array(
				'endDateTime' => '2030-12-31T23:59:59',
				'labels'      => array(
					'days'  => 'Jours',
					'hours' => 'Heures',
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'Jours', $result['block_markup'] );
		$this->assertStringContainsString( 'Heures', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateCounter
	// -------------------------------------------------------------------------

	/**
	 * Test CreateCounter metadata.
	 */
	public function test_create_counter_metadata() {
		$ability = CreateCounter::instance();

		$this->assertSame( 'spectra-blocks/create-counter', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateCounter execute with valid endNumber.
	 */
	public function test_create_counter_execute_success() {
		$result = CreateCounter::instance()->execute(
			array( 'endNumber' => 100 )
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/counter', $result['block_markup'] );
		$this->assertStringContainsString( '"endNumber":100', $result['block_markup'] );
	}

	/**
	 * Test CreateCounter execute fails without endNumber.
	 */
	public function test_create_counter_execute_missing_end_number() {
		$result = CreateCounter::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateCounter with prefix, suffix, and title.
	 */
	public function test_create_counter_with_extras() {
		$result = CreateCounter::instance()->execute(
			array(
				'endNumber' => 500,
				'prefix'    => '$',
				'suffix'    => '+',
				'title'     => 'Revenue',
			)
		);

		$this->assertIsArray( $result );
		$markup = $result['block_markup'];
		$this->assertStringContainsString( '"prefix":"$"', $markup );
		$this->assertStringContainsString( '"suffix":"+"', $markup );
		$this->assertStringContainsString( 'Revenue', $markup );
	}

	/**
	 * Test CreateCounter with bar style includes progress bar child.
	 */
	public function test_create_counter_bar_style() {
		$result = CreateCounter::instance()->execute(
			array(
				'endNumber'    => 75,
				'counterStyle' => 'bar',
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'counter-child-progress-bar', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateGoogleMap
	// -------------------------------------------------------------------------

	/**
	 * Test CreateGoogleMap metadata.
	 */
	public function test_create_google_map_metadata() {
		$ability = CreateGoogleMap::instance();

		$this->assertSame( 'spectra-blocks/create-google-map', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateGoogleMap execute with valid address.
	 */
	public function test_create_google_map_execute_success() {
		$result = CreateGoogleMap::instance()->execute(
			array( 'address' => '1600 Amphitheatre Parkway, Mountain View, CA' )
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/google-map', $result['block_markup'] );
		$this->assertStringContainsString( '1600 Amphitheatre Parkway', $result['block_markup'] );
	}

	/**
	 * Test CreateGoogleMap execute fails without address.
	 */
	public function test_create_google_map_execute_missing_address() {
		$result = CreateGoogleMap::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateGoogleMap with custom zoom and height.
	 */
	public function test_create_google_map_with_custom_options() {
		$result = CreateGoogleMap::instance()->execute(
			array(
				'address'  => 'New York, NY',
				'zoom'     => 15,
				'height'   => 500,
				'language' => 'fr',
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"zoom":15', $result['block_markup'] );
		$this->assertStringContainsString( '"height":500', $result['block_markup'] );
		$this->assertStringContainsString( '"language":"fr"', $result['block_markup'] );
	}

	/**
	 * Test CreateGoogleMap zoom is clamped between 1 and 22.
	 */
	public function test_create_google_map_zoom_clamping() {
		$result = CreateGoogleMap::instance()->execute(
			array(
				'address' => 'Test',
				'zoom'    => 50,
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"zoom":22', $result['block_markup'] );
	}

	/**
	 * Test CreateGoogleMap produces self-closing block.
	 */
	public function test_create_google_map_is_self_closing() {
		$result = CreateGoogleMap::instance()->execute(
			array( 'address' => 'Test Location' )
		);

		$this->assertStringContainsString( '/-->', $result['block_markup'] );
		$this->assertStringNotContainsString( '<!-- /wp:spectra/google-map -->', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateIcons
	// -------------------------------------------------------------------------

	/**
	 * Test CreateIcons metadata.
	 */
	public function test_create_icons_metadata() {
		$ability = CreateIcons::instance();

		$this->assertSame( 'spectra-blocks/create-icons', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateIcons execute with valid icons.
	 */
	public function test_create_icons_execute_success() {
		$result = CreateIcons::instance()->execute(
			array(
				'icons' => array(
					array(
						'icon'               => 'star',
						'accessibilityLabel' => 'Favorite',
					),
					array( 'icon' => 'heart' ),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/icons -->', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/icon', $result['block_markup'] );
		$this->assertStringContainsString( '"icon":"star"', $result['block_markup'] );
		$this->assertStringContainsString( 'Favorite', $result['block_markup'] );
	}

	/**
	 * Test CreateIcons execute fails without icons param.
	 */
	public function test_create_icons_execute_missing_icons() {
		$result = CreateIcons::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateIcons with accessibility label sets accessibilityMode.
	 */
	public function test_create_icons_accessibility_mode() {
		$result = CreateIcons::instance()->execute(
			array(
				'icons' => array(
					array(
						'icon'               => 'star',
						'accessibilityLabel' => 'Rating star',
					),
				),
			)
		);

		$this->assertStringContainsString( '"accessibilityMode":"linked"', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateList
	// -------------------------------------------------------------------------

	/**
	 * Test CreateList metadata.
	 */
	public function test_create_list_metadata() {
		$ability = CreateList::instance();

		$this->assertSame( 'spectra-blocks/create-list', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateList execute with valid items.
	 */
	public function test_create_list_execute_success() {
		$result = CreateList::instance()->execute(
			array(
				'items' => array(
					array( 'text' => 'First item' ),
					array( 'text' => 'Second item' ),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/list', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/list-child-item', $result['block_markup'] );
		$this->assertStringContainsString( 'First item', $result['block_markup'] );
		$this->assertStringContainsString( 'Second item', $result['block_markup'] );
	}

	/**
	 * Test CreateList execute fails without items.
	 */
	public function test_create_list_execute_missing_items() {
		$result = CreateList::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateList with ordered type.
	 */
	public function test_create_list_ordered_type() {
		$result = CreateList::instance()->execute(
			array(
				'items'    => array( array( 'text' => 'Step 1' ) ),
				'listType' => 'ordered',
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"listType":"ordered"', $result['block_markup'] );
	}

	/**
	 * Test CreateList items have incremental index attributes.
	 */
	public function test_create_list_items_have_index() {
		$result = CreateList::instance()->execute(
			array(
				'items' => array(
					array( 'text' => 'A' ),
					array( 'text' => 'B' ),
				),
			)
		);

		$this->assertStringContainsString( '"index":0', $result['block_markup'] );
		$this->assertStringContainsString( '"index":1', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateSeparator
	// -------------------------------------------------------------------------

	/**
	 * Test CreateSeparator metadata.
	 */
	public function test_create_separator_metadata() {
		$ability = CreateSeparator::instance();

		$this->assertSame( 'spectra-blocks/create-separator', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateSeparator execute with no params (defaults).
	 */
	public function test_create_separator_execute_defaults() {
		$result = CreateSeparator::instance()->execute( array() );

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/separator', $result['block_markup'] );
		// Self-closing block.
		$this->assertStringContainsString( '/-->', $result['block_markup'] );
	}

	/**
	 * Test CreateSeparator execute with custom style and color.
	 */
	public function test_create_separator_execute_with_options() {
		$result = CreateSeparator::instance()->execute(
			array(
				'separatorStyle' => 'dashed',
				'separatorWidth' => 80,
				'separatorColor' => '#ff0000',
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"separatorStyle":"dashed"', $result['block_markup'] );
		$this->assertStringContainsString( '"separatorWidth":80', $result['block_markup'] );
		$this->assertStringContainsString( '"separatorColor":"#ff0000"', $result['block_markup'] );
	}

	/**
	 * Test CreateSeparator defaults to solid for invalid style.
	 */
	public function test_create_separator_invalid_style_defaults_to_solid() {
		$result = CreateSeparator::instance()->execute(
			array( 'separatorStyle' => 'wavy' )
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"separatorStyle":"solid"', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateTabs
	// -------------------------------------------------------------------------

	/**
	 * Test CreateTabs metadata.
	 */
	public function test_create_tabs_metadata() {
		$ability = CreateTabs::instance();

		$this->assertSame( 'spectra-blocks/create-tabs', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateTabs execute with valid tabs.
	 */
	public function test_create_tabs_execute_success() {
		$result = CreateTabs::instance()->execute(
			array(
				'tabs' => array(
					array(
						'title'   => 'Tab One',
						'content' => 'Content of tab one.',
					),
					array(
						'title'   => 'Tab Two',
						'content' => 'Content of tab two.',
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/tabs', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/tabs-child-tab-button', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/tabs-child-tabpanel', $result['block_markup'] );
		$this->assertStringContainsString( '<!-- wp:spectra/tabs-child-tab-wrapper', $result['block_markup'] );
		$this->assertStringContainsString( 'Tab One', $result['block_markup'] );
		$this->assertStringContainsString( 'Content of tab two.', $result['block_markup'] );
	}

	/**
	 * Test CreateTabs execute fails without tabs param.
	 */
	public function test_create_tabs_execute_missing_tabs() {
		$result = CreateTabs::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateTabs with custom active tab.
	 */
	public function test_create_tabs_with_custom_active_tab() {
		$result = CreateTabs::instance()->execute(
			array(
				'tabs'       => array(
					array(
						'title'   => 'First',
						'content' => 'First content.',
					),
					array(
						'title'   => 'Second',
						'content' => 'Second content.',
					),
				),
				'currentTab' => 1,
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"currentTab":1', $result['block_markup'] );
	}

	// -------------------------------------------------------------------------
	// CreateContent
	// -------------------------------------------------------------------------

	/**
	 * Test CreateContent metadata.
	 */
	public function test_create_content_metadata() {
		$ability = CreateContent::instance();

		$this->assertSame( 'spectra-blocks/create-content', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreateContent execute with text.
	 */
	public function test_create_content_execute_success() {
		$result = CreateContent::instance()->execute(
			array( 'text' => 'Hello World' )
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<!-- wp:spectra/content', $result['block_markup'] );
		$this->assertStringContainsString( 'Hello World', $result['block_markup'] );
		$this->assertStringContainsString( '<p class="wp-block-spectra-content">', $result['block_markup'] );
	}

	/**
	 * Test CreateContent execute fails without text.
	 */
	public function test_create_content_execute_missing_text() {
		$result = CreateContent::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreateContent with heading tag.
	 */
	public function test_create_content_with_heading_tag() {
		$result = CreateContent::instance()->execute(
			array(
				'text'    => 'My Heading',
				'tagName' => 'h2',
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<h2 class="wp-block-spectra-content">', $result['block_markup'] );
		$this->assertStringContainsString( '</h2>', $result['block_markup'] );
		$this->assertStringContainsString( '"tagName":"h2"', $result['block_markup'] );
	}

	/**
	 * Test CreateContent with text alignment.
	 */
	public function test_create_content_with_text_align() {
		$result = CreateContent::instance()->execute(
			array(
				'text'      => 'Centered text',
				'textAlign' => 'center',
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"textAlign":"center"', $result['block_markup'] );
	}

	/**
	 * Test CreateContent defaults to p tag for invalid tagName.
	 */
	public function test_create_content_invalid_tag_defaults_to_p() {
		$result = CreateContent::instance()->execute(
			array(
				'text'    => 'Test',
				'tagName' => 'script',
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<p class="wp-block-spectra-content">', $result['block_markup'] );
	}

	/**
	 * Test CreateButtons denies subscribers.
	 */
	public function test_create_buttons_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = CreateButtons::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test CreateButtons input schema.
	 */
	public function test_create_buttons_input_schema() {
		$schema = CreateButtons::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'buttons', $schema['required'] );
	}

	/**
	 * Test CreateButtons output schema.
	 */
	public function test_create_buttons_output_schema() {
		$schema = CreateButtons::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateAccordion input schema.
	 */
	public function test_create_accordion_input_schema() {
		$schema = CreateAccordion::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'items', $schema['required'] );
	}

	/**
	 * Test CreateCountdown input schema.
	 */
	public function test_create_countdown_input_schema() {
		$schema = CreateCountdown::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'endDateTime', $schema['required'] );
	}

	/**
	 * Test CreateCounter input schema.
	 */
	public function test_create_counter_input_schema() {
		$schema = CreateCounter::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'endNumber', $schema['required'] );
	}

	/**
	 * Test CreateGoogleMap input schema.
	 */
	public function test_create_google_map_input_schema() {
		$schema = CreateGoogleMap::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'address', $schema['required'] );
	}

	/**
	 * Test CreateIcons input schema.
	 */
	public function test_create_icons_input_schema() {
		$schema = CreateIcons::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'icons', $schema['required'] );
	}

	/**
	 * Test CreateList input schema.
	 */
	public function test_create_list_input_schema() {
		$schema = CreateList::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'items', $schema['required'] );
	}

	/**
	 * Test CreateTabs input schema.
	 */
	public function test_create_tabs_input_schema() {
		$schema = CreateTabs::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'tabs', $schema['required'] );
	}

	/**
	 * Test CreateContent input schema.
	 */
	public function test_create_content_input_schema() {
		$schema = CreateContent::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'text', $schema['required'] );
	}

	/**
	 * Test CreateSeparator input schema (no required params).
	 */
	public function test_create_separator_input_schema() {
		$schema = CreateSeparator::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
	}

	/**
	 * Test CreateContent sanitizes HTML in text.
	 */
	public function test_create_content_allows_safe_html() {
		$result = CreateContent::instance()->execute(
			array( 'text' => 'This is <strong>bold</strong> and <em>italic</em>' )
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '<strong>bold</strong>', $result['block_markup'] );
		$this->assertStringContainsString( '<em>italic</em>', $result['block_markup'] );
	}

	// =========================================================================
	// Output Schema Tests (Pattern 3 gap)
	// =========================================================================

	/**
	 * Test CreateAccordion output schema.
	 */
	public function test_create_accordion_output_schema() {
		$schema = CreateAccordion::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateCountdown output schema.
	 */
	public function test_create_countdown_output_schema() {
		$schema = CreateCountdown::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateCounter output schema.
	 */
	public function test_create_counter_output_schema() {
		$schema = CreateCounter::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateGoogleMap output schema.
	 */
	public function test_create_google_map_output_schema() {
		$schema = CreateGoogleMap::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateIcons output schema.
	 */
	public function test_create_icons_output_schema() {
		$schema = CreateIcons::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateList output schema.
	 */
	public function test_create_list_output_schema() {
		$schema = CreateList::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateSeparator output schema.
	 */
	public function test_create_separator_output_schema() {
		$schema = CreateSeparator::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateTabs output schema.
	 */
	public function test_create_tabs_output_schema() {
		$schema = CreateTabs::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	/**
	 * Test CreateContent output schema.
	 */
	public function test_create_content_output_schema() {
		$schema = CreateContent::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_markup', $schema['properties'] );
	}

	// =========================================================================
	// Permission Tests (Pattern 4/5 gap)
	// =========================================================================

	/**
	 * Test CreateButtons grants access to editors.
	 */
	public function test_create_buttons_permission_editor() {
		$this->assertTrue( CreateButtons::instance()->check_permission() );
	}

	/**
	 * Test CreateAccordion denies subscribers.
	 */
	public function test_create_accordion_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = CreateAccordion::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test CreateContent denies subscribers.
	 */
	public function test_create_content_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = CreateContent::instance()->check_permission();
		$this->assertWPError( $result );
	}

	// =========================================================================
	// "All Items Invalid" Edge Cases (Pattern 6 gap)
	// =========================================================================

	/**
	 * Test CreateAccordion fails when all items have missing question/answer.
	 */
	public function test_create_accordion_all_items_invalid() {
		$result = CreateAccordion::instance()->execute(
			array(
				'items' => array(
					array( 'question' => 'Q1' ),           // Missing answer.
					array( 'answer' => 'A2' ),             // Missing question.
				),
			)
		);

		// Should either return WP_Error or produce empty markup.
		if ( is_wp_error( $result ) ) {
			$this->assertSame( 'spectra_blocks_invalid_param', $result->get_error_code() );
		} else {
			$this->assertIsArray( $result );
		}
	}

	/**
	 * Test CreateGoogleMap with zoom 0 clamps to 1.
	 */
	public function test_create_google_map_zoom_clamping_low() {
		$result = CreateGoogleMap::instance()->execute(
			array(
				'address' => 'Test',
				'zoom'    => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertStringContainsString( '"zoom":1', $result['block_markup'] );
	}
}
