# Slider Block

## Introduction

The Slider Block creates dynamic, responsive carousels for showcasing content, images, testimonials, or any combination of blocks. Built on the powerful Swiper.js library, it offers smooth transitions, touch support, and extensive customization options. Perfect for hero sections, product showcases, testimonial carousels, or any content that benefits from sequential presentation.

![Slider Block Preview](https://wpspectra.com/wp-content/uploads/2025/07/spectrav3-slider-block-doc-preview.png)


## How to Add or Use the Block in the Gutenberg Editor

![Slider Block Inserter](https://wpspectra.com/wp-content/uploads/2025/07/spectrav3-slider-inserter.gif)

1. **Adding the Block**
   - Click the **"+"** button to open the block inserter
   - Search for "Slider" or navigate to the Spectra category
   - Click on the **Slider Block** to add it to your page
   - The block starts with three default slides
   
2. **Adding Slides**
   - Click the **"+"** button in the slider to add a new slide
   - Each slide is a container that can hold any blocks
   - Add images, text, buttons, or complex layouts
   - Use the toolbar to manage slides
   
🎥 **[Watch Video: Adding slides](https://wpspectra.com/wp-content/uploads/2025/07/spectrav3-slider-add.gif)**

3. **Navigating Slides in Editor**
   - Click arrow buttons to move between slides
   - Use the slide selector in the toolbar
   - Drag to reorder slides
   - Delete slides using the toolbar options

## Block Toolbar Options

🎥 **[Watch Video: Slider Toolbar](https://wpspectra.com/wp-content/uploads/2025/07/spectrav3-slider-toolbar.gif)**

### Slider Container Toolbar
| Option | Description |
|--------|-------------|
| **Add Slide** | Insert new focused slide  |
| **remove Slide** | remove current focused slide |
| **Wide/Full Width** | Container width options |
| **More Options** | Additional settings |

### Individual Slide Toolbar
| Option | Description |
|--------|-------------|
| **Move Left** | Reorder slide position |
| **Move Right** | Reorder slide position |
| **Add Block** | Insert content to slide |
| **Remove Block** | Remove content to slide |
| **Change Items justification** | Align items horizontally |
| **Change Vertical alignment** | Align items vertically |


## Block Styling Options

🎥 **[Watch Video: Spectra Slider setting panel](https://wpspectra.com/wp-content/uploads/2025/07/spectrav3-slider-toolbar.gif)**

### General Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Slides Per View** | Number of visible slides | 1, 2, 3, 4, Auto |
| **Space Between** | Gap between slides | 0-100px |
| **Loop** | Continuous navigation | On/Off toggle |
| **Autoplay** | Automatic progression | On/Off toggle |
| **Autoplay Speed** | Time between transitions | 1-10 seconds |
| **Transition Speed** | Animation duration | 100-2000ms |
| **Slider Height** | Container height | Auto, px, vh, % |

### Navigation Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Show Arrows** | Display navigation arrows | On/Off toggle |
| **Previous Icon** | Left/Previous arrow icon | Icon picker |
| **Next Icon** | Right/Next arrow icon | Icon picker |
| **Arrow Size** | Navigation button size | 20-100px |
| **Icon Size** | Arrow icon size | 10-50px |
| **Arrow Distance** | Position from edges | -100 to 100px |

### Pagination Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Show Dots** | Display pagination dots | On/Off toggle |
| **Dots Position** | Vertical offset | -100 to 100px |
| **Dot Size** | Pagination dot size | Via CSS |

### Color Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Arrow Color** | Navigation arrow color | Color picker |
| **Arrow Hover Color** | Arrow color on hover | Color picker |
| **Arrow Background** | Arrow button background | Color picker |
| **Arrow Background Hover** | Background on hover | Color picker |
| **Dot Color** | Pagination dot color | Color picker |
| **Dot Hover Color** | Dot color on hover | Color picker |
| **Active Dot Color** | Current slide dot | Color picker |

### Advanced Settings

| Option | Description | Available Settings |
|--------|-------------|-------------------|
| **Pause on Hover** | Stop autoplay on hover | On/Off toggle |
| **Pause on Interaction** | Stop after user action | On/Off toggle |
| **Allow Touch Move** | Enable swipe navigation | On/Off toggle |
| **Overflow** | Content overflow behavior | Hidden, Visible |

## Responsive Behavior

<table>
<tr>
<td width="33%">

![Responsive Setting ](https://wpspectra.com/wp-content/uploads/2025/08/spectrav3-sliderresponsive-setting.png)
*Mobile Responsive Settings*

</td>

<td width="33%">

![Responsive Setting ](https://wpspectra.com/wp-content/uploads/2025/08/spectrav3-slider-responsive-setting2.png)
*Tablet Responsive Settings*

</td>

<td width="33%">

![Responsive Setting](https://wpspectra.com/wp-content/uploads/2025/08/spectrav3-slider-responsive-setting3.png)
*Desktop Responsive Settings*

</td>
</tr>
</table>


### Breakpoint Options

| Setting | Description | Options |
|---------|-------------|---------|
| **Breakpoints** | Responsive behavior | None, Default, Custom |
| **Desktop Slides** | Slides on desktop | 1-4 or Auto |
| **Tablet Slides** | Slides on tablet | 1-4 or Auto |
| **Mobile Slides** | Slides on mobile | 1-4 or Auto |

### Default Breakpoints
- **Mobile**: < 544px
- **Tablet**: 544px - 1023px
- **Desktop**: ≥ 1024px

## Common Slider Configurations

### Testimonial Slider

![Spectra v3 Slider block for testimonials](https://wpspectra.com/wp-content/uploads/2025/08/spectra-V3-testimonial.png)


Settings:
- Slides per view: 1-3 (responsive)
- Space between: 30px
- Autoplay: Disabled
- Custom arrow icons
- Center active slide

### Content Slider

🎥 **[Watch Video: Spectra content Slider](https://wpspectra.com/wp-content/uploads/2025/07/Spectrav3-slider-example.gif)**

Settings:
- Slides per view: 1
- Fixed height: 500px
- Autoplay with pause on hover
- Custom navigation styling

## Navigation Options

### Arrow Navigation

🎥 **[Watch Video: Slider Navigation Demo](https://wpspectra.com/wp-content/uploads/2025/08/slider-v3-navigation.gif)**
*Click to see the slider arrow navigation settings *

Customization options:
- **Icon Selection**: Choose from Font Awesome icons
- **Positioning**: Inside or outside slider
- **Size**: Adjust button and icon sizes separately
- **Colors**: Normal and hover states
- **Visibility**: Show on hover only (CSS)

### Pagination Dots

🎥 **[Watch Video: Slider Dots settinga](https://wpspectra.com/wp-content/uploads/2025/08/spectrav3-image-crousal.gif)**  
*Click to see the slider Dots settings in action*

Dot features:
- Click to jump to specific slide
- Active state indication
- Customizable colors
- Adjustable position
- Can be hidden on mobile

### Keyboard Navigation

The slider supports keyboard controls:
- **Arrow Keys**: Navigate next/previous
- **Home/End**: Jump to first/last slide
- **Tab**: Navigate through focusable elements

## Slide Content Options

### Simple Image Slides

🎥 **[Watch Video: Slider Navigation Demo](https://wpspectra.com/wp-content/uploads/2025/08/spectrav3-image-crousal.gif)**
*Click to see the slider navigation and carousel functionality in action*

- Add Image block to each slide
- Set consistent aspect ratios
- Use high-quality images
- Consider lazy loading


### Background Options

<table>
<tr>
<td width="50%">

![Slider Background Setting](https://wpspectra.com/wp-content/uploads/2025/08/spectrav3-slider-bacground-setting.png)
*Background Color Settings*

</td>
<td width="50%">

![Slider Gradient Setting](https://wpspectra.com/wp-content/uploads/2025/08/spectrav3-slider-graident-setting.png)
*Background Gradient Settings*

</td>
</tr>
</table>

- **Color backgrounds**: Solid or gradient
- **Image backgrounds**: With overlay options
- **Video backgrounds**: Autoplay support
- **Transparent**: For overlay effects

## Animation Integration

🎥 **[Watch Video: Slider Animation Demo](https://wpspectra.com/wp-content/uploads/2025/08/spectrav3-slider-animation.gif)**
*Click to see the slider loop demo in action*

### Using Animations with Slides

1. Enable Animation extension on slide content
2. Choose animation type (fade, slide, zoom)
3. Set animation to trigger on slide change
4. Adjust timing to match slide transitions

### Animation Best Practices
- Keep animations subtle
- Match animation speed to slide speed
- Use consistent animation types
- Test on mobile devices

## Pro Features

### Hash Navigation
![Hash Navigation](https://wpspectra.com/wp-content/uploads/2024/12/hashnavigation.png)

The Hash Navigation feature allows direct linking to specific slides using URL hash values.

**Key Benefits:**
- Direct linking to specific slides (e.g., `yoursite.com/page#slide2`)
- Seamless deep linking for presentations
- Better user experience for multi-slide content
- Maintains slide position on page refresh


**Implementation Tips:**
- Enable hash navigation in block settings
- Combine with autoplay for smooth transitions
- Perfect for product showcases and portfolios

### Custom Navigation
[![Creating custom navigation](https://img.shields.io/badge/▶️-Watch%20Video-red?style=for-the-badge&logo=youtube)](https://wpspectra.com/wp-content/uploads/2024/12/sectra-v3-slider-custom-navigation.mp4)

Custom Navigation allows any element on your page to control the slider, extending beyond traditional arrow buttons.

**Features:**
- Link any element to control slides
- Create custom thumbnail navigation
- Build interactive slide menus
- Design unique navigation experiences

**Navigation Options:**
- Next/Previous slide triggers
- Jump to specific slide
- Play/Pause controls
- Custom thumbnail strips

**Example Use Cases:**
1. Thumbnail strip navigation
2. External button controls
3. Menu-based slide selection
4. Interactive timeline navigation

## Advanced Features

### Autoplay Configuration

<table>
<tr>
<td width="50%">

![Autoplay Disabled](https://wpspectra.com/wp-content/uploads/2024/12/spectrav3-sllider-autolay.png)
*Autoplay Disabled Settings*

</td>
<td width="50%">

![Autoplay Enabled](https://wpspectra.com/wp-content/uploads/2025/08/Spectrac3-autoplay-enabled.png)
*Autoplay Enabled Settings*

</td>
</tr>
</table>

Options:
- **Speed**: 1-10 seconds between slides
- **Pause on Hover**: User-friendly option
- **Pause on Interaction**: Stops after manual navigation

### Touch and Swipe Support

Mobile gestures:
- Swipe left/right to navigate
- Pinch to zoom (when applicable)
- Touch-friendly navigation buttons

### Loop Mode

🎥 **[Watch Video: Slider loop Demo](https://wpspectra.com/wp-content/uploads/2025/08/Spectrav3-loop.gif)**
*Click to see the slider loop demo in action*

Benefits:
- Seamless continuous navigation
- No dead ends
- Better for autoplay
- Natural user experience

## Performance Optimization

### Best Practices

1. **Image Optimization**
   - Compress images before upload
   - Use appropriate formats (WebP)
   - Implement lazy loading
   - Set explicit dimensions

2. **Slide Limits**
   - Keep to 5-10 slides maximum
   - Use pagination for more content
   - Consider performance on mobile

3. **Animation Performance**
   - Limit complex animations
   - Use CSS transforms
   - Test on lower-end devices

## Accessibility Features

### Built-in Accessibility

- **ARIA Labels**: Proper labeling for screen readers
- **Role Attributes**: Semantic slider roles
- **Keyboard Navigation**: Full keyboard support
- **Focus Management**: Visible focus indicators
- **Live Regions**: Announce slide changes

### Accessibility Best Practices

1. **Alt Text**: Add to all images
2. **Heading Structure**: Maintain hierarchy
3. **Link Context**: Clear link purposes
4. **Color Contrast**: WCAG compliance
5. **Motion Control**: Respect prefers-reduced-motion

## Troubleshooting

**Slider not advancing?**
- Check if autoplay is enabled
- Verify loop setting for continuous play
- Look for JavaScript errors
- Test touch/swipe on mobile

**Navigation not showing?**
- Ensure arrows/dots are enabled
- Check color contrast
- Verify CSS isn't hiding elements
- Look for theme conflicts

**Responsive issues?**
- Check breakpoint settings
- Test actual devices
- Verify slides per view settings
- Consider content overflow

**Performance problems?**
- Optimize images
- Reduce number of slides
- Disable unnecessary animations
- Check for plugin conflicts

## Frequently Asked Questions

**Q: How many slides can I add?**
A: No hard limit, but 5-10 slides is recommended for performance.

**Q: Can I have different heights for slides?**
A: Yes, set height to 'auto' and slides adjust to content.

**Q: Can I link entire slides?**
A: Add links within slide content. For whole-slide links, use a container with link.

**Q: Do videos autoplay in slides?**
A: Background videos can autoplay. Content videos follow browser policies.

**Q: Can I use the slider for a single image?**
A: Yes, but consider using the Image block for single images.

**Q: How do I create a full-screen slider?**
A: Set height to 100vh and use full-width alignment.

## Use Cases

### Hero Sliders
![Watch Slider hero section Action](https://wpspectra.com/wp-content/uploads/2024/12/spectrav3-slider-hero-demo.png)
- Full-width promotional content
- Multiple CTAs
- Brand storytelling
- Feature highlights

### Team members Showcases
![Watch Slider portfolio team Action](https://wpspectra.com/wp-content/uploads/2024/12/spectrav3-slider-demo-core-team.png)
- Team photo galleries
- Simple Avatar Images.
- Custom Navigation for slider.


### Testimonial Carousels
![Spectra v3 Slider block for testimonials](https://wpspectra.com/wp-content/uploads/2025/08/spectra-V3-testimonial.png)
- Customer reviews
- Success stories
- Team quotes
- Social proof


## Tips and Best Practices

### Design Guidelines
1. **Slide Content Strategy**
   - Limit text to 2-3 sentences per slide
   - Use high-quality images (optimize for web)
   - Maintain consistent visual hierarchy
   - Ensure readability on all devices

2. **Navigation Best Practices**
   - Always show navigation controls
   - Make arrows large enough for touch (44x44px)
   - Position dots where they won't obscure content
   - Consider adding pause button for accessibility

3. **Performance Optimization**
   - Optimize images before upload (use WebP)
   - Lazy load off-screen slides
   - Limit to 5-7 slides maximum
   - Use srcset for responsive images

### Animation and Timing
1. **Transition Settings**
   - Keep transitions under 1 second
   - Use ease-in-out for smooth motion
   - Match speed to content complexity
   - Avoid jarring effects

2. **Autoplay Considerations**
   - Default to 5-7 seconds per slide
   - Always include pause controls
   - Stop on user interaction
   - Consider motion preferences

### Accessibility Requirements
1. **Keyboard Support**
   - Arrow keys for navigation
   - Tab through interactive elements
   - Escape to pause autoplay
   - Clear focus indicators

2. **Screen Reader Optimization**
   - Descriptive slide labels
   - Announce slide changes
   - Alternative text for images
   - Skip navigation options

### Mobile Optimization
1. **Touch Gestures**
   - Enable swipe navigation
   - Sufficient touch targets
   - Momentum scrolling
   - Pinch-to-zoom friendly

2. **Responsive Design**
   - Stack content vertically
   - Adjust font sizes
   - Simplify on small screens
   - Test actual devices

### Common Mistakes to Avoid
- Too many slides (cognitive overload)
- Autoplay too fast (unreadable)
- Small navigation controls
- Poor color contrast
- Missing pause controls
- Inconsistent slide layouts
- Heavy unoptimized images
- Ignoring keyboard users

## Compatibility and Requirements

### System Requirements
- **WordPress**: 6.6.0 or higher
- **PHP**: 8.1 or higher
- **Browser**: Modern browsers with JavaScript enabled

### Library Dependencies
- **Swiper.js**: Version 8.x
- Modular architecture (only loads needed features)
- Mobile-optimized

### Theme Compatibility
- Works with all standard themes
- May need z-index adjustments
- Full-width support varies by theme

