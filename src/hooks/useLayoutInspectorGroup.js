/**
 * Internal dependencies.
 */
import { coreViewportStatesAreIndependent } from '../extensions/responsive-controls/utils/constants';

/**
 * Where a block's per-device layout controls should render.
 *
 * #789 moved these controls (Post columns / slides per view / space between,
 * Slider slides per view / space between, Separator style / alignment,
 * Container orientation reverse) into core's `layout` inspector group so they
 * survive WordPress 7.1's responsive style-state view, which drops
 * third-party panels on Tablet and Mobile. That group does not exist before
 * 7.1 — 7.0's inspector groups are default, advanced, background, bindings,
 * border, color, content, dimensions, effects, filter, list, position,
 * settings, styles and typography — so a fill into `layout` rendered nowhere
 * and every one of those controls vanished for pre-7.1 sites. Measured on
 * 7.0.4: none of the four blocks offered them in either tab.
 *
 * Gate on the same capability the rest of the responsive system uses, never
 * on the version number: where core renders viewport states it also has the
 * `layout` group, and the controls are hosted inside core's own Layout
 * ToolsPanel; everywhere else they get a Spectra ToolsPanel of their own in
 * the Settings tab, whose label is in the responsive panel list so it carries
 * the device switcher exactly as before #789.
 *
 * @since 1.0.7
 * @return {{group: string, isHosted: boolean}} The group to fill, and whether
 *                                              core's panel hosts the items
 *                                              (so the block must not render a
 *                                              ToolsPanel of its own).
 */
const useLayoutInspectorGroup = () => {
	const hosted = coreViewportStatesAreIndependent();

	return {
		group: hosted ? 'layout' : 'settings',
		isHosted: hosted,
	};
};

export default useLayoutInspectorGroup;
