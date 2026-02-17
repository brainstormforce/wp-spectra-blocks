# Spectra v3 - Inline Documentation Guide

## Purpose
This guide helps developers understand and document Spectra code directly within the codebase, enabling confident navigation, contribution, debugging, and extension without external documentation.

## Where Inline Documentation is Required

**Always document:**
- **Public functions and methods** - APIs other developers will use
- **Hooks and filters** - Integration points for extensions
- **Helper utilities** - Reusable functions across blocks
- **Lifecycle methods** - Block initialization, rendering, cleanup
- **Callback handlers** - Event processing functions
- **Complex logic flows** - Multi-step processes or algorithms
- **Public APIs** - Methods exposed via Spectra APIs

**Optional:**
- Simple getters/setters
- Self-explanatory one-line functions

---

## PHP Documentation Style

### Function Documentation Format
```php
/**
 * Brief description of function purpose.
 *
 * @param string $param1 Parameter name and expected type.
 * @param array  $param2 Parameter name and expected structure.
 * @return string Return value type and description.
 */
public function my_function( $param1, $param2 ) {
    // Implementation
}
```

### Document Side Effects and Dependencies
```php
/**
 * Enqueues block assets and registers styles.
 *
 * Side effects: Modifies global wp_styles, creates CSS files
 * Dependencies: Requires WordPress 6.6+, filesystem write access
 *
 * @param string $block_name Block identifier.
 * @param array  $assets Asset configuration.
 * @return bool True on success, false on failure.
 */
public function enqueue_block_assets( $block_name, $assets ) {
    // Implementation
}
```

### Class Documentation
```php
/**
 * What this class does.
 */
class MyClass {
    /**
     * What this property stores.
     * @var array
     */
    private $data = [];
}
```

### Hooks and Filters
```php
/**
 * Fires after enqueuing editor assets for extensions.
 *
 * Allows plugins to add custom editor scripts/styles.
 *
 * @param string $folder_name Extension folder name.
 * @param array  $asset_file  Asset file data with dependencies.
 */
do_action( 'spectra_editor_assets', $folder_name, $asset_file );
```

```php
/**
 * Filters countdown context before rendering.
 *
 * Modify countdown labels, timing, or display options.
 *
 * @param array $context Countdown data (labels, timing, display).
 * @param array $attributes Block attributes from editor.
 * @return array Modified countdown context.
 */
$context = apply_filters( 'spectra_countdown_context', $context, $attributes );
```

---

## JavaScript/TypeScript Documentation Style

### Function Documentation (JSDoc)
```javascript
/**
 * Generates styles and class names from block attributes.
 *
 * @param {Object} attributes Block attributes from WordPress.
 * @param {Array}  config Configuration with cssVar, className properties.
 * @return {Object} Object with styles and classNames properties.
 */
export const useSpectraStyles = ( attributes, config = [] ) => {
    // Implementation
};
```

### Lifecycle Methods
```javascript
/**
 * Initializes block on DOM ready.
 *
 * Side effects: Attaches event listeners, modifies DOM
 * Dependencies: Requires DOM to be loaded
 *
 * @param {string} blockId Unique block identifier.
 * @param {Object} settings Block configuration.
 * @return {void}
 */
const initializeBlock = ( blockId, settings ) => {
    // Implementation
};
```

### Callback Handlers
```javascript
/**
 * Handles slider navigation events.
 *
 * @param {Event} event Browser event object.
 * @param {Object} sliderInstance Swiper instance.
 * @return {void}
 */
const handleSliderNavigation = ( event, sliderInstance ) => {
    // Implementation
};
```

### React Components
```javascript
/**
 * Icon picker component for FontAwesome icons.
 *
 * @param {Object}   props Component properties.
 * @param {string}   props.value Currently selected icon name.
 * @param {Function} props.onChange Callback when selection changes.
 * @return {JSX.Element} Rendered icon picker.
 */
const IconPicker = ( { value, onChange } ) => {
    // Implementation
};
```

---

## Documenting Spectra v3 Components

### Block Controllers (PHP)
```php
/**
 * Controller for countdown block rendering.
 *
 * Processes attributes, sets context, determines view template.
 * Side effects: May modify global countdown state
 *
 * @param array $attributes Block attributes from editor.
 * @param string $content Block content HTML.
 * @param WP_Block $block Block instance.
 * @return string Rendered block HTML or empty string.
 */
function countdown_controller( $attributes, $content, $block ) {
    // Implementation
}
```

### Extension Classes
```php
/**
 * Manages responsive controls for Spectra blocks.
 *
 * Handles device-specific styling and CSS generation.
 * Uses Singleton pattern for global state management.
 *
 * @since 0.0.1
 */
class ResponsiveControls {
    use Singleton;
    
    /**
     * Generates responsive CSS for block.
     *
     * @param string $block_id Unique block identifier.
     * @param array $controls Responsive control data.
     * @return string Generated CSS with media queries.
     */
    public function generate_css( $block_id, $controls ) {
        // Implementation
    }
}
```

