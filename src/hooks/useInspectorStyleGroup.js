/**
 * Internal dependencies.
 */
import { coreViewportStatesAreIndependent } from '../extensions/responsive-controls/utils/constants';

/**
 * The inspector group Spectra's own style panels fill.
 *
 * WordPress 7.1's responsive style-state view renders a FIXED list of block-
 * support group slots — typography, color, background, layout, dimensions,
 * border, elements — and drops the unlabelled `styles` slot. Panels such as
 * Background, Overlay Settings and Shape Dividers would therefore vanish on
 * Tablet/Mobile if they filled `styles`. `background` is the closest surviving
 * slot, and the blocks that host here declare no core `background` support, so
 * the panel rendered there is Spectra's alone — nothing of core's is displaced.
 *
 * @since 1.0.7
 * @type {string}
 */
export const RESPONSIVE_HOST_GROUP = 'background';

/**
 * The inspector group these panels fill where core renders no viewport states.
 *
 * The unlabelled `styles` slot: a fill there brings its own `ToolsPanel`, and
 * so its own heading and reset menu. It is dropped from 7.1's responsive view,
 * which is the whole reason `RESPONSIVE_HOST_GROUP` exists — but below that
 * capability there is no responsive view, and this is where every one of these
 * panels rendered before.
 *
 * @since x.x.x
 * @type {string}
 */
export const LEGACY_STYLE_GROUP = 'styles';

/**
 * Which inspector group Spectra's style panels should fill, and whether they
 * are hosted inside a core ToolsPanel (so they must not render their own).
 *
 * Where core renders viewport states, these panels fill the `background` group
 * on EVERY device — not only the narrow ones — so their position stays
 * consistent between Desktop and the responsive style-state view. Filling the
 * unlabelled `styles` slot on Desktop and only falling back to `background` on
 * Tablet/Mobile made the group jump position between the two views.
 *
 * Below that capability there is no style-state view to stay consistent with,
 * and hosting cost pre-7.1 sites two regressions measured on 7.0.4:
 *
 * - Core's grid there contributes its own `row-gap` on top of the hosted
 *   panels' `margin-top`, so every row in the group sat 32px apart instead of
 *   16px — the whole group half again as tall (490px against 430px on 7.1).
 * - Core labels the host panel "Background image" on 7.0, not "Background", so
 *   `showHostedHeading={ false }` — correct on 7.1, where the host says the
 *   same word — left the Background group with no heading at all while Overlay
 *   Settings and Shape Dividers below it kept theirs.
 *
 * Gate on the capability rather than the version, exactly as
 * `useLayoutInspectorGroup` does: below it these panels bring their own
 * `ToolsPanel` in the `styles` slot, which is where they have always been and
 * what `RESPONSIVE_CONTROLS_PANELS` already recognises for the device switcher
 * and reset handling.
 *
 * @since 1.0.7
 * @return {{group: string, isHosted: boolean}} The group to fill, and whether
 *                                              the panel is hosted inside a core
 *                                              ToolsPanel (so it renders no
 *                                              ToolsPanel of its own).
 */
const useInspectorStyleGroup = () => {
	const hosted = coreViewportStatesAreIndependent();

	return {
		group: hosted ? RESPONSIVE_HOST_GROUP : LEGACY_STYLE_GROUP,
		isHosted: hosted,
	};
};

export default useInspectorStyleGroup;
