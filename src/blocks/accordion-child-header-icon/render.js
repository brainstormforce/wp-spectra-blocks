/**
 * External dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { memo, useState, useEffect, useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import RenderSVG from '@spectra-helpers/render-svg';

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
			'spectra/accordion/rotation': accordionIconRotation,
			'spectra/accordion/item/isActiveInEditor': isActiveInEditor,
		},
		attributes,
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

	// Configuration for the useSpectraStyles hook - memoized to prevent recalculation.
	const config = useMemo( () => [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	], [] );

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	// Use the block props, with the added CSS varialbes and their related classes.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	const iconSize = size || accordionIconSize || '24px';

	return (
		<span { ...blockProps }>
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
