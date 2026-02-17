# Spectra v3 - Public Actions and Hooks Documentation

## Overview

Spectra v3 provides a comprehensive set of public actions and filters that allow developers to extend, modify, or hook into Spectra's functionality safely and predictably. This documentation covers all available hooks, their parameters, usage examples, and best practices.

### What are Public Actions and Filters?

- **Actions (`do_action`)**: Allow you to execute custom code at specific points in Spectra's execution
- **Filters (`apply_filters`)**: Allow you to modify data before it's processed or output

### When to Use Hooks

- Adding custom logic before/after block rendering
- Modifying block attributes or configurations
- Extending block functionality without modifying core code
- Adding custom controls or tweaking default values
- Integrating with third-party plugins or services

---

## Block Management Hooks

### Block Registration

#### `block_categories_all`
**Type**: Filter  
**Purpose**: Modify block categories in the editor  
**Parameters**: 
- `$categories` (array): List of registered block categories
- `$editor_context` (WP_Block_Editor_Context): Block editor context

**Example**:
```php
add_filter( 'block_categories_all', function( $categories ) {
    // Add custom category before Spectra
    array_unshift( $categories, [
        'slug'  => 'my-custom-blocks',
        'title' => 'My Custom Blocks',
        'icon'  => 'star-filled'
    ] );
    return $categories;
}, 9999998 ); // Lower priority than Spectra's 9999999
```

#### `block_type_metadata_settings`
**Type**: Filter  
**Purpose**: Configure block settings during registration  
**Parameters**:
- `$settings` (array): Block settings from metadata
- `$metadata` (array): Block metadata from block.json

**Example**:
```php
add_filter( 'block_type_metadata_settings', function( $settings, $metadata ) {
    if ( isset( $metadata['name'] ) && strpos( $metadata['name'], 'spectra/' ) === 0 ) {
        // Add custom render callback for all Spectra blocks
        $original_callback = $settings['render_callback'] ?? null;
        $settings['render_callback'] = function( $attributes, $content, $block ) use ( $original_callback ) {
            // Custom logic before rendering
            do_action( 'my_before_spectra_block_render', $block );
            
            $output = $original_callback ? $original_callback( $attributes, $content, $block ) : $content;
            
            // Custom logic after rendering
            return apply_filters( 'my_spectra_block_output', $output, $block );
        };
    }
    return $settings;
}, 12, 2 );
```

---

## Extension Hooks

### Extension Asset Loading

#### `spectra_3_extensions_editor_assets`
**Type**: Action  
**Purpose**: Fires after enqueuing editor assets for extensions  
**Parameters**:
- `$folder_name` (string): Extension folder name
- `$asset_file` (array): Asset file data with dependencies and version

**Example**:
```php
add_action( 'spectra_3_extensions_editor_assets', function( $folder_name, $asset_file ) {
    if ( 'animations' === $folder_name ) {
        // Add custom script for animations extension
        wp_enqueue_script(
            'my-animation-enhancer',
            plugin_dir_url( __FILE__ ) . 'assets/animation-enhancer.js',
            [ 'spectra-3-extension-animations-editor' ],
            '1.0.0',
            true
        );
    }
}, 10, 2 );
```

---

## Block Rendering Hooks

### Animation Extension

#### `render_block`
**Type**: Filter  
**Purpose**: Modify block content during rendering (used by animations extension)  
**Parameters**:
- `$block_content` (string): The rendered block HTML content
- `$block` (array): The complete block data

**Example**:
```php
add_filter( 'render_block', function( $block_content, $block ) {
    // Add custom animation attributes to Spectra blocks
    if ( isset( $block['blockName'] ) && strpos( $block['blockName'], 'spectra/' ) === 0 ) {
        if ( isset( $block['attrs']['customAnimation'] ) ) {
            $processor = new WP_HTML_Tag_Processor( $block_content );
            if ( $processor->next_tag() ) {
                $processor->set_attribute( 'data-custom-animation', $block['attrs']['customAnimation'] );
                return $processor->get_updated_html();
            }
        }
    }
    return $block_content;
}, 15, 2 ); // After Spectra's animation processing (priority 10)
```

### Responsive Controls

#### `render_block_data`
**Type**: Filter  
**Purpose**: Process block data before rendering  
**Parameters**:
- `$block` (array): Block data from render_block_data filter

