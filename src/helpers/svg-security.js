/**
 * SVG Security utilities for sanitizing and validating SVG content.
 * 
 * @since x.x.x
 */

/**
 * Basic SVG sanitization function to remove potentially dangerous elements and attributes.
 * This is a client-side safety measure - proper server-side sanitization should also be implemented.
 * 
 * @param {string} svgContent - Raw SVG content to sanitize
 * @return {string|null} Sanitized SVG content or null if invalid
 */
export const processSpectraSVG = async ( svgContent ) => {
	if ( ! svgContent || typeof svgContent !== 'string' ) {
		return null;
	}

	try {
		// Parse the SVG content
		const parser = new window.DOMParser();
		const svgDoc = parser.parseFromString( svgContent, 'image/svg+xml' );
		
		// Check for parsing errors
		const parserError = svgDoc.querySelector( 'parsererror' );
		if ( parserError ) {
			return null;
		}

		const svgElement = svgDoc.querySelector( 'svg' );
		if ( ! svgElement ) {
			return null;
		}

		// List of potentially dangerous elements to remove
		const dangerousElements = [
			'script',
			'object',
			'embed',
			'iframe',
			'link',
			'meta',
			'foreignObject',
			'animation',
			'animateMotion',
			'animateTransform',
			'set'
		];
		
		// Note: Removed 'style' from dangerous elements as it's needed for clipPath and other legitimate SVG styling
		// clipPath, defs, mask, pattern, marker, symbol, use are legitimate SVG elements and should not be removed

		// List of potentially dangerous attributes to remove
		const dangerousAttributes = [
			'onload',
			'onerror',
			'onmouseover',
			'onmouseout',
			'onclick',
			'onkeydown',
			'onkeyup',
			'onkeypress',
			'onfocus',
			'onblur',
			'onchange',
			'onsubmit',
			'onreset',
			'onselect',
			'onresize',
			'onscroll',
			'href',
			'xlink:href',
			'formaction',
			'form',
			'action'
		];

		// Remove dangerous elements
		dangerousElements.forEach( tagName => {
			const elements = svgElement.querySelectorAll( tagName );
			elements.forEach( element => {
				element.remove();
			} );
		} );
		
		// Special handling for <style> elements - remove only those with dangerous content
		const styleElements = svgElement.querySelectorAll( 'style' );
		styleElements.forEach( styleElement => {
			const styleContent = styleElement.textContent || '';
			// Remove if contains javascript or dangerous CSS
			if ( styleContent.includes( 'javascript:' ) || 
			     styleContent.includes( '@import' ) || 
			     styleContent.includes( 'expression(' ) ||
			     styleContent.includes( 'behavior:' ) ) {
				styleElement.remove();
			}
		} );

		// Remove dangerous attributes from all elements
		const allElements = svgElement.querySelectorAll( '*' );
		allElements.forEach( element => {
			dangerousAttributes.forEach( attrName => {
				if ( element.hasAttribute( attrName ) ) {
					element.removeAttribute( attrName );
				}
			} );

			// Remove any attribute that starts with 'on' (event handlers)
			Array.from( element.attributes ).forEach( attr => {
				if ( attr.name.toLowerCase().startsWith( 'on' ) ) {
					element.removeAttribute( attr.name );
				}
			} );
		} );

		// Also check the svg element itself
		dangerousAttributes.forEach( attrName => {
			if ( svgElement.hasAttribute( attrName ) ) {
				svgElement.removeAttribute( attrName );
			}
		} );

		// Remove event handler attributes from svg element
		Array.from( svgElement.attributes ).forEach( attr => {
			if ( attr.name.toLowerCase().startsWith( 'on' ) ) {
				svgElement.removeAttribute( attr.name );
			}
		} );

		// Serialize the cleaned SVG back to string
		const serializer = new window.XMLSerializer();
		const cleanedSVG = serializer.serializeToString( svgElement );

		// Basic validation - ensure it still contains SVG
		if ( ! cleanedSVG.includes( '<svg' ) || ! cleanedSVG.includes( '</svg>' ) ) {
			return null;
		}

		return cleanedSVG;

	} catch ( error ) {
		return null;
	}
};

/**
 * Validate SVG file size and basic structure.
 * 
 * @param {string} svgContent - SVG content to validate
 * @param {Object} options - Validation options
 * @return {boolean} Whether the SVG passes validation
 */
export const validateSVGStructure = ( svgContent, options = {} ) => {
	const {
		maxSize = 1024 * 1024, // 1MB default
		allowAnimations = false
	} = options;

	if ( ! svgContent || typeof svgContent !== 'string' ) {
		return false;
	}

	// Check file size
	if ( svgContent.length > maxSize ) {
		return false;
	}

	// Check for basic SVG structure
	if ( ! svgContent.includes( '<svg' ) || ! svgContent.includes( '</svg>' ) ) {
		return false;
	}

	// Check for animations if not allowed
	if ( ! allowAnimations ) {
		const animationElements = [
			'animate',
			'animateColor',
			'animateMotion',
			'animateTransform',
			'set'
		];

		const hasAnimations = animationElements.some( element => 
			svgContent.includes( `<${element}` )
		);

		if ( hasAnimations ) {
			return false;
		}
	}

	return true;
};

/**
 * Check if WordPress allows SVG uploads by checking mime types.
 * This is a client-side check - server-side validation is still required.
 * 
 * @return {boolean} Whether SVG uploads appear to be enabled
 */
export const isSVGUploadEnabled = () => {
	// This is a basic check - in a real implementation you'd want to
	// make an AJAX call to check server-side capabilities
	if ( window?.wp?.media?.view?.settings?.allowedTypes ) {
		return window.wp.media.view.settings.allowedTypes.includes( 'image/svg+xml' );
	}
	
	// Default to true and let server-side handle the actual validation
	return true;
};

export default {
	processSpectraSVG,
	validateSVGStructure,
	isSVGUploadEnabled
};