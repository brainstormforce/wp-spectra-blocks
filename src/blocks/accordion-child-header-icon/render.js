/**
 * External dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { memo, useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import RenderSVG from '@spectra-helpers/render-svg';
import {
	getResponsivePreviewCss,
	iconDimensionStyles,
	inheritResponsiveKey,
	resolveInheritedResponsiveValue,
} from '@spectra-helpers/responsive-preview';

/**
 * The Editor Block render.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const {
		context: {
			'spectra/accordion/icon': accordionIcon,
			'spectra/accordion/iconSecondary': accordionIconSecondary,
			'spectra/accordion/size': accordionIconSize,
			'spectra/accordion/style': accordionStyle,
			'spectra/accordion/rotation': accordionIconRotation,
			'spectra/accordion/item/isActiveInEditor': isActiveInEditor,
		},
		attributes,
		clientId,
	} = props;

	const {
		icon,
		iconSecondary,
		size,
		rotation,
		flipForRTL,
		flipForRTLSecondary,
	} = attributes;

	// State to use the required icon.
	const [ editorIcon, setEditorIcon ] = useState( icon );
	const [ editorIconFlipForRTL, setEditorIconFlipForRTL ] = useState( flipForRTL );

	// Effect to use the required icon based on whether this accordion item is selected.
	useEffect( () => {
		setEditorIcon( isActiveInEditor
			? iconSecondary || accordionIconSecondary || 'minus'
			: icon || accordionIcon || 'plus'
		);
		setEditorIconFlipForRTL( isActiveInEditor ? flipForRTLSecondary : flipForRTL );
	}, [
		isActiveInEditor,
		icon,
		iconSecondary,
		accordionIcon,
		accordionIconSecondary,
		flipForRTL,
		flipForRTLSecondary,
	] );

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	// Use the block props, with the added CSS varialbes and their related classes.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	/*
	 * The inherited size comes from block context, which carries the parent's
	 * ROOT attribute — the last-edited device's value — so an inheriting icon
	 * previewed that one value at every breakpoint. Resolve it from the parent's
	 * `style` for the previewed device, and let the bands the icon does not size
	 * itself inherit the parent's bands in the preview CSS.
	 */
	const previewDevice = useSelect( ( select ) => select( 'core/editor' )?.getDeviceType?.(), [] );
	const inheritedSize = resolveInheritedResponsiveValue( accordionStyle, 'size', previewDevice, accordionIconSize );
	const iconSize = size || inheritedSize || '24px';

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes: { ...attributes, style: inheritResponsiveKey( attributes.style, 'size', accordionStyle, 'size' ) },
		blockName: 'spectra/accordion-child-header-icon',
		producers: [ ( attrs ) => iconDimensionStyles( attrs.size || '24px' ) ],
	} );

	return (
		<span { ...blockProps }>
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			<RenderSVG
				svg={ editorIcon }
				needsRTL={ editorIconFlipForRTL }
				extraProps={ {
					width: iconSize,
					height: iconSize,
					style: {
						width: iconSize,
						height: iconSize,
						transform:
							rotation || accordionIconRotation
								? `rotate(${ rotation || accordionIconRotation }deg)`
								: '',
					},
				} }
			/>
		</span>
	);
};

export default memo( Render );
