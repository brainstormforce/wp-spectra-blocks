/**
 * External dependencies.
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { processSpectraSVG } from '@spectra-helpers';

/**
 * Render an SVG element.
 *
 * @param {Object} props The block object.
 * @since x.x.x
 * @return {Element|null} The rendered SVG, or null.
 */
const RenderSVG = ( props ) => {
    // If the required localization asset isn't available, abandon ship.
    if ( ! window?.uagb_blocks_info?.uagb_svg_icons ) { 
        return null;
    }

    return <RenderSVGWithHooks {...props} />;
};

// Global SVG cache to prevent re-fetching
const svgCache = new Map();

/**
 * Internal component that uses hooks for SVG rendering.
 *
 * @param {Object} props The block object.
 * @return {Element|null} The rendered SVG, or null.
 */
const RenderSVGWithHooks = ( props ) => {
    const {
        svg,
        needsRTL, // eslint-disable-line no-unused-vars
        extraProps = {},
        className = '',
    } = props;

    // Early return if svg prop is invalid
    if ( !svg ) {
        return null;
    }

    // State for fetched SVG content and loading state
    // eslint-disable-next-line react-hooks/rules-of-hooks -- Early return above only occurs when svg is falsy, which doesn't change during component lifecycle
    const [svgContent, setSvgContent] = useState( null );
    // eslint-disable-next-line react-hooks/rules-of-hooks -- Early return above only occurs when svg is falsy, which doesn't change during component lifecycle
    const [isLoading, setIsLoading] = useState( false );
    // eslint-disable-next-line react-hooks/rules-of-hooks -- Early return above only occurs when svg is falsy, which doesn't change during component lifecycle
    const [hasError, setHasError] = useState( false );
    
    // Check if this is uploaded SVG format
    const isUploadedFormat = svg && typeof svg === 'object' && svg.library === 'svg' && svg.value?.id;
    
    // Generate cache key for this SVG
    const cacheKey = isUploadedFormat && svg?.value?.id ? `svg_${svg.value.id}` : null;
    
    // Effect to fetch SVG content for uploaded SVGs
    // eslint-disable-next-line react-hooks/rules-of-hooks -- Early return above only occurs when svg is falsy, which doesn't change during component lifecycle
    useEffect( () => {
        // Only run for uploaded SVGs, not for default Font Awesome icons
        if ( !isUploadedFormat || !cacheKey || !svg?.value || typeof svg !== 'object' ) {
            return;
        }
        
        const svgUrl = svg.value.url || svg.value.source_url;
        if ( !svgUrl ) {
            return;
        }
        
        // Check cache first
        if ( svgCache.has( cacheKey ) ) {
            const cachedContent = svgCache.get( cacheKey );
            setSvgContent( cachedContent );
            return;
        }
        
        // Fetch SVG content
        const fetchSvgContent = async () => {
            setIsLoading( true );
            setHasError( false );
            
            try {
                const response = await fetch( svgUrl );
                if ( !response.ok ) {
                    throw new Error( `HTTP error! status: ${response.status}` );
                }
                
                const content = await response.text();
                
                // Cache the content
                svgCache.set( cacheKey, content );
                setSvgContent( content );
            } catch ( error ) {
                // Silent fail - SVG fetch failed
                setHasError( true );
            } finally {
                setIsLoading( false );
            }
        };
        
        fetchSvgContent();
    }, [isUploadedFormat, cacheKey, svg?.value?.url, svg?.value?.source_url] );
    

    // Handle uploaded format SVG rendering
    if ( isUploadedFormat ) {
        // If we have processed SVG content, render it
        if ( svgContent ) {
            const processedProps = { ...props };
            delete processedProps.svg; // Remove the original svg prop
            return <RenderRawSVG svgContent={svgContent} {...processedProps} />;
        }
        
        // Always show immediate fallback while loading (even on first upload)
        // This prevents the "blank icon" issue on first upload
        if ( svg?.value?.url || svg?.value?.source_url ) {
            const svgUrl = svg.value.url || svg.value.source_url;
            const loadingClass = isLoading ? ' spectra-svg-loading' : '';
            const errorClass = hasError ? ' spectra-svg-error' : '';
            
            const imgWidth = extraProps?.width || '24';
            const imgHeight = extraProps?.height || '24';
            
            return <img 
                src={svgUrl} 
                alt="SVG icon" 
                width={imgWidth} 
                height={imgHeight}
                className={`${className} spectra-custom-svg${loadingClass}${errorClass}`}
                style={{
                    opacity: isLoading ? 0.5 : 1,
                    transition: 'opacity 0.2s ease',
                    // Ensure explicit dimensions to override max-width: 100%
                    width: imgWidth,
                    height: imgHeight,
                    minWidth: imgWidth,
                    minHeight: imgHeight,
                    flexShrink: 0, // Prevent shrinking in flex containers
                    ...( extraProps?.style || {} )
                }}
                onLoad={ () => {
                    // If img loads successfully but we don't have SVG content yet,
                    // this is a good fallback (especially for first uploads)
                } }
                onError={ () => {
                    // Handle case where even the img fallback fails
                    setHasError( true );
                } }
            />;
        }
        
        
        // Error state or no URL - return nothing
        return null;
    }

    // Check if this is raw SVG content (contains <svg tag) - uploaded SVG that needs sanitization
    if ( svg && typeof svg === 'string' && svg.includes( '<svg' ) ) {
        return <RenderRawSVG svgContent={svg} {...props} />;
    }

    // Legacy path - keep for backwards compatibility
    return <RenderIconSVG svg={svg} {...props} />;
};

