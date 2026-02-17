/**
 * External dependencies.
 */
import {
	useBlockProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import { memo, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { RenderFullWidthAppenderWhenEmpty } from '@spectra-components/block-appender';

/**
 * The Editor Block render.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element}     The rendered block.
 */
const Render = ( props ) => {

	const {
		clientId,
		context: {
			'spectra/accordion/item/isActiveInEditor': isActiveInEditor,
		},
	} = props;

	// State for the display style in the editor.
	const [ editorStyle, setEditorStyle ] = useState( { display: 'none' } );

	// Effect to update the display of this block in the editor based on the 
	useEffect( () => {
		setEditorStyle( isActiveInEditor ? {} : { display: 'none' } );
	}, [ isActiveInEditor ] )

	// Use the block props, and pass the editor styles.
	const blockProps = useBlockProps( {
		style: {
			...editorStyle,
		}
	} );

	return (
		<div { ...blockProps } >
			<InnerBlocks
				renderAppender={ RenderFullWidthAppenderWhenEmpty( clientId ) }
			/>
		</div>
	);
};

export default memo( Render );
