# Advanced Gradient Control

A reusable component system for adding advanced gradient controls to any Spectra block.

## Features

- ✅ Precise gradient control with exact location points (0-100%)
- ✅ Theme color support with CSS variables
- ✅ Support for linear and radial gradients
- ✅ Color picker with alpha/transparency channel
- ✅ WordPress Gutenberg design patterns and styling (matches core UI)
- ✅ Automatic default gradient creation when toggled on
- ✅ Super simple config-based API
- ✅ Works independently from basic gradient picker
- ✅ Fully reusable across all 30+ Spectra blocks
- ✅ Automatic InspectorControls wrapper (no need to wrap manually)
- ✅ Flexible toggle configuration (global + individual, or global only)

## Quick Start

There are two ways to use advanced gradients, depending on your use case:

### Option 1: Multiple Gradients with Global + Individual Toggles (Container Block)

For blocks with multiple gradient options (e.g., Background and Background Hover).

#### Step 1: Add Attributes to `block.json`

```json
{
  "attributes": {
    "enableAdvGradients": {
      "type": "boolean",
      "default": false
    },
    "enableAdvBgGradient": {
      "type": "boolean",
      "default": true
    },
    "advBgGradient": {
      "type": "string"
    },
    "enableAdvBgGradientHover": {
      "type": "boolean",
      "default": true
    },
    "advBgGradientHover": {
      "type": "string"
    }
  }
}
```

#### Step 2: Add Control to Settings (settings.js)

```javascript
import AdvancedGradientControlsGroup from '@spectra-components/advanced-gradient-control';

const GradientSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;

	const gradientConfigs = [
		{
			label: __( 'Advanced BG Hover', 'ultimate-addons-for-gutenberg' ),
			valueAttr: 'advBgGradientHover',
			useAdvancedAttr: 'enableAdvBgGradientHover',
			showTopBorder: true,
		},
		{
			label: __( 'Advanced BG', 'ultimate-addons-for-gutenberg' ),
			valueAttr: 'advBgGradient',
			useAdvancedAttr: 'enableAdvBgGradient',
		},
	];

	return (
		<AdvancedGradientControlsGroup
			clientId={ clientId }
			setAttributes={ setAttributes }
			attributes={ attributes }
			gradients={ gradientConfigs }
			enableAttr="enableAdvGradients"
		/>
	);
} );
```

### Option 2: Single Gradient with Only Global Toggle (Slider/Slider-Child)

For blocks with only ONE gradient option. This uses only a global "Enable Advanced Gradients" toggle without individual toggles.

#### Step 1: Add Attributes to `block.json`

```json
{
  "attributes": {
    "enableAdvGradients": {
      "type": "boolean",
      "default": false
    },
    "advBgGradient": {
      "type": "string"
    }
  }
}
```

#### Step 2: Add Control to Settings (settings.js)

```javascript
import AdvancedGradientControlsGroup from '@spectra-components/advanced-gradient-control';

const GradientSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;

	const gradientConfigs = [
		{
			label: __( 'Background', 'ultimate-addons-for-gutenberg' ),
			valueAttr: 'advBgGradient',
		},
	];

	return (
		<AdvancedGradientControlsGroup
			clientId={ clientId }
			setAttributes={ setAttributes }
			attributes={ attributes }
			gradients={ gradientConfigs }
			enableAttr="enableAdvGradients"
			hideIndividualToggles={ true }
		/>
	);
} );
```

