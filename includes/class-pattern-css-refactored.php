<?php
/**
 * Refactored Pattern CSS Generation with SOLID Principles
 *
 * This file intentionally contains multiple classes implementing design patterns
 * (Strategy, Builder, Factory) for cohesive pattern-based CSS generation.
 *
 * @package Spectra
 * @since 3.0.0
 *
 * phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
 */

namespace SpectraBlocks;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for CSS generators following Strategy Pattern.
 *
 * @since 3.0.0
 */
interface PatternCSSGeneratorInterface {
	/**
	 * Check if this generator can handle the given block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_name The block name to check.
	 * @return bool Whether this generator can handle the block.
	 */
	public function can_handle( string $block_name ): bool;

	/**
	 * Generate CSS for the given block.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block   The block data.
	 * @param array $context Additional context for CSS generation.
	 * @return string Generated CSS.
	 */
	public function generate_css( array $block, array $context = array() ): string;
}

/**
 * CSS Builder class following Builder Pattern.
 *
 * @since 3.0.0
 */
class PatternCSSBuilder {
	/**
	 * Array of CSS rules.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	private $css_rules = array();

	/**
	 * Track base CSS that has been added.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	private $base_css_added = array();

	/**
	 * Add a CSS rule.
	 *
	 * @since 3.0.0
	 *
	 * @param string $selector   CSS selector.
	 * @param array  $properties CSS properties.
	 * @return self For method chaining.
	 */
	public function add_rule( string $selector, array $properties ): self {
		if ( empty( $properties ) ) {
			return $this;
		}

		$css_rule = $selector . " {\n";
		foreach ( $properties as $property => $value ) {
			if ( $this->is_valid_css_value( $value ) ) {
				$css_rule .= "    {$property}: {$value};\n";
			}
		}
		$css_rule .= "}\n";

		$this->css_rules[] = $css_rule;
		return $this;
	}

	/**
	 * Add base CSS once (prevents duplication).
	 *
	 * @since 3.0.0
	 *
	 * @param string $key Unique key for this base CSS.
	 * @param string $css CSS content to add.
	 * @return self For method chaining.
	 */
	public function add_base_css_once( string $key, string $css ): self {
		if ( ! isset( $this->base_css_added[ $key ] ) ) {
			$this->css_rules[]            = $css;
			$this->base_css_added[ $key ] = true;
		}
		return $this;
	}

	/**
	 * Build and return the final CSS string.
	 *
	 * @since 3.0.0
	 *
	 * @return string Complete CSS output.
	 */
	public function build(): string {
		return implode( "\n", $this->css_rules );
	}

	/**
	 * Check if a CSS value is valid.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $value Value to check.
	 * @return bool Whether the value is valid.
	 */
	private function is_valid_css_value( $value ): bool {
		return ! empty( $value ) && is_scalar( $value );
	}
}

/**
 * WordPress Core Block CSS Generator.
 *
 * @since 3.0.0
 */
class WordPressCoreBlockCSSGenerator implements PatternCSSGeneratorInterface {
	/**
	 * Supported WordPress core blocks.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	private $supported_blocks = array(
		'core/group',
		'core/columns',
		'core/column',
		'core/image',
		'core/gallery',
	);

	/**
	 * Check if this generator can handle the given block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_name The block name to check.
	 * @return bool Whether this generator can handle the block.
	 */
	public function can_handle( string $block_name ): bool {
		return strpos( $block_name, 'core/' ) === 0;
	}

	/**
	 * Generate CSS for the given block.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block   The block data.
	 * @param array $context Additional context for CSS generation.
	 * @return string Generated CSS.
	 */
	public function generate_css( array $block, array $context = array() ): string {
		$builder    = new PatternCSSBuilder();
		$block_name = $block['blockName'] ?? '';
		$attrs      = $block['attrs'] ?? array();

		// Add base WordPress layout CSS.
		$this->add_base_wordpress_css( $builder );

		// Generate block-specific CSS.
		switch ( $block_name ) {
			case 'core/group':
				$this->generate_group_css( $builder, $attrs );
				break;
			case 'core/columns':
				$this->generate_columns_css( $builder, $attrs );
				break;
			case 'core/column':
				$this->generate_column_css( $builder, $attrs );
				break;
			case 'core/image':
				$this->generate_image_css( $builder, $attrs );
				break;
			case 'core/gallery':
				$this->generate_gallery_css( $builder, $attrs );
				break;
			default:
				$this->generate_generic_layout_css( $builder, $block_name, $attrs );
		}

		return $builder->build();
	}

