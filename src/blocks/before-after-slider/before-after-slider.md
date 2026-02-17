# Before After Slider

## Description
An interactive before/after image comparison slider. Users can drag a handle (or hover) to reveal the before and after images side by side.

## Attributes
| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| beforeImageId | number | 0 | Before image attachment ID |
| beforeImageUrl | string | "" | Before image URL |
| beforeImageAlt | string | "" | Before image alt text |
| afterImageId | number | 0 | After image attachment ID |
| afterImageUrl | string | "" | After image URL |
| afterImageAlt | string | "" | After image alt text |
| beforeLabel | string | "Before" | Label for before image |
| afterLabel | string | "After" | Label for after image |
| orientation | string | "horizontal" | Slider orientation (horizontal/vertical) |
| initialOffset | number | 50 | Initial handle position (0-100%) |
| moveOnHover | boolean | false | Move handle on hover instead of drag |
| showLabels | string | "hover" | Label display mode (hover/always/none) |
| overlayColor | string | — | Overlay color on images |
| handleColor | string | — | Handle/divider color |
| handleThickness | number | — | Handle line thickness (px) |
| handleCircleSize | number | — | Handle circle diameter (px) |
| labelColor | string | — | Label text color |
| labelBackgroundColor | string | — | Label background color |

## CSS Variables
| Variable | Default | Description |
|----------|---------|-------------|
| --spectra-ba-handle-color | #fff | Handle and arrow color |
| --spectra-ba-handle-thickness | 3px | Handle line thickness |
| --spectra-ba-handle-circle-size | 40px | Handle circle diameter |
| --spectra-ba-overlay-color | transparent | Overlay on images |
| --spectra-ba-label-color | #fff | Label text color |
| --spectra-ba-label-bg-color | rgba(0,0,0,0.5) | Label background color |

## Interaction Modes
- **Drag (default):** Click and drag the handle to reveal before/after.
- **Hover:** Move handle by hovering over the container (enabled via `moveOnHover`).

## Accessibility
- Images use provided alt text for screen readers.
- Handle uses cursor styles to indicate interactivity.
- Touch events supported for mobile devices.
