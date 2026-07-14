/**
 * External dependencies.
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	ToggleControl,
	SelectControl,
	CheckboxControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	BaseControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies.
 */
import {
	DEFAULT_DISPLAY_CONDITIONS,
	BROWSER_OPTIONS,
	OS_OPTIONS,
	DAY_OPTIONS,
} from './utils/constants';
import { isAllowedBlock } from './utils/helpers';

/**
 * Display Conditions Options Panel.
 *
 * Provides UI controls for managing block visibility based on user login state,
 * allowing users to hide blocks from logged-in or logged-out users.
 *
 * @since x.x.x
 *
 * @param {Object} props - Component props.
 * @return {Element} Display conditions options panel component.
 */
const DisplayConditionsOptions = ( props ) => {
	const {
		setAttributes,
		attributes: { displayConditions = DEFAULT_DISPLAY_CONDITIONS },
	} = props;

	/**
	 * Updates display conditions attribute.
	 *
	 * @param {string}  condition - Condition type (hideWhenLoggedIn, hideWhenLoggedOut).
	 * @param {boolean} value     - Whether to hide for this condition.
	 */
	const updateDisplayCondition = ( condition, value ) => {
		setAttributes( {
			displayConditions: {
				...displayConditions,
				[ condition ]: value,
			},
		} );
	};

	return (
		<>
			<ToolsPanelItem
				label={ __(
					'Hide From Logged In Users',
					'spectra-blocks'
				) }
				hasValue={ () => displayConditions.hideWhenLoggedIn }
				onDeselect={ () =>
					updateDisplayCondition( 'hideWhenLoggedIn', false )
				}
				isShownByDefault={ true }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Hide From Logged In Users',
						'spectra-blocks'
					) }
					checked={ displayConditions.hideWhenLoggedIn }
					onChange={ ( value ) =>
						updateDisplayCondition( 'hideWhenLoggedIn', value )
					}
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				label={ __(
					'Hide From Logged Out Users',
					'spectra-blocks'
				) }
				hasValue={ () => displayConditions.hideWhenLoggedOut }
				onDeselect={ () =>
					updateDisplayCondition( 'hideWhenLoggedOut', false )
				}
				isShownByDefault={ true }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Hide From Logged Out Users',
						'spectra-blocks'
					) }
					checked={ displayConditions.hideWhenLoggedOut }
					onChange={ ( value ) =>
						updateDisplayCondition( 'hideWhenLoggedOut', value )
					}
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				label={ __(
					'Hide for User Role',
					'spectra-blocks'
				) }
				hasValue={ () => !! displayConditions.hideForRole }
				onDeselect={ () =>
					updateDisplayCondition( 'hideForRole', '' )
				}
				isShownByDefault={ false }
			>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __(
						'Hide for User Role',
						'spectra-blocks'
					) }
					value={ displayConditions.hideForRole }
					options={ [
						{
							label: __(
								'Select Role',
								'spectra-blocks'
							),
							value: '',
						},
						...( window.spectraDisplayConditions?.userRoles ||
							[] ),
					] }
					onChange={ ( value ) =>
						updateDisplayCondition( 'hideForRole', value )
					}
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				label={ __(
					'Hide for Browser',
					'spectra-blocks'
				) }
				hasValue={ () => !! displayConditions.hideForBrowser }
				onDeselect={ () =>
					updateDisplayCondition( 'hideForBrowser', '' )
				}
				isShownByDefault={ false }
			>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __(
						'Hide for Browser',
						'spectra-blocks'
					) }
					value={ displayConditions.hideForBrowser }
					options={ BROWSER_OPTIONS }
					onChange={ ( value ) =>
						updateDisplayCondition( 'hideForBrowser', value )
					}
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				label={ __(
					'Hide for Operating System',
					'spectra-blocks'
				) }
				hasValue={ () => !! displayConditions.hideForOS }
				onDeselect={ () =>
					updateDisplayCondition( 'hideForOS', '' )
				}
				isShownByDefault={ false }
			>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __(
						'Hide for Operating System',
						'spectra-blocks'
					) }
					value={ displayConditions.hideForOS }
					options={ OS_OPTIONS }
					onChange={ ( value ) =>
						updateDisplayCondition( 'hideForOS', value )
					}
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				label={ __(
					'Hide on Days',
					'spectra-blocks'
				) }
				hasValue={ () =>
					displayConditions.hideOnDays &&
					displayConditions.hideOnDays.length > 0
				}
				onDeselect={ () =>
					updateDisplayCondition( 'hideOnDays', [] )
				}
				isShownByDefault={ false }
			>
				<BaseControl
					id="spectra-display-conditions-hide-on-days"
					label={ __(
						'Hide on Days',
						'spectra-blocks'
					) }
				>
					{ DAY_OPTIONS.map( ( day ) => (
						<CheckboxControl
							__nextHasNoMarginBottom
							key={ day }
							label={
								day.charAt( 0 ).toUpperCase() + day.slice( 1 )
							}
							checked={
								displayConditions.hideOnDays?.includes(
									day
								) ?? false
							}
							onChange={ ( checked ) => {
								const current =
									displayConditions.hideOnDays || [];
								const updated = checked
									? [ ...current, day ]
									: current.filter( ( d ) => d !== day );
								updateDisplayCondition(
									'hideOnDays',
									updated
								);
							} }
						/>
					) ) }
				</BaseControl>
			</ToolsPanelItem>
		</>
	);
};

