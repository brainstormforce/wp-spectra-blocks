/**
 * HTML Attributes Extension Controls.
 *
 * Adds two attribute layers to every allowed Spectra block:
 *   Layer 1 — Contextual Tag Items: ToolsPanelItems rendered inside the block's
 *             own General/Container ToolsPanel. Only spectra/content and
 *             spectra/container use this layer since they are the only blocks
 *             with a native HTML tag selector.
 *   Layer 2 — Custom Attributes: a name/value repeater with an explicit
 *             "+ Add Attribute" button and per-row remove buttons, on every block.
 *
 * @since x.x.x
 */

import { InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	TextControl,
	ToggleControl,
	SelectControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { close, plus } from '@wordpress/icons';

import { BLOCKED_ATTRS, BOOLEAN_ATTRS } from './utils/constants';
import {
	getAttrValue,
	getBlockFields,
	getTagFields,
	isAllowedBlock,
	setAttrValue,
	splitAttributes,
} from './utils/helpers';

/**
 * Whether the current user can set arbitrary HTML attributes.
 *
 * Mirrors the PHP `current_user_can('unfiltered_html')` check. Defaults to
 * true when the localized data is absent (e.g. unit test environments).
 *
 * @since x.x.x
 */
const canEdit = window?.spectraBlocksHtmlAttrs?.canEdit ?? true;

/**
 * Normalises a raw htmlAttributes value to an array.
 *
 * Older content/container blocks stored htmlAttributes as type:"object" in
 * block.json. After the schema was corrected to type:"array", old posts may
 * still have an object-shaped value in the editor. Normalise it here so all
 * downstream code can safely call Array methods.
 *
 * @since x.x.x
 *
 * @param {*} raw - The raw htmlAttributes value from block attributes.
 * @return {Array} Guaranteed array of { attribute, value } objects.
 */
const normalizeAttrs = ( raw ) => {
	if ( Array.isArray( raw ) ) {
		return raw;
	}
	if ( raw && typeof raw === 'object' ) {
		return Object.entries( raw ).map( ( [ attribute, value ] ) => ( { attribute, value: String( value ) } ) );
	}
	return [];
};

/**
 * Validates a <time> datetime attribute value against HTML spec formats.
 *
 * Accepts: year, year-month, date, yearless date, week, time, local datetime,
 * global datetime (with Z or numeric offset), and duration strings.
 * An empty value is considered valid (the field is optional).
 *
 * @since x.x.x
 *
 * @param {string} value - The candidate datetime string.
 * @return {boolean} True when the value is empty or matches a recognised format.
 */
const isValidDatetime = ( value ) => {
	if ( ! value ) {
		return true;
	}
	const TIME = '\\d{2}:\\d{2}(:\\d{2}(\\.\\d+)?)?';
	const TZ   = '(Z|[+-]\\d{2}:\\d{2})';
	const patterns = [
		/^\d{4}$/,                                          // year
		/^\d{4}-\d{2}$/,                                   // year-month
		/^\d{4}-\d{2}-\d{2}$/,                            // date
		/^--\d{2}-\d{2}$/,                                 // yearless date
		/^\d{4}-W\d{2}$/,                                  // week
		new RegExp( `^${ TIME }$` ),                        // time
		new RegExp( `^\\d{4}-\\d{2}-\\d{2}[T ]${ TIME }${ TZ }?$` ), // datetime (local / global)
		/^P(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(\d+H)?(\d+M)?(\d+(\.\d+)?S)?)?$/, // duration
	];
	return patterns.some( ( re ) => re.test( value ) );
};

/**
 * Renders the appropriate control for a single contextual field definition.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Object}   props.field        - Field definition from TAG_ATTRIBUTE_CONFIG.
 * @param {string}   props.currentValue - Current stored value.
 * @param {Function} props.onChange     - Callback receiving the new value.
 * @return {Element} The rendered control.
 */
const ContextualFieldControl = ( { field, currentValue, onChange } ) => {
	const { attribute, label, type, help, options } = field;

	if ( type === 'toggle' ) {
		return (
			<ToggleControl
				__nextHasNoMarginBottom
				label={ label }
				help={ help }
				checked={ currentValue === 'true' }
				onChange={ ( checked ) => onChange( checked ? 'true' : '' ) }
			/>
		);
	}

	if ( type === 'select' ) {
		return (
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				help={ help }
				value={ currentValue }
				options={ options }
				onChange={ onChange }
			/>
		);
	}

	if ( type === 'number' ) {
		return (
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				type="number"
				label={ label }
				help={ help }
				value={ currentValue }
				onChange={ onChange }
				placeholder={ attribute }
			/>
		);
	}

	const isDatetime  = attribute === 'datetime';
	const dateInvalid = isDatetime && ! isValidDatetime( currentValue );

	return (
		<>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				help={ help }
				value={ currentValue }
				onChange={ onChange }
				placeholder={ attribute }
				className={ dateInvalid ? 'spectra-field-invalid' : '' }
			/>
			{ dateInvalid && (
				<p className="spectra-field-error">
					{ __( 'Use a valid datetime format, e.g. 2024–06–15 or 14:30', 'spectra-blocks' ) }
				</p>
			) }
		</>
	);
};

/**
 * ToolsPanelItems for contextual tag attributes.
 *
 * Renders one ToolsPanelItem per field defined in TAG_ATTRIBUTE_CONFIG for the
 * current tag. Designed to be placed as a direct child of an existing ToolsPanel
 * so the fields appear inline (adjacent to the HTML Tag selector) rather than in
 * a separate panel. Returns null when the active tag has no contextual fields.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {string}   props.tag            - The currently selected HTML tag (lowercase).
 * @param {Array}    props.htmlAttributes - The full htmlAttributes array.
 * @param {string}   props.clientId       - Block clientId (must match parent ToolsPanel panelId).
 * @param {Function} props.setAttributes  - Block setAttributes.
 * @param {Array}    props.excludeAttrs   - Attribute names to skip (e.g. when the block
 *                                        handles href/target/rel via a dedicated UI).
 * @return {Element|null} ToolsPanelItem elements, or null if no fields for this tag.
 */
const ContextualTagItems = ( { tag, htmlAttributes, clientId, setAttributes, excludeAttrs = [] } ) => {
	if ( ! canEdit ) {
		return null;
	}

	const fields = getTagFields( tag ).filter( ( f ) => ! excludeAttrs.includes( f.attribute ) );

	if ( ! fields.length ) {
		return null;
	}

	const updateAttr = ( attrName, value ) => {
		let updated = setAttrValue( htmlAttributes, attrName, value );

		// Security: auto-populate rel when target switches to _blank.
		if ( attrName === 'target' && value === '_blank' ) {
			const currentRel = getAttrValue( updated, 'rel' );
			if ( ! currentRel ) {
				updated = setAttrValue( updated, 'rel', 'noopener noreferrer' );
			}
		}

		setAttributes( { htmlAttributes: updated } );
	};

	return (
		<>
			{ fields.map( ( field ) => {
				const currentValue = getAttrValue( htmlAttributes, field.attribute );
				const hasValue = currentValue !== '' && currentValue !== undefined;

				return (
					<ToolsPanelItem
						key={ field.attribute }
						label={ field.label }
						panelId={ clientId }
						isShownByDefault={ true }
						hasValue={ () => hasValue }
						onDeselect={ () => updateAttr( field.attribute, '' ) }
					>
						<ContextualFieldControl
							field={ field }
							currentValue={ currentValue }
							onChange={ ( value ) => updateAttr( field.attribute, value ) }
						/>
					</ToolsPanelItem>
				);
			} ) }
		</>
	);
};


/**
 * A committed attribute row (name/value pair already saved to block attributes).
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {Object}   props.row      - The { attribute, value } object.
 * @param {number}   props.index    - Row index.
 * @param {Function} props.onChange - Called with (index, { attribute, value }).
 * @param {Function} props.onRemove - Called with (index).
 * @return {Element} The row element.
 */
const AttributeRow = ( { row, index, onChange, onRemove } ) => {
	const lowerName = ( row.attribute || '' ).toLowerCase();
	const isBlocked =
		row.attribute &&
		( lowerName.startsWith( 'on' ) || BLOCKED_ATTRS.has( lowerName ) );

	return (
		<div className="spectra-attr-row">
			<HStack alignment="center" spacing={ 1 }>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					aria-label={ __( 'Attribute name', 'spectra-blocks' ) }
					placeholder={ __( 'Name', 'spectra-blocks' ) }
					value={ row.attribute }
					onChange={ ( attribute ) => onChange( index, { ...row, attribute } ) }
					className={
						'spectra-attr-name' +
						( isBlocked ? ' spectra-attr-name--blocked' : '' )
					}
				/>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					aria-label={ __( 'Attribute value', 'spectra-blocks' ) }
					placeholder={ __( 'Value', 'spectra-blocks' ) }
					value={ row.value }
					onChange={ ( value ) => onChange( index, { ...row, value } ) }
					className="spectra-attr-value"
				/>
				<Button
					icon={ close }
					isDestructive
					label={ __( 'Remove', 'spectra-blocks' ) }
					onClick={ () => onRemove( index ) }
					size="small"
					className="spectra-attr-remove"
				/>
			</HStack>
			{ isBlocked && (
				<p className="spectra-attr-warning">
					{ lowerName.startsWith( 'on' )
						? __( 'Event handler — will be stripped on save.', 'spectra-blocks' )
						: __( 'Managed by block settings — not allowed here.', 'spectra-blocks' ) }
				</p>
			) }
		</div>
	);
};

/**
 * Shared repeater body for the custom attributes panel.
 *
 * Renders committed attribute rows, each with inline name/value inputs and a
 * remove button. An explicit "+ Add Attribute" button appends a new empty row.
 * Used by both CustomAttributesSection (per-block / Settings group) and the HOC.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {string}   props.tag            - Currently selected HTML tag.
 * @param {Array}    props.htmlAttributes - The full htmlAttributes array.
 * @param {Function} props.setAttributes  - Block setAttributes.
 * @return {Element} The repeater body.
 */
/**
 * A row is complete — and so worth persisting — once it has a name and a value.
 * A "0" counts as a value; whitespace alone does not. Boolean attributes
 * (download, open, reversed, …) are complete with just a name, since they render
 * as bare attributes.
 *
 * @param {Object} row - The { attribute, value } row.
 * @return {boolean} Whether the row should be written to block attributes.
 */
const isCompleteRow = ( row ) => {
	const name = ( row.attribute || '' ).trim();
	if ( '' === name ) {
		return false;
	}
	// A value makes any row complete. A boolean attribute (download, open,
	// reversed, …) is complete with no value — it renders as a bare attribute,
	// so it must not be treated as an unfinished row and dropped. See #568.
	return ( row.value || '' ).trim() !== '' || BOOLEAN_ATTRS.has( name.toLowerCase() );
};

const CustomAttributesBody = ( { tag, htmlAttributes, setAttributes } ) => {
	const { contextual, custom } = splitAttributes( htmlAttributes, tag );

	// Editing rows live in local state: the committed rows plus any the author
	// is still filling in. Only complete rows are written back to block
	// attributes, so a half-typed row never lands in saved content — while the
	// row being typed stays visible here.
	const [ rows, setRows ] = useState( custom );

	// Re-seed from block attributes when they change outside this panel (undo /
	// redo, or selecting a different block). The committed subset of our local
	// rows already mirrors `custom`, so guard against that to avoid a loop and
	// to keep the in-progress empty row while editing.
	const customKey = JSON.stringify( custom );
	useEffect( () => {
		const committedKey = JSON.stringify( rows.filter( isCompleteRow ) );
		if ( committedKey !== customKey ) {
			setRows( custom );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ customKey ] );

	const commit = ( nextRows ) => {
		setRows( nextRows );
		setAttributes( {
			htmlAttributes: [ ...contextual, ...nextRows.filter( isCompleteRow ) ],
		} );
	};

	const handleChange = ( index, updatedRow ) => {
		commit( rows.map( ( row, i ) => ( i === index ? updatedRow : row ) ) );
	};

	const handleRemove = ( index ) => {
		commit( rows.filter( ( _, i ) => i !== index ) );
	};

	// A new row is added to the editing list only; it is not persisted until it
	// has both a name and a value.
	const handleAdd = () => {
		setRows( [ ...rows, { attribute: '', value: '' } ] );
	};

	return (
		<>
			{ rows.length > 0 && (
				<div className="spectra-attr-headers" aria-hidden="true">
					<span>{ __( 'Attribute', 'spectra-blocks' ) }</span>
					<span>{ __( 'Value', 'spectra-blocks' ) }</span>
				</div>
			) }
			{ rows.map( ( row, index ) => (
				<AttributeRow
					key={ index }
					row={ row }
					index={ index }
					onChange={ handleChange }
					onRemove={ handleRemove }
				/>
			) ) }
			<Button
				variant="secondary"
				icon={ plus }
				onClick={ handleAdd }
				className="spectra-attr-add-btn"
			>
				{ __( 'Add Attribute', 'spectra-blocks' ) }
			</Button>
		</>
	);
};

/**
 * Layer 2 — Custom Attributes section rendered in the Advanced panel.
 *
 * Renders in the Advanced tab for all blocks including spectra/content and
 * spectra/container, keeping it separate from the tag selector controls.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {string}   props.tag            - Currently selected HTML tag.
 * @param {Array}    props.htmlAttributes - The full htmlAttributes array.
 * @param {Function} props.setAttributes  - Block setAttributes.
 * @return {Element} The panel element.
 */
const CustomAttributesSection = ( { tag, htmlAttributes, setAttributes } ) => {
	if ( ! canEdit ) {
		return null;
	}

	return (
		<InspectorControls group="advanced">
			<div className="spectra-html-attrs-panel">
				<p className="spectra-html-attrs-label">
					{ __( 'Custom Attributes', 'spectra-blocks' ) }
				</p>
				<CustomAttributesBody
					tag={ tag }
					htmlAttributes={ htmlAttributes }
					setAttributes={ setAttributes }
				/>
			</div>
		</InspectorControls>
	);
};

/**
 * Blocks that manage their own HTML tag selector. The HOC adds only
 * BlockContextualItems for these blocks; their own settings.js renders
 * CustomAttributesSection directly (in the Advanced panel).
 *
 * @since x.x.x
 */
const PER_BLOCK_TAG_PANEL = new Set( [ 'spectra/content', 'spectra/container' ] );

/**
 * Block-specific contextual attribute panel.
 *
 * Renders a ToolsPanel in the Settings tab for fields registered by individual
 * blocks via the `spectra.blockContextualFields` WordPress filter. Returns null
 * when no fields are registered for this block or editing is not permitted.
 *
 * @since x.x.x
 *
 * @param {Object}   props
 * @param {string}   props.blockName      - The block name (e.g. 'spectra/image').
 * @param {Array}    props.htmlAttributes - The full htmlAttributes array.
 * @param {string}   props.clientId       - Block clientId for ToolsPanel panelId.
 * @param {Function} props.setAttributes  - Block setAttributes.
 * @return {Element|null} The panel element, or null when empty / not permitted.
 */
const BlockContextualItems = ( { blockName, htmlAttributes, clientId, setAttributes } ) => {
	if ( ! canEdit ) {
		return null;
	}

	const fields = getBlockFields( blockName );

	if ( ! fields.length ) {
		return null;
	}

	const updateAttr = ( attrName, value ) => {
		setAttributes( {
			htmlAttributes: setAttrValue( htmlAttributes, attrName, value ),
		} );
	};

	const resetAll = () => {
		const names = new Set( fields.map( ( f ) => f.attribute ) );
		setAttributes( {
			htmlAttributes: htmlAttributes.filter( ( item ) => ! names.has( item.attribute ) ),
		} );
	};

	return (
		<InspectorControls>
			<ToolsPanel
				label={ __( 'Block Attributes', 'spectra-blocks' ) }
				resetAll={ resetAll }
				panelId={ clientId }
			>
				{ fields.map( ( field ) => {
					const currentValue = getAttrValue( htmlAttributes, field.attribute );
					const hasValue = currentValue !== '' && currentValue !== undefined;

					return (
						<ToolsPanelItem
							key={ field.attribute }
							label={ field.label }
							panelId={ clientId }
							isShownByDefault={ true }
							hasValue={ () => hasValue }
							onDeselect={ () => updateAttr( field.attribute, '' ) }
						>
							<ContextualFieldControl
								field={ field }
								currentValue={ currentValue }
								onChange={ ( value ) => updateAttr( field.attribute, value ) }
							/>
						</ToolsPanelItem>
					);
				} ) }
			</ToolsPanel>
		</InspectorControls>
	);
};

/**
 * Higher-order component that injects block-specific and custom attribute
 * panels into the block inspector for all allowed Spectra blocks.
 *
 * For blocks in PER_BLOCK_TAG_PANEL (content, container), their own settings.js
 * renders both layers — the HOC adds only BlockContextualItems for filter-based
 * block-specific fields. All other blocks get BlockContextualItems +
 * CustomAttributesSection via the HOC.
 *
 * @since x.x.x
 *
 * @param {Function} BlockEdit - The original block edit component.
 * @return {Function} Enhanced block edit component with HTML attribute panels.
 */
const addHtmlAttributesControls = ( BlockEdit ) => ( props ) => {
	if ( ! isAllowedBlock( { name: props.name } ) ) {
		return <BlockEdit { ...props } />;
	}

	const {
		isSelected,
		clientId,
		setAttributes,
		attributes,
	} = props;

	const htmlAttributes = normalizeAttrs( attributes.htmlAttributes );

	// PER_BLOCK blocks manage their own tag UI and Custom Attributes panel.
	// HOC only contributes block-specific fields registered via filter.
	if ( PER_BLOCK_TAG_PANEL.has( props.name ) ) {
		return (
			<>
				<BlockEdit { ...props } />
				{ isSelected && (
					<BlockContextualItems
						blockName={ props.name }
						htmlAttributes={ htmlAttributes }
						clientId={ clientId }
						setAttributes={ setAttributes }
					/>
				) }
			</>
		);
	}

	return (
		<>
			<BlockEdit { ...props } />
			{ isSelected && (
				<>
					<BlockContextualItems
						blockName={ props.name }
						htmlAttributes={ htmlAttributes }
						clientId={ clientId }
						setAttributes={ setAttributes }
					/>
					<CustomAttributesSection
						tag=""
						htmlAttributes={ htmlAttributes }
						setAttributes={ setAttributes }
					/>
				</>
			) }
		</>
	);
};

export { BlockContextualItems, ContextualTagItems, CustomAttributesSection, normalizeAttrs };
export default addHtmlAttributesControls;
