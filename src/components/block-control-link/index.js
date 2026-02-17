/**
 * External dependencies.
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	LinkControl,
} from '@wordpress/block-editor';
import {
	Popover,
	ToolbarGroup,
	ToolbarButton,
} from '@wordpress/components';
import { link } from '@wordpress/icons';
import { applyFilters } from '@wordpress/hooks';

const BlockControlLink = ( props ) => {
	const {
		setAttributes = () => {},
		url = {},
		target = {},
		rel = {
			label: undefined,
			value: [],
		},
	} = props

	const [ isEditingLink, setIsEditingLink ] = useState( false );

	// Filter to add content above LinkControl.
	const linkControlExtensions = applyFilters(
		'spectra.linkControl.Extensions',
		null,
		props
	);

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon={ link }
					label={ url?.value ? 'Edit Link' : 'Add Link' }
					onClick={ () => setIsEditingLink( ! isEditingLink ) }
					isPressed={ isEditingLink }
				/>
				{ isEditingLink && (
					<Popover>
						{ /* Render filtered content if exists */ }
						{ linkControlExtensions }

						<LinkControl
							showInitialSuggestions
							settings={ [
								{ id: 'opensInNewTab', title: __( 'Open in a new tab', 'ultimate-addons-for-gutenberg' ) },
								{ id: 'noFollow', title: __( 'Mark as nofollow', 'ultimate-addons-for-gutenberg' ) },
								{ id: 'noReferer', title: __( 'Mark as noreferer', 'ultimate-addons-for-gutenberg' ) },
								{ id: 'noOpener', title: __( 'Mark as noopener', 'ultimate-addons-for-gutenberg' ) },
							] }
							value={ {
								url: url?.value,
								opensInNewTab: '_blank' === target?.value,
								noFollow: rel.value?.includes( 'nofollow' ),
								noReferer: rel.value?.includes( 'noreferer' ),
								noOpener: rel.value?.includes( 'noopener' ),
							} }
							onChange={ ( value ) => {
								setAttributes( {
									[ url.label ]: value.url,
									[ target.label ]: value.opensInNewTab ? '_blank' : '_self',
									[ rel.label ]: [
										value.noFollow && 'nofollow',
										value.noReferer && 'noreferer',
										value.noOpener && 'noopener',
									],
								} );
							} }
							onRemove={ () => {
								setAttributes( {
									[ url.label ]: undefined,
									[ target.label ]: undefined,
									[ rel.label ]: undefined,
								} );
							} }
							onClose={ () => setIsEditingLink( false ) }
						/>
					</Popover>
				) }
			</ToolbarGroup>
		</BlockControls>
	);
};

export default BlockControlLink;