	/**
	 * Add base WordPress layout CSS.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder CSS builder instance.
	 * @return void
	 */
	private function add_base_wordpress_css( PatternCSSBuilder $builder ): void {
		$base_css = "\n/* WordPress Core Layout CSS */\n" .
			"body .is-layout-flex {\n    display: flex;\n}\n" .
			".is-layout-flex {\n    flex-wrap: wrap;\n    align-items: center;\n}\n" .
			"body .is-layout-grid {\n    display: grid;\n}\n" .
			".is-layout-grid {\n    grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));\n    gap: 1.25rem;\n}\n";

		$builder->add_base_css_once( 'wordpress_core_layout', $base_css );
	}

	/**
	 * Generate CSS for core/group block.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder CSS builder instance.
	 * @param array             $attrs   Block attributes.
	 * @return void
	 */
	private function generate_group_css( PatternCSSBuilder $builder, array $attrs ): void {
		$layout = $attrs['layout'] ?? array();
		$style  = $attrs['style'] ?? array();

		if ( isset( $layout['type'] ) && 'flex' === $layout['type'] ) {
			$this->add_flex_layout_css( $builder, $layout, 'wp-block-group' );
		}

		$this->add_gap_css( $builder, $style, 'wp-container-core-group-is-layout' );

		$builder->add_rule( '.wp-block-group-is-layout-flex', array( 'display' => 'flex' ) );
	}

	/**
	 * Add flex layout CSS.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder     CSS builder instance.
	 * @param array             $layout      Layout configuration.
	 * @param string            $block_class Block CSS class.
	 * @return void
	 */
	private function add_flex_layout_css( PatternCSSBuilder $builder, array $layout, string $block_class ): void {
		// Orientation.
		if ( isset( $layout['orientation'] ) && 'vertical' === $layout['orientation'] ) {
			$builder->add_rule( ".{$block_class}.is-vertical", array( 'flex-direction' => 'column' ) );
		}

		// Justification.
		$justify_content = $layout['justifyContent'] ?? 'left';
		$align_props     = $this->get_alignment_properties( $justify_content );
		$builder->add_rule( ".{$block_class}.is-content-justification-{$justify_content}", $align_props );
		$builder->add_rule( ".is-content-justification-{$justify_content}", $align_props );
	}

	/**
	 * Get alignment properties for flex layout.
	 *
	 * @since 3.0.0
	 *
	 * @param string $justification Justification value.
	 * @return array CSS properties.
	 */
	private function get_alignment_properties( string $justification ): array {
		$alignment_map = array(
			'center'        => array( 'align-items' => 'center' ),
			'right'         => array( 'align-items' => 'flex-end' ),
			'space-between' => array( 'justify-content' => 'space-between' ),
			'left'          => array( 'align-items' => 'flex-start' ),
		);

		return $alignment_map[ $justification ] ?? $alignment_map['left'];
	}

	/**
	 * Add gap CSS for layout.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder          CSS builder instance.
	 * @param array             $style            Style configuration.
	 * @param string            $container_prefix Container class prefix.
	 * @return void
	 */
	private function add_gap_css( PatternCSSBuilder $builder, array $style, string $container_prefix ): void {
		if ( ! isset( $style['spacing']['blockGap'] ) ) {
			return;
		}

		$gap       = $style['spacing']['blockGap'];
		$selectors = array();

		for ( $i = 1; $i <= 10; $i++ ) {
			$selectors[] = ".{$container_prefix}-{$i}";
		}

		$builder->add_rule( implode( ",\n", $selectors ), array( 'gap' => $gap ) );
	}

	/**
	 * Generate CSS for core/columns block.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder CSS builder instance.
	 * @param array             $attrs   Block attributes.
	 * @return void
	 */
	private function generate_columns_css( PatternCSSBuilder $builder, array $attrs ): void {
		$builder->add_rule(
			'.wp-block-columns',
			array(
				'display'   => 'flex',
				'flex-wrap' => 'wrap',
			)
		);

		if ( isset( $attrs['isStackedOnMobile'] ) && $attrs['isStackedOnMobile'] ) {
			$builder->add_rule(
				'.wp-block-columns.is-stacked-on-mobile',
				array(
					'flex-direction' => 'column',
				)
			);
		}
	}

	/**
	 * Generate CSS for core/column block.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder CSS builder instance.
	 * @param array             $attrs   Block attributes.
	 * @return void
	 */
	private function generate_column_css( PatternCSSBuilder $builder, array $attrs ): void {
		if ( isset( $attrs['width'] ) ) {
			$builder->add_rule(
				'.wp-block-column',
				array(
					'flex-basis' => $attrs['width'],
					'flex-grow'  => '0',
				)
			);
		}
	}

