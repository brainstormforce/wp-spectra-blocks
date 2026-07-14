<?php
/**
 * Abilities Manager for Spectra Blocks.
 *
 * Registers ability categories and concrete abilities with the WordPress Abilities API.
 *
 * @package SpectraBlocks
 */

namespace SpectraBlocks;

use SpectraBlocks\Traits\Singleton;
use SpectraBlocks\Abilities\ListAvailableBlocks;
use SpectraBlocks\Abilities\GetBlockConfig;
use SpectraBlocks\Abilities\GeneratePageLayout;
use SpectraBlocks\Abilities\CreateAccordion;
use SpectraBlocks\Abilities\CreateButtons;
use SpectraBlocks\Abilities\CreateContainer;
use SpectraBlocks\Abilities\CreateCountdown;
use SpectraBlocks\Abilities\CreateCounter;
use SpectraBlocks\Abilities\CreateGoogleMap;
use SpectraBlocks\Abilities\CreateIcons;
use SpectraBlocks\Abilities\CreateList;
use SpectraBlocks\Abilities\CreateModal;
use SpectraBlocks\Abilities\CreateSeparator;
use SpectraBlocks\Abilities\CreateSlider;
use SpectraBlocks\Abilities\CreateTabs;
use SpectraBlocks\Abilities\GetAnalyticsSummary;
use SpectraBlocks\Abilities\ToggleBlockActivation;
use SpectraBlocks\Abilities\ListPopups;
use SpectraBlocks\Abilities\GetPopup;
use SpectraBlocks\Abilities\TogglePopupStatus;
use SpectraBlocks\Abilities\DeletePopup;
use SpectraBlocks\Abilities\ListSelectedFonts;
use SpectraBlocks\Abilities\ListAvailableGoogleFonts;
use SpectraBlocks\Abilities\CreateContent;
use SpectraBlocks\Abilities\CreatePopup;
use SpectraBlocks\Abilities\GetPluginSettings;
use SpectraBlocks\Abilities\GetBlockActivationStatus;
use SpectraBlocks\Abilities\GetPostContent;
use SpectraBlocks\Abilities\UpdateBlockAttributes;
use SpectraBlocks\Abilities\RemoveBlock;
use SpectraBlocks\Abilities\UpdatePluginSetting;
use SpectraBlocks\Abilities\ApplyAnimation;
use SpectraBlocks\Abilities\RemoveAnimation;
use SpectraBlocks\Abilities\ApplySticky;
use SpectraBlocks\Abilities\RemoveSticky;
use SpectraBlocks\Abilities\ApplyResponsiveConditions;
use SpectraBlocks\Abilities\RemoveResponsiveConditions;
use SpectraBlocks\Abilities\SearchPostsByBlock;
use SpectraBlocks\Abilities\SearchPostContent;
use SpectraBlocks\Abilities\AddGoogleFont;
use SpectraBlocks\Abilities\RemoveGoogleFont;
use SpectraBlocks\Abilities\MoveBlock;
use SpectraBlocks\Abilities\DuplicateBlock;
use SpectraBlocks\Abilities\ApplyZIndex;
use SpectraBlocks\Abilities\ApplyImageMask;
use SpectraBlocks\Abilities\UpdatePopup;
use SpectraBlocks\Abilities\CreatePost;
use SpectraBlocks\Abilities\ApplyDisplayConditions;
use SpectraBlocks\Abilities\RemoveDisplayConditions;
use SpectraBlocks\Abilities\GetGlobalStylesConfig;
use SpectraBlocks\Abilities\UpdateGlobalStyles;

defined( 'ABSPATH' ) || exit;

/**
 * Abilities Manager class.
 *
 * @since 1.0.0
 */
class AbilitiesManager {

	use Singleton;

	/**
	 * Initialize the abilities manager.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		if ( 'enabled' !== \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_abilities', 'disabled' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register ability categories.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_categories(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		$categories = array(
			'spectra-blocks-discovery'     => array(
				'label'       => __( 'Spectra Blocks — Discovery', 'spectra-blocks' ),
				'description' => __( 'Discover available blocks, read post content, search for blocks across the site, and view analytics.', 'spectra-blocks' ),
			),
			'spectra-blocks-content'       => array(
				'label'       => __( 'Spectra Blocks — Content', 'spectra-blocks' ),
				'description' => __( 'Create, update, move, duplicate, and remove blocks and popups in posts.', 'spectra-blocks' ),
			),
			'spectra-blocks-layout'        => array(
				'label'       => __( 'Spectra Blocks — Layout', 'spectra-blocks' ),
				'description' => __( 'Create containers, modals, sliders, and generate full page layouts.', 'spectra-blocks' ),
			),
			'spectra-blocks-configuration' => array(
				'label'       => __( 'Spectra Blocks — Configuration', 'spectra-blocks' ),
				'description' => __( 'Manage plugin settings, block activation, and Google Fonts.', 'spectra-blocks' ),
			),
			'spectra-blocks-extensions'    => array(
				'label'       => __( 'Spectra Blocks — Extensions', 'spectra-blocks' ),
				'description' => __( 'Apply and remove block extensions like animations, sticky, responsive conditions, z-index, and image masks.', 'spectra-blocks' ),
			),
		);

		foreach ( $categories as $slug => $args ) {
			wp_register_ability_category( $slug, $args );
		}
	}

	/**
	 * Register all concrete abilities.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		$abilities = array(
			// Discovery.
			ListAvailableBlocks::class,
			GetBlockConfig::class,
			GetPostContent::class,
			SearchPostsByBlock::class,
			SearchPostContent::class,

			// Layout.
			GeneratePageLayout::class,
			CreateContainer::class,
			CreateModal::class,
			CreateSlider::class,

			// Content.
			CreateAccordion::class,
			CreateButtons::class,
			CreateCountdown::class,
			CreateCounter::class,
			CreateGoogleMap::class,
			CreateIcons::class,
			CreateList::class,
			CreateContent::class,
			CreateSeparator::class,
			CreateTabs::class,
			CreatePost::class,
			UpdateBlockAttributes::class,
			RemoveBlock::class,
			MoveBlock::class,
			DuplicateBlock::class,

			// Analytics.
			GetAnalyticsSummary::class,

			// Configuration.
			GetPluginSettings::class,
			UpdatePluginSetting::class,
			GetBlockActivationStatus::class,
			ToggleBlockActivation::class,
			ListSelectedFonts::class,
			ListAvailableGoogleFonts::class,
			AddGoogleFont::class,
			RemoveGoogleFont::class,
			GetGlobalStylesConfig::class,
			UpdateGlobalStyles::class,

			// Popup Management.
			CreatePopup::class,
			ListPopups::class,
			GetPopup::class,
			TogglePopupStatus::class,
			DeletePopup::class,
			UpdatePopup::class,

			// Extensions.
			ApplyAnimation::class,
			RemoveAnimation::class,
			ApplySticky::class,
			RemoveSticky::class,
			ApplyResponsiveConditions::class,
			RemoveResponsiveConditions::class,
			ApplyZIndex::class,
			ApplyImageMask::class,
			ApplyDisplayConditions::class,
			RemoveDisplayConditions::class,
		);

		foreach ( $abilities as $ability_class ) {
			( $ability_class::instance() )->register();
		}
	}
}