/**
 * Higher-order component to add display conditions controls to block settings.
 *
 * @since x.x.x
 *
 * @param {Function} BlockEdit - Original block edit component.
 * @return {Function} Enhanced block edit component with display conditions controls.
 */
const addDisplayConditionsControls = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			if ( ! isAllowedBlock( { name: props.name } ) ) {
				return <BlockEdit { ...props } />;
			}

			const { setAttributes, isSelected } = props;

			const resetAll = () => {
				setAttributes( {
					displayConditions: DEFAULT_DISPLAY_CONDITIONS,
				} );
			};

			return (
				<>
					<BlockEdit { ...props } />
					{ isSelected && (
						<InspectorControls>
							<ToolsPanel
								label={ __(
									'Display Conditions',
									'spectra-blocks'
								) }
								resetAll={ resetAll }
							>
								<DisplayConditionsOptions { ...props } />

								<div
									style={ {
										gridColumn: 'span 2',
									} }
								>
									<BaseControl
										help={ __(
											'Control block visibility based on user state, role, browser, OS, or day. Hidden blocks are completely removed from the page output.',
											'spectra-blocks'
										) }
									/>
								</div>
							</ToolsPanel>
						</InspectorControls>
					) }
				</>
			);
		};
	},
	'addDisplayConditionsControls'
);

/**
 * Higher-order component to add display condition classes to block wrapper in editor.
 * Adds a visual indicator when any display condition is active.
 *
 * @since x.x.x
 *
 * @param {Function} BlockListBlock - Original BlockListBlock component.
 * @return {Function} Enhanced BlockListBlock component with display condition classes.
 */
export const addDisplayConditionsClasses = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			const { attributes, name } = props;

			if (
				! isAllowedBlock( { name } ) ||
				! attributes?.displayConditions
			) {
				return <BlockListBlock { ...props } />;
			}

			const { displayConditions } = attributes;

			const shouldShowIndicator =
				displayConditions.hideWhenLoggedIn ||
				displayConditions.hideWhenLoggedOut ||
				!! displayConditions.hideForRole ||
				!! displayConditions.hideForBrowser ||
				!! displayConditions.hideForOS ||
				( displayConditions.hideOnDays &&
					displayConditions.hideOnDays.length > 0 );

			const displayClassName = shouldShowIndicator
				? 'spectra-display-condition-active'
				: '';

			const enhancedProps = displayClassName
				? {
						...props,
						className: props.className
							? `${ props.className } ${ displayClassName }`
							: displayClassName,
				  }
				: props;

			return <BlockListBlock { ...enhancedProps } />;
		};
	},
	'addDisplayConditionsClasses'
);

export default addDisplayConditionsControls;
