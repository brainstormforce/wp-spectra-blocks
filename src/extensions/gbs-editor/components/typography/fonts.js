/**
 * Font catalog — mirrors GlobalStylesBridge::FONT_MAP.
 *
 * Only fonts with bundled woff2 files can be persisted via
 * sync_user_layer_typography(). Anything added here must have a
 * corresponding entry in the PHP FONT_MAP constant.
 *
 * @since x.x.x
 */

export const FONTS = [
	// ── Sans-serif ────────────────────────────────────────────────────────────
	{ slug: 'inter',             name: 'Inter',             category: 'sans' },
	{ slug: 'space-grotesk',     name: 'Space Grotesk',     category: 'sans' },
	{ slug: 'dm-sans',           name: 'DM Sans',           category: 'sans' },
	{ slug: 'manrope',           name: 'Manrope',           category: 'sans' },
	{ slug: 'plus-jakarta-sans', name: 'Plus Jakarta Sans', category: 'sans' },
	{ slug: 'outfit',            name: 'Outfit',            category: 'sans' },
	{ slug: 'sora',              name: 'Sora',              category: 'sans' },
	{ slug: 'rubik',             name: 'Rubik',             category: 'sans' },

	// ── Serif ─────────────────────────────────────────────────────────────────
	{ slug: 'playfair-display',   name: 'Playfair Display',   category: 'serif' },
	{ slug: 'cormorant-garamond', name: 'Cormorant Garamond', category: 'serif' },
	{ slug: 'lora',               name: 'Lora',               category: 'serif' },
	{ slug: 'dm-serif-display',   name: 'DM Serif Display',   category: 'serif' },
	{ slug: 'fraunces',           name: 'Fraunces',           category: 'serif' },
];

/** Lookup by slug. */
export const FONT_BY_SLUG = Object.fromEntries( FONTS.map( ( f ) => [ f.slug, f ] ) );

/**
 * CSS font-family stack for a given slug.
 * Falls back to the appropriate generic so cards render even if the
 * woff2 hasn't been activated in Global Styles yet.
 *
 * @since x.x.x
 *
 * @param {string} slug Font slug.
 * @return {string} CSS font-family value.
 */
export function fontStack( slug ) {
	const font = FONT_BY_SLUG[ slug ];
	if ( ! font ) {return 'sans-serif';}
	const generic = font.category === 'serif' ? 'Georgia, serif' : 'system-ui, sans-serif';
	return `'${ font.name }', ${ generic }`;
}
