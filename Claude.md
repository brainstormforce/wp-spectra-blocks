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

**Complete Separation with Unique Function Names**

To ensure both plugins can work independently and simultaneously, all `spectra-blocks` functions use unique `spectra_blocks_*` naming with NO function_exists() checks or backward compatibility aliases:

```php
// Clean, unique function - no conditional checks needed
function spectra_blocks_get_font_awesome_polyfiller() {
    // Function implementation
}
```

This ensures:
1. **Complete independence**: Each plugin uses its own uniquely-named functions
2. **No conflicts**: Different function names mean zero collision risk
3. **Works in any load order**: Both plugins can be active simultaneously
4. **Clean separation**: No shared code or dependencies between plugins
5. **Independent functionality**: Users can use features from both plugins at the same time

### Files Requiring Protection

When adding or modifying global functions, always check:
- `classes/utils.php` - Utility functions
- Any file with standalone functions (not class methods)
- Functions that exist in both plugins

### Development Guidelines

1. **Use unique function names**: ALL global functions MUST use `spectra_blocks_*` prefix
2. **NO function_exists() checks**: Functions should be declared directly without conditionals
3. **NO backward compatibility aliases**: No duplicate function names that could conflict
4. **Complete separation**: spectra-blocks and ultimate-addons-for-gutenberg are independent
5. **Update all calls**: When renaming functions, update ALL references in the codebase

### Function Naming Convention

- **All functions**: `spectra_blocks_*` (e.g., `spectra_blocks_get_post_assets()`)
- **Class names**: `Spectra_Blocks_*` (e.g., `Spectra_Blocks_Helper`)
- **File names**: `class-spectra-blocks-*.php` or descriptive names
- **NO shared names**: Never use function/class names that exist in ultimate-addons-for-gutenberg

### Testing Checklist

Before committing function changes:
- [ ] Uses unique `spectra_blocks_*` naming (no shared names with legacy plugin)
- [ ] NO function_exists() checks (clean declaration)
- [ ] NO backward compatibility aliases
- [ ] All function calls updated throughout codebase
- [ ] Tested with both plugins active simultaneously
- [ ] Tested with only spectra-blocks active
- [ ] No fatal errors on plugin activation
- [ ] Both plugins work independently with full functionality

## Related Files

- `/docs/V3-CLAUDE.md` - Comprehensive development guide for Spectra v3
- `classes/utils.php` - Main utility functions file
- `ultimate-addons-for-gutenberg.php` - Main plugin file

## Commands

See `/docs/V3-CLAUDE.md` for full list of development commands and architecture details.
