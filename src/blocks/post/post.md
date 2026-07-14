# Post Block

Create powerful, dynamic post listings with advanced filtering, searching, sorting, and pagination.

## 📹 Quick Demo Video

[**▶️ Watch Post Block Overview Demo**](https://www.tella.tv/video/post-block-demo-link)
*Complete overview of Post Block features, variations, and setup process*

## Getting Started

### Adding the Block
1. Click the **"+"** button in the block editor
2. Search for "Post" or find it in the **Spectra** category  
3. Click to add the block to your page
4. Choose from the pre-built variations (Grid, Masonry, Carousel)

![Adding Post Block](https://wpspectra.com/wp-content/uploads/2026/02/post-block-overview.gif)
*Complete process: Adding block, selecting variation, and customizing template*

## Core Features

### Advanced Post Querying
Display any post type with powerful filtering options.

**Configuration:**
- **Post Types**: Posts, pages, or any custom post type
- **Query Controls**: Posts per page, offset
- **Sorting**: Date, title, random, menu order (ascending/descending)
- **Filters**: Authors, taxonomies (categories/tags), specific post exclusion
- **Sticky Posts**: Include, exclude, ignore, or show only sticky posts

### Template System
Design custom post layouts using any WordPress blocks with multiple pre-built template variations. You have complete freedom over the look of the posts loop.

## Block Architecture

The Post block uses a parent-child system where the container handles the query and settings, and the child blocks handle the layout of individual posts:

```text
🏗️ Post (Container)
├── 📄 Post Template (Child block - determines the layout for individual posts)
└── ❌ Post No Results (Child block - fallback content when no posts match the query)
```

## Pre-built Variations

![Post Block Variations](https://wpspectra.com/wp-content/uploads/2026/02/variations-post-block.webp)
*All block variations available when adding the Post block*

### Grid *(Default)*
- Displays posts in a standard grid layout.
- Perfect for blogs, news sites, and product showcases.

### Masonry
- Creates a Pinterest-style cascading grid layout.
- Great for portfolios and displaying images with varying heights.

### Carousel
- Shows posts in a sliding carousel.
- Ideal for space-saving designs, latest post sliders, and testimonials.

## ⚙️ Settings Tab

![Post Settings Overview](https://wpspectra.com/wp-content/uploads/2026/02/post-block-settings-overview.webp)
*Overview of all settings panels and configuration options*

### Settings Panel
**Core configuration for the content query and foundational layout:**

- **Layout Type**: Switch between Grid, Masonry, or Carousel layouts.
- **Post Type**: Choose between posts, pages, or any registered custom post type.
- **Posts Per Page / Posts to Show**: Set the number of posts displayed.
- **Offset**: The number of posts to skip from the beginning of the query.
- **Order By & Order**: Sort criteria (Date, Title, Random, Menu Order) and direction.
- **Sticky Posts** *(Posts only)*: Controls how sticky posts are handled (Include, Exclude, Ignore, Only Sticky).

### Grid & Masonry Panel *(For Grid/Masonry Layouts)*
- **Columns**: Determines the number of columns in the layout (1-6).

### Carousel Panel *(For Carousel Layouts)*
- **Slides Per View**: Number of slides visible simultaneously.
- **Space Between**: The gap between individual slides.
- **Equal Height**: Make all slides equal height based on the tallest slide.
- **Infinite Loop**: Enable continuous sliding by looping back to the first slide.
- **Autoplay**: Automatically cycle through posts at a chosen interval. Includes speed and "Pause On Hover" controls.
- **Transition Speed**: Defines how fast the sliding animation occurs.
- **Show Arrows / Show Dots**: Toggle navigation arrows and pagination dots.

### Filters Panel
**Advanced content filtering:**
- **Exclude Current Post**: Disables the current post from appearing (useful for "Related Posts").
- **Search Keyword**: Filter posts by a specific term.
- **Authors**: Form token field to filter by specific authors.
- **Taxonomies**: Filter by categories, tags, or custom taxonomies supported by the post type.
- **Exclude Post IDs**: Comma-separated list of numeric post IDs to exclude.

### Pagination Panel *(For Grid/Masonry Layouts)*
**Control pagination style and functionality:**
- **Pagination Type**: Options include None, Numbers (Classic links), Load More Button, or Infinite Scroll.
- **Labels & Text**: Customize text for Previous/Next links, Load More button, and Loading state.
- **Alignment**: Left, Center, or Right alignment for pagination controls.
- **Layout**: Border or Filled styling for numbered pagination.

---

## 🎛️ Child Block Settings

### Post Template Block
The Post Template block dictates the layout of individual posts within the loop.

#### **Template Variations:**
When the template block is initialized, you can choose from these predefined post layouts:

![Template Variations](https://wpspectra.com/wp-content/uploads/2026/02/variations-post-template.webp)
*All template layout options available for post display*

- **Portrait**: A clean, vertical card design featuring a 16:9 featured image, categories, title, date, excerpt, and author avatar.
- **Landscape**: A two-column horizontal layout displaying the featured image on the left (2:3 aspect ratio) and the post content on the right.
- **Create from scratch**: A minimalist starting point featuring only the post title, giving you complete freedom to build your own loop layout using inner blocks.

#### **WordPress Native Controls:**
The Template Block utilizes native WordPress controls for maximum flexibility:
- **Typography**: Font family, size, weight, line height, letter spacing
- **Dimensions**: Margin, padding
- **Border & Shadow**: Width, color, radius, style, shadow
- **Align**: Wide and full width alignments

#### **Allowed Inner Blocks:**
You can compose your customized post layout using standard WordPress core blocks alongside Spectra blocks. Common blocks include:
- **Core Blocks**: `core/post-title`, `core/post-featured-image`, `core/post-excerpt`, `core/post-date`, `core/post-author-name`, `core/avatar`, `core/post-terms`.
- **Spectra Blocks**: Container, Headings, Images, Buttons, etc.

### Post No Results Block
Provides the fallback container displayed when no posts match the query. You can add any text or blocks (*like a heading or paragraph*) here to guide users.

#### **WordPress Native Controls:**
The No Results block also has full styling control:
- **Color Settings**: Customize background color, text color, and gradients.
- **Typography**: Font family, size, weight, line height, letter spacing
- **Dimensions**: Margin, padding, block gap
- **Border & Shadow**: Width, color, radius, style, shadow

---

## 🎨 Style Tab

### Color Panel
Color controls appear in the `Style` tab and adapt based on your layout and pagination settings.

#### **For Carousel Layout:**
Shown when the Carousel layout is active. Controls appear conditionally based on which navigation elements are enabled.

- **Arrow Color** *(shown when "Show Arrows" is enabled)*: Sets the icon color of the navigation arrows.
- **Arrow Background Color** *(shown when "Show Arrows" is enabled)*: Sets the background color behind each arrow button.
- **Dot Color** *(shown when "Show Dots" is enabled)*: Sets the color of the pagination dots.

#### **For Grid / Masonry Layout (with Pagination enabled):**
Shown when a Pagination Type other than "None" is selected. Color options vary by the active pagination type.

**Numbers pagination:**
- **Pagination Text**: The text color for page number links.
- **Pagination Background**: The background color for page number buttons.
- **Pagination Text Hover**: Text color when hovering over a page number.
- **Pagination Background Hover**: Background color on hover.
- **Pagination Text Active**: Text color of the currently active page number.
- **Pagination Background Active**: Background color of the active page number.

**Load More Button pagination:**
- **Pagination Text**: The text color of the Load More button.
- **Pagination Background**: The background color of the Load More button.
- **Pagination Text Hover**: Text color when hovering the button.
- **Pagination Background Hover**: Background color on hover.

**Infinite Scroll pagination:**
- **Loader Color**: The color of the loading spinner shown while new posts are being fetched.

---

### Dimensions Panel
The Post block integrates dimension controls natively in the block sidebar `Style` tab, dynamically adapting based on your chosen layout.

#### **Native WordPress Controls (all layouts):**
- **Padding**: Inner spacing between the block edge and its content.
- **Margin**: Outer spacing around the block.
- **Block Spacing**: Controls the vertical gap between inner block elements.

#### **For Grid & Masonry:**
- **Column Gap**: Adjusts the horizontal spacing between grid columns.
- **Row Gap**: Adjusts the vertical spacing between grid rows.

#### **For Carousel:**
- **Arrow Size**: Controls the size of the navigation arrows.
- **Arrow Distance**: Adjusts the inset/outset position of arrows from the carousel edges (Negative values push arrows outside).
- **Dots Position**: Controls the vertical placement of pagination dots (Negative values pull them downwards).

![Style Tab Dimensions](https://wpspectra.com/wp-content/uploads/2026/02/post-block-style-dimensions.webp)

## Use Cases & Examples

### Blog Grid
- **Setup**: Grid layout variation
- **Template**: Portrait template variation 
- **Features**: Pagination, categories filter
- **Styling**: Standard borders, subtle shadows for modern cards

![Blog Grid Example](https://wpspectra.com/wp-content/uploads/2026/02/post-blog-grid.webp)
*Modern blog grid layout*

### Portfolio Showcase
- **Setup**: Masonry layout variation
- **Template**: Create from scratch (Using images and overlay titles)
- **Features**: Custom filtering based on project types
- **Styling**: Custom width, gap adjustments, and hover animations

![Portfolio Example](https://wpspectra.com/wp-content/uploads/2026/02/post-portfolio-layout.webp)
*Creative portfolio layout*

### Latest News Ticker / Testimonials
- **Setup**: Carousel layout variation
- **Template**: Portrait (styled as cards) or Custom
- **Features**: Autoplay, infinite loop, navigation arrows
- **Styling**: Equal heights enabled to prevent layout jumps

![Carousel Example](https://wpspectra.com/wp-content/uploads/2026/02/post-carousel-layout.gif)
*Sleek sliding carousel layout*
