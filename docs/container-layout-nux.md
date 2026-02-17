# Container Layout NUX Implementation

## Overview

This document describes the implementation of the New User Experience (NUX) guide for Container block layout types. The implementation addresses the confusion users experience when layout types automatically change when selecting variations.

## Problem Addressed

1. **Layout Type Confusion**: Users don't understand the difference between Flow, Flex, Constrained, and Grid layouts
2. **Automatic Changes**: When users select variations (2-column, 3-column), the layout type changes from Flex to Grid automatically
3. **Lack of Guidance**: No explanations or help for when to use each layout type

## Solution Components

### 1. Container Layout Guide (`src/components/container-layout-guide/`)

A comprehensive NUX guide component that:
- Explains each layout type (Flow, Flex, Grid, Constrained)
- Shows visual demonstrations of how each layout works
- Provides "best for" recommendations for each layout type
- Explains why layout types change when selecting variations
- Uses WordPress's native Guide component for consistent UX

### 2. Layout Type Tooltip (`src/components/layout-type-tooltip/`)

Inline help tooltips that:
- Provide quick descriptions for each layout type
- Show "best for" use cases
- Include a link to open the full guide
- Use WordPress's native Tooltip component

### 3. NUX Hook (`src/hooks/useContainerLayoutGuide.js`)

A custom hook that:
- Integrates with WordPress's `@wordpress/nux` system
- Manages guide visibility state
- Handles tip dismissal to prevent repeated showing
- Provides methods to manually trigger the guide

### 4. Layout Information Panel

Added to Container block settings:
- Shows current layout type
- Includes tooltip with layout information
- Provides quick access to the full guide
- Appears in the Settings panel for easy access

## Integration Points

### Container Block Edit Component (`src/blocks/container/edit.js`)

- Imports and initializes the NUX guide hook
- Adds a "Layout Help" button to the block toolbar
- Renders the ContainerLayoutGuide component
- Manages guide visibility state

### Container Block Settings (`src/blocks/container/settings.js`)

- Adds LayoutInformation component to settings panel
- Shows current layout type with tooltip
- Provides access to detailed guide

## WordPress NUX Integration

The implementation follows WordPress's NUX system as documented at:
https://developer.wordpress.org/block-editor/reference-guides/data/data-core-nux/

### Key NUX Features Used:

1. **areTipsEnabled**: Checks if user has tips enabled globally
2. **isTipVisible**: Checks if specific tip hasn't been dismissed
3. **dismissTip**: Marks tip as seen so it doesn't show again
4. **Tip ID**: `spectra/container-layout-guide` - unique identifier for this guide

## User Experience Flow

### First-Time Users:
1. Add Container block
2. NUX guide automatically appears (if tips are enabled)
3. User goes through 6-page guide explaining layout types
4. Guide is dismissed and won't show again

### Returning Users:
1. Add Container block
2. See layout information in settings panel
3. Can click tooltip for quick help
4. Can click "Layout Help" toolbar button for full guide

### Layout Type Information:
- **Flow**: Natural top-to-bottom flow, like normal documents
- **Flex**: Controlled alignment and spacing, horizontal/vertical arrangements
- **Grid**: Precise positioning with defined rows and columns
- **Constrained**: Centered content with maximum width limits

## Files Modified/Added

### New Files:
- `src/components/container-layout-guide/index.js`
- `src/components/container-layout-guide/style.scss`
- `src/components/layout-type-tooltip/index.js`
- `src/components/layout-type-tooltip/style.scss`
- `src/hooks/useContainerLayoutGuide.js`

### Modified Files:
- `src/blocks/container/edit.js` - Added NUX guide integration
- `src/blocks/container/settings.js` - Added layout information panel
- `src/blocks/container/editor.scss` - Added layout info panel styles

## Accessibility

- All components use semantic HTML
- Proper ARIA labels for buttons and tooltips
- Keyboard navigation support through WordPress components
- High contrast colors for readability
- Screen reader friendly content structure

## Responsive Design

- Guide adapts to different screen sizes
- Mobile-friendly layout demonstrations
- Responsive grid layouts in demos
- Touch-friendly interactive elements

## Future Enhancements

1. **Contextual Tips**: Show specific tips based on current layout type
2. **Animation Previews**: Add animated demonstrations of layout behaviors
3. **Quick Actions**: Add buttons to quickly switch between layout types
4. **Usage Analytics**: Track which layout types users choose most
5. **Advanced Patterns**: Show common layout patterns for each type

## Testing

To test the implementation:

1. **First-time Experience**:
   - Clear WordPress user meta for NUX tips
   - Add Container block
   - Verify guide appears automatically

2. **Toolbar Help**:
   - Add Container block
   - Click "Layout Help" toolbar button
   - Verify guide opens

3. **Layout Information**:
   - Add Container block
   - Check settings panel for layout information
   - Test tooltip functionality

4. **Layout Type Changes**:
   - Add Container block
   - Select different variations
   - Verify layout information updates correctly

## Performance Considerations

- Components only load when needed
- Guide content is lazy-loaded
- Minimal impact on block editor performance
- Efficient state management with hooks
- Proper memoization of components

This implementation significantly improves the user experience for Container block layouts while maintaining WordPress standards and accessibility guidelines.
