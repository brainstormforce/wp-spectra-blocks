/**
 * Shared validation for authored names and CSS in the Global Styles editor.
 *
 * Deliberately narrow. CSS values have no single grammar to check against —
 * what is valid depends entirely on the property and the use case — so whether
 * a value is CORRECT CSS is the author's responsibility. Anyone reaching for a
 * raw-CSS editor is expected to know CSS, and guessing on their behalf means
 * false alarms on vendor prefixes, new features, and anything this browser has
 * not shipped yet.
 *
 * What is checked is only what the editor itself would otherwise swallow:
 *
 *   - a name that cannot be a CSS identifier at all
 *   - an empty name or value
 *   - input our own parser silently discards — a line with no
 *     `property: value` shape, or a property declared twice, which
 *     `textToBucket` overwrites so the first one disappears on save
 *
 * Everything reported is therefore an error: it is all input that would not
 * survive being saved. The `level` field stays so callers keep using
 * `hasBlockingIssue` unchanged, and so a future advisory tier has somewhere to
 * live.
 *
 * @since 1.0.10
 */

/**
 * WordPress dependencies.
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Per-kind identifier rules.
 *
 * Prefixes (`--`, `gs-`) are added by the panels, so the patterns below describe
 * the part the author actually types.
 *
 * @since 1.0.10
 */
const NAME_KINDS = {
	cssVar: {
		pattern: /^[a-zA-Z_][a-zA-Z0-9_-]*$/,
		charset: __(
			'Start with a letter, then use letters, digits, hyphens or underscores.',
			'spectra-blocks'
		),
	},
	class: {
		pattern: /^[a-z][a-z0-9-]*$/,
		charset: __(
			'Start with a lowercase letter, then use lowercase letters, digits or hyphens.',
			'spectra-blocks'
		),
	},
	keyframe: {
		pattern: /^[a-zA-Z][a-zA-Z0-9_-]*$/,
		charset: __(
			'Start with a letter, then use letters, digits, hyphens or underscores.',
			'spectra-blocks'
		),
	},
};

/**
 * Validate an authored identifier.
 *
 * Reports the *specific* reason rather than one catch-all message: a leading
 * digit and an embedded space are different mistakes and deserve different
 * corrections.
 *
 * @since 1.0.10
 *
 * @param {string}   raw           The name as typed (without its prefix).
 * @param {Object}   options       Options.
 * @param {string}   options.kind  One of 'cssVar', 'class', 'keyframe'.
 * @param {string[]} options.taken Names already in use, in the same un-prefixed form.
 * @return {string} Error message, or '' when the name is usable.
 */
export function validateName( raw, { kind, taken = [] } ) {
	const rules = NAME_KINDS[ kind ] ?? NAME_KINDS.cssVar;
	const name = String( raw ?? '' ).trim();

	if ( ! name ) {
		return __( 'Enter a name.', 'spectra-blocks' );
	}

	if ( /\s/.test( name ) ) {
		return __( 'Spaces are not allowed — use a hyphen instead.', 'spectra-blocks' );
	}

	if ( /^[0-9]/.test( name ) ) {
		return __( 'Cannot start with a digit.', 'spectra-blocks' );
	}

	if ( /^-/.test( name ) ) {
		return __( 'Cannot start with a hyphen.', 'spectra-blocks' );
	}

	if ( ! rules.pattern.test( name ) ) {
		return rules.charset;
	}

	if ( taken.includes( name ) ) {
		return __( 'That name is already in use.', 'spectra-blocks' );
	}

	return '';
}

/**
 * Validate the value side of a custom property (`--name: value`).
 *
 * The hard rules mirror what the CSSOM itself rejects (verified against Chrome:
 * an empty value, a stray `;`, or a `{`/`}` all make `setProperty` a no-op, so
 * the declaration would vanish on write). Unbalanced brackets and quotes are
 * NOT errors — CSS auto-closes them, so the value survives, just not as typed;
 * that earns a warning.
 *
 * @since 1.0.10
 *
 * @param {string} raw The value as typed.
 * @return {{level: string, message: string}} Severity and message; level '' means fine.
 */
export function validateCustomPropertyValue( raw ) {
	const value = String( raw ?? '' ).trim();

	if ( ! value ) {
		return { level: 'error', message: __( 'Enter a value.', 'spectra-blocks' ) };
	}

	if ( value.includes( ';' ) ) {
		return {
			level: 'error',
			message: __( 'Remove the semicolon — it ends the declaration early.', 'spectra-blocks' ),
		};
	}

	if ( /[{}]/.test( value ) ) {
		return {
			level: 'error',
			message: __( 'Braces are not allowed in a value.', 'spectra-blocks' ),
		};
	}

	return { level: '', message: '' };
}

/**
 * Validate one `property: value` line from a class body.
 *
 * A missing colon is an ERROR rather than a warning because `textToBucket`
 * drops such lines outright — the author would otherwise save, see nothing
 * happen, and have no way to find out why.
 *
 * @since 1.0.10
 *
 * @param {string} line Raw line.
 * @return {{level: string, message: string}} Severity and message; level '' means fine.
 */
