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

**Priority**: Spectra Blocks code must ALWAYS load first and take precedence.

**Implementation**:
All shared functions in `classes/utils.php` and similar files must be wrapped with `function_exists()` checks:

```php
if ( ! function_exists( 'get_spectra_font_awesome_polyfiller' ) ) {
    function get_spectra_font_awesome_polyfiller() {
        // Function implementation
    }
}
```

This ensures:
1. Spectra Blocks functions are declared first
2. Legacy plugin's duplicate functions are silently skipped
3. No fatal "Cannot redeclare function" errors
4. Smooth migration path for users

### Files Requiring Protection

When adding or modifying global functions, always check:
- `classes/utils.php` - Utility functions
- Any file with standalone functions (not class methods)
- Functions that exist in both plugins

### Development Guidelines

1. **Always use `function_exists()` checks** for any global function that might exist in the legacy plugin
2. **Load order matters**: Spectra Blocks should load before ultimate-addons-for-gutenberg
3. **Test with both plugins active** to ensure no conflicts
4. **Gradual migration**: Users may have both plugins during transition period

### Testing Checklist

Before committing function changes:
- [ ] Wrapped in `function_exists()` check
- [ ] Tested with both plugins active
- [ ] No fatal errors on plugin activation
- [ ] Spectra Blocks functionality works correctly

## Related Files

- `/docs/V3-CLAUDE.md` - Comprehensive development guide for Spectra v3
- `classes/utils.php` - Main utility functions file
- `ultimate-addons-for-gutenberg.php` - Main plugin file

## Commands

See `/docs/V3-CLAUDE.md` for full list of development commands and architecture details.
