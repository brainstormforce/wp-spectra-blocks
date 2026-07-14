/**
 * Post Template Block - Render Component
 *
 * Handles the editor preview rendering for the Post Template block, including
 * grid, masonry, and carousel layouts with Swiper integration.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import {
	BlockContextProvider,
	__experimentalUseBlockPreview as useBlockPreview,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useRefEffect } from '@wordpress/compose';
import { memo, useMemo, useState, useRef, useCallback, useEffect } from '@wordpress/element';

/**
 * External dependencies
 */
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import Swiper from 'swiper';

/**
 * Internal dependencies
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import RenderSVG from '@spectra-helpers/render-svg';

/**
 * Renders the inner blocks for a single post in the template.
 *
 * @param {Object}       props           Component props.
 * @param {Array|string} props.classList The array of CSS classes for the post or a string.
 * @param {string}       props.itemTag   The HTML tag to use (li or div).
 * @return {Element} The rendered element containing inner blocks.
 */
const PostTemplateInnerBlocks = memo( ( { classList, itemTag = 'li' } ) => {
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: Array.isArray( classList ) ? classList.join( ' ' ) : classList || '',
		},
		{
			templateLock: false,
			__unstableDisableLayoutClassNames: true,
		}
	);
	const TagName = itemTag;
	return <TagName { ...innerBlocksProps } />;
} );

/**
 * Recursively generate unique clientIds for preview blocks based on context.
 * Defined outside component to avoid recreation on each render.
 *
 * @param {Object} block          The block to process.
 * @param {number} blockContextId Context ID for unique key generation.
 * @return {Object} Block with unique clientId.
 */
const getUniqueBlocks = ( block, blockContextId ) => {
	const { clientId, innerBlocks, ...rest } = block;
	return {
		...rest,
		clientId: clientId ? `${ clientId }-${ blockContextId }` : undefined,
		innerBlocks: innerBlocks ? innerBlocks.map( ( inner ) => getUniqueBlocks( inner, blockContextId ) ) : [],
	};
};

/**
 * Renders a block preview for a post.
 *
 * @param {Object}       props                         Component props.
 * @param {Array}        props.blocks                  Block configurations to preview.
 * @param {number}       props.blockContextId          Context ID for the block.
 * @param {Array|string} props.classList               CSS classes for the preview element or a string.
 * @param {boolean}      props.isHidden                Whether the preview is hidden.
 * @param {Function}     props.setActiveBlockContextId Function to set active context.
 * @param {string}       props.itemTag                 HTML tag to use (li or div).
 * @return {Element} The rendered block preview.
 */
const PostTemplateBlockPreview = ( props ) => {
	const { blocks, blockContextId, classList, isHidden, setActiveBlockContextId, itemTag = 'li' } = props;

	// Deep clone blocks with unique IDs for fresh preview
	const previewBlocks = useMemo( () => {
		return blocks ? blocks.map( ( block ) => getUniqueBlocks( block, blockContextId ) ) : [];
	}, [ blocks, blockContextId ] );

	const blockPreviewProps = useBlockPreview( {
		blocks: previewBlocks,
		props: {
			className: 'spectra-post-preview-content',
			style: { height: '100%' },
		},
	} );

	const handleOnClick = useCallback( () => {
		setActiveBlockContextId( blockContextId );
	}, [ setActiveBlockContextId, blockContextId ] );

	const style = useMemo(
		() => ( {
			display: isHidden ? 'none' : undefined,
		} ),
		[ isHidden ]
	);

	const className = useMemo(
		() => ( Array.isArray( classList ) ? classList.join( ' ' ) : classList || '' ),
		[ classList ]
	);

	const TagName = itemTag;

	return (
		<TagName
			className={ className }
			tabIndex={ 0 }
			role="button"
			onClick={ handleOnClick }
			onKeyPress={ handleOnClick }
			style={ style }
		>
			<div { ...blockPreviewProps } />
		</TagName>
	);
};

const MemoizedPostTemplateBlockPreview = memo( PostTemplateBlockPreview );