export function validateDeclarationLine( line ) {
	const text = String( line ?? '' ).trim().replace( /;$/, '' );

	if ( ! text || text.startsWith( '/*' ) ) {
		return { level: '', message: '' };
	}

	const idx = text.indexOf( ':' );

	if ( idx < 0 ) {
		return {
			level: 'error',
			message: __( 'Expected “property: value”.', 'spectra-blocks' ),
		};
	}

	const property = text.slice( 0, idx ).trim();

	if ( ! property ) {
		return { level: 'error', message: __( 'Missing property name.', 'spectra-blocks' ) };
	}

	const value = text.slice( idx + 1 ).trim();

	if ( ! value ) {
		return {
			level: 'error',
			message: sprintf(
				/* translators: %s: CSS property name. */
				__( 'Missing value for “%s”.', 'spectra-blocks' ),
				property
			),
		};
	}

	if ( /[{}]/.test( text ) ) {
		return {
			level: 'error',
			message: __( 'Braces are not allowed — write declarations only.', 'spectra-blocks' ),
		};
	}

	return { level: '', message: '' };
}

/**
 * Validate a whole block of declarations, reporting per line.
 *
 * Also catches a property declared twice, which no single line can see.
 * `textToBucket` stores declarations in an object keyed by property, so a
 * repeat silently overwrites the earlier one — the author writes two lines,
 * saves, and the first is gone with nothing said. CSS itself allows the repeat
 * (it is how fallbacks are written), but this storage cannot hold it, so the
 * honest answer is to refuse rather than to drop half the input.
 *
 * @since 1.0.10
 *
 * @param {string} text Multi-line declaration text.
 * @return {Array<{line: number, level: string, message: string}>} Issues, 1-indexed by line.
 */
export function validateDeclarations( text ) {
	const issues = [];

	/** First line each property was seen on, for the duplicate message. */
	const seen = new Map();

	String( text ?? '' )
		.split( '\n' )
		.forEach( ( line, i ) => {
			const lineNo = i + 1;
			const { level, message } = validateDeclarationLine( line );

			if ( level ) {
				issues.push( { line: lineNo, level, message } );
			}

			// An unusable line cannot own a property name; a merely warned one can,
			// so duplicate tracking continues past a warning.
			if ( 'error' === level ) {
				return;
			}

			const declaration = String( line ).trim().replace( /;$/, '' );
			const idx = declaration.indexOf( ':' );
			if ( idx < 1 ) {
				return;
			}

			// CSS property names are case-insensitive, so `Color` and `color` are
			// the same key once stored.
			const property = declaration.slice( 0, idx ).trim().toLowerCase();
			if ( ! property ) {
				return;
			}

			if ( seen.has( property ) ) {
				issues.push( {
					line: lineNo,
					level: 'error',
					message: sprintf(
						/* translators: 1: CSS property name. 2: the earlier line number. */
						__( '“%1$s” is already set on line %2$d — only one value per property is kept.', 'spectra-blocks' ),
						property,
						seen.get( property )
					),
				} );
				return;
			}

			seen.set( property, lineNo );
		} );

	return issues;
}

/**
 * True when any issue in the list blocks saving.
 *
 * @since 1.0.10
 *
 * @param {Array<{level: string}>} issues Issue list.
 * @return {boolean} True when at least one is an error.
 */
export function hasBlockingIssue( issues ) {
	return ( issues ?? [] ).some( ( issue ) => 'error' === issue?.level );
}

/**
 * Parse a bulk `--name: value;` block, reporting every line it cannot use.
 *
 * The previous parser returned only the lines it liked, so a typo meant the
 * variable quietly disappeared on Apply. This returns the issues alongside the
 * parsed map so the caller can refuse to apply a block that would lose work.
 *
 * @since 1.0.10
 *
 * @param {string} text Raw textarea content.
 * @return {{variables: Object, issues: Array<{line: number, level: string, message: string}>}} Parsed result.
 */
export function parseVarDeclarations( text ) {
	const variables = {};
	const issues = [];
	const seen = new Set();

	String( text ?? '' )
		.split( '\n' )
		.forEach( ( raw, i ) => {
			const line = raw.trim().replace( /;$/, '' );
			const lineNo = i + 1;

			if ( ! line || line.startsWith( '/*' ) ) {
				return;
			}

			const idx = line.indexOf( ':' );
			if ( idx < 0 ) {
				issues.push( {
					line: lineNo,
					level: 'error',
					message: __( 'Expected “--name: value”.', 'spectra-blocks' ),
				} );
				return;
			}

			const name = line.slice( 0, idx ).trim();

			if ( ! name.startsWith( '--' ) ) {
				issues.push( {
					line: lineNo,
					level: 'error',
					message: __( 'Variable names must start with “--”.', 'spectra-blocks' ),
				} );
				return;
			}

			const nameError = validateName( name.replace( /^--/, '' ), {
				kind: 'cssVar',
				taken: [],
			} );
			if ( nameError ) {
				issues.push( { line: lineNo, level: 'error', message: nameError } );
				return;
			}

			if ( seen.has( name ) ) {
				issues.push( {
					line: lineNo,
					level: 'error',
					message: sprintf(
						/* translators: %s: CSS variable name. */
						__( '“%s” is declared more than once.', 'spectra-blocks' ),
						name
					),
				} );
				return;
			}

			const value = line.slice( idx + 1 ).trim();
			const valueIssue = validateCustomPropertyValue( value );
			if ( 'error' === valueIssue.level ) {
				issues.push( { line: lineNo, level: 'error', message: valueIssue.message } );
				return;
			}

			seen.add( name );
			variables[ name ] = value;
		} );

	return { variables, issues };
}
