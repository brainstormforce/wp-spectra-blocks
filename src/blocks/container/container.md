# Container Block

## Introduction

The Container Block is a powerful layout block that serves as the foundation for creating complex page layouts in WordPress. It goes beyond the standard Group block by offering advanced styling options, pre-built layout variations, background media support, and responsive controls. Use it to create sections, columns, grids, and sophisticated page structures with ease.

## How to Add or Use the Block in the Gutenberg Editor

[![Container Settings Overview](https://img.shields.io/badge/▶️-Watch%20Video-red?style=for-the-badge&logo=youtube)](https://iframe.mediadelivery.net/embed/54842/1ae84f46-6cc4-4105-9605-e1fce4b6c8d7)

1. **Adding the Block**
   - Click the **"+"** button in the editor to open the block inserter
   - Search for "Container" or navigate to the Spectra category
   - Click on the **Container Block** to add it
   
![Container variation picker](https://wpspectra.com/wp-content/uploads/2025/07/Screenshot-on-2025-07-15-at-10-03-12.png)

2. **Choosing a Layout Variation**
   - Upon adding, you'll see a variation picker with 12 pre-built layouts
   - Options include: 1-column, 2-column (50-50), 3-column, 4-column, and complex grids
   - Select a variation or start with a blank container
   - You can change the layout later using the toolbar

3. **Adding Content to Container**
   - Click inside the container to add blocks
   - Use the **"+"** button to add any block type
   - Containers can be nested for complex layouts

## Settings Panel Organization

![Container settings panel](https://wpspectra.com/wp-content/uploads/2025/07/Screenshot-on-2025-07-21-at-01-14-36.png)

The Container Block settings follow the editor panel structure. Here's what you'll find in each section:

---

## Layout Tab

### Layout Settings

Choose how your content is arranged inside the container:

#### Flow Layout
![Container Flow Layout](https://wpspectra.com/wp-content/uploads/2025/07/container-flow-layout.png)

**What it is:** Content flows naturally from top to bottom, like a normal document.

**When to use:**
- Simple content sections
- Text with images
- Basic page layouts

**How it works:** Items stack vertically in the order you add them. No special alignment needed.

---

#### Flex Layout
![Container Flex Layout](https://wpspectra.com/wp-content/uploads/2025/07/container-flex-layout.png)

**What it is:** Gives you control over how items line up and space out.

**When to use:**
- Side-by-side content
- Centering items
- Equal spacing between elements

**Key controls:**
- **Direction:** Arrange items left-to-right (Row) or top-to-bottom (Column)
- **Align:** Position items vertically - top, center, bottom, or stretch
- **Justify:** Space items horizontally - start, center, end, or spread out
- **Gap:** Add consistent space between all items

---

#### Grid Layout
![Container Grid Layout](https://wpspectra.com/wp-content/uploads/2025/07/container-grid-layout.png)

**What it is:** Creates a precise grid with defined rows and columns.

**When to use:**
- Complex layouts with specific positioning
- Card layouts
- Magazine-style designs

**How it works:** Define exact rows and columns, then place items anywhere on the grid.

---

#### Constrained Layout
![Container Constrained Layout](https://wpspectra.com/wp-content/uploads/2025/07/container-constrained-layout.png)

**What it is:** Centers content and limits its maximum width.

**When to use:**
- Main content areas
- Reading-focused sections
- Consistent page width

**How it works:** Content stays centered and never gets too wide, even on large screens.

---

### Container Settings

**HTML Tag**
Choose the right HTML element for your container:
- **div** - General container (default)
- **header** - Page or section header
- **footer** - Page or section footer
- **main** - Main page content
- **section** - Distinct page section
- **article** - Standalone content
- **aside** - Sidebar content
- **nav** - Navigation area

**Make Container a Link**
Turn your entire container into a clickable area. Perfect for feature cards or call-to-action sections.

### Global Styles

Apply your theme's design system:
- Use consistent colors across your site
- Apply predefined spacing values
- Maintain brand consistency

### Animation

![Container animation settings](https://wpspectra.com/wp-content/uploads/2025/07/Annotation-on-2025-07-21-at-13-25-19.png)

Add entrance animations:
- **Fade in** - Gradually appear
- **Slide up/down/left/right** - Enter from a direction
- **Zoom in/out** - Scale into view
- **Custom timing** - Control speed and delay

### Advanced

Technical options for developers:
- Custom CSS classes
- HTML anchor for direct linking
- Advanced responsive controls

---

## Styles Tab

### Color

![Container Color Settings](https://wpspectra.com/wp-content/uploads/2025/07/container-color-settings.png)

Set colors that affect all content inside:

**Text Color** - Default color for all text inside the container
**Link Color** - Color for all links within the container
**Background Color** - Solid background color behind content
**Background Gradient** - Gradient background with multiple colors
**Text Hover** - Text color when hovering over container (if container is a link)
**Background Hover** - Background color change on hover

### Typography

Control default text appearance for all content inside:
- **Font family** - Choose from available fonts
- **Font size** - Set base text size
- **Font weight** - Light, normal, bold, etc.
- **Line height** - Space between lines of text
- **Letter spacing** - Space between characters

All typography settings are responsive - set different values for desktop, tablet, and mobile.

### Dimensions

![Container Dimensions](https://wpspectra.com/wp-content/uploads/2025/07/container-dimensions-panel.png)

Control container size:

**Width & Height**
Set container size using:
- **px** - Exact pixels
- **%** - Percentage of parent
- **vw/vh** - Percentage of viewport (screen)
- **em/rem** - Based on text size

**Constraints**
- **Min Width/Height** - Smallest the container can be
- **Max Width/Height** - Largest the container can be
- **Overflow** - What happens if content doesn't fit:
  - **Visible** - Content spills out
  - **Hidden** - Content gets cut off
  - **Auto** - Scrollbars appear if needed

### Border & Shadow

![Container Border and Shadow](https://wpspectra.com/wp-content/uploads/2025/07/container-border-shadow.png)

**Border**
- Set border thickness, style (solid, dashed, dotted), and color
- Control corner roundness individually for each corner
- Different settings for each device size

**Shadow**
- Add depth with drop shadows
- Control shadow color, blur amount, and spread
- Create multiple shadow layers
- Use inset shadows for recessed effects

### Background

![Container Background Options](https://wpspectra.com/wp-content/uploads/2025/07/container-background-panel.png)

Choose your background type:

**None** - No background media
**Image** - Upload background images
**Video** - Upload background videos

## Background Media Usage

[![Container Background Settings](https://img.shields.io/badge/▶️-Watch%20Video-red?style=for-the-badge&logo=youtube)](https://iframe.mediadelivery.net/embed/54842/979de796-18d3-4114-b5fc-49bed7ac1b93)

### Setting Up Background Media

1. **Select the Container Block**
2. **Choose Background Type** in the Background settings panel:
   - **None**: No background media
   - **Image**: Upload images with position, size, and repeat controls
   - **Video**: Upload MP4 videos for dynamic backgrounds

3. **Upload Your Media**
   - Click "Add background image/video" button
   - Select from Media Library or upload new media
   - **Image recommendations**: Use WebP format, optimize for web
   - **Video recommendations**: MP4 format, <10MB, no audio for autoplay

4. **Configure Media Settings**
   - **For Images**: Adjust position (focal point), size (cover/contain/auto), and repeat options
   - **For Videos**: Video will autoplay and loop automatically
   
5. **Add Overlay** (Optional)
   - Use overlay color/gradient for better text readability
   - Adjust overlay opacity from 0-100%

---

## Layout Variations

When you first add a Container block, you can choose from these pre-built layouts:

![Container Layout Variations](https://wpspectra.com/wp-content/uploads/2025/07/container-layout-variations.png)

### Simple Layouts
Perfect for beginners:

**One Column** - Full-width single column for basic content
**Two Columns 50-50** - Two equal columns side by side
**Three Columns** - Three equal columns for features or services
**Four Columns** - Four equal columns for cards or testimonials

### Advanced Layouts
For more complex designs:

**Two Column Two Row** - Grid layout with rows and columns
**25-75 Split** - Narrow sidebar with wide content area
**75-25 Split** - Wide content with narrow sidebar
**25-50-25** - Three columns with emphasis on center
**Complex Grid** - Multi-row, multi-column combinations

You can switch between any variation after adding the block using the toolbar.

---

## Block Toolbar Options

![Container toolbar](https://wpspectra.com/wp-content/uploads/2025/07/Annotation-on-2025-07-21-at-13-19-39.png)

Quick actions available in the toolbar:
- **Change Layout** - Switch to a different variation
- **Transform** - Convert to Group or other blocks
- **Alignment** - Make container wide or full-width
- **Drag Handle** - Move the container
- **Options Menu** - More settings and actions

---

## Advanced Features

### Container as Link

![Container link settings](https://wpspectra.com/wp-content/uploads/2025/07/Annotation-on-2025-07-21-at-13-22-34.png)

Make your entire container clickable - great for feature cards or call-to-action sections:

**How to set up:**
1. In Container settings, change HTML tag to "Link"
2. Enter your URL
3. Choose if link opens in new tab
4. Set up hover effects for better user experience

**Link options:**
- **URL** - Where the link goes
- **Target** - Same window or new tab
- **Rel attributes** - For SEO and security (nofollow, noopener, etc.)

### Hover Effects

Make your containers interactive:
- **Background changes** - Different colors on hover
- **Text changes** - Different text colors on hover
- **Smooth transitions** - Animated color changes
- **Works with links** - Perfect for clickable containers

---

## Common Use Cases

![Container Use Cases](https://wpspectra.com/wp-content/uploads/2025/07/container-use-cases.png)

**Hero Sections** - Full-width container with video background and centered text
**Content Sections** - Constrained layout with max-width for readability
**Feature Grids** - Multi-column layouts showcasing services or products
**Call-to-Action Blocks** - Container as link with hover effects
**Image/Text Combinations** - Two-column layouts mixing media and content

---

## Device-Specific Settings

Most Container settings work across all device sizes:

| Setting | Desktop | Tablet | Mobile |
|---------|---------|--------|--------|
| Width/Height | ✓ | ✓ | ✓ |
| Padding/Margin | ✓ | ✓ | ✓ |
| Typography | ✓ | ✓ | ✓ |
| Border | ✓ | ✓ | ✓ |
| Gap | ✓ | ✓ | ✓ |

Use the device switcher in the editor to set different values for different screen sizes.

---

## Tips and Best Practices

### Design Guidelines
- **Consistent spacing** - Use the same padding values across similar containers
- **Mobile-first** - Design for mobile screens first, then enhance for larger screens
- **Semantic HTML** - Use appropriate tags (header, main, footer, section) for better SEO
- **Content hierarchy** - Use Container blocks to create clear content sections

### Performance Tips
- **Optimize media** - Compress background images, use WebP format when possible
- **Video sizes** - Keep background videos under 10MB for faster loading
- **Minimize nesting** - Don't nest containers too deeply
- **Test on mobile** - Always check how your containers look on small screens

---

## Troubleshooting

### Common Issues

**Background video not playing?**
- Check file format (MP4 works best)
- Ensure file size is under 10MB
- Some mobile browsers block autoplay videos

**Border radius not working with backgrounds?**
- Set Overflow to "Hidden" in container settings
- This clips the background to match the border shape

**Content overflowing container?**
- Check your Overflow setting (try "Auto" for scrollbars)
- Verify height settings aren't too restrictive
- Test on mobile devices

**Nested containers not working properly?**
- Make sure parent container has proper layout settings
- Check that child containers have appropriate dimensions
- Avoid nesting too many levels deep

---

## Frequently Asked Questions

**Q: What's the difference between Container and Group blocks?**
A: Container offers advanced features like video backgrounds, layout variations, hover states, dimension controls, and can act as a clickable link.

**Q: Can I nest containers inside each other?**
A: Yes! You can nest containers to create complex layouts. Each container maintains its own settings.

**Q: How do I make a full-height hero section?**
A: Set Height to 100vh in the dimension settings. Consider using Min Height for better mobile experience.

**Q: Can I use Container for headers and footers?**
A: Absolutely! Change the HTML tag to 'header' or 'footer' for semantic correctness and better SEO.

**Q: Why isn't my background video showing on mobile?**
A: Many mobile browsers restrict video autoplay to save battery and data. Consider providing a fallback background image.

**Q: How do I center content vertically?**
A: Use Flex layout, set Direction to Column, and choose "Center" for both Align Items and Justify Content.

---

## Compatibility and Requirements

### System Requirements
- **WordPress** 6.6.0 or higher
- **PHP** 8.1 or higher
- **Modern browsers** with CSS Grid and Flexbox support

### Performance Considerations
- Optimize background images (WebP format recommended)
- Keep video backgrounds under 10MB
- Use lazy loading for off-screen containers
- Minimize nesting depth for better performance
- Test on various devices and connection speeds