# Claude.md

## Plugin Overview

**Spectra Blocks** is the new WordPress plugin that replaces the legacy `ultimate-addons-for-gutenberg` plugin. This is an active development project where the Spectra Blocks code should always load first and take priority.

## Critical Plugin Conflict Issue

### Problem
There are TWO versions of essentially the same plugin installed:
1. **spectra-blocks/** (NEW - active development)
2. **ultimate-addons-for-gutenberg/** (LEGACY - old name)

Both plugins share common function names, causing fatal PHP errors when both are active:
- `get_spectra_font_awesome_polyfiller()`
- `spectra_get_post_assets()`

### Solution Strategy

**Unique Function Names + Backward Compatibility Aliases**

To avoid conflicts regardless of plugin load order, all new functions use unique `spectra_blocks_*` naming:

```php
// Primary function with unique name
if ( ! function_exists( 'spectra_blocks_get_font_awesome_polyfiller' ) ) {
    function spectra_blocks_get_font_awesome_polyfiller() {
        // Function implementation
    }
}

// Backward compatibility alias (only if legacy plugin hasn't declared it)
if ( ! function_exists( 'get_spectra_font_awesome_polyfiller' ) ) {
    function get_spectra_font_awesome_polyfiller() {
        return spectra_blocks_get_font_awesome_polyfiller();
    }
}
```

This ensures:
1. **No conflicts**: Spectra Blocks uses unique function names that won't collide
2. **Works in any load order**: Both plugins can be active simultaneously
3. **Backward compatibility**: Aliases maintain compatibility when legacy plugin isn't active
4. **Smooth migration**: Users can have both plugins active during transition

### Files Requiring Protection

When adding or modifying global functions, always check:
- `classes/utils.php` - Utility functions
- Any file with standalone functions (not class methods)
- Functions that exist in both plugins

### Development Guidelines

1. **Use unique function names**: Prefix all new global functions with `spectra_blocks_*`
2. **Add backward compatibility aliases**: For functions that might be called by external code
3. **Always use `function_exists()` checks**: Wrap both primary functions and aliases
4. **Test with both plugins active**: Ensure no conflicts regardless of load order
5. **No load order dependency**: Code should work whether loaded first or second

### Function Naming Convention

- **New functions**: `spectra_blocks_*` (e.g., `spectra_blocks_get_post_assets()`)
- **Legacy aliases**: Original names only if legacy plugin hasn't declared them
- **Internal calls**: Always use the new `spectra_blocks_*` function names

### Testing Checklist

Before committing function changes:
- [ ] Uses unique `spectra_blocks_*` naming
- [ ] Wrapped in `function_exists()` check
- [ ] Has backward compatibility alias (if needed for external usage)
- [ ] Tested with both plugins active
- [ ] Tested with only spectra-blocks active
- [ ] No fatal errors on plugin activation
- [ ] Spectra Blocks functionality works correctly

## Related Files

- `/docs/V3-CLAUDE.md` - Comprehensive development guide for Spectra v3
- `classes/utils.php` - Main utility functions file
- `ultimate-addons-for-gutenberg.php` - Main plugin file

## Commands

See `/docs/V3-CLAUDE.md` for full list of development commands and architecture details.