	/**
	 * Generate CSS for core/image block.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder CSS builder instance.
	 * @param array             $attrs   Block attributes.
	 * @return void
	 */
	private function generate_image_css( PatternCSSBuilder $builder, array $attrs ): void {
		if ( isset( $attrs['align'] ) ) {
			$align_props = $this->get_image_alignment_properties( $attrs['align'] );
			$builder->add_rule( ".wp-block-image.align{$attrs['align']}", $align_props );
		}

		$image_props = array();
		if ( isset( $attrs['width'] ) ) {
			$image_props['width'] = $attrs['width'];
		}
		if ( isset( $attrs['height'] ) ) {
			$image_props['height'] = $attrs['height'];
		}

		if ( ! empty( $image_props ) ) {
			$builder->add_rule( '.wp-block-image img', $image_props );
		}
	}

	/**
	 * Get alignment properties for image block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $align Alignment value.
	 * @return array CSS properties.
	 */
	private function get_image_alignment_properties( string $align ): array {
		$alignment_map = array(
			'center' => array( 'text-align' => 'center' ),
			'left'   => array( 'margin-right' => '1em' ),
			'right'  => array( 'margin-left' => '1em' ),
		);

		return $alignment_map[ $align ] ?? array();
	}

	/**
	 * Generate CSS for core/gallery block.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder CSS builder instance.
	 * @param array             $attrs   Block attributes.
	 * @return void
	 */
	private function generate_gallery_css( PatternCSSBuilder $builder, array $attrs ): void {
		if ( isset( $attrs['columns'] ) ) {
			$columns = $attrs['columns'];
			$builder->add_rule(
				".wp-block-gallery.has-{$columns}-columns",
				array(
					'grid-template-columns' => "repeat({$columns}, 1fr)",
				)
			);
		}
	}

	/**
	 * Generate generic layout CSS for core blocks.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder    CSS builder instance.
	 * @param string            $block_name Block name.
	 * @param array             $attrs      Block attributes.
	 * @return void
	 */
	private function generate_generic_layout_css( PatternCSSBuilder $builder, string $block_name, array $attrs ): void {
		if ( ! isset( $attrs['layout'] ) ) {
			return;
		}

		$layout      = $attrs['layout'];
		$block_class = '.wp-block-' . str_replace( 'core/', '', $block_name );

		if ( isset( $layout['type'] ) && 'flex' === $layout['type'] ) {
			$builder->add_rule( "{$block_class}.is-layout-flex", array( 'display' => 'flex' ) );

			if ( isset( $layout['orientation'] ) && 'vertical' === $layout['orientation'] ) {
				$builder->add_rule( "{$block_class}.is-vertical", array( 'flex-direction' => 'column' ) );
			}
		}
	}
}

/**
 * Spectra Block CSS Generator.
 *
 * @since 3.0.0
 */
class SpectraBlockCSSGenerator implements PatternCSSGeneratorInterface {
	/**
	 * Check if this generator can handle the given block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_name The block name to check.
	 * @return bool Whether this generator can handle the block.
	 */
	public function can_handle( string $block_name ): bool {
		return strpos( $block_name, 'spectra/' ) === 0;
	}

	/**
	 * Generate CSS for the given block.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block   The block data.
	 * @param array $context Additional context for CSS generation.
	 * @return string Generated CSS.
	 */
	public function generate_css( array $block, array $context = array() ): string {
		$builder    = new PatternCSSBuilder();
		$block_name = $block['blockName'] ?? '';
		$attrs      = $block['attrs'] ?? array();

		// Add base Spectra layout CSS.
		$this->add_base_spectra_css( $builder );

		// Generate layout-specific CSS.
		$this->generate_layout_css( $builder, $block_name, $attrs );

		return $builder->build();
	}

	/**
	 * Add base Spectra layout CSS.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder CSS builder instance.
	 * @return void
	 */
	private function add_base_spectra_css( PatternCSSBuilder $builder ): void {
		$base_css = "\n/* Spectra Layout Support CSS */\n" .
			"body .wp-block-spectra-container.is-layout-flex,\n" .
			"body .wp-block-spectra-buttons.is-layout-flex,\n" .
			"body .wp-block-spectra-icons.is-layout-flex,\n" .
			"body .wp-block-spectra-accordion.is-layout-flex {\n    display: flex;\n}\n" .
			"body .wp-block-spectra-container.is-layout-grid {\n    display: grid;\n}\n";

		$builder->add_base_css_once( 'spectra_layout', $base_css );
	}