/**
 * Render component for the Post Template block editor preview.
 *
 * Displays posts in grid, masonry, or carousel layouts with support for
 * responsive controls and Swiper carousel integration.
 *
 * @param {Object} props                            Component props.
 * @param {string} props.clientId                   Block client ID.
 * @param {Object} props.context                    Block context from parent.
 * @param {Object} props.attributes                 Block attributes.
 * @param {Object} props.__unstableLayoutClassNames Layout class names.
 * @return {Element} The rendered block element.
 */
const Render = ( props ) => {
	const { clientId, context, __unstableLayoutClassNames } = props;

	const layoutType = context?.[ 'spectra/post/layoutType' ] || 'grid';
	const columns = context?.[ 'spectra/post/columns' ] || 3;
	const columnGap = context?.[ 'spectra/post/columnGap' ] || '20px';
	const rowGap = context?.[ 'spectra/post/rowGap' ] || '20px';
	const responsiveControls = context?.[ 'spectra/post/responsiveControls' ] || {};

	const slidesPerView = context?.[ 'spectra/post/slidesPerView' ] || 3;
	const spaceBetween = context?.[ 'spectra/post/spaceBetween' ] || 30;
	const loop = context?.[ 'spectra/post/loop' ] ?? true;
	const speed = context?.[ 'spectra/post/speed' ] ?? 500;
	const autoplay = context?.[ 'spectra/post/autoplay' ] ?? true;
	const autoplaySpeed = context?.[ 'spectra/post/autoplaySpeed' ] ?? 2000;
	const autoplayPauseOnHover = context?.[ 'spectra/post/autoplayPauseOnHover' ] ?? true;
	const equalHeight = context?.[ 'spectra/post/equalHeight' ] ?? true;
	const navigation = context?.[ 'spectra/post/navigation' ] !== false;
	const pagination = context?.[ 'spectra/post/pagination' ] !== false;
	// Refs for slider instance and state.
	const swiperInstanceRef = useRef( null );

	const attributesObject = {
		columns,
		columnGap,
		rowGap,
		responsiveControls,
	};

	const config = [
		{
			key: 'columns',
			cssVar: '--spectra-post-columns',
			className: null,
		},
		{
			key: 'columnGap',
			cssVar: '--spectra-post-column-gap',
			className: null,
		},
		{
			key: 'rowGap',
			cssVar: '--spectra-post-row-gap',
			className: null,
		},
	];

	const { style, classNames } = useSpectraStyles( attributesObject, config );

	const query = context?.[ 'spectra/post/query' ] || {};
	const postsToShow = context?.[ 'spectra/post/postsToShow' ] || 6;

	const {
		perPage = 6,
		postType = 'post',
		order = 'desc',
		orderBy = 'date',
		author,
		search,
		exclude,
		sticky,
		offset,
		taxQuery,
		excludeCurrentPost,
	} = query;

	const [ activeBlockContextId, setActiveBlockContextId ] = useState();

	// Stabilize array/object dependencies to prevent unnecessary re-renders
	const authorKey = useMemo( () => ( author ? JSON.stringify( author ) : '' ), [ author ] );
	const excludeKey = useMemo( () => ( exclude ? JSON.stringify( exclude ) : '' ), [ exclude ] );
	const taxQueryKey = useMemo( () => ( taxQuery ? JSON.stringify( taxQuery ) : '' ), [ taxQuery ] );

	// Map taxonomy slugs to their REST base keys (e.g. 'category' -> 'categories')
	const taxonomyRestBases = useSelect(
		( select ) => {
			const { getTaxonomies } = select( coreStore );
			const taxonomies = getTaxonomies( { type: postType, per_page: -1, context: 'view' } );
			return ( taxonomies || [] ).reduce( ( acc, tax ) => {
				acc[ tax.slug ] = tax.rest_base;
				return acc;
			}, {} );
		},
		[ postType ]
	);

	const currentPostId = context?.postId;

	const queryArgs = useMemo( () => {
		const args = {
			per_page: layoutType === 'carousel' ? postsToShow || 6 : perPage || 6,
			order,
			orderby: orderBy,
			status: 'publish',
			offset: offset || 0,
		};

		if ( author && author.length ) {
			args.author = author;
		}
		if ( search ) {
			args.search = search;
		}

		let finalExclude = exclude || [];
		if ( excludeCurrentPost && currentPostId ) {
			finalExclude = [ ...finalExclude, currentPostId ];
		}
		if ( finalExclude.length ) {
			args.exclude = finalExclude;
		}

		/*
		 * Handle cases where sticky is set to `exclude` or `only`.
		 * Which works as a `post__in/post__not_in` query for sticky posts.
		 */
		if ( [ 'exclude', 'only' ].includes( sticky ) ) {
			args.sticky = sticky === 'only';
		}

		// Empty string represents the default behavior of including sticky posts.
		if ( [ '', 'ignore' ].includes( sticky ) ) {
			// Remove any leftover sticky query parameter.
			delete args.sticky;
			args.ignore_sticky = sticky === 'ignore';
		}

		// Add Taxonomy Filters
		if ( taxQuery ) {
			Object.entries( taxQuery ).forEach( ( [ slug, terms ] ) => {
				const restBase = taxonomyRestBases[ slug ];
				if ( restBase && terms && terms.length ) {
					args[ restBase ] = terms;
				}
			} );
		}

		return args;
	}, [
		layoutType,
		postsToShow,
		perPage,
		order,
		orderBy,
		offset,
		authorKey,
		search,
		excludeKey,
		sticky,
		excludeCurrentPost,
		currentPostId,
		taxQueryKey,
		taxonomyRestBases,
	] );

	const posts = useSelect(
		( select ) => {
			const { getEntityRecords } = select( coreStore );
			return getEntityRecords( 'postType', postType, queryArgs );
		},
		[ postType, queryArgs ]
	);

	const blocks = useSelect(
		( select ) => {
			const { getBlocks } = select( blockEditorStore );
			return getBlocks( clientId );
		},
		[ clientId ]
	);

	// Memoize carousel class to avoid array recreation
	const carouselClass = useMemo( () => ( layoutType === 'carousel' ? 'swiper-slide' : '' ), [ layoutType ] );

	const blockContexts = useMemo(
		() =>
			posts?.map( ( post ) => {
				const postClassList = Array.isArray( post.class_list )
					? post.class_list
					: Object.values( post.class_list || {} );
				return {
					postType: post.type,
					postId: post.id,
					classList: [ 'spectra-post-item', ...postClassList, carouselClass ].filter( Boolean ),
				};
			} ),
		[ posts, carouselClass ]
	);

	const blockProps = useBlockProps( {
		style: { ...style, width: '100%' },
		className: spectraClassNames( [
			__unstableLayoutClassNames,
			'wp-block-spectra-post-template-wrapper',
			`block-${ clientId }`,
			...classNames,
		] ),
	} );

	// Determine if controls should be shown based on post count and slides per view.
	const totalPosts = posts?.length || 0;
	// Ensure slidesPerView is at least 1 to avoid division by zero or invalid logic.
	const numericSlidesPerView = parseFloat( slidesPerView ) || 3;
	// Show controls only if we have more posts than can fit in a single view.
	const showControls = totalPosts > numericSlidesPerView;

	// Determine if loop should be enabled.
	// We only enable loop if the user wants it AND we have enough posts to loop gracefully.
	const shouldEnableLoop = loop && showControls;

	// Stable references for Swiper options to prevent unnecessary re-initialization
	const autoplayObj = useMemo(
		() => ( {
			delay: autoplaySpeed,
			disableOnInteraction: false,
			pauseOnMouseEnter: false, // Handled manually via event listeners for full editor control.
		} ),
		[ autoplaySpeed ]
	);

	// Ref for carousel params that update dynamically without requiring Swiper re-init.
	// Avoids putting frequently-changing values (range sliders) in useRefEffect deps,
	// which would cause expensive destroy+recreate cycles on every change.
	const carouselParamsRef = useRef( {} );
	carouselParamsRef.current = {
		slidesPerView: numericSlidesPerView,
		spaceBetween: parseFloat( spaceBetween || 30 ),
		speed,
		autoplay,
		autoplayObj,
		autoplayPauseOnHover,
		equalHeight,
	};

	// Ref to prevent stale init calls
	const initTokenRef = useRef( 0 );

	// State to track if Swiper is initialized (prevents FOUC)
	const [ isSwiperInitialized, setIsSwiperInitialized ] = useState( false );

	// Initialize the slider.
	const sliderRef = useRefEffect(
		( element ) => {
			if ( ! element || layoutType !== 'carousel' ) {
				return;
			}

			// If controls are hidden (not enough posts), we might not need to init Swiper fully or
			// navigation elements might be missing. But we usually still init swiper for layout purposes.
			// However, if navigation/pagination are requested but hidden, Swiper init might warn or fail if elements are missing.
			// We handle this by passing `false` to nav/pag params if elements aren't found, which we do below.

			const token = ++initTokenRef.current;

			// Reset initialization state when re-initializing
			setIsSwiperInitialized( false );

			// Destroy previous instance immediately (hard stop)
			if ( swiperInstanceRef.current && ! swiperInstanceRef.current.destroyed ) {
				swiperInstanceRef.current.destroy( true, true );
				swiperInstanceRef.current = null;
			}

			const root = element.closest( `.block-${ clientId }` );
			const nextEl = root?.querySelector( '.swiper-button-next' );
			const prevEl = root?.querySelector( '.swiper-button-prev' );
			const pagEl = root?.querySelector( '.swiper-pagination' );

			// Read current dynamic params from ref to avoid triggering re-init for param changes.
			const params = carouselParamsRef.current;

			const swiperParams = {
				modules: [ Navigation, Pagination, Autoplay ],
				slidesPerView: params.slidesPerView,
				slidesPerGroup: 1,
				spaceBetween: params.spaceBetween,
				loop: shouldEnableLoop, // Use validated loop state
				speed: params.speed,
				autoHeight: ! params.equalHeight,
				autoplay: params.autoplay ? params.autoplayObj : false,
				navigation:
					navigation && nextEl && prevEl
						? {
								nextEl,
								prevEl,
						  }
						: false,
				pagination:
					pagination && pagEl
						? {
								el: pagEl,
								clickable: true,
						  }
						: false,
				grabCursor: false,
				allowTouchMove: true,
				simulateTouch: false,
				// Disabled in editor: MutationObservers on multiple carousel blocks cause
				// cascade reactions in the editor DOM, leading to UI freezes.
				observer: false,
				observeParents: false,
				observeSlideChildren: false,
				resizeObserver: true,
				watchOverflow: true,
				on: {
					init: () => {
						setIsSwiperInitialized( true );
					},
				},
			};

			const swiper = new Swiper( element, swiperParams );

			// Manual fix for Pause on Hover in Editor.
			// Store listeners on instance for later cleanup/updates.
			swiper._spectraMouseEnter = () => {
				if ( swiper && swiper.autoplay && swiper.autoplay.running ) {
					swiper.autoplay.stop();
				}
			};

			swiper._spectraMouseLeave = () => {
				if ( swiper && swiper.autoplay && ! swiper.autoplay.running ) {
					swiper.autoplay.start();
				}
			};

			if ( params.autoplay && params.autoplayPauseOnHover ) {
				element.addEventListener( 'mouseenter', swiper._spectraMouseEnter );
				element.addEventListener( 'mouseleave', swiper._spectraMouseLeave );
			}

			// If a newer init started while we were setting up, kill this one
			if ( token !== initTokenRef.current ) {
				element.removeEventListener( 'mouseenter', swiper._spectraMouseEnter );
				element.removeEventListener( 'mouseleave', swiper._spectraMouseLeave );
				swiper.destroy( true, true );
				return;
			}

			swiperInstanceRef.current = swiper;

			window.requestAnimationFrame( () => {
				if ( swiper && ! swiper.destroyed ) {
					swiper.update();
					// Always stop+start to ensure autoplay begins with correct delay.
					// Swiper may auto-start during init with stale internal state.
					if ( params.autoplay && swiper.autoplay ) {
						swiper.autoplay.stop();
						swiper.autoplay.start();
					}
					// Ensure state is set if init event didn't fire (sometimes happens with rapid updates)
					if ( ! swiper.destroyed ) {
						setIsSwiperInitialized( true );
					}
				}
			} );

			return () => {
				if ( swiper._spectraMouseEnter ) {
					element.removeEventListener( 'mouseenter', swiper._spectraMouseEnter );
				}
				if ( swiper._spectraMouseLeave ) {
					element.removeEventListener( 'mouseleave', swiper._spectraMouseLeave );
				}
				if ( swiper && ! swiper.destroyed ) {
					swiper.destroy( true, true );
				}
				swiperInstanceRef.current = null;
				setIsSwiperInitialized( false );
			};
		},
		[
			layoutType,
			clientId,
			navigation,
			pagination,
			shouldEnableLoop, // Re-init if loop requirements change (e.g. not enough posts)
			showControls, // Re-init if controls appearance changes (affects DOM elements)
		]
	);

	// Update Swiper params dynamically without destroying the instance
	useEffect( () => {
		const swiper = swiperInstanceRef.current;
		if ( ! swiper || swiper.destroyed ) {
			return;
		}

		const element = swiper.el;
		let shouldUpdate = false;
		let frameId;

		// Update slidesPerView
		const newSlidesPerView = parseFloat( slidesPerView ) || 3;
		if ( swiper.params.slidesPerView !== newSlidesPerView ) {
			swiper.params.slidesPerView = newSlidesPerView;
			shouldUpdate = true;
		}

		// Update spaceBetween
		const newSpaceBetween = parseFloat( spaceBetween || 30 );
		if ( swiper.params.spaceBetween !== newSpaceBetween ) {
			swiper.params.spaceBetween = newSpaceBetween;
			shouldUpdate = true;
		}

		// Update speed
		if ( swiper.params.speed !== speed ) {
			swiper.params.speed = speed;
			shouldUpdate = true;
		}

		// Update equalHeight (autoHeight is the inverse)
		const newAutoHeight = ! equalHeight;
		if ( swiper.params.autoHeight !== newAutoHeight ) {
			swiper.params.autoHeight = newAutoHeight;
			shouldUpdate = true;
		}

		// Update Autoplay Logic
		const newAutoplayDelay = parseFloat( autoplaySpeed ) || 2000;
		const newPauseOnHover = autoplayPauseOnHover;

		// Define handlers if they don't exist
		if ( ! swiper._spectraMouseEnter ) {
			swiper._spectraMouseEnter = () => {
				if ( swiper && swiper.autoplay && swiper.autoplay.running ) {
					swiper.autoplay.stop();
				}
			};
		}
		if ( ! swiper._spectraMouseLeave ) {
			swiper._spectraMouseLeave = () => {
				if ( swiper && swiper.autoplay && ! swiper.autoplay.running ) {
					swiper.autoplay.start();
				}
			};
		}

		// Always remove existing listeners first to ensure clean slate
		element.removeEventListener( 'mouseenter', swiper._spectraMouseEnter );
		element.removeEventListener( 'mouseleave', swiper._spectraMouseLeave );

		if ( autoplay ) {
			// If autoplay was disabled (false or boolean), we need to re-initialize the object
			if ( ! swiper.params.autoplay || typeof swiper.params.autoplay === 'boolean' ) {
				swiper.params.autoplay = {
					delay: newAutoplayDelay,
					disableOnInteraction: false,
					pauseOnMouseEnter: false,
				};
				shouldUpdate = true;
			} else if ( swiper.params.autoplay.delay !== newAutoplayDelay ) {
				// Update delay if changed
				swiper.params.autoplay.delay = newAutoplayDelay;
				shouldUpdate = true;
			}

			// Re-attach listeners ONLY if pause on hover is enabled
			if ( newPauseOnHover ) {
				element.addEventListener( 'mouseenter', swiper._spectraMouseEnter );
				element.addEventListener( 'mouseleave', swiper._spectraMouseLeave );
			}

			// If we are NOT updating params, but autoplay is enabled, check if we need to force restart
			if ( ! shouldUpdate && swiper.autoplay ) {
				if ( ! swiper.autoplay.running ) {
					swiper.autoplay.start();
				}
			}
		} else if ( swiper.params.autoplay ) {
			// Autoplay disabled
			swiper.params.autoplay = false;
			if ( swiper.autoplay && swiper.autoplay.running ) {
				swiper.autoplay.stop();
			}
			shouldUpdate = true;
		}

		if ( shouldUpdate ) {
			frameId = window.requestAnimationFrame( () => {
				if ( swiper && ! swiper.destroyed ) {
					swiper.update();

					// Always stop+start to ensure autoplay applies new params cleanly.
					// Checking `running` first is unreliable at very low delays (100-200ms)
					// where update() may have already stopped it mid-cycle.
					if ( autoplay && swiper.autoplay ) {
						swiper.autoplay.stop();
						swiper.autoplay.start();
					}
				}
			} );
		}

		// Cancel pending rAF on re-run to prevent accumulated callbacks.
		// At very low autoplay delays (100ms), stacked rAFs each doing stop/start
		// create race conditions that prevent autoplay from stabilizing.
		return () => {
			if ( frameId ) {
				window.cancelAnimationFrame( frameId );
			}
		};
	}, [ slidesPerView, spaceBetween, speed, autoplaySpeed, autoplayPauseOnHover, autoplay, equalHeight ] );

	// Update Swiper when inner blocks change (replaces MutationObserver for editor)
	useEffect( () => {
		const swiper = swiperInstanceRef.current;
		if ( ! swiper || swiper.destroyed || layoutType !== 'carousel' ) {
			return;
		}
		const frameId = window.requestAnimationFrame( () => {
			if ( swiper && ! swiper.destroyed ) {
				swiper.update();

				// Ensure autoplay is in correct state after content update
				if ( autoplay && swiper.autoplay && ! swiper.autoplay.running ) {
					swiper.autoplay.start();
				}
			}
		} );
		return () => window.cancelAnimationFrame( frameId );
	}, [ blocks, layoutType, totalPosts ] ); // Added totalPosts to trigger update when post count changes

	// If no posts are fetched yet (still loading), show a spinner.
	if ( ! posts ) {
		return (
			<div { ...blockProps }>
				<div
					className="spectra-loading-container"
					style={ { display: 'flex', justifyContent: 'center', padding: '20px' } }
				>
					<Spinner />
				</div>
			</div>
		);
	}

	// If no posts are found (empty array), return null to let the parent show the no-results block.
	if ( ! posts.length ) {
		return null;
	}

	// Force 'div' in editor to maintain consistent DOM structure across layout switches
	const itemTag = 'div';

	return (
		<div { ...blockProps }>
			<div
				className={
					layoutType === 'carousel'
						? `spectra-post-carousel-container spectra-post-layout-carousel block-${ clientId } ${
								equalHeight ? 'spectra-post-equal-height' : ''
						  }`
						: 'spectra-post-grid-container'
				}
				style={
					layoutType === 'carousel'
						? {
								position: 'relative',
								opacity: isSwiperInitialized ? 1 : 0,
								transition: 'opacity 0.2s ease-in-out',
						  }
						: {}
				}
			>
				<div
					className={ layoutType === 'carousel' ? 'swiper' : '' }
					ref={ sliderRef }
					style={ layoutType !== 'carousel' ? { display: 'contents' } : undefined }
					key={ layoutType === 'carousel' ? `spectra-post-carousel-${ shouldEnableLoop }` : undefined }
				>
					<div className={ layoutType === 'carousel' ? 'swiper-wrapper' : 'spectra-post-loop-wrapper' }>
						{ blockContexts &&
							blockContexts.map( ( blockContext ) => {
								const isActive =
									blockContext.postId === ( activeBlockContextId || blockContexts[ 0 ]?.postId );
								// Use a stable key to prevent re-mounting the editor when data changes.
								const contextKey = isActive ? 'active-spectra-post-template' : blockContext.postId;

								return (
									<BlockContextProvider key={ contextKey } value={ blockContext }>
										{ isActive ? (
											<PostTemplateInnerBlocks
												classList={ blockContext.classList }
												itemTag={ itemTag }
											/>
										) : (
											<MemoizedPostTemplateBlockPreview
												blocks={ blocks }
												blockContextId={ blockContext.postId }
												classList={ blockContext.classList }
												setActiveBlockContextId={ setActiveBlockContextId }
												isHidden={ false }
												itemTag={ itemTag }
											/>
										) }
									</BlockContextProvider>
								);
							} ) }
					</div>
				</div>
				{ layoutType === 'carousel' && navigation && showControls && (
					<>
						<div className="swiper-button-prev">
							<RenderSVG svg="arrow-left" />
						</div>
						<div className="swiper-button-next">
							<RenderSVG svg="arrow-right" />
						</div>
					</>
				) }
				{ layoutType === 'carousel' && pagination && showControls && <div className="swiper-pagination"></div> }
			</div>
		</div>
	);
};

export default memo( Render );
