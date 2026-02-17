# Counter Block

## Introduction

The Counter Block is a powerful animated counter component that can display numbers with smooth animations, progress visualization, and flexible layouts. It supports three distinct styles: Simple number display, Circular progress, and Bar progress, making it perfect for statistics, metrics, and data visualization.

## How to Add or Use the Block in the Gutenberg Editor

1. **Adding the Block**
   - Click the **"+"** button in the editor to open the block inserter
   - Search for "Counter" or navigate to the Spectra category
   - Click on the **Counter Block** to add it

2. **Block Structure**
   - The counter block comes with three child blocks by default:
     - **Counter Icon**: For displaying icons
     - **Counter Number**: For the animated number display
     - **Counter Text**: For labels, descriptions, or additional content

3. **Adding Content**
   - Click inside each child block to add content
   - Use the **"+"** button to add more blocks inside the text child
   - Rearrange child blocks using the layout controls

## Settings Panel Organization

The Counter Block settings are organized into logical sections:

---

## Counter Tab

### Counter Style

Choose from three different counter styles:

#### Simple
- Clean number display with prefix/suffix support
- Perfect for basic statistics and metrics
- Supports custom formatting and animations

#### Circular
- Animated circular progress ring
- Shows progress as a percentage of the end number
- Customizable stroke width and colors
- Number displayed in the center

#### Bar
- Horizontal progress bar animation
- Shows progress as a percentage of the end number
- Customizable colors and height
- Number displayed above or below the bar

### Animation Settings

#### Start Number
- The number to start counting from (default: 0)
- Supports decimal values
- Can be negative

#### End Number
- The target number to count to (default: 100)
- Supports decimal values
- Can be negative

#### Animation Duration
- How long the animation takes in milliseconds (default: 2000ms)
- Range: 500ms to 10,000ms
- Longer durations create smoother animations

#### Animation Easing
Choose from five easing functions:
- **Linear**: Constant speed
- **Ease Out Quart**: Smooth deceleration (default)
- **Ease Out Cubic**: Moderate deceleration
- **Ease Out Back**: Slight overshoot effect
- **Ease Out Bounce**: Bouncy effect

### Number Formatting

#### Prefix
- Text to display before the number (e.g., "$", "#", "+")
- Useful for currency, symbols, or indicators

#### Suffix
- Text to display after the number (e.g., "%", "K", "M")
- Useful for units, abbreviations, or indicators

#### Thousand Separator
- Character to separate thousands (default: ",")
- Common options: ",", ".", " " (space)

#### Decimal Places
- Number of decimal places to show (default: 0)
- Range: 0 to 5 decimal places

---

## Progress Tab

*Only visible for Circular and Bar styles*

### Progress Size
- Controls the size of the progress visualization
- For circular: diameter of the circle
- For bar: width of the progress bar
- Supports all CSS units (px, em, rem, %, vw, etc.)

### Stroke Width
*Only for Circular style*
- Thickness of the progress ring
- Range: 1px to 20px
- Default: 8px

---

## Color Tab

### Text Colors
- **Text**: Main text color for all content
- **Text Hover**: Color when hovering over the counter

### Background Colors
- **Background**: Background color for the counter
- **Background Hover**: Background color on hover
- **Background Gradient**: Gradient background support
- **Background Gradient Hover**: Gradient background on hover

### Progress Colors
*Only for Circular and Bar styles*
- **Progress Color**: Color of the progress visualization
- **Progress Background**: Color of the progress background/track

---

## Layout Tab

### Layout Controls
- **Orientation**: Vertical or horizontal arrangement
- **Justify Content**: How content is distributed
- **Align Items**: Vertical alignment of content
- **Gap**: Space between child elements

### Spacing
- **Margin**: Outer spacing around the counter
- **Padding**: Inner spacing within the counter
- **Block Gap**: Space between child blocks

---

## Typography Tab

### Font Settings
- **Font Family**: Choose from available fonts
- **Font Size**: Size of the text
- **Font Weight**: Boldness of the text
- **Font Style**: Normal, italic, or oblique
- **Line Height**: Spacing between lines
- **Letter Spacing**: Space between characters
- **Text Transform**: Uppercase, lowercase, capitalize
- **Text Decoration**: Underline, overline, line-through

---

## Child Blocks

### Counter Icon
- Displays icons from the Spectra icon library
- Supports custom SVG uploads
- Configurable size, rotation, and RTL flip
- Hover effects and color customization

### Counter Number
- The main animated number display
- Inherits formatting from parent counter settings
- Typography and color customization
- Hover effects

### Counter Text
- Flexible text content area
- Supports paragraphs, headings, and lists
- Typography and color customization
- Hover effects

---

## Advanced Features

### Intersection Observer
- Counters animate when they come into view
- Optimized performance with lazy loading
- Configurable threshold and root margin

### Responsive Design
- Automatically adapts to different screen sizes
- Circular counters scale down on mobile
- Flexible layout controls for all devices

### Accessibility
- Proper ARIA labels and roles
- Screen reader friendly
- Keyboard navigation support
- High contrast mode support

### Performance
- Efficient animation using requestAnimationFrame
- Minimal DOM manipulation
- Optimized for multiple counters on one page

---

## Use Cases

### Statistics Dashboard
- Display key metrics with circular progress
- Use different colors for different categories
- Add icons to represent different data types

### Sales Counter
- Show revenue with currency prefix
- Use bar progress for goal tracking
- Add descriptive text for context

### Achievement Counter
- Display milestones with animated numbers
- Use bounce easing for celebration effect
- Add icons and descriptive text

### Progress Tracking
- Show completion percentage with circular progress
- Use custom colors for different statuses
- Add labels for clarity

---

## Tips and Best Practices

1. **Performance**: Use reasonable animation durations (1-3 seconds) for better UX
2. **Accessibility**: Always provide meaningful text labels for screen readers
3. **Responsive**: Test on different screen sizes and adjust layout accordingly
4. **Colors**: Use high contrast colors for better readability
5. **Content**: Keep text concise and meaningful
6. **Animation**: Choose easing functions that match your brand personality
7. **Numbers**: Use appropriate decimal places and separators for your audience
8. **Layout**: Use consistent spacing and alignment across your design

---

## Technical Notes

- Built with modern JavaScript (ES6+)
- Uses CSS custom properties for theming
- Intersection Observer API for performance
- RequestAnimationFrame for smooth animations
- WordPress block editor standards compliance
- Spectra design system integration