**Example**:
```php
add_filter( 'render_block_data', function( $block ) {
    // Modify responsive controls for specific blocks
    if ( isset( $block['blockName'] ) && $block['blockName'] === 'spectra/container' ) {
        if ( isset( $block['attrs']['responsiveControls'] ) ) {
            // Add custom responsive breakpoint
            $block['attrs']['responsiveControls']['xl'] = [
                'layout' => [
                    'type' => 'flex',
                    'orientation' => 'horizontal'
                ]
            ];
        }
    }
    return $block;
}, 15 ); // After Spectra's processing
```

#### `spectra_blocks_responsive_default_layout`
**Type**: Filter  
**Purpose**: Modify default layout configurations for responsive controls  
**Parameters**:
- `$blocks_default_layout` (array): Default layout configurations for blocks

**Example**:
```php
add_filter( 'spectra_blocks_responsive_default_layout', function( $default_layouts ) {
    // Customize default layout for container blocks
    $default_layouts['spectra/container']['layout']['justifyContent'] = 'space-between';
    
    // Add default layout for custom block
    $default_layouts['my-plugin/custom-block'] = [
        'layout' => [
            'type' => 'flex',
            'orientation' => 'vertical',
            'justifyContent' => 'center'
        ]
    ];
    
    return $default_layouts;
} );
```

#### `spectra_enable_css_cache`
**Type**: Filter  
**Purpose**: Control whether responsive CSS caching is enabled  
**Parameters**:
- `$enable_cache` (bool): Whether to enable caching (default: true)

**Example**:
```php
// Disable CSS caching during development
add_filter( 'spectra_enable_css_cache', function( $enable_cache ) {
    return defined( 'WP_DEBUG' ) && WP_DEBUG ? false : $enable_cache;
} );
```

#### `spectra_responsive_attr_definitions`
**Type**: Filter  
**Purpose**: Modify responsive attribute definitions for block-specific CSS generation  
**Parameters**:
- `$attr_definitions` (array): Block attribute definitions mapping

**Example**:
```php
add_filter( 'spectra_responsive_attr_definitions', function( $attr_definitions ) {
    // Add custom responsive attributes for a custom block
    $attr_definitions['my-plugin/custom-block'] = array(
        'customSize' => array(
            'property' => 'font-size',
            'default'  => '16px',
        ),
        'customColor' => array(
            'property' => 'color',
            'selector' => ' .custom-element',
        ),
    );
    
    return $attr_definitions;
} );
```

---

## Spectra Pro v2 Hooks

### Extension Management

#### `spectra_pro_2_extensions_editor_assets`
**Type**: Action  
**Purpose**: Fires after enqueuing editor assets for Spectra Pro v2 extensions  
**Parameters**:
- `$handle` (string): The script handle
- `$folder_name` (string): Extension folder name
- `$asset_file` (array): Asset file data with dependencies and version

**Example**:
```php
add_action( 'spectra_pro_2_extensions_editor_assets', function( $handle, $folder_name, $asset_file ) {
    if ( 'global-styles' === $folder_name ) {
        // Add custom localization for global styles extension
        wp_localize_script( $handle, 'myCustomGSData', array(
            'customSettings' => get_option( 'my_custom_gs_settings' ),
        ) );
    }
}, 10, 3 );
```

### Global Styles

#### `spectra_pro_gs_theme_colors`
**Type**: Filter  
**Purpose**: Modify theme colors for Global Styles system  
**Parameters**:
- `$formatted_theme_colors` (array): Array of formatted theme colors

**Example**:
```php
add_filter( 'spectra_pro_gs_theme_colors', function( $formatted_theme_colors ) {
    // Add custom theme color definitions
    $formatted_theme_colors[] = array(
        'label'   => 'Custom Accent',
        'value'   => '#ff6b35',
        'default' => 'accent',
    );
    
    return $formatted_theme_colors;
} );
```

### REST API

#### `spectra_pro_rest_api_get_controllers`
**Type**: Filter  
**Purpose**: Modify REST API controllers for Spectra Pro v2  
**Parameters**:
- `$controllers` (array): Array of controller classes

**Example**:
```php
add_filter( 'spectra_pro_rest_api_get_controllers', function( $controllers ) {
    // Add custom REST API controller
    $controllers[] = MyCustomController::class;
    
    return $controllers;
} );
```

### Loop Builder

#### `spectra_pro_loop_builder_query_args`
**Type**: Filter  
**Purpose**: Modify the database query (WP_Query) used to fetch posts for the Loop Builder block. This allows you to aggressively filter content based on complex rules like "current author", "timeframes", or "custom fields."  
**Parameters**:
- `$query_args` (array): standard WP_Query arguments
- `$block` (WP_Block): The block instance (useful for conditional logic per block)

