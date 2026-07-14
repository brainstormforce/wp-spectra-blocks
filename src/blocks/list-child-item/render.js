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
 * The render function for the List Item block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
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


        const { hasChildBlocks, itemIndex, blockChildren, isNested } = useSelect(
                ( select ) => {
                        const {
                                getBlockOrder,
                                getBlockRootClientId,
                                getBlocks,
                                getBlockParentsByBlockName,
                        } = select( blockEditorStore );

                        const parentListIds = getBlockParentsByBlockName( clientId, 'spectra/list' );
                        const isNestedList = parentListIds.length > 1;

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
                        };
                },
                [ clientId ]
        );

        const { updateBlockAttributes } = useDispatch( blockEditorStore );

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

        // Always include list-icon block, it will conditionally render number or icon
        const template = [
                ['spectra/list-child-icon', { itemIndex, level, rotation, flipForRTL, isNested }],
                ['spectra/container', { variationSelected: true }]
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

        // Keep list-child-icon attributes in sync with position
        useEffect( () => {
                if ( blockChildren && blockChildren.length > 0 ) {
                        const iconBlock = blockChildren.find( ( child ) => child.name === 'spectra/list-child-icon' );
                        if ( iconBlock ) {
                                updateBlockAttributes( iconBlock.clientId, {
                                        itemIndex,
                                        level,
                                        isNested,
                                } );
                        }
                }
        }, [ blockChildren, itemIndex, level, isNested, updateBlockAttributes ] );

        // Persist current index attribute for frontend numbering.
        useEffect( () => {
                if ( attributes.index !== itemIndex ) {
                        updateBlockAttributes( clientId, { index: itemIndex } );
                }
        }, [ attributes.index, itemIndex, clientId, updateBlockAttributes ] );

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