/**
 * Component to render raw SVG content with security sanitization.
 *
 * @param {Object} root0 - The component props.
 * @param {string} root0.svgContent - The SVG content to render.
 * @param {boolean} root0.needsRTL - Whether RTL transformation is needed.
 * @param {Object} root0.extraProps - Additional props to apply.
 * @param {string} root0.className - CSS class name.
 */
const RenderRawSVG = ( { svgContent, needsRTL, extraProps = {}, className = '' } ) => {
    const [sanitizedContent, setSanitizedContent] = useState( null );

    useEffect( () => {
        const sanitizeSVG = async () => {
            if ( !svgContent ) {
                setSanitizedContent( null );
                return;
            }

            try {
                // Ensure processSpectraSVG is available
                if ( typeof processSpectraSVG !== 'function' ) {
                    setSanitizedContent( svgContent );
                    return;
                }

                const sanitized = await processSpectraSVG( svgContent );
                setSanitizedContent( sanitized );
            } catch ( error ) {
                // Fallback to original content if sanitization fails
                setSanitizedContent( svgContent );
            }
        };

        sanitizeSVG();
    }, [svgContent] );

    // Return null while content is being processed
    if ( !sanitizedContent ) {
        return null;
    }


    // Parse the sanitized SVG to extract attributes and content
    const parser = new window.DOMParser();
    let svgDoc = parser.parseFromString( sanitizedContent, 'image/svg+xml' );
    let svgElement = svgDoc.querySelector( 'svg' );
    
    // If initial parsing fails, try cleaning the SVG content first
    if ( !svgElement ) {
        // Remove XML declaration and DOCTYPE if present
        const cleanedContent = sanitizedContent
            .replace( /<\?xml[^>]*\?>/g, '' )
            .replace( /<!DOCTYPE[^>]*>/g, '' )
            .trim();
        
        // Try parsing again with cleaned content
        svgDoc = parser.parseFromString( cleanedContent, 'image/svg+xml' );
        svgElement = svgDoc.querySelector( 'svg' );
        
        // If still failing, try parsing as HTML instead of XML
        if ( !svgElement ) {
            svgDoc = parser.parseFromString( cleanedContent, 'text/html' );
            svgElement = svgDoc.querySelector( 'svg' );
        }
    }
    
    if ( !svgElement ) {
        // Last resort: create img tag with data URL for editor preview
        const imgWidth = extraProps?.width || '24';
        const imgHeight = extraProps?.height || '24';
        
        return (
            <img 
                src={`data:image/svg+xml;charset=utf-8,${encodeURIComponent( sanitizedContent )}`}
                alt="SVG icon"
                width={imgWidth} 
                height={imgHeight}
                className={`${className} spectra-custom-svg`}
                style={{
                    // Ensure explicit dimensions to override max-width: 100%
                    width: imgWidth,
                    height: imgHeight,
                    minWidth: imgWidth,
                    minHeight: imgHeight,
                    flexShrink: 0, // Prevent shrinking in flex containers
                    ...( extraProps?.style || {} )
                }}
            />
        );
    }

    // Extract all original SVG attributes
    const originalAttributes = {};
    Array.from( svgElement.attributes ).forEach( attr => {
        originalAttributes[attr.name] = attr.value;
    } );

    // Handle RTL transformation and merge with extraProps style
    const extraStyle = extraProps.style || {};
    const finalStyle = { ...extraStyle };
    if ( window?.uagb_blocks_info?.is_rtl && needsRTL ) {
        const rtlTransform = 'scaleX(-1)';
        finalStyle.transform = finalStyle.transform ? `${rtlTransform} ${finalStyle.transform}` : rtlTransform;
    }

    // Get inner content (everything inside <svg> tags)
    const innerHTML = svgElement.innerHTML;

    // Extract style and className from extraProps to handle them separately
    const { className: extraPropsClassName, ...restExtraProps } = extraProps;

    // Merge classNames properly - combine original className, main className, and extraProps className
    const originalClassName = originalAttributes.class || '';
    const combinedClassName = [originalClassName, className, extraPropsClassName, 'spectra-custom-svg'].filter( Boolean ).join( ' ' );

    // Build final SVG props, spreading original attributes first, then applying our overrides
    const svgProps = {
        // Start with all original SVG attributes
        ...originalAttributes,
        // Ensure xmlns is set for proper SVG rendering
        xmlns: 'http://www.w3.org/2000/svg',
        // No default dimensions - let block attributes control sizing
        // Don't override fill for custom SVGs - preserve original colors
        fill: originalAttributes.fill || 'currentColor',
        // Apply extraProps (width, height, style, etc.) - these can override defaults
        ...restExtraProps,
        // Apply final merged style last to ensure proper override
        style: finalStyle,
        dangerouslySetInnerHTML: { __html: innerHTML }
    };

    // Only add className if we have one
    if ( combinedClassName ) {
        svgProps.className = combinedClassName;
    }

    return <svg {...svgProps} />;
};