**Scenarios**:

**A: "Author's Other Works" (Dynamic Author Filter)**
*   **Goal**: On a Single Post page, show other posts by the *same author*, excluding the current post.
*   **Result**: The loop displays a tailored list of the author's other articles.

```php
add_filter( 'spectra_pro_loop_builder_query_args', function( $query_args, $block ) {
    // Apply only on single post pages
    if ( is_single() ) {
        // Dynamic: get current post's author ID
        $query_args['author'] = get_the_author_meta( 'ID' );
        // Exclude the current post itself
        $query_args['post__not_in'] = [ get_the_ID() ];
    }
    return $query_args;
}, 10, 2 );
```

**B: "Trending Now" (Time-Bound Content)**
*   **Goal**: Show only posts published in the last 48 hours.
*   **Result**: The loop acts as a "Fresh Feed" or "Trending" section.

```php
add_filter( 'spectra_pro_loop_builder_query_args', function( $query_args ) {
    $query_args['date_query'] = array(
        array(
            'after' => '48 hours ago',
        ),
    );
    return $query_args;
} );
```

**C: Dynamic "Featured" Filtering (URL Parameters)**
*   **Goal**: Filter the loop based on a URL parameter (e.g., `?featured=1`).
*   **Result**: Allows you to create dynamic landing pages where the content changes based on the link clicked.

```php
add_filter( 'spectra_pro_loop_builder_query_args', function( $query_args ) {
    // Check if '?featured=1' is in the URL
    if ( isset( $_GET['featured'] ) && '1' === $_GET['featured'] ) {
        $query_args['meta_query'] = array(
            array(
                'key'     => 'is_featured_item', // Setup in ACF or Custom Fields
                'value'   => '1',
                'compare' => '=',
            ),
        );
    }
    return $query_args;
} );
```

**D: Filter by Custom Field Status**
*   **Goal**: Only show posts where a custom field (like `featured_status`) is set to `active`.
*   **Result**: Automatically filters out "inactive" items without changing post status to Draft.

```php
add_filter( 'spectra_pro_loop_builder_query_args', function( $args, $block ) {
    $args['meta_key'] = 'featured_status';
    $args['meta_value'] = 'active';
    return $args;
}, 10, 2 );
```

---

#### `spectra_pro_loop_builder_item_classes`
**Type**: Filter  
**Purpose**: Add custom CSS classes to individual post items *inside* the loop. This enables conditional styling for specific items (like the first post, or out-of-stock products).  
**Parameters**:
- `$classes` (array): List of CSS classes for the item
- `$post_id` (int): The ID of the post currently being rendered
- `$block` (WP_Block): The block instance
- `$query` (WP_Query): The full query object

**Scenarios**:

**A: Highlight the First Post (Hero Style)**
*   **Goal**: Make the first post in the loop stand out (larger image, different background).
*   **Result**: The first item gets the class `spectra-hero-post`, which you can style with CSS.

```php
add_filter( 'spectra_pro_loop_builder_item_classes', function( $classes, $post_id, $block, $query ) {
    // Check if it is the very first post (index 0)
    if ( 0 === $query->current_post ) {
        $classes[] = 'spectra-hero-post';
    }
    return $classes;
}, 10, 4 );
```

**B: Dim Out-of-Stock Products**
*   **Goal**: Visually indicate unavailability for WooCommerce products.
*   **Result**: Out-of-stock items get the `spectra-out-of-stock` class (e.g., opacity: 0.5).

```php
add_filter( 'spectra_pro_loop_builder_item_classes', function( $classes, $post_id ) {
    // Ensure we are dealing with a product
    if ( 'product' !== get_post_type( $post_id ) ) {
        return $classes;
    }

    $product = wc_get_product( $post_id );
    if ( $product && ! $product->is_in_stock() ) {
        $classes[] = 'spectra-out-of-stock';
    }

    return $classes;
}, 10, 2 );
```

---

### Loop Builder Filter

#### `spectra_pro_loop_builder_filter_terms`
**Type**: Filter  
**Purpose**: Modify the list of categories or tags shown in the Loop Builder's **Category Filter** bar. You can rename terms, reorder them, or hide specific ones.  
**Parameters**:
- `$terms` (array): Array of term objects (categories/tags)
- `$block` (WP_Block): The block instance
- `$filter_type` (string): The UI style of the filter ('select', 'checkbox', or 'button')

