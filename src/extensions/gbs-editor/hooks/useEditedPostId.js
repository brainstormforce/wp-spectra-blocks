/**
 * useEditedPostId — resolve the edited entity to a numeric post ID, FSE-safe.
 *
 * In the Site Editor `core/editor.getCurrentPostId()` returns the edited
 * TEMPLATE ID as a string (e.g. "astra//home"), not a numeric post ID.
 * Feeding that string into the page-scoped GBS REST routes fails schema
 * validation (`post_id` is `type: integer` → 400 rest_invalid_param), which
 * silently breaks every page-scoped consumer in the FSE context.
 *
 * This hook only treats the edited entity as a page/post when
 * `core/edit-site` says it actually is one, and coerces with
 * `Number( ... ) || 0` so template-string IDs fall back to `0` — i.e. the
 * global scope — matching the page editor's behaviour for global data.
 *
 * @since x.x.x
 */

import { useSelect } from '@wordpress/data';

/**
 * @since x.x.x
 *
 * @return {number} The edited page/post ID, or 0 when the edited entity is
 *                  not a page/post (templates, template parts, patterns).
 */
export function useEditedPostId() {
	return useSelect( ( select ) => {
		const site = select( 'core/edit-site' );
		const type = site?.getEditedPostType?.();
		if ( type ) {
			// Site Editor: only real pages/posts have numeric IDs usable as
			// a post_id — templates/parts/patterns resolve to global scope.
			if ( 'page' === type || 'post' === type ) {
				return Number( site.getEditedPostId?.() ) || 0;
			}
			return 0;
		}
		return Number( select( 'core/editor' )?.getCurrentPostId?.() ) || 0;
	}, [] );
}