### Asset Management
```php
/**
 * Loads block assets conditionally.
 *
 * Side effects: Enqueues scripts/styles, modifies wp_scripts
 * Dependencies: WordPress asset system
 *
 * @param string $block_name Block identifier.
 * @param array $asset_config Asset configuration.
 * @return void
 */
public function load_block_assets( $block_name, $asset_config ) {
    // Implementation
}
```

### Helper Utilities
```php
/**
 * Converts HEX color to RGBA format.
 *
 * Handles 3/6 character hex codes, with/without # prefix.
 *
 * @param string $color HEX color value (#fff or #ffffff).
 * @param float $opacity Opacity value between 0-1.
 * @return string RGBA color string or original on failure.
 */
public static function hex2rgba( $color, $opacity = 1 ) {
    // Implementation
}
```

### Block Registration
```javascript
/**
 * Registers Spectra block with WordPress.
 *
 * @param {string} blockName Block identifier (spectra/block-name).
 * @param {Object} blockConfig Block configuration object.
 * @param {Function} blockConfig.edit Edit component.
 * @param {Function} blockConfig.save Save component.
 * @return {Object} Registered block object.
 */
export const registerSpectraBlock = ( blockName, blockConfig ) => {
    // Implementation
};
```

### Block Edit Components
```javascript
/**
 * Edit component for container block.
 *
 * @param {Object} props Block editor props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Update attributes function.
 * @param {string} props.clientId Unique block client ID.
 * @return {JSX.Element} Block edit interface.
 */
const ContainerEdit = ( { attributes, setAttributes, clientId } ) => {
    // Implementation
};
```

### Block Settings/Controls
```javascript
/**
 * Responsive control component.
 *
 * @param {Object} props Control properties.
 * @param {string} props.label Control label.
 * @param {*} props.value Current value.
 * @param {Function} props.onChange Value change handler.
 * @param {Array} props.devices Supported devices ['desktop', 'tablet', 'mobile'].
 * @return {JSX.Element} Responsive control interface.
 */
const ResponsiveControl = ( { label, value, onChange, devices } ) => {
    // Implementation
};
```

### View Scripts (Frontend)
```javascript
/**
 * Initializes slider block on frontend.
 *
 * Side effects: Creates Swiper instance, attaches event listeners
 * Dependencies: Swiper library, DOM element
 *
 * @param {HTMLElement} element Block container element.
 * @param {Object} config Slider configuration from PHP.
 * @return {Object} Swiper instance or null on failure.
 */
const initSlider = ( element, config ) => {
    // Implementation
};
```

---

## Documentation Style Conventions

### Consistent Format Requirements
1. **Brief description** - One line explaining function purpose
2. **Parameter documentation** - Name, type, and purpose of each parameter
3. **Return values** - Type and description of what's returned
4. **Side effects** - Document global state changes, file operations
5. **Dependencies** - Note WordPress version, plugin requirements
6. **Use JSDoc for JavaScript, PHPDoc for PHP consistently**

### PR Review Requirements
**During code review, ensure:**
- [ ] All public functions documented
- [ ] Hook/filter purposes explained
- [ ] Complex logic has inline comments
- [ ] Side effects and dependencies noted
- [ ] Documentation updated when code changes
- [ ] Parameter types match actual usage

### Spectra v3 Architecture Coverage
**This guide covers documentation for:**
- ✅ **Block Controllers** - PHP rendering logic
- ✅ **Extension Classes** - ResponsiveControls, Animations, ImageMask
- ✅ **Asset Management** - AssetLoader, FontManager
- ✅ **Helper Utilities** - Core, BlockAttributes, Renderer
- ✅ **Block Components** - Edit, Save, Settings components
- ✅ **Frontend Scripts** - View.js initialization
- ✅ **Hooks & Filters** - Integration points
- ✅ **React Components** - IconPicker, Controls, etc.

### Benefits for Developers
- **Faster onboarding** - New developers understand code immediately
- **Confident debugging** - Clear function contracts prevent misuse
- **Easier extension** - Well-documented APIs encourage proper usage
- **Reduced regressions** - Documentation guides testing and changes

---

## Special Cases

### Deprecated Functions
```php
/**
 * Old function - use new_function() instead.
 *
 * @deprecated 3.0.0 Use new_function() instead.
 * @param string $param Parameter.
 * @return string Result.
 */
```

### Error Handling
```php
/**
 * Validates block attributes.
 *
 * @param array $attributes Block attributes.
 * @return array Clean attributes.
 * @throws InvalidArgumentException If data is invalid.
 */
```

---

## Quick Reference

### Required Tags
- `@param` - Describe each parameter
- `@return` - Describe what function returns

### Optional Tags
- `@throws` - For functions that can throw errors
- `@deprecated` - For old functions
- `@since` - Version when added

### Maintenance During Development
1. **Write documentation first** - Document function signature before implementation
2. **Update immediately** - Change docs when changing function behavior  
3. **Keep it current** - Outdated docs are worse than no docs
4. **Use IDE features** - Enable documentation tooltips and auto-completion

*Inline documentation is your code's user manual - make it count.*
