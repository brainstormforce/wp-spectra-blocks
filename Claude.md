# Spectra Blocks Plugin - Development Notes

## Overview
This plugin is a fork of Ultimate Addons for Gutenberg (UAGB), renamed to Spectra Blocks with version 0.0.1.

## Comprehensive Renaming Completed

All UAG and UAGB references have been systematically replaced throughout the entire codebase. This ensures clean separation from the original plugin and prevents naming conflicts.

### Renaming Patterns Applied

**PHP Constants:**
- `UAGB_*` → `SPECTRA_BLOCKS_*`
- `UAG_*` → `SPECTRA_*`

**PHP Classes:**
- `UAGB_*` → `Spectra_Blocks_*`
- `Uagb_*` → `Spectra_Blocks_*`

**PHP Functions:**
- `uagb_*()` → `spectra_blocks_*()`
- `uag_*()` → `spectra_*()`

**PHP Namespaces:**
- `UagAdmin` → `SpectraAdmin`
- `\UagAdmin\` → `\SpectraAdmin\`

**File Names:**
- `class-uagb-*.php` → `class-spectra-blocks-*.php`
- All PHP class files have been renamed to match the new naming convention

**String Literals:**
- `'uagb_'` → `'spectra_blocks_'`
- `"uagb_"` → `"spectra_blocks_"`
- `'uag_'` → `'spectra_'`
- Database option names and transient keys updated accordingly

## Plugin Information

- **Plugin Name:** Spectra Blocks
- **Version:** 0.0.1
- **Text Domain:** spectra-blocks
- **Main File:** spectra-blocks.php
- **Namespace:** SpectraBlocks (classes), SpectraAdmin (admin)

## Important Notes

### No UAG/UAGB References Remain
- ✅ All function names updated
- ✅ All class names updated
- ✅ All file names updated
- ✅ All constants updated
- ✅ All namespaces updated
- ✅ All string literals updated

### Plugin Structure
```
spectra-blocks/
├── admin/                    # Admin assets and views
├── admin-core/              # Admin panel (uses SpectraAdmin namespace)
├── assets/                   # Frontend assets
├── blocks-config/           # Block configuration files
├── classes/                  # Core PHP classes (uses SpectraBlocks namespace)
├── includes/                # Additional includes
├── lib/                     # Third-party libraries
└── spectra-blocks.php       # Main plugin file
```