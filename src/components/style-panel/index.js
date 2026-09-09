/**
 * External dependencies.
 */
import { __experimentalToolsPanel as ToolsPanel } from '@wordpress/components';

/**
 * A style panel that can either own its heading or be hosted inside core's.
 *
 * Spectra's style panels normally fill core's unlabelled `styles` slot, where a
 * fill brings its own `ToolsPanel` and therefore its own heading and reset
 * menu. WordPress 7.1's responsive view drops that slot, so on Tablet and
 * Mobile these panels have to fill a LABELLED slot instead — and a labelled
 * slot already wraps its fills in a `ToolsPanel` of core's own
 * (`BlockSupportToolsPanel`). Rendering a second one inside it would nest two
 * panels, with two headings and two reset menus.
 *
 * Hosted, this therefore renders no panel at all: just a heading so the group
 * of controls keeps its identity, followed by the `ToolsPanelItem`s, which the
 * surrounding core panel picks up as its own. Reset is handled by the
 * `resetAllFilter` prop on the `InspectorControls` fill in that case, not by
 * the `resetAll` passed here.
 *
 * Modelled on core's `asWrapper` prop (`hooks/typography.js`), which exists for
 * the same reason: a panel that must render both inside and outside a slot.
 *
 * @since 1.0.7
 *
 * @param {Object}   props                   Component props.
 * @param {boolean}  props.isHosted          Whether a core ToolsPanel already surrounds this.
 * @param {string}   props.label             The panel heading.
 * @param {Function} props.resetAll          Reset handler, used only when not hosted.
 * @param {string}   props.panelId           The ToolsPanel id, used only when not hosted.
 * @param {boolean}  props.showHostedHeading Whether to keep the heading while hosted.
 *                                           Pass false when the host panel's own
 *                                           label already says the same thing:
 *                                           Background is hosted by core's
 *                                           "Background" panel, so repeating it
 *                                           would print the word twice.
 * @param {Element}  props.children          The ToolsPanelItems.
 * @return {Element} The rendered panel.
 */
const StylePanel = ( {
	isHosted,
	label,
	resetAll,
	panelId,
	showHostedHeading = true,
	children,
} ) => {
	if ( ! isHosted ) {
		return (
			<ToolsPanel label={ label } resetAll={ resetAll } panelId={ panelId }>
				{ children }
			</ToolsPanel>
		);
	}

	/*
	 * Hosted content is wrapped, and the wrapper is `display: contents`.
	 *
	 * A labelled inspector group is one `bubblesVirtually` slot, and every fill
	 * portals its content into that slot's SINGLE container node — so without a
	 * wrapper every panel's items are plain siblings of every other panel's.
	 * React tracks each portal's children separately, and a node appended to an
	 * EARLIER portal after mount lands at the END of that container, behind
	 * everything the later portals have already put there.
	 *
	 * Any control that appears in response to an edit is such a node. Measured
	 * on 7.1: choosing a background image with the panel open sent Background
	 * Size, Repeat, Attachment and Image Position below the Shape Dividers
	 * section and below Pro's Dynamic Image panel; reopening the panel on the
	 * same state was correctly ordered, because then every portal mounted in
	 * turn.
	 *
	 * One host element per fill fixes that: its position in the container is
	 * settled when it mounts, and anything appearing later is placed inside it,
	 * where React orders it correctly. `display: contents` keeps the wrapper
	 * from generating a box, so the heading and the items remain grid children
	 * of core's panel exactly as before.
	 *
	 * @since x.x.x
	 */
	return (
		<div className="spectra-hosted-style-panel">
			{ showHostedHeading && (
				<h3 className="spectra-hosted-style-panel__heading">
					{ label }
				</h3>
			) }
			{ children }
		</div>
	);
};

export default StylePanel;