	/**
	 * Generate layout-specific CSS.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder    CSS builder instance.
	 * @param string            $block_name Block name.
	 * @param array             $attrs      Block attributes.
	 * @return void
	 */
	private function generate_layout_css( PatternCSSBuilder $builder, string $block_name, array $attrs ): void {
		$layout = $attrs['layout'] ?? array();
		$style  = $attrs['style'] ?? array();

		if ( empty( $layout['type'] ) ) {
			return;
		}

		$block_class = '.wp-block-' . str_replace( '/', '-', $block_name );

		if ( 'flex' === $layout['type'] ) {
			$this->generate_flex_layout_css( $builder, $block_class, $layout );
		} elseif ( 'grid' === $layout['type'] ) {
			$this->generate_grid_layout_css( $builder, $block_class, $layout );
		}

		$this->add_spacing_css( $builder, $block_class, $style );
	}

	/**
	 * Generate flex layout CSS.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder     CSS builder instance.
	 * @param string            $block_class Block CSS class.
	 * @param array             $layout      Layout configuration.
	 * @return void
	 */
	private function generate_flex_layout_css( PatternCSSBuilder $builder, string $block_class, array $layout ): void {
		// Orientation.
		if ( isset( $layout['orientation'] ) ) {
			$orientation    = $layout['orientation'];
			$flex_direction = 'vertical' === $orientation ? 'column' : 'row';
			$builder->add_rule(
				"{$block_class}.is-{$orientation}",
				array(
					'flex-direction' => $flex_direction,
				)
			);
		}

		// Flex wrap.
		if ( isset( $layout['flexWrap'] ) ) {
			$builder->add_rule(
				"{$block_class}.is-flex-wrap-{$layout['flexWrap']}",
				array(
					'flex-wrap' => $layout['flexWrap'],
				)
			);
		}

		// Justification and alignment.
		$this->add_flex_alignment_css( $builder, $block_class, $layout );
	}

	/**
	 * Add flex alignment CSS.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder     CSS builder instance.
	 * @param string            $block_class Block CSS class.
	 * @param array             $layout      Layout configuration.
	 * @return void
	 */
	private function add_flex_alignment_css( PatternCSSBuilder $builder, string $block_class, array $layout ): void {
		if ( isset( $layout['justifyContent'] ) ) {
			$justify = $layout['justifyContent'];
			$props   = $this->get_justify_content_properties( $justify );
			$builder->add_rule( "{$block_class}.is-content-justification-{$justify}", $props );
		}

		if ( isset( $layout['verticalAlignment'] ) ) {
			$align = $layout['verticalAlignment'];
			$props = $this->get_vertical_alignment_properties( $align );
			$builder->add_rule( "{$block_class}.is-vertical-alignment-{$align}", $props );
		}
	}

	/**
	 * Get justify-content CSS properties.
	 *
	 * @since 3.0.0
	 *
	 * @param string $justify Justification value.
	 * @return array CSS properties.
	 */
	private function get_justify_content_properties( string $justify ): array {
		$justify_map = array(
			'center'        => array( 'justify-content' => 'center' ),
			'right'         => array( 'justify-content' => 'flex-end' ),
			'space-between' => array( 'justify-content' => 'space-between' ),
			'stretch'       => array( 'align-items' => 'stretch' ),
			'left'          => array( 'justify-content' => 'flex-start' ),
		);

		return $justify_map[ $justify ] ?? $justify_map['left'];
	}

	/**
	 * Get vertical alignment CSS properties.
	 *
	 * @since 3.0.0
	 *
	 * @param string $align Alignment value.
	 * @return array CSS properties.
	 */
	private function get_vertical_alignment_properties( string $align ): array {
		$align_map = array(
			'center' => array( 'align-items' => 'center' ),
			'bottom' => array( 'align-items' => 'flex-end' ),
			'top'    => array( 'align-items' => 'flex-start' ),
		);

		return $align_map[ $align ] ?? $align_map['top'];
	}

	/**
	 * Generate grid layout CSS.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder     CSS builder instance.
	 * @param string            $block_class Block CSS class.
	 * @param array             $layout      Layout configuration.
	 * @return void
	 */
	private function generate_grid_layout_css( PatternCSSBuilder $builder, string $block_class, array $layout ): void {
		if ( isset( $layout['columnCount'] ) ) {
			$columns = $layout['columnCount'];
			$builder->add_rule(
				"{$block_class}.has-{$columns}-columns",
				array(
					'grid-template-columns' => "repeat({$columns}, 1fr)",
				)
			);
		}

		if ( isset( $layout['minimumColumnWidth'] ) ) {
			$min_width = $layout['minimumColumnWidth'];
			$builder->add_rule(
				"{$block_class}.has-min-column-width",
				array(
					'grid-template-columns' => "repeat(auto-fit, minmax({$min_width}, 1fr))",
				)
			);
		}
	}