That's it! The component automatically handles:
- Global enable/disable toggle
- InspectorControls wrapper with "color" group
- Attribute updates
- Theme color integration with CSS variables
- Color picker with hex/RGB/HSL input modes
- Transparency/alpha channel support
- Clear button functionality
- Reset functionality
- Default gradient creation (linear-gradient(0deg, #06558a 0%, #0063A1 100%))
- Default values when colors/positions/angles are undefined

## Rendering (Optional Step)

#### In render.js (Frontend - Editor):

```javascript
import { getAdvancedGradientValue } from '@spectra-helpers/get-advanced-gradient-value';

// Get attributes
const {
	backgroundGradient,
	backgroundGradientHover,
	enableAdvBgGradient,
	enableAdvBgGradientHover,
	advBgGradient,
	advBgGradientHover,
	enableAdvGradients
} = attributes;

// Get final values
const finalBackgroundGradient = getAdvancedGradientValue(
	enableAdvBgGradient,
	advBgGradient,
	backgroundGradient,
	enableAdvGradients
);

const finalBackgroundGradientHover = getAdvancedGradientValue(
	enableAdvBgGradientHover,
	advBgGradientHover,
	backgroundGradientHover,
	enableAdvGradients
);

// Use in config
const config = [
	// ... other config
	{ key: 'backgroundGradient', value: finalBackgroundGradient },
	{ key: 'backgroundGradientHover', value: finalBackgroundGradientHover },
];

// Use in styles
const styles = {
	...generatedStyle,
	...getBackgroundImageStyles( {
		background,
		backgroundGradient: finalBackgroundGradient,
		backgroundGradientHover: finalBackgroundGradientHover
	} ),
};
```

#### In controller.php (Frontend - Server):

```php
use Spectra\Helpers\Core;

// Get background attributes
$enable_adv_gradients      = $attributes['enableAdvGradients'] ?? false;
$enable_adv_bg_gradient    = $attributes['enableAdvBgGradient'] ?? false;
$enable_adv_bg_grad_hover  = $attributes['enableAdvBgGradientHover'] ?? false;
$background_gradient       = Core::get_advanced_gradient_value(
	$enable_adv_bg_gradient,
	$attributes['advBgGradient'] ?? '',
	$attributes['backgroundGradient'] ?? '',
	$enable_adv_gradients
);
$background_gradient_hover = Core::get_advanced_gradient_value(
	$enable_adv_bg_grad_hover,
	$attributes['advBgGradientHover'] ?? '',
	$attributes['backgroundGradientHover'] ?? '',
	$enable_adv_gradients
);

// Use in config
$config = array(
	// ... other config
	array(
		'key'   => 'backgroundGradientHover',
		'value' => $background_gradient_hover,
	),
	array(
		'key'   => 'backgroundGradient',
		'value' => $background_gradient,
	),
);
```

## API Reference

### AdvancedGradientControlsGroup Props (Primary API)

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `clientId` | string | Yes | Block client ID |
| `setAttributes` | function | Yes | Function to set block attributes |
| `attributes` | object | Yes | Block attributes object |
| `gradients` | array | Yes | Array of gradient configuration objects |
| `enableAttr` | string | No | Attribute name for global enable toggle (default: 'enableAdvGradients') |
| `enableLabel` | string | No | Label for the enable toggle (default: 'Enable Advanced Gradients') |
| `defaultGradient` | string | No | Default gradient to create when toggled on (default: 'linear-gradient(0deg, #06558a 0%, #0063A1 100%)') |
| `showGlobalToggle` | boolean | No | Whether to show global toggle (default: true) |
| `hideIndividualToggles` | boolean | No | Whether to hide individual "Use Advanced Gradient" toggles (default: false). Use for blocks with single gradient. |

### Gradient Config Object

Each object in the `gradients` array should have:

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `label` | string | Yes | Label for the gradient control (e.g., "Background", "Border") |
| `valueAttr` | string | Yes | Attribute name for gradient value (e.g., 'advBgGradient') |
| `useAdvancedAttr` | string | Conditional | Attribute name for useAdvanced flag (e.g., 'enableAdvBgGradient'). Required when `hideIndividualToggles` is false, not needed when true. |
| `showTopBorder` | boolean | No | Whether to show top border (default: false) |

### Helper Functions

#### `getAdvancedGradientValue(enableAdvBg, advValue, basicValue, enableAdvGradients)`

Returns the final gradient value based on whether advanced mode is enabled (both global and individual toggles).

#### `getAdvancedGradientAttributes(baseAttrName)`

Generates advanced gradient attributes for block.json (useful for programmatic generation).

## Examples

### Example 1: Multiple Gradients with Individual Toggles (Container Block)

For blocks with multiple gradient options that need both global and individual control.

```javascript
import AdvancedGradientControlsGroup from '@spectra-components/advanced-gradient-control';

const gradients = [
	{
		label: __( 'Advanced BG Hover', 'ultimate-addons-for-gutenberg' ),
		valueAttr: 'advBgGradientHover',
		useAdvancedAttr: 'enableAdvBgGradientHover',
		showTopBorder: true,
	},
	{
		label: __( 'Advanced BG', 'ultimate-addons-for-gutenberg' ),
		valueAttr: 'advBgGradient',
		useAdvancedAttr: 'enableAdvBgGradient',
	},
];

<AdvancedGradientControlsGroup
	clientId={ clientId }
	setAttributes={ setAttributes }
	attributes={ attributes }
	gradients={ gradients }
	enableAttr="enableAdvGradients"
/>
```

### Example 2: Single Gradient with Only Global Toggle (Slider Block)

For blocks with only ONE gradient option - shows gradient controls directly without individual toggle.

```javascript
import AdvancedGradientControlsGroup from '@spectra-components/advanced-gradient-control';

const gradients = [
	{
		label: __( 'Background', 'ultimate-addons-for-gutenberg' ),
		valueAttr: 'advBgGradient',
	},
];

<AdvancedGradientControlsGroup
	clientId={ clientId }
	setAttributes={ setAttributes }
	attributes={ attributes }
	gradients={ gradients }
	enableAttr="enableAdvGradients"
	hideIndividualToggles={ true }
/>
```

### Example 3: Custom Enable Label

```javascript
<AdvancedGradientControlsGroup
	clientId={ clientId }
	setAttributes={ setAttributes }
	attributes={ attributes }
	gradients={ gradients }
	enableAttr="enableAdvGradients"
	enableLabel={ __( 'Use Precise Gradient Controls', 'ultimate-addons-for-gutenberg' ) }
/>
```

### Example 4: Multiple Gradient Types (Background + Border)

```javascript
const gradients = [
	{
		label: __( 'Advanced Background', 'ultimate-addons-for-gutenberg' ),
		valueAttr: 'advBgGradient',
		useAdvancedAttr: 'enableAdvBgGradient',
		showTopBorder: true,
	},
	{
		label: __( 'Advanced Border', 'ultimate-addons-for-gutenberg' ),
		valueAttr: 'advancedBorderGradient',
		useAdvancedAttr: 'useAdvancedBorderGradient',
	},
	{
		label: __( 'Advanced Text', 'ultimate-addons-for-gutenberg' ),
		valueAttr: 'advancedTextGradient',
		useAdvancedAttr: 'useAdvancedTextGradient',
	},
];

<AdvancedGradientControlsGroup
	clientId={ clientId }
	setAttributes={ setAttributes }
	attributes={ attributes }
	gradients={ gradients }
	enableAttr="enableAdvGradients"
/>
```

## Benefits

1. **Reusable**: One component works across all 30+ blocks
2. **Small Code**: ~15 lines of config vs ~250+ lines of implementation before
3. **Consistent**: Same UX/UI across all blocks - matches WordPress core styling exactly
4. **Maintainable**: Fix once, applies everywhere
5. **Scalable**: Easy to add to new blocks
6. **Self-Contained**: Includes InspectorControls wrapper and useEffect logic internally
7. **Auto-Defaults**: Automatically creates default gradients when toggled on
8. **Config-Based**: Simple array configuration - no need to understand internal implementation

## Key Features Explained

### Global Enable Toggle

The component includes a built-in "Enable Advanced Gradients" toggle that controls visibility of all gradient controls. When disabled, the gradient values are preserved but not applied.

### Automatic Default Gradient Creation

When a user toggles on "Use Advanced Gradient" for any individual gradient, the component automatically creates a default gradient value if one doesn't exist:
- Default: `linear-gradient(0deg, #06558a 0%, #0063A1 100%)`
- Customizable via the `defaultGradient` prop

This is handled by an internal `useEffect` hook that monitors the toggle states.

### WordPress Core UI Match

The color picker buttons and dropdowns match the exact styling from WordPress core's global styles panel:
- Uses `clsx` for conditional classes
- Proper `HStack`, `Flex`, `FlexItem` component structure
- `ColorPalette` component with theme color swatches
- `ColorPicker` with hex/RGB/HSL modes and alpha channel
- Secondary button variant for consistent look
- Proper dropdown width (260px) matching core implementation

### Theme Color Integration

When users select a theme color from the palette, the component stores it as a CSS variable (`var(--wp--preset--color--{slug})`), which means the gradient automatically updates if the user changes their theme colors.

### Default Values & Validation

The component automatically provides default values when colors, positions, or angles are undefined:
- Color 1: `#06558a` at `0%`
- Color 2: `#0063A1` at `100%`
- Angle: `0deg` (not 90deg)

This ensures gradients always render correctly even with partial data.

### Precise Control

Unlike basic gradient pickers, this provides:
- Exact location values (0-100%) with sliders
- Angle control for linear gradients (0-360°)
- Support for both linear and radial gradients
- Alpha channel support for transparent gradients

## Migration Guide

To migrate existing gradient controls:

1. Add advanced gradient attributes to `block.json` (if not already present)
2. Replace custom gradient dropdown code with `<AdvancedGradientControlsGroup />`
3. Create a simple config array with your gradient labels and attribute names
4. Update `render.js`/`controller.php` to use `getAdvancedGradientValue()`
5. Remove old gradient control code

That's it! 🚀

## Troubleshooting

### Color picker not showing hex input or transparency slider

Make sure you're using the latest version of the component. The `ColorPicker` component should have `enableAlpha={true}` and `ColorPalette` should have `disableCustomColors={false}` for the custom color picker to appear.

### Default angle is 90deg instead of 0deg

This was fixed in the latest version. The default angle is now `0deg` for all new gradients. Check `use-enhanced-gradient.js` line 47 to ensure it says `angle: isNaN( angle ) ? 0 : angle`.

### Theme colors not updating

Make sure you're storing theme colors as CSS variables. The component does this automatically when users select from the theme palette.

### Gradient not rendering

Check that you're using `getAdvancedGradientValue()` in both `render.js` and `controller.php` to get the final gradient value based on whether advanced mode is enabled.

### Need to wrap in InspectorControls manually

No need! The component now includes `<InspectorControls group="color">` wrapper internally. Just use `<AdvancedGradientControlsGroup />` directly in your settings component.
