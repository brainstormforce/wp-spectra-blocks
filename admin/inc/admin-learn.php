<?php
/**
 * Spectra Learn Helper Class
 *
 * @package Spectra
 * @since 0.0.1
 */

namespace SpectraBlocksAdmin\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Admin_Learn class.
 *
 * @since 0.0.1
 */
class Admin_Learn {
	/**
	 * Get default learn chapters structure.
	 *
	 * Returns the complete structure of all available chapters and their steps.
	 * This serves as the source of truth for chapter definitions used across
	 * the theme for both frontend display and analytics validation.
	 *
	 * @return array Array of chapter objects with their steps.
	 * @since 0.0.1
	 */
	public static function get_chapters_structure() {
		// Add Edit Your Homepage chapter as the last item.
		$homepage_id  = intval( get_option( 'page_on_front', 0 ) ); // @phpstan-ignore-line as get_option returns mixed.
		$homepage_url = $homepage_id ? admin_url( 'post.php?post=' . $homepage_id . '&action=edit' ) : admin_url( 'options-reading.php' );
		$chapters     = array(
			array(
				'id'          => 'editor-basics',
				'title'       => __( 'Editor Basics', 'spectra' ),
				'description' => __( 'Edit your pages using Spectra with step-by-step guide and make them live with confidence.', 'spectra' ),
				'url'         => 'https://wpspectra.com/docs/getting-started-v3/#build-from-scratch-',
				'steps'       => array(
					array(
						'id'          => 'add-your-first-block',
						'title'       => __( 'Add Your First Block', 'spectra' ),
						'description' => __( 'Use the plus icon to insert a block like a heading, image, or button. Its the quickest way to start shaping your page.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/add-your-first-block.png',
									'alt' => __( 'Add Your First Spectra Block in Editor', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => $homepage_url . '#learn-add-your-first-block',
							'isExternal' => true,
						),
						'completed'   => false,
					),
					array(
						'id'          => 'insert-ready-made-sections',
						'title'       => __( 'Insert Ready-Made Sections', 'spectra' ),
						'description' => __( 'Add pre-designed Spectra patterns to build sections faster without starting from scratch.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/01/insert-ready-made-template-1.png',
									'alt' => __( 'Inseart the Ready-Made Spectra sections in Editor', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => ( 'yes' === \Spectra_Admin_Helper::get_admin_settings_option( 'uag_enable_templates_button', 'yes' ) ? $homepage_url . '#learn-insert-ready-made-sections' : admin_url( 'admin.php?page=spectra&path=settings&settings=editor-enhancements' ) ),
							'isExternal' => true,
						),
						'completed'   => false,
					),
				),
			),
			array(
				'id'          => 'design-essentials',
				'title'       => __( 'Design Essentials', 'spectra' ),
				'description' => __( 'Create clean, consistent sections that reflect your brand and message', 'spectra' ),
				'url'         => 'https://wpspectra.com/docs/spectra-design-library-guide/',
				'steps'       => array(
					array(
						'id'          => 'replace-placeholder-content',
						'title'       => __( 'Replace Placeholder Content', 'spectra' ),
						'description' => __( 'Swap out demo text and images with your own to make every section feel authentic and relevant to your business.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/replace-placeholder-content.png',
									'alt' => __( 'Replace Placeholder Content', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => $homepage_url . '#learn-replace-placeholder-content',
							'isExternal' => true,
						),
						'completed'   => false,
					),
					array(
						'id'          => 'customize-cta-sections',
						'title'       => __( 'Customize CTA Sections', 'spectra' ),
						'description' => __( 'Edit buttons, links, and calls to action so visitors know exactly where to go next.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/customize-cta-sections.png',
									'alt' => __( 'Customize CTA Sections in Astra', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => $homepage_url . '#learn-customize-cta-sections',
							'isExternal' => true,
						),
						'completed'   => false,
					),
					array(
						'id'          => 'block-settings-styles',
						'title'       => __( 'Block Settings & Styles', 'spectra' ),
						'description' => __( 'Open the Settings and Styles panels to shape each block the way you want. Small changes in spacing, colors, and typography can make your page feel instantly more refined.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/block-settings-styles.png',
									'alt' => __( 'Block Settings & Styles', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => $homepage_url . '#learn-block-settings-styles',
							'isExternal' => true,
						),
						'isPro'       => false,
						'completed'   => false,
					),
				),
			),
		);

		if ( defined( 'ASTRA_THEME_VERSION' ) ) {
			$chapters[] = array(
				'id'          => 'page-layout-settings',
				'title'       => __( 'Page Layout Settings', 'spectra' ),
				'description' => __( 'Control how your page looks from edge to edge using layout options powered by Astra', 'spectra' ),
				'url'         => 'https://wpastra.com/docs/understanding-container-style-in-astra-theme-customizing-your-containers-look/',
				'steps'       => array(
					array(
						'id'          => 'choose-page-layout',
						'title'       => __( 'Choose Page Layout', 'spectra' ),
						'description' => __( 'Pick from Full Width, Boxed, or other layouts to create the structure that suits your design best.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/change-page-layout.png',
									'alt' => __( 'Choose Page Layout', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => $homepage_url . '#astra-container-layout',
							'isExternal' => true,
						),
						'completed'   => false,
					),
					array(
						'id'          => 'show-hide-elements',
						'title'       => __( 'Show or Hide Elements', 'spectra' ),
						'description' => __( 'Toggle the header, footer, or page title visibility when you need a clean, distraction-free look.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/show-and-hide-elements.png',
									'alt' => __( 'Show or Hide Elements', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => $homepage_url . '#astra-disable-elements',
							'isExternal' => true,
						),
						'completed'   => false,
					),
				),
			);
		}

		if ( is_plugin_active( 'spectra-pro/spectra-pro.php' ) ) {
			$chapters[] = array(
				'id'          => 'global-styles',
				'title'       => __( 'Global Styles', 'spectra' ),
				'description' => __( 'Set consistent colors, fonts, and spacing across your entire site.', 'spectra' ),
				'url'         => 'https://wpspectra.com/docs/global-styles/',
				'steps'       => array(
					array(
						'id'          => 'open-global-styles',
						'title'       => __( 'Open Global Styles', 'spectra' ),
						'description' => __( 'Access Global Styles to control typography, colors, and spacing for your entire site from one place.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/open-global-styles.png',
									'alt' => __( 'Open Global Styles', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => admin_url( 'admin.php?page=spectra&path=global-styles&learn=open-global-styles' ),
							'isExternal' => false,
						),
						'isPro'       => true,
						'completed'   => false,
					),
					array(
						'id'          => 'find-global-styles-in-block-settings',
						'title'       => __( 'Find Global Styles in Block Settings', 'spectra' ),
						'description' => __( 'Global Styles can be applied directly from the block editor, letting you control a block’s design without changing individual settings.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/find-global-styles-in-block-settings.png',
									'alt' => __( 'Find Global Styles in Block Settings', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => $homepage_url . '#learn-find-global-styles-in-block-settings',
							'isExternal' => true,
						),
						'isPro'       => true,
						'completed'   => false,
					),
					array(
						'id'          => 'set-global-colors-fonts-spacing',
						'title'       => __( 'Set Global Colors, Fonts & Spacing', 'spectra' ),
						'description' => __( 'Define colors, font sizes, and spacing once so every page stays consistent without manual block styling.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/set-global-colors-fonts-spacing.png',
									'alt' => __( 'Set Global Colors, Fonts & Spacing', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => admin_url( 'admin.php?page=spectra&path=global-styles&settings=colors&learn=set-global-colors-fonts-spacing' ),
							'isExternal' => false,
						),
						'isPro'       => true,
						'completed'   => false,
					),
					array(
						'id'          => 'use-block-defaults',
						'title'       => __( 'Use Block Defaults', 'spectra' ),
						'description' => __( 'Set default styles for the blocks so new sections look right the moment you add them.', 'spectra' ),
						'learn'       => array(
							'type'    => 'dialog',
							'content' => array(
								'type' => 'image',
								'data' => array(
									'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/use-block-defaults.png',
									'alt' => __( 'Use Block Defaults', 'spectra' ),
								),
							),
						),
						'action'      => array(
							'label'      => __( 'Set Up', 'spectra' ),
							'url'        => admin_url( 'admin.php?page=spectra&path=global-styles&settings=block-defaults&learn=use-block-defaults' ),
							'isExternal' => false,
						),
						'isPro'       => true,
						'completed'   => false,
					),
				),
			);
		}

		$chapters[] = array(
			'id'          => 'make-your-page-live',
			'title'       => __( 'Make Your Page Live', 'spectra' ),
			'description' => __( 'Review, save, and publish your work with confidence', 'spectra' ),
			'url'         => 'https://wpspectra.com/docs/preview-options/',
			'steps'       => array(
				array(
					'id'          => 'preveiw-your-changes',
					'title'       => __( 'Preview Your Changes', 'spectra' ),
					'description' => __( 'Keep your progress safe by saving your draft as you refine your design and preview how your page looks to the world!', 'spectra' ),
					'learn'       => array(
						'type'    => 'dialog',
						'content' => array(
							'type' => 'image',
							'data' => array(
								'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/preview-your-changes.png',
								'alt' => __( 'Preview Your Changes', 'spectra' ),
							),
						),
					),
					'action'      => array(
						'label'      => __( 'Set Up', 'spectra' ),
						'url'        => $homepage_url . '#learn-preveiw-your-changes',
						'isExternal' => true,
					),
					'completed'   => false,
				),
				array(
					'id'          => 'publish-your-page',
					'title'       => __( 'Publish Your Page', 'spectra' ),
					'description' => __( 'Make your homepage live and ready for visitors. Celebrate your first win.', 'spectra' ),
					'learn'       => array(
						'type'    => 'dialog',
						'content' => array(
							'type' => 'image',
							'data' => array(
								'src' => 'https://wpspectra.com/wp-content/uploads/2026/02/publish-your-page.png',
								'alt' => __( 'Publish Your Page', 'spectra' ),
							),
						),
					),
					'action'      => array(
						'label'      => __( 'Set Up', 'spectra' ),
						'url'        => $homepage_url . '#learn-publish-your-page',
						'isExternal' => true,
					),
					'completed'   => false,
				),
			),
		);

		/**
		 * Filter learn chapters structure.
		 *
		 * @param array $chapters Learn chapters data.
		 * @since 0.0.1
		 */
		return (array) apply_filters( 'spectra_learn_chapters', (array) $chapters );
	}

	/**
	 * Get learn chapters with user progress merged.
	 *
	 * @param int $user_id Optional. User ID to get progress for. Defaults to current user.
	 * @return array Chapters array with progress data merged.
	 * @since 0.0.1
	 */
	public static function get_learn_chapters( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// Get chapters structure.
		$chapters = (array) self::get_chapters_structure();

		// Get saved progress from user meta.
		$saved_progress = get_user_meta( $user_id, 'spectra_learn_progress', true );
		if ( ! is_array( $saved_progress ) ) {
			$saved_progress = array();
		}

		// Merge saved progress with chapters.
		foreach ( $chapters as &$chapter ) {
			// Validate chapter structure.
			if ( ! isset( $chapter['id'], $chapter['steps'] ) || ! is_array( $chapter['steps'] ) ) {
				continue;
			}

			$chapter_id = $chapter['id'];

			foreach ( $chapter['steps'] as &$step ) {
				if ( ! isset( $step['id'] ) ) {
					continue;
				}

				$step_id = $step['id'];
				if ( isset( $saved_progress[ $chapter_id ][ $step_id ] ) ) {
					$step['completed'] = $saved_progress[ $chapter_id ][ $step_id ];
				}
			}
		}

		return $chapters;
	}
}