	/**
	 * Add spacing CSS.
	 *
	 * @since 3.0.0
	 *
	 * @param PatternCSSBuilder $builder     CSS builder instance.
	 * @param string            $block_class Block CSS class.
	 * @param array             $style       Style configuration.
	 * @return void
	 */
	private function add_spacing_css( PatternCSSBuilder $builder, string $block_class, array $style ): void {
		if ( isset( $style['spacing']['blockGap'] ) ) {
			$gap = $style['spacing']['blockGap'];
			$builder->add_rule(
				"{$block_class}-is-layout-flex,\n{$block_class}-is-layout-grid",
				array( 'gap' => $gap )
			);
		}
	}
}

/**
 * Pattern CSS Factory following Factory Pattern.
 *
 * @since 3.0.0
 */
class PatternCSSGeneratorFactory {
	/**
	 * Cached generators instances.
	 *
	 * @since 3.0.0
	 * @var array|null
	 */
	private static $generators = null;

	/**
	 * Get all registered CSS generators.
	 *
	 * @since 3.0.0
	 *
	 * @return array Array of generator instances.
	 */
	public static function get_generators(): array {
		if ( null === self::$generators ) {
			self::$generators = array(
				new WordPressCoreBlockCSSGenerator(),
				new SpectraBlockCSSGenerator(),
			);
		}

		return self::$generators;
	}

	/**
	 * Get the appropriate generator for a block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_name Block name to get generator for.
	 * @return PatternCSSGeneratorInterface|null Generator instance or null if none found.
	 */
	public static function get_generator_for_block( string $block_name ): ?PatternCSSGeneratorInterface {
		foreach ( self::get_generators() as $generator ) {
			if ( $generator->can_handle( $block_name ) ) {
				return $generator;
			}
		}

		return null;
	}
}

/**
 * Main Pattern CSS Service following Single Responsibility Principle.
 *
 * @since 3.0.0
 */
class PatternCSSService {
	/**
	 * Generate CSS for a post.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id Post ID to generate CSS for.
	 * @return string Generated CSS.
	 */
	public function generate_css_for_post( int $post_id ): string {
		if ( ! $post_id ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post || empty( $post->post_content ) ) {
			return '';
		}

		$blocks = parse_blocks( $post->post_content );
		return $this->process_blocks( $blocks );
	}

	/**
	 * Process an array of blocks recursively.
	 *
	 * @since 3.0.0
	 *
	 * @param array $blocks Array of blocks to process.
	 * @return string Generated CSS.
	 */
	private function process_blocks( array $blocks ): string {
		$css_content = '';

		foreach ( $blocks as $block ) {
			$css_content .= $this->process_single_block( $block );

			// Process inner blocks recursively.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$css_content .= $this->process_blocks( $block['innerBlocks'] );
			}
		}

		return $css_content;
	}

	/**
	 * Process a single block.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block Block data to process.
	 * @return string Generated CSS.
	 */
	private function process_single_block( array $block ): string {
		$block_name = $block['blockName'] ?? '';

		if ( empty( $block_name ) ) {
			return '';
		}

		$generator = PatternCSSGeneratorFactory::get_generator_for_block( $block_name );

		if ( null === $generator ) {
			return '';
		}

		return $generator->generate_css( $block, $this->get_context() );
	}

	/**
	 * Get context for CSS generation.
	 *
	 * @since 3.0.0
	 *
	 * @return array Context data.
	 */
	private function get_context(): array {
		// Check if we're in pattern preview context.
		$is_pattern_preview = $this->is_pattern_preview_context();

		return array(
			'is_pattern_preview' => $is_pattern_preview,
			'base_selector'      => $is_pattern_preview ? '.st-block-container' : 'body',
		);
	}

	/**
	 * Check if we're in pattern preview context.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether we're in pattern preview context.
	 */
	private function is_pattern_preview_context(): bool {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Required to detect rendering context for proper CSS selector generation in pattern previews.
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );

		foreach ( $backtrace as $trace ) {
			if ( isset( $trace['function'] ) &&
				in_array(
					$trace['function'],
					array(
						'spectra_blocks_get_v3_blocks_css_for_preview',
						'spectra_get_comprehensive_responsive_css_for_post',
						'spectra_blocks_process_blocks_for_comprehensive_css',
					),
					true
				) ) {
				return true;
			}
		}

		return false;
	}
}
