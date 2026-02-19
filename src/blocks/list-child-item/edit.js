/**
 * WordPress dependencies
 */
import { memo, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createBlock, cloneBlock } from '@wordpress/blocks';
import { BlockControls, store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Render from './render';
import Settings from './settings';
import { helperIcons } from '../../helpers/block-icons';
import './editor.scss';

/**
 * The edit function for the List Item block.
 *
 * @param {Object} props Block props.
 * @return {JSX.Element} Block edit component.
 */
const Edit = ( props ) => {
  const { clientId, isSelected } = props;
  
  const { replaceBlocks } = useDispatch( blockEditorStore );
  
  const {
    parentListType,
    canIndent,
    canOutdent,
  } = useSelect(
    ( select ) => {
      const {
        getBlockParentsByBlockName,
        getBlockAttributes,
        getBlockIndex,
        getAdjacentBlockClientId,
        getBlockRootClientId,
      } = select( blockEditorStore );
      
      // Find parent list
      const parentListIds = getBlockParentsByBlockName( clientId, 'spectra/list' );
      let listType = 'unordered';
      
      if ( parentListIds.length ) {
        const parentAttrs = getBlockAttributes( parentListIds[0] );
        listType = parentAttrs?.listType || 'unordered';
      }
      
      // Get previous block for indentation
      const index = getBlockIndex( clientId );
      const prevBlockId = getAdjacentBlockClientId( clientId, -1 );

      const parentListId = getBlockRootClientId( clientId );

      const canIndentValue = index > 0 && !!prevBlockId;

      const canOutdentValue = !!getBlockRootClientId( parentListId );
      
      return {
        parentListType: listType,
        canIndent: canIndentValue,
        canOutdent: canOutdentValue,
      };
    },
    [ clientId ]
  );
  
  // Handle indent - transform list item into a list block
  const handleIndent = useCallback( () => {
    if ( !canIndent ) {
      return;
    }

    const currentBlock = wp.data.select( blockEditorStore ).getBlock( clientId );

    const newListItemBlock = createBlock(
      'spectra/list-child-item',
      {
        ...currentBlock.attributes,
        level: 0,
      },
      currentBlock.innerBlocks.map( ( innerBlock ) => cloneBlock( innerBlock ) )
    );

    const newListBlock = createBlock( 'spectra/list', { listType: 'unordered' }, [ newListItemBlock ] );

    replaceBlocks( [ clientId ], [ newListBlock ] );
  }, [ clientId, canIndent, parentListType, replaceBlocks ] );
  
  // Handle outdent - move list item out of its parent list
  const handleOutdent = useCallback( () => {
    if ( !canOutdent ) {
      return;
    }

    const blockEditor = wp.data.select( blockEditorStore );
    const { insertBlock, removeBlock } = wp.data.dispatch( blockEditorStore );

    const parentListId = blockEditor.getBlockRootClientId( clientId );
    if ( !parentListId ) return;

    const parentBlock = blockEditor.getBlock( parentListId );

    if ( parentBlock.name === 'spectra/list' ) {
      const currentBlock = blockEditor.getBlock( clientId );

      const grandParentId = blockEditor.getBlockRootClientId( parentListId );
      if ( !grandParentId ) return;

      const parentIndex = blockEditor.getBlockIndex( parentListId );

      const newListItem = createBlock(
        'spectra/list-child-item',
        {
          ...currentBlock.attributes,
          level: 0,
        },
        currentBlock.innerBlocks.map( ( innerBlock ) => cloneBlock( innerBlock ) )
      );

      insertBlock( newListItem, parentIndex + 1, grandParentId );

      removeBlock( clientId );

      const remainingItems = parentBlock.innerBlocks.filter( ( block ) => block.clientId !== clientId );
      if ( remainingItems.length === 0 ) {
        removeBlock( parentListId );
      }
    }
  }, [ clientId, canOutdent ] );

  const isRTL = '1' === spectra_blocks_info.is_rtl ? true : false;

  return (
    <>
      { isSelected && <Settings { ...props } /> }
      <BlockControls>
        <ToolbarButton
          icon={ isRTL ? helperIcons.list.indentRTL( canIndent ) : helperIcons.list.indent( canIndent ) }
          title={ __( 'Indent', 'spectra-blocks' ) }
          onClick={ handleIndent }
          isDisabled={ !canIndent }
        />
        <ToolbarButton
          icon={ isRTL ? helperIcons.list.outdentRTL( canOutdent ) : helperIcons.list.outdent( canOutdent ) }
          title={ __( 'Outdent', 'spectra-blocks' ) }
          onClick={ handleOutdent }
          isDisabled={ !canOutdent }
        />
      </BlockControls>
      <Render { ...props } />
    </>
  );
};

export default memo( Edit );