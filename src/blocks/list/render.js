/**
 * WordPress dependencies
 */
import { BlockControls, useBlockProps, useInnerBlocksProps, store as blockEditorStore } from '@wordpress/block-editor';
import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { memo, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { helperIcons } from '../../helpers/block-icons';

/**
 * Hook for outdenting a list.
 *
 * @param {string} clientId The client ID of the block.
 * @return {Function} The outdent function.
 */
function useOutdentList( clientId ) {
	const { replaceBlocks, selectionChange } = useDispatch( blockEditorStore );
	const { getBlockRootClientId, getBlockAttributes, getBlock, getBlockName } = useSelect(
		( select ) => select( blockEditorStore ),
		[]
	);

	return useCallback( () => {
		const parentBlockId = getBlockRootClientId( clientId );
		if ( !parentBlockId ) return;

		const parentBlockName = getBlockName( parentBlockId );

		if ( parentBlockName === 'spectra/list-child-item' ) {
			const parentBlockAttributes = getBlockAttributes( parentBlockId );
			const newParentBlock = createBlock(
					'spectra/list-child-item',
					parentBlockAttributes
			);
			const { innerBlocks } = getBlock( clientId );

			replaceBlocks( [ parentBlockId ], [ newParentBlock, ...innerBlocks ] );

			if ( innerBlocks.length > 0 ) {
					selectionChange( innerBlocks[ innerBlocks.length - 1 ].clientId );
			}
		} else if ( parentBlockName === 'spectra/list' ) {
			const { innerBlocks } = getBlock( clientId );
			replaceBlocks( [ clientId ], innerBlocks );
			if ( innerBlocks.length > 0 ) {
				selectionChange( innerBlocks[ innerBlocks.length - 1 ].clientId );
			}
		}
	}, [ clientId, getBlockRootClientId, getBlockAttributes, getBlock, getBlockName, replaceBlocks, selectionChange ] );
}

/**
 * Outdent UI component.
 *
 * @param {Object} props Component props.
 * @param {string} props.clientId Block client ID.
 * @return {JSX.Element} Component to render.
 */
function OutdentUI( { clientId } ) {
	const isRTL = '1' === spectra_blocks_info.is_rtl ? true : false;
	const outdentList = useOutdentList( clientId );
	const canOutdent = useSelect(
		( select ) => {
			const { getBlockRootClientId, getBlockName } = select( blockEditorStore );
			const parentId = getBlockRootClientId( clientId );
			if ( !parentId ) return false;
			const parentName = getBlockName( parentId );
			return parentName === 'spectra/list-child-item' || parentName === 'spectra/list';
		},
		[ clientId ]
	);
	
	return (
		<ToolbarButton
			icon={ isRTL ? helperIcons.list.outdentRTL( canOutdent ) : helperIcons.list.outdent( canOutdent ) }
			title={ __( 'Outdent', 'spectra-blocks' ) }
			description={ __( 'Outdent list', 'spectra-blocks' ) }
			disabled={ !canOutdent }
			onClick={ outdentList }
		/>
	);
}


/**
 * The render function for the List block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Render = memo( ( props ) => {
	const {
		attributes,
		setAttributes,
		clientId
	} = props;
	
	const {
		listType,
		reversed,
		start,
		alignment,
		textColor,
		textColorHover,
	} = attributes;

	const DEFAULT_BLOCK = {
		name: 'spectra/list-child-item',
	};
	const TEMPLATE = [ [ 'spectra/list-child-item' ] ];

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor', class: 'spectra-text-color', value: textColor },
		{ key: 'textColorHover', class: 'spectra-text-color-hover', value: textColorHover },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	// Add additional props for ordered lists
	const listProps = {};
	if ( listType === 'ordered' ) {
		if ( reversed ) {
			listProps.reversed = true;
		}
		if ( start !== undefined ) {
			listProps.start = start;
		}
	}

	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style,
		...listProps
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		defaultBlock: DEFAULT_BLOCK,
		directInsert: true,
		template: TEMPLATE,
		templateLock: false,
		templateInsertUpdatesSelection: true,
		__experimentalCaptureToolbars: true,
	} );

	const controls = (
		<BlockControls group="block">
			<ToolbarButton
				icon={ listType === 'unordered' ? helperIcons.list.unordered( false ) : helperIcons.list.unordered }
				title={ __( 'Unordered', 'spectra-blocks' ) }
				description={ __( 'Convert to unordered list', 'spectra-blocks' ) }
				isActive={ listType === 'unordered' }
				onClick={ () => { setAttributes( { listType: 'unordered' } ) } }
			/>
			<ToolbarButton
				icon={ listType === 'ordered' ? helperIcons.list.ordered( false ) : helperIcons.list.ordered }
				title={ __( 'Ordered', 'spectra-blocks' ) }
				description={ __( 'Convert to ordered list', 'spectra-blocks' ) }
				isActive={ listType === 'ordered' }
				onClick={ () => { setAttributes( { listType: 'ordered' } ) } }
			/>
                       { alignment !== 'horizontal' && (
                               <OutdentUI clientId={ clientId } />
                       ) }
               </BlockControls>
       );

	// Choose the appropriate HTML tag based on list type
	const ListTag = listType === 'ordered' ? 'ol' : 'ul';

	return (
		<>
			<ListTag { ...innerBlocksProps } />
			{ controls }
		</>
	);
} );

export default Render;