**Scenarios**:

**A: Rename "Uncategorized" to "General News"**
*   **Goal**: Fix ugly default category names on the front end without changing them in the database.
*   **Result**: Users see "General News" in the filter bar.

```php
add_filter( 'spectra_pro_loop_builder_filter_terms', function( $terms, $block, $filter_type ) {
    foreach ( $terms as $term ) {
        if ( 'Uncategorized' === $term->name ) {
            $term->name = __( 'General News', 'text-domain' );
        }
    }
    return $terms;
}, 10, 3 );
```

**B: Sort Filter Terms by Popularity (Post Count)**
*   **Goal**: Show the most popular categories first in the filter list.
*   **Result**: Categories with more posts appear at the start of the list.

```php
add_filter( 'spectra_pro_loop_builder_filter_terms', function( $terms, $block, $filter_type ) {
    usort( $terms, function( $a, $b ) {
        // Sort descending by count
        return $b->count - $a->count;
    });
    return $terms;
}, 10, 3 );
```

**C: Hide Inactive Markets (Meta Field Filter)**
*   **Goal**: Hide terms from the filter bar if they are marked as "inactive" via a custom field.
*   **Result**: Only relevant/active categories are clickable.

```php
add_filter( 'spectra_pro_loop_builder_filter_terms', function( $terms, $block, $filter_type ) {
    // Apply logic only for 'button' style filters
    if ( 'button' !== $filter_type ) {
        return $terms;
    }

    return array_filter( $terms, function( $term ) {
        // Check ACF field 'is_active' on the term
        $is_active = get_field( 'is_active', 'term_' . $term->term_id );
        return ( false !== $is_active );
    });
}, 10, 3 );
```

---

### Loop Builder Sort

#### `spectra_pro_loop_builder_sort_options`
**Type**: Filter  
**Purpose**: Add new custom sorting options to the dropdown menu in the **Sort Block** (Loop Builder child block).  
**Important**: This filter only adds the option to the visual list. To make the sorting actually *work*, you must also use the `spectra_pro_loop_builder_query_args` filter to handle the logic. 
**Parameters**:
- `$options` (array): key-value pairs of sorting options (`value` => `Label`)
- `$block` (WP_Block): The block instance

**Scenarios**:

**A: Sort by "Most Discussed" (Comment Count)**
*   **Goal**: Allow users to sort posts by the number of comments.
*   **Result**: The "Sort By" dropdown now includes "Most Discussed", and selecting it sorts posts by engagement.

**Step 1: Add option to Sort Block UI**
```php
add_filter( 'spectra_pro_loop_builder_sort_options', function( $options, $block ) {
    // Key structure: 'orderby_param|order'
    // This adds the item to the Sort Block's dropdown list
    $options['comment_count|desc'] = __( 'Most Discussed', 'text-domain' );
    return $options;
}, 10, 2 );
```

**Step 2: Apply Sorting Logic**
```php
add_filter( 'spectra_pro_loop_builder_query_args', function( $args, $block ) {
    // Connect the UI selection to actual Query parameters
    if ( isset( $args['orderby'] ) && 'comment_count' === $args['orderby'] ) {
        $args['orderby'] = 'comment_count';
    }
    return $args;
}, 10, 2 );
```

**B: Sort by "Highest Rated" (Custom Meta Field)**
*   **Goal**: Sort items by a numeric custom field (e.g., `product_rating`).
*   **Result**: A "Highest Rated" option appears in the Sort Block, sorting items by their rating value.

**Step 1: Add option to Sort Block UI**
```php
add_filter( 'spectra_pro_loop_builder_sort_options', function( $options, $block ) {
    $options['rating|desc'] = __( 'Highest Rated', 'text-domain' );
    return $options;
}, 10, 2 );
```

**Step 2: Apply Sorting Logic**
```php
add_filter( 'spectra_pro_loop_builder_query_args', function( $args, $block ) {
    // If our custom 'rating' sort is selected
    if ( isset( $args['orderby'] ) && 'rating' === $args['orderby'] ) {
        $args['meta_key'] = 'product_rating';
        // 'meta_value_num' sorts numerically (1, 2, 10) instead of alphabetically (1, 10, 2)
        $args['orderby']  = 'meta_value_num'; 
    }
    return $args;
}, 10, 2 );
```

---

## Block-Specific Hooks

### Countdown Block

#### `spectra_countdown_context`
**Type**: Filter  
**Purpose**: Modify countdown block context before rendering  
**Parameters**:
- `$countdown_context` (array): The countdown context data
- `$attributes` (array): The block attributes

**Context Structure**:
```php
$countdown_context = [
    'endDateTime' => '2024-12-31T23:59:59',
    'showDays'    => true,
    'showHours'   => true,
    'showMinutes' => true,
    'showSeconds' => true,
    'labels'      => [
        'dayLabel'     => 'Day',
        'daysLabel'    => 'Days',
        'hourLabel'    => 'Hour',
        'hoursLabel'   => 'Hours',
        'minuteLabel'  => 'Minute',
        'minutesLabel' => 'Minutes',
        'secondLabel'  => 'Second',
        'secondsLabel' => 'Seconds'
    ],
    'countdown'   => [
        'days'      => '00',
        'hours'     => '00',
        'minutes'   => '00',
        'seconds'   => '00',
        'isExpired' => false
    ]
];
```

**Example**:
```php
add_filter( 'spectra_countdown_context', function( $context, $attributes ) {
    // Add custom timezone handling
    if ( isset( $attributes['timezone'] ) ) {
        $context['timezone'] = $attributes['timezone'];
    }
    
    // Customize labels based on language
    if ( get_locale() === 'es_ES' ) {
        $context['labels'] = [
            'dayLabel'     => 'Día',
            'daysLabel'    => 'Días',
            'hourLabel'    => 'Hora',
            'hoursLabel'   => 'Horas',
            'minuteLabel'  => 'Minuto',
            'minutesLabel' => 'Minutos',
            'secondLabel'  => 'Segundo',
            'secondsLabel' => 'Segundos'
        ];
    }
    
    return $context;
}, 10, 2 );
```

### Slider Block

#### `spectra_slider_params`
**Type**: Filter  
**Purpose**: Modify Swiper parameters for slider blocks  
**Parameters**:
- `$swiper_params` (array): Swiper configuration parameters
- `$attributes` (array): Block attributes

**Example**:
```php
add_filter( 'spectr# Spectra v3 Hooks and Actions Documentation

## Quick Start Guide

### What are Hooks?
Hooks let you modify how Spectra blocks work without changing the core code. Think of them as "connection points" where you can add your own functionality.

### Difficulty Levels
- 🟢 **Beginner**: Copy-paste examples, basic changes
- 🟡 **Intermediate**: Some PHP knowledge needed
- 🔴 **Advanced**: Complex integrations

### Most Common Use Cases
1. Change text labels (🟢 Beginner)
2. Add custom styles (🟢 Beginner) 
3. Modify block behavior (🟡 Intermediate)
4. Create custom integrations (🔴 Advanced)

---

## 🟢 Beginner Examples

### Change Countdown Labels
**What it does**: Replace "Days", "Hours" etc. with your own text

```php
// Simple label changes
add_filter( 'spectra_countdown_context', function( $context ) {
    $context['labels'] = [
        'days' => 'Days Left',
        'hours' => 'Hours',
        'minutes' => 'Minutes', 
        'seconds' => 'Seconds'
    ];
    return $context;
} );
```

### Add Custom Styles
**What it does**: Load your CSS when Spectra blocks are used

```php
// Load custom styles for container blocks
add_action( 'wp_enqueue_scripts', function() {
    if ( has_block( 'spectra/container' ) ) {
        wp_enqueue_style( 'my-container-styles', 'path/to/styles.css' );
    }
} );
```

---

## 🟡 Intermediate Examples

### Modify Block Content
**What it does**: Add custom HTML to any Spectra block

```php
add_filter( 'render_block', function( $content, $block ) {
    // Only affect Spectra blocks
    if ( strpos( $block['blockName'] ?? '', 'spectra/' ) !== 0 ) {
        return $content;
    }
    
    // Add custom wrapper
    return '<div class="my-wrapper">' . $content . '</div>';
}, 10, 2 );
```

### Customize Countdown Behavior
**What it does**: Add urgency styling based on time remaining

```php
add_filter( 'spectra_countdown_context', function( $context, $attributes ) {
    // Add urgency classes
    $end_date = strtotime( $attributes['endDateTime'] ?? '' );
    $time_left = $end_date - current_time( 'timestamp' );
    
    if ( $time_left < DAY_IN_SECONDS ) {
        $context['urgencyClass'] = 'countdown-urgent';
    }
    
    return $context;
}, 10, 2 );
```

### Modify Slider Settings
**What it does**: Add fade effects to sliders

```php
add_filter( 'spectra_slider_params', function( $params, $attributes ) {
    // Add fade effect
    if ( isset( $attributes['enableFade'] ) ) {
        $params['effect'] = 'fade';
        $params['fadeEffect'] = ['crossFade' => true];
    }
    return $params;
}, 10, 2 );
```

---

## 🔴 Advanced Examples

### Complex Slider Customization
**What it does**: Add advanced Swiper features

```php
add_filter( 'spectra_slider_params', function( $params, $attributes ) {
    // Respect user motion preferences
    if ( isset( $attributes['respectReducedMotion'] ) && $attributes['respectReducedMotion'] ) {
        $params['autoplay'] = false;
    }
    
    // Add custom effects
    if ( isset( $attributes['customEffect'] ) ) {
        $params['effect'] = $attributes['customEffect'];
    }
    
    return $params;
}, 10, 2 );
```

### Add Swiper Modules
**What it does**: Enable additional Swiper features

```php
add_filter( 'spectra_slider_modules', function( $modules, $attributes ) {
    if ( isset( $attributes['showThumbnails'] ) ) {
        $modules[] = 'Thumbs';
    }
    return $modules;
}, 10, 2 );
```

### Add Custom Icons
**What it does**: Add your own icons to the icon picker

```php
add_filter( 'spectra_icon_chunks', function( $icon_chunks ) {
    $custom_icons = [
        'my-icon' => [
            'svg' => [
                'solid' => [
                    'path' => 'M12 2L2 7v10c0 5.55 3.84 10 9 11z',
                    'width' => 24,
                    'height' => 24
                ]
            ]
        ]
    ];
    
    $icon_chunks[] = $custom_icons;
    return $icon_chunks;
} );
```

### Load Editor Assets
**What it does**: Add custom scripts/styles to the block editor

```php
add_action( 'enqueue_block_editor_assets', function() {
    wp_enqueue_script(
        'my-editor-script',
        'path/to/script.js',
        [ 'wp-blocks' ],
        '1.0.0'
    );
} );
```

### Track Block Usage
**What it does**: Save metadata when Spectra blocks are used

```php
add_action( 'save_post', function( $post_id, $post ) {
    if ( has_blocks( $post->post_content ) ) {
        $blocks = parse_blocks( $post->post_content );
        
        foreach ( $blocks as $block ) {
            if ( strpos( $block['blockName'] ?? '', 'spectra/' ) === 0 ) {
                update_post_meta( $post_id, '_has_spectra_blocks', true );
                break;
            }
        }
    }
}, 25, 2 );
```

---

## Safety Guidelines

### Always Check Data
```php
// Good: Check if data exists
add_filter( 'spectra_countdown_context', function( $context, $attributes ) {
    if ( ! is_array( $context ) ) {
        return $context;
    }
    // Safe to modify
    return $context;
}, 10, 2 );
```

### Use Correct Priorities
```php
// Good: Run after Spectra (priority 15+)
add_filter( 'render_block', 'my_function', 15, 2 );

// Avoid: Running before Spectra (priority 5)
add_filter( 'render_block', 'my_function', 5, 2 );
```

### Only Process Spectra Blocks
```php
add_filter( 'render_block', function( $content, $block ) {
    // Exit early if not a Spectra block
    if ( strpos( $block['blockName'] ?? '', 'spectra/' ) !== 0 ) {
        return $content;
    }
    // Your code here
    return $content;
}, 10, 2 );
```

### Performance Tips
- Only load assets when blocks are present
- Use `has_block()` to check for specific blocks
- Test on staging sites first
- Document your customizations

---

## Complete Hook Reference

### Block Content Hooks
- `render_block` - Modify any block's HTML output
- `spectra_countdown_context` - Change countdown data
- `spectra_slider_params` - Modify slider settings
- `spectra_slider_modules` - Add Swiper modules
- `spectra_icon_chunks` - Add custom icons

### Asset Loading Hooks  
- `wp_enqueue_scripts` - Load frontend assets
- `enqueue_block_editor_assets` - Load editor assets

### Post Management Hooks
- `save_post` - Process posts when saved

### Need Help?
- Test changes on staging sites first
- Check WordPress and PHP error logs
- Use browser developer tools to debug
- Contact support for complex integrations
```

*This documentation covers Spectra v3.0.0+. All hooks are stable and safe to use.*