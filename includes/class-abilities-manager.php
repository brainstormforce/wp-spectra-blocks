<?php
/**
 * Abilities Manager for Spectra Blocks.
 *
 * Registers ability categories and concrete abilities with the WordPress Abilities API.
 *
 * @package Spectra
 */

namespace Spectra;

defined( 'ABSPATH' ) || exit;

use Spectra\Traits\Singleton;
use Spectra\Abilities\ListAvailableBlocks;
use Spectra\Abilities\GetBlockConfig;
use Spectra\Abilities\GeneratePageLayout;
use Spectra\Abilities\CreateAccordion;
use Spectra\Abilities\CreateButtons;
use Spectra\Abilities\CreateContainer;
use Spectra\Abilities\CreateCountdown;
use Spectra\Abilities\CreateCounter;
use Spectra\Abilities\CreateGoogleMap;
use Spectra\Abilities\CreateIcons;
use Spectra\Abilities\CreateList;
use Spectra\Abilities\CreateModal;
use Spectra\Abilities\CreateSeparator;
use Spectra\Abilities\CreateSlider;
use Spectra\Abilities\CreateTabs;
use Spectra\Abilities\ToggleBlockActivation;
use Spectra\Abilities\ListPopups;
use Spectra\Abilities\GetPopup;
use Spectra\Abilities\TogglePopupStatus;
use Spectra\Abilities\DeletePopup;
use Spectra\Abilities\ListSelectedFonts;
use Spectra\Abilities\ListAvailableGoogleFonts;
use Spectra\Abilities\CreateContent;
use Spectra\Abilities\CreatePopup;
use Spectra\Abilities\GetPluginSettings;
use Spectra\Abilities\GetBlockActivationStatus;
use Spectra\Abilities\GetPostContent;
use Spectra\Abilities\UpdateBlockAttributes;
use Spectra\Abilities\RemoveBlock;
use Spectra\Abilities\UpdatePluginSetting;
use Spectra\Abilities\ApplyAnimation;
use Spectra\Abilities\RemoveAnimation;
use Spectra\Abilities\ApplySticky;
use Spectra\Abilities\RemoveSticky;
use Spectra\Abilities\ApplyResponsiveConditions;
use Spectra\Abilities\RemoveResponsiveConditions;
use Spectra\Abilities\SearchPostsByBlock;
use Spectra\Abilities\SearchPostContent;
use Spectra\Abilities\AddGoogleFont;
use Spectra\Abilities\RemoveGoogleFont;
use Spectra\Abilities\MoveBlock;
use Spectra\Abilities\DuplicateBlock;
use Spectra\Abilities\ApplyZIndex;
use Spectra\Abilities\ApplyImageMask;
use Spectra\Abilities\UpdatePopup;

defined( 'ABSPATH' ) || exit;

/**
 * Abilities Manager class.
 *
 * @since x.x.x
 */
class AbilitiesManager {

	use Singleton;

	/**
	 * Initialize the abilities manager.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register ability categories.
	 *
	 * @since x.x.x
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
				'description' => __( 'Discover available blocks, read post content, and search for blocks across the site.', 'spectra-blocks' ),
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
	 * @since x.x.x
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
			UpdateBlockAttributes::class,
			RemoveBlock::class,
			MoveBlock::class,
			DuplicateBlock::class,

			// Configuration.
			GetPluginSettings::class,
			UpdatePluginSetting::class,
			GetBlockActivationStatus::class,
			ToggleBlockActivation::class,
			ListSelectedFonts::class,
			ListAvailableGoogleFonts::class,
			AddGoogleFont::class,
			RemoveGoogleFont::class,

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
		);

		foreach ( $abilities as $ability_class ) {
			( $ability_class::instance() )->register();
		}
	}
}
