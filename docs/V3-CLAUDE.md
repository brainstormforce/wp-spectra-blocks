# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Spectra v3 is a WordPress plugin that creates interactive Gutenberg blocks using the WordPress Interactivity API. This is a rewrite/modernization effort for the Ultimate Addons for Gutenberg plugin.

**Requirements:**
- Node.js: 23.5.0
- PHP: 8.1.26+
- WordPress: 6.6.0+

## Development Commands

**Build & Development:**
- `npm run build` - Production build with experimental modules and PHP copying
- `npm run build:fresh` - Clean build (removes existing build files first)
- `npm run start` - Development server with hot reloading
- `npm run start:fresh` - Clean development start

**Code Quality:**
- `npm run format` - Format code using wp-scripts
- `npm run lint:css` - Lint CSS/SCSS files
- `npm run lint:js` - Lint JavaScript files
- `phpcs --standard=./phpcs.xml patterns/` - Lint PHP pattern files

**Other:**
- `npm run packages-update` - Update WordPress packages
- `npm run plugin-zip` - Create plugin ZIP for distribution

## Architecture

**Frontend (JavaScript):**
- `src/blocks/` - 40+ Gutenberg block implementations
- Each block has: `index.js` (registration), `edit.js` (editor), `save.js` (frontend), `render.js` (dynamic)
- Uses WordPress Interactivity API for interactive functionality
- Built with `@wordpress/scripts` and webpack

**Backend (PHP):**
- `includes/` - Core PHP classes and functionality
- `includes/Blocks/` - Server-side block registration and rendering
- `includes/Extensions/` - Plugin extensions and features
- Uses autoloading and singleton pattern

**Assets:**
- `assets/` - Static assets (images, fonts, etc.)
- `build/` - Compiled JavaScript and CSS files (generated)

## Coding Standards

**Naming Conventions:**
- JavaScript: camelCase
- PHP: snake_case
- CSS Variables: `--spectra-{attribute-name}`
- CSS Classes: `spectra-{attribute-name}`

**Block Attributes:**
- Use common names that work across blocks
- Multiple attributes of same type: `icon`, `iconSecondary`, `iconTertiary`, etc.

**Dynamic CSS:**
- Use CSS variables instead of enqueueing additional CSS files
- Variables are scoped globally but can be overridden per block
- Focus on: text-color, background-color, font-family, font-style, font-weight

**PHPCS for Patterns:**
- Pattern files contain Gutenberg block markup that triggers false Mustache warnings
- Use custom `phpcs.xml` ruleset when linting patterns
- The curly brace syntax is standard Gutenberg format, not actual templating

## Analytics Integration

Spectra v3 leverages the existing BSF Analytics library from the parent Spectra 2.x.x implementation for collecting analytical data.

**BSF Analytics Setup:**
- Analytics are configured in the main `../classes/class-uagb-loader.php:79-112` using `BSF_Analytics_Loader`
- The library is loaded from `../lib/bsf-analytics/` directory
- Entity configuration includes product name, deactivation surveys, and opt-in settings

**Adding New Analytical Points:**

1. **Global Settings Data** (`../classes/class-uagb-loader.php:726-782`):
   - Add new global configuration options to the `global_settings_data()` method
   - Use `get_option()` to retrieve settings values
   - Follow existing pattern: `$global_data['setting_name'] = get_option( 'option_name' );`

2. **Block Status Tracking** (`../classes/class-uagb-loader.php:688-718`):
   - Block enable/disable status is automatically tracked via `create_block_status_array()` method
   - New blocks are automatically included in analytics when registered

3. **Custom Stats Integration** (`../classes/class-uagb-loader.php:791-800`):
   - Add custom analytical data through the `spectra_get_specific_stats()` method
   - Hook into the `bsf_core_stats` filter (line 114)
   - Merge custom data with existing analytics using `array_merge_recursive()`

4. **UTM Analytics** (`../lib/class-uagb-utm-analytics.php`):
   - Separate UTM tracking system loads from `../lib/utm-analytics/`
   - Handles campaign and referral tracking
   - Automatically loads latest version via version comparison

**Spectra 3 Analytics Integration:**

Spectra 3 includes a comprehensive analytics system that automatically integrates with the parent BSF Analytics:

### Core Analytics Components

1. **Block Usage Tracking** (`includes/Analytics/BlockUsageTracker.php`):
   - **Multi-Plugin Support**: Tracks both Spectra 3 (`spectra/`) and Spectra Pro (`spectra-pro/`) blocks
   - **Root-Level Detection**: Only tracks user-insertable blocks, not auto-generated child blocks
   - **Usage Statistics**: Collects block usage counts, post statistics, adoption rates, and engagement levels
   - **Security Filters**: Multiple filter hooks for secure block validation and tracking control
   - **Automatic Integration**: Hooks into `save_post` action and `bsf_core_stats` filter

