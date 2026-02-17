# SVG Animator Block

## Introduction

The SVG Animator Block allows you to add animated SVG graphics to your WordPress pages with customizable draw, fade, scale, and rotate effects. Upload an SVG file or paste SVG code to create eye-catching path drawing animations, hover effects, and scroll-triggered animations without any coding required.

## How to Add or Use the Block in the Gutenberg Editor

1. **Adding the Block**
   - Click the **"+"** button in the editor
   - Search for "SVG Animator" or navigate to the Spectra category
   - Click on the **SVG Animator Block** to add it
   - The block will display a placeholder prompting you to add an SVG

2. **Adding SVG Content**
   - **Upload**: Click "Upload SVG" to select an SVG file from the media library
   - **Code**: Switch to "Code" mode and paste SVG markup directly
   - The SVG will be sanitized automatically for security

3. **Configuring Animation**
   - Choose an animation type (Draw, Fade, Scale, Rotate)
   - Select a trigger (Viewport scroll, Page load, Hover, Click)
   - Adjust duration, delay, and easing
   - Enable loop, reverse, or yoyo playback

## Block Styling Options

### SVG Source Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Source Type** | How to provide the SVG | Upload / Code |
| **Upload SVG** | Select from media library | SVG files only |
| **SVG Code** | Paste SVG markup directly | Auto-sanitized textarea |

### Animation Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Animation Type** | The animation effect | Draw (Stroke), Fade, Scale, Rotate |
| **Trigger** | When animation starts | On Scroll (Viewport), Page Load, Hover, Click |
| **Duration** | Animation length | 100ms - 10000ms |
| **Delay** | Wait before starting | 0ms - 5000ms |
| **Easing** | Animation curve | Ease, Linear, Ease In, Ease Out, Ease In Out |
| **Path Stagger** | Delay between paths | 0ms - 2000ms |
| **Fill Behavior** | How fill appears (Draw only) | No Fill, Fill After Stroke, Show Fill |
| **Loop** | Repeat animation | On/Off toggle |
| **Reverse** | Play backwards | On/Off toggle |
| **Yoyo** | Alternate direction on loop | On/Off toggle (requires Loop) |

### Dimension Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Width** | SVG display width | px, %, vw, em, rem |
| **Height** | SVG display height | px, %, vh, em, rem |
| **Padding** | Internal spacing | Top, Right, Bottom, Left |
| **Margin** | External spacing | Top, Right, Bottom, Left |

### Color Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Stroke Color** | SVG stroke color | Color picker |
| **Stroke Hover Color** | Stroke color on hover | Color picker |
| **Fill Color** | SVG fill color | Color picker |

### Border Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Border Width** | Border thickness | px units |
| **Border Color** | Border color | Color picker |
| **Border Style** | Border appearance | Solid, Dashed, Dotted |
| **Border Radius** | Corner rounding | px, % units |
| **Box Shadow** | Drop shadow effect | Multiple shadow layers |

### Accessibility Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Accessibility Mode** | ARIA role assignment | SVG, Image, Decorative |
| **Accessibility Label** | Descriptive label | Text input (for SVG/Image modes) |

## Animation Types

### Draw (Stroke)
Animates SVG paths by drawing them from start to end using `strokeDasharray` and `strokeDashoffset`. Works with `path`, `circle`, `rect`, `polygon`, `line`, `polyline`, and `ellipse` elements.

### Fade
Animates the entire SVG opacity from 0 to 1.

### Scale
Animates the SVG transform from `scale(0)` to `scale(1)` with a center origin.

### Rotate
Animates the SVG transform from `rotate(0deg)` to `rotate(360deg)` with a center origin.

## Advanced Features

### Fill After Stroke
When using the Draw animation type, enable "Fill After Stroke" to reveal the SVG fill color with a smooth fade after the stroke animation completes.

### Path Stagger
Add a delay between animating each path element in the SVG. This creates a sequential drawing effect where paths animate one after another.

### Scroll Trigger
Uses IntersectionObserver to detect when the block enters the viewport. Animation starts when 20% of the block is visible, with a 50px bottom margin offset.

### Hover and Click Triggers
- **Hover**: Animation plays on mouseenter. If reverse is enabled, it reverses on mouseleave.
- **Click**: Animation plays on click. Clicking again resets and replays.

## Tips and Best Practices

### SVG Preparation
- Use simple, clean SVGs with well-defined paths for best draw animation results
- Ensure SVG paths have stroke attributes for draw animations
- Remove unnecessary metadata and comments from SVG code
- Use viewBox attribute for proper scaling

### Performance
- Keep SVG complexity reasonable (fewer paths = smoother animation)
- Use viewport trigger instead of load trigger when possible
- Avoid looping animations on multiple blocks simultaneously
- Consider animation duration relative to scroll speed

### Accessibility
- Set accessibility mode to "Decorative" for purely decorative SVGs
- Provide descriptive labels for meaningful SVG graphics
- Ensure animations don't cause issues for users with motion sensitivity

## Common Use Cases

1. **Logo Animations**
   - Brand logo reveal on page load
   - Logo draw effect on scroll

2. **Icon Animations**
   - Service icons that animate on scroll
   - Interactive hover-triggered icon effects

3. **Illustrations**
   - Hero section animated illustrations
   - Step-by-step process diagrams

4. **Data Visualization**
   - Animated chart elements
   - Progress indicators

## Troubleshooting

**SVG not animating?**
- Ensure SVG elements have stroke attributes for draw animations
- Check that the animation trigger is correctly configured
- Verify the SVG is rendered inline (not as an image tag)
- Try increasing the animation duration

**SVG not displaying?**
- Verify the SVG file is valid
- Check the media library upload was successful
- For code input, ensure the SVG markup is well-formed
- SVG content is sanitized; some elements may be removed for security

**Draw animation incomplete?**
- Some SVG elements without `getTotalLength()` support may not animate
- Ensure paths are not clipped or masked
- Try simplifying complex SVG paths

## Compatibility and Requirements

### System Requirements
- **WordPress**: 6.6.0 or higher
- **PHP**: 8.1 or higher
- **Browser**: Modern browsers with Web Animations API support

### Browser Support
- Chrome 36+
- Firefox 48+
- Safari 13.1+
- Edge 79+

### Theme Compatibility
- Works with all standard WordPress themes
- Supports block-based themes
- Responsive in most layouts