/**
 * Component to render Font Awesome icons from the icon library.
 *
 * @param {Object} root0 - The component props.
 * @param {string} root0.svg - The SVG icon name or content.
 * @param {boolean} root0.needsRTL - Whether RTL transformation is needed.
 * @param {Object} root0.extraProps - Additional props to apply.
 * @param {string} root0.className - CSS class name.
 */
const RenderIconSVG = ( { svg, needsRTL, extraProps = {}, className = '' } ) => {
    // Legacy check - if this is raw SVG that shouldn't be here, handle gracefully
    if ( svg && typeof svg === 'string' && svg.includes( '<svg' ) ) {
        return <RenderRawSVG svgContent={svg} needsRTL={needsRTL} extraProps={extraProps} className={className} />;
    }

    // For icon names (not uploaded SVGs), proceed with original logic
    let fontAwesomeElement;
    // Load Polyfiller Array if needed.
    if ( 0 !== window.uagb_blocks_info?.font_awesome_5_polyfill?.length ) {
        fontAwesomeElement = window.uagb_blocks_info.uagb_svg_icons[ svg ];
        if ( ! fontAwesomeElement ) {
            fontAwesomeElement = window.uagb_blocks_info.uagb_svg_icons[ window.uagb_blocks_info.font_awesome_5_polyfill?.[ svg ] ];
        }
    }

    // If this SVG is not set, abandon ship.
    if ( ! fontAwesomeElement ) {
        return null;
    }

    // Get the required variant of the SVG.
    const fontAwesomeSvg = fontAwesomeElement.svg?.brands ? fontAwesomeElement.svg.brands : fontAwesomeElement.svg.solid;
    
    // Create a copy of extraProps to avoid mutating the original
    const processedExtraProps = { ...extraProps };
    
    // If RTL inversion is required, mirror the SVG.
    if ( window?.uagb_blocks_info?.is_rtl && needsRTL ) {
        const rtlTransform = 'scaleX(-1)';

        // Ensure style object exists
        if ( ! processedExtraProps.style ) {
            processedExtraProps.style = {};
        }

        // Append or add the transformation for RTL.
        if ( ! processedExtraProps.style.transform?.includes( rtlTransform ) ) {
            if ( processedExtraProps.style.transform ) {
                // If there is a transform style already, append the RTL transform to it.
                processedExtraProps.style.transform = rtlTransform + ' ' + processedExtraProps.style.transform;
            } else {
                // Else just add the RTL style transform.
                processedExtraProps.style.transform = rtlTransform;
            }
        }
    }

    // Return the rendered SVG.
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox={ `0 0 ${ fontAwesomeSvg.width } ${ fontAwesomeSvg.height }` }
			fill='currentColor'
            className={ className } // Only applies the passed className.
            { ...processedExtraProps } // Block attributes control all dimensions
        >
            <path d={ fontAwesomeSvg.path } />
        </svg>
    );
};

export default RenderSVG;