2. **Analytics Manager** (`includes/Analytics/AnalyticsManager.php`):
   - Central hub for all Spectra 3 analytics functionality
   - Provides easy extension points for adding new analytical data
   - Includes theme compatibility and environment data

### Security & Filter System

**Available Security Filters:**
- `spectra_analytics_allow_block_tracking` - Control individual block tracking with validation
- `spectra_analytics_allow_root_block` - Control root block inclusion in analytics
- `spectra_pro_blocks_directory` - Custom directory for Spectra Pro blocks
- `spectra_analytics_include_pro_blocks` - Toggle Spectra Pro block inclusion
- `spectra_pro_safe_analytics_blocks` - Customize safe Pro block whitelist
- `spectra_analytics_tracked_blocks` - Modify final tracked blocks array

**Security Implementation:**
- Whitelist approach: Only predefined safe blocks are tracked
- File system security: Directory traversal prevention and read validation
- Input sanitization: All block names and paths validated before processing
- Multi-layer validation: Block name, prefix, and metadata checks

### Spectra Pro Integration

**Pro Plugin Analytics** (`spectra-pro/spectra-pro-v2/includes/`):

1. **AnalyticsManager.php**: Main integration manager for Pro plugin
   - Initializes analytics integration on plugin load
   - Manages Pro-specific analytics components

2. **Analytics/BlockUsageIntegration.php**: Core integration class
   - **Secure Pro Block Tracking**: Whitelist of safe Pro blocks (loop-builder, filters, pagination, etc.)
   - **Automatic Detection**: Discovers Pro plugin installation and version
   - **BSF Analytics Integration**: Adds Pro-specific data to analytics payload
   - **Cache Management**: Handles cache invalidation on plugin activation/deactivation

**Supported Pro Blocks:**
```php
// All 14 Spectra Pro blocks are safely tracked:
- loop-builder (root level)
- loop-builder-child-filter
- loop-builder-child-filter-button/checkbox/select
- loop-builder-child-pagination (+ next/prev/numbers buttons)
- loop-builder-child-search/sort/template
- loop-builder-child-no-results/reset-all-button
```

### Analytics Data Structure

**Enhanced BSF Analytics Payload:**
```php
$stats['plugin_data']['spectra'] = array(
    // Spectra 3 block usage data
    'spectra_3_blocks' => array(
        'version' => UAGB_VER,
        'numeric_values' => array(
            'total_posts_with_blocks' => 150,
            'total_block_instances' => 500,
            'unique_blocks_used' => 25,
            'total_blocks_available' => 40,
            'adoption_rate_percent' => 62.5,
        ),
        'boolean_values' => array(
            'blocks_actively_used' => true,
            'high_adoption_rate' => true,
        ),
        'top_used_blocks' => array( 'container' => 100, 'heading' => 75 ),
        'most_popular_block' => 'container',
        'user_engagement_level' => 'high', // none/low/medium/high
    ),
    
    // Spectra Pro integration data (if Pro is active)
    'spectra_pro_v2' => array(
        'version' => SPECTRA_PRO_VER,
        'active' => true,
        'blocks_directory_accessible' => true,
        'integration_active' => true,
    ),
);
```

**Block Naming Convention:**
- Spectra 3 blocks: stored as `block-name` (e.g., 'container', 'heading')
- Spectra Pro blocks: stored as `pro-block-name` (e.g., 'pro-loop-builder')

### Implementation Examples

**Adding Custom Analytics:**
```php
// In BlockUsageTracker, data is automatically collected via filters
add_filter( 'spectra_analytics_tracked_blocks', function( $blocks, $parsed_blocks ) {
    // Custom block processing logic
    return $blocks;
}, 10, 2 );

// Adding Pro-specific analytics
add_filter( 'spectra_pro_safe_analytics_blocks', function( $safe_blocks ) {
    $safe_blocks[] = 'new-pro-block';
    return $safe_blocks;
} );
```

### Extension Analytics Integration

Spectra 3 includes comprehensive extension analytics that dynamically discovers and tracks usage:

**Core Extension Analytics** (`includes/Analytics/ExtensionUsageTracker.php`):
- **Dynamic Discovery**: Automatically scans `src/extensions/` for extensions with `index.js` files
- **Consolidated Storage**: Uses single `spectra_extension_analytics` option with multi-dimensional structure:
```php
'spectra_extension_analytics' => array(
    'usage_data' => array(), // Post-specific extension usage
    'statistics' => array(
        'extensions_used' => array(),
        'posts_with_extensions' => 0,
        'most_used_extensions' => array(),
        'last_updated' => time(),
    ),
);
```
- **Extension-Specific Analytics**: Each extension can have detailed tracking via dedicated methods
- **BSF Analytics Integration**: Separate BSF analytics payload at priority 25

