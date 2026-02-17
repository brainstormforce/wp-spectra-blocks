# Dev Guide to Spectra 3 #
Hey there, this is Zane. That guy at Spectra.
If you're reading this, then you're in the WIP branch for Spectra 3.

Here's a brief rundown of the standards I've set from the get-go for this version.
If I add something new to this, I'll be updating this readme.

_Note to self: Eliminate this before going live._

## Restrictions and Configurations.
Node Version: 23.5.0
PHP Version: 8.1.26
Minimum WordPress Version: 6.6.0
- Interactivity API: <a href="https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/">Since 6.5.0</a>.
- Nested Variation Active Paths: <a href="https://developer.wordpress.org/block-editor/reference-guides/block-api/block-variations/#:~:text=Nested%20object%20paths%20are%20also%20supported%20since%20WordPress">Since 6.6.0</a>. 

## Naming Conventions ##
Anything in JS has to be named in *camelCase*, and anything in PHP has to be named in *snake_case*

All attributes have to be named commonly. Keep a simple name that can be used across any possible block in the future.

If a block has more than a single attribute of the same type (For example, 2 icons in the same block) - keep this naming convention.
- For the first attribute of a type, just use the name. *No need* to add a suffix like _primary_: *icon*
- For the second attribute of a type, add the suffix _secondary_: *iconSecondary*
- For the third attribute of a type, add the suffix _tertiary_: *iconTertiary*
- _*Note:* If you feel like you need to use the naming conventions below this point, perhaps you need to make innerblocks instead. Keep blocks the least complicated._
- For the fourth attribute of a type, add the suffix _quaternary_: *iconQuaternary*
- For the fifth attribute of a type, add the suffix _quinary_: *iconQuinary*
- For the sixth attribute of a type, add the suffix _senary_: *iconSenary*
- For the seventh attribute of a type, add the suffix _septenary_: *iconSeptenary*
- For the eight attribute of a type, add the suffix _octonary_: *iconOctonary*
- For the ninth attribute of a type, add the suffix _nonary_: *iconNonary*
- For the tenth attribute of a type, add the suffix _denary_: *iconDenary*

## Adding Dynamic CSS ##
Instead of enqueuing additional CSS assets with complex logic, we will be going the simpler approach of having CSS variables with the required values, and CSS selectors that utilize these values to avoid declaring undefined values.

Note that the CSS variables will remain common across the plugin. This ensures that if the variable exists in a root-level, it will use this value unless overwritten inside a block.

- For CSS variables, follow the naming convention *--spectra-<em>attribute-name</em>*.
- For CSS selectors, follow the naming convention *spectra-<em>attribute-name</em>*.

Currently, these are the list of attributes that will require this approach. The rest either gets handled by Core, or does not need classes.
- text-color (All variants) (All pseudo-selectors)
- background-color (All variants) (All pseudo-selectors)
- font-family (All variants)
- font-style (All variants)
- font-weight (All variants)

# Spectra v3

## PHPCS Configuration for Pattern Files

The pattern files in this project contain WordPress Gutenberg block markup with JSON-like attributes in curly braces. This can sometimes trigger false positive warnings from the WordPress VIP Minimum coding standards, particularly the `WordPressVIPMinimum.Security.Mustache.OutputNotation` rule.

### Why We Ignore This Warning

We've disabled the Mustache unescaped output notation warning for pattern files because:

1. Our pattern files don't use actual Mustache templating
2. The warning is triggered by the Gutenberg block attribute syntax which uses curly braces
3. The block markup is standard WordPress format and doesn't present a security risk

### How We Handle This

We've taken two approaches to handle this:

1. **Global Configuration**: We've created a custom `phpcs.xml` file that excludes this specific rule for pattern files
2. **File-specific Comments**: For individual pattern files, we use `phpcs:disable` and `phpcs:enable` comments with explanatory text

### Running PHPCS

When running PHPCS on this project, use the following command to apply our custom ruleset:

```bash
phpcs --standard=./phpcs.xml patterns/
```

This ensures that pattern files are correctly linted without false positives.