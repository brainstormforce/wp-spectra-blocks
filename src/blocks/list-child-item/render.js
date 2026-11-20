/**
 * WordPress dependencies
 */
import { useBlockProps, InnerBlocks, useInnerBlocksProps, store as blockEditorStore } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * The spacing markers a converted item's container wears (zipwp-credits-saas block-converter
 * `markers.ts`), so an item the owner adds sits like the converted ones beside it.
 *
 * @since 1.0.16
 */
const CONVERTED_CONTAINER_MARKERS = 'spectra-no-padding spectra-no-width spectra-no-block-gap spectra-no-position';

/**
 * The render function for the List Item block.
 *
 * @param {Object} props The element props.
 * @since 1.0.16
 * @return {Element} Element to render.
 */
const Render = ( props ) => {
        const {
                clientId,
                attributes,
                context: {
                        'spectra/list/listType': listType,
                        'spectra/list/rotation': rotation,
                        'spectra/list/flipForRTL': flipForRTL,
                },
        } = props;

        const { 
                level = 0,
                style: blockStyle,
        } = attributes;

        // Extract WordPress core colors to use in Spectra system
        const wpTextColor = blockStyle?.color?.text;
        const wpBackgroundColor = blockStyle?.color?.background;


        const { hasChildBlocks, itemIndex, blockChildren, isNested, isConvertedList } = useSelect(
                ( select ) => {
                        const {
                                getBlockOrder,
                                getBlockRootClientId,
                                getBlocks,
                                getBlockParentsByBlockName,
                                getBlockAttributes,
                        } = select( blockEditorStore );

                        const parentListIds = getBlockParentsByBlockName( clientId, 'spectra/list' );
                        const isNestedList = parentListIds.length > 1;
                        const parentListClassName = getBlockAttributes( parentListIds[ parentListIds.length - 1 ] )?.className || '';

                        const parentId = getBlockRootClientId( clientId );
                        let index = 0;
                        let total = 0;
                        if ( parentId ) {
                                const siblings = getBlocks( parentId ).filter( ( block ) => block.name === 'spectra/list-child-item' );
                                index = siblings.findIndex( ( block ) => block.clientId === clientId );
                                total = siblings.length;
                        }

                        return {
                                hasChildBlocks: getBlockOrder( clientId ).length > 0,
                                itemIndex: index + 1,
                                blockChildren: getBlocks( clientId ),
                                isNested: isNestedList,
                                totalItems: total,
                                isConvertedList: parentListClassName.split( ' ' ).includes( 'spectra-no-list-icon' ),
                        };
                },
                [ clientId ]
        );

        const { updateBlockAttributes, __unstableMarkNextChangeAsNotPersistent: markNotPersistent } = useDispatch( blockEditorStore );

        // Configuration for the useSpectraStyles hook.
        const config = [
                { key: 'textColor', value: wpTextColor },
                { key: 'textColorHover' },
                { key: 'backgroundColor', value: wpBackgroundColor },
                { key: 'backgroundColorHover' },
                { key: 'backgroundGradient' },
                { key: 'backgroundGradientHover' },
        ];

        // Custom class names.
        const customClassNames = [
                'spectra-list-item',
                `spectra-list-item-${listType || 'unordered'}`,
                `spectra-list-item-level-${level}`
        ];

        // Generate styles and class names.
        const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

        // Use the block props directly
        const blockProps = useBlockProps( {
                className: spectraClassNames( classNames ),
                style: {
                        ...style,
                        marginLeft: `${level * 20}px`, // Add indentation based on level
                        listStyleType: 'none', // Hide the default marker
                },
        } );

        // Always include list-icon block, it will conditionally render number or icon. An item of a
        // converted list gets the container its siblings have: the spacing markers, and a Text
        // `<span>` to type its words into.
        const template = [
                ['spectra/list-child-icon', { itemIndex, level, rotation, flipForRTL, isNested }],
                isConvertedList
                        ? ['spectra/container', { variationSelected: true, htmlTag: 'div', layout: { type: 'default' }, className: CONVERTED_CONTAINER_MARKERS }, [ ['spectra/content', { tagName: 'span' }] ]]
                        : ['spectra/container', { variationSelected: true }]
        ];

        // Propagate variation selection to inner blocks when required.
        useEffect( () => {
                if ( blockChildren && blockChildren.length > 0 ) {
                        blockChildren.forEach( ( child ) => {
                                // Mark this child as the default selected variation if it is a container.
                                if ( false === child.attributes?.variationSelected && 'spectra/container' === child.name ) {
                                        updateBlockAttributes( child.clientId, { variationSelected: true } );
                                }
                        } );
                }
        }, [ blockChildren, updateBlockAttributes ] );

        // Keep list-child-icon attributes in sync with position. A position is derived from the
        // tree, so its sync joins the change that moved the item, never an undo step of its own:
        // an Undo that restored the old value would only see it written again.
        useEffect( () => {
                if ( blockChildren && blockChildren.length > 0 ) {
                        const iconBlock = blockChildren.find( ( child ) => child.name === 'spectra/list-child-icon' );
                        const current = iconBlock?.attributes ?? {};
                        if ( iconBlock && ( current.itemIndex !== itemIndex || current.isNested !== isNested ) ) {
                                markNotPersistent();
                                updateBlockAttributes( iconBlock.clientId, {
                                        itemIndex,
                                        isNested,
                                } );
                        }
                }
        }, [ blockChildren, itemIndex, isNested, updateBlockAttributes, markNotPersistent ] );

        // Persist current index attribute for frontend numbering, as part of the change that moved
        // the item (see the icon sync above).
        useEffect( () => {
                if ( attributes.index !== itemIndex ) {
                        markNotPersistent();
                        updateBlockAttributes( clientId, { index: itemIndex } );
                }
        }, [ attributes.index, itemIndex, clientId, updateBlockAttributes, markNotPersistent ] );

        const innerBlocksProps = useInnerBlocksProps(
                {},
                {
                        template,
                        templateLock: false,
                        orientation: 'horizontal',
                        renderAppender: hasChildBlocks ? undefined : InnerBlocks.ButtonBlockAppender,
                }
        );

        return (
                <li { ...blockProps }>
                        <div { ...innerBlocksProps } />
                </li>
        );
};

export default Render;