**Extension-Specific Tracking Methods:**
- `get_animations_specific_analytics()` - Tracks animation types, most popular animations
- `get_image_mask_specific_analytics()` - Tracks mask types, most popular masks
- Extensions without specific needs (z-index, responsive-controls) use general tracking only

**Spectra Pro Extension Analytics** (`spectra-pro/spectra-pro-v2/includes/Analytics/ExtensionUsageIntegration.php`):
- **Pro Extension Discovery**: Dynamically discovers Pro extensions from `src/extensions/`
- **Filter Integration**: Hooks into core extension analytics via filters
- **Pro-Enhanced Extensions**: Adds Pro-specific data to core extensions (animations, responsive-controls)
- **Pro-Only Extensions**: Provides specific tracking for dynamic-content and global-styles

### Adding New Extension-Specific Analytics

**For Core Spectra 3 Extensions:**

1. **Add Method in ExtensionUsageTracker.php**:
```php
/**
 * Get my-extension-specific analytics.
 *
 * @since x.x.x
 *
 * @return array Extension-specific analytics data.
 */
private function get_my_extension_specific_analytics() {
    $analytics_data = get_option( self::ANALYTICS_KEY, array() );
    $usage_data = $analytics_data['usage_data'] ?? array();
    
    $specific_metrics = array();
    $total_instances = 0;
    
    foreach ( $usage_data as $post_data ) {
        if ( ! in_array( 'my-extension', $post_data['extensions'] ?? array(), true ) ) {
            continue;
        }
        
        $total_instances++;
        // Add specific tracking logic here
        $specific_metrics['feature_type'] = ( $specific_metrics['feature_type'] ?? 0 ) + 1;
    }
    
    if ( $total_instances === 0 ) {
        return array();
    }
    
    arsort( $specific_metrics );
    
    return array(
        'total_instances' => $total_instances,
        'feature_types_used' => $specific_metrics,
        'most_popular_feature' => ! empty( $specific_metrics ) ? array_key_first( $specific_metrics ) : '',
        'unique_features' => count( $specific_metrics ),
    );
}
```

2. **Method Naming Convention**: `get_{extension_name}_specific_analytics()` where hyphens become underscores

**For Spectra Pro Extensions:**

1. **Add Filter Hook in ExtensionUsageIntegration.php**:
```php
add_filter( 'spectra_analytics_extension_specific_my-extension', array( $this, 'add_my_extension_analytics' ), 10, 2 );
```

2. **Add Method**:
```php
public function add_my_extension_analytics( $data, $usage_data ) {
    if ( ! $this->should_add_pro_extension_stats() ) {
        return $data;
    }
    
    // Add Pro-specific tracking
    return array_merge( $data, array(
        'pro_feature_usage' => $this->track_pro_features( $usage_data ),
    ) );
}
```

**For Third-Party Extensions:**
```php
add_filter( 'spectra_analytics_extension_specific_my-extension', function( $data, $usage_data ) {
    // Add third-party specific data
    $data['third_party_metrics'] = array(
        'custom_feature_usage' => count( $usage_data ),
    );
    return $data;
}, 10, 2 );
```

**Cache Management:**
- Root blocks cached for 12 hours: `spectra_root_blocks_spectra`, `spectra_root_blocks_spectra-pro`
- Block analytics cached for 1 hour: `spectra_3_comprehensive_analytics`
- Extension analytics cached for 1 hour: `spectra_3_extension_analytics`
- Available extensions cached for 6 hours: `spectra_available_extensions`
- Auto-invalidation on post saves and plugin activation changes
- Automatic cleanup when analytics disabled via `spectra_analytics_optin`

**Data Storage Optimization:**
- **Consolidated Options**: Only 2 options instead of 4+ separate ones
- **Non-autoloaded**: All analytics options stored with `autoload=no` for performance
- **Automatic Cleanup**: Data automatically deleted when user disables analytics

**Privacy & Compliance:**
- Respects user opt-in setting: `spectra_analytics_optin` (from parent plugin)
- Only collects usage patterns, no personal or content data
- Integrates with existing BSF Analytics privacy controls
- Automatic data cleanup when analytics disabled

## WordPress Integration

- Built on WordPress Interactivity API (requires WP 6.5+)
- Uses nested block variations (requires WP 6.6+)
- Follows WordPress coding standards and best practices
- Integrates with WordPress block editor and theme system
- When adding any new JSDoc comment or PHPDoc comment, for the `@since` tag always put `x.x.x`