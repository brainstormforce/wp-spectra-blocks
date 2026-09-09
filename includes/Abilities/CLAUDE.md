# Abilities — Development Guide

## Adding a New Ability

1. Create a class extending `AbstractAbility` in `includes/Abilities/`:
   - Group by verb category: `create-*`, `get-*`, `list-*`, `apply-*`, `remove-*`, `update-*`, `delete-*`, `toggle-*`
2. Use the `Singleton` trait (inherited from `AbstractAbility`).
3. Register it in `AbilitiesManager` (find the registrar in this directory).

## AbstractAbility Contract

Every ability must implement:

| Method | Returns | Notes |
|--------|---------|-------|
| `get_name(): string` | `'spectra-blocks/{verb}-{noun}'` | Unique ID, no spaces |
| `get_label(): string` | Translated label | Wrap with `__( ..., 'spectra-blocks' )` |
| `get_description(): string` | Translated description | Brief, one sentence |
| `get_category(): string` | Category slug | e.g. `'spectra-blocks-content'` |
| `get_input_schema(): array` | JSON Schema array | Validated before `execute()` is called |
| `get_output_schema(): array` | JSON Schema array | Documents the return shape |
| `execute( array $params )` | `array\|WP_Error` | Core logic; return `WP_Error` on failure, never throw |

## Annotations (Override `get_annotations()` When Needed)

Default annotations auto-detect destructive based on `mode: replace` input schema default. Override explicitly when:

```php
public function get_annotations(): array {
    return [
        'readonly'    => true,   // GET via MCP (no side effects)
        'destructive' => false,
        'idempotent'  => true,   // Same input → same result
    ];
}
```

- `readonly: true` → HTTP GET, `edit_posts` capability
- `readonly: false` → HTTP POST, `edit_others_posts` capability
- `destructive: true` → HTTP DELETE

## Conventions

- **ID format:** `spectra-blocks/{verb}-{noun}` (e.g. `spectra-blocks/create-container`)
- **Namespace:** `SpectraBlocks\Abilities`
- **Singleton:** class uses `Singleton` trait — never instantiate with `new`, use `::get_instance()`
- **Return `WP_Error`** for failures — never throw exceptions
- **Block markup:** use `sanitize_block_markup()` (inherited) before returning serialized blocks to neutralize `-->` sequences that would corrupt block comments
- **Block dir path:** use `get_blocks_dir()` (inherited) to locate `build/blocks/`
- **All new classes** need `@since x.x.x` docblock tags
- **Text domain:** always `'spectra-blocks'`

## Architecture

```
includes/Abilities/
├── class-abstract-ability.php       Base class — extend this; do not modify without updating all abilities
├── class-abilities-manager.php      Registers categories + all ability instances
├── class-create-container.php       Creates spectra/container block
├── class-create-accordion.php
├── class-create-buttons.php
├── class-create-content.php         Creates spectra/content (heading/paragraph)
├── class-create-countdown.php
├── class-create-counter.php
├── class-create-google-map.php
├── class-create-icons.php
├── class-create-list.php
├── class-create-modal.php
├── class-create-popup.php
├── class-create-post.php            Creates spectra/post (query loop)
├── class-create-separator.php
├── class-create-slider.php
├── class-create-tabs.php
├── class-apply-animation.php        Applies UAGAnimationType + related attrs
├── class-apply-display-conditions.php
├── class-apply-image-mask.php
├── class-apply-responsive-conditions.php
├── class-apply-sticky.php
├── class-apply-zindex.php
├── class-remove-animation.php
├── class-remove-display-conditions.php
├── class-remove-responsive-conditions.php
├── class-remove-sticky.php
├── class-remove-block.php
├── class-get-active-colors.php      Reads current Global Styles palette
├── class-get-analytics-summary.php
├── class-get-block-activation-status.php
├── class-get-block-config.php       Returns block.json attributes for a block type
├── class-get-global-styles-config.php
├── class-get-plugin-settings.php
├── class-get-popup.php
├── class-get-post-content.php       Returns parsed blocks from a post
├── class-list-available-blocks.php  Lists all registered spectra/ blocks
├── class-list-available-google-fonts.php
├── class-list-popups.php
├── class-list-selected-fonts.php
├── class-move-block.php
├── class-duplicate-block.php
├── class-search-post-content.php
├── class-search-posts-by-block.php
├── class-set-active-colors.php
├── class-toggle-block-activation.php
├── class-toggle-popup-status.php
├── class-update-block-attributes.php
├── class-update-global-styles.php
├── class-update-plugin-setting.php
├── class-add-google-font.php
├── class-remove-google-font.php
├── class-delete-popup.php
└── class-generate-page-layout.php   Generates a full page from a text prompt
```

## Backward Compatibility Note

Extensions apply attributes with `UAG`-prefixed names (e.g. `UAGAnimationType`, `UAGDisplayConditions`). When building block markup for abilities that touch these extensions, use the exact attribute names — do not rename them.

## WP Version Guard

The `AbilitiesManager` bails if `wp_register_ability()` does not exist (WP < 6.9). Zero overhead on older sites.
