# Spectra Responsive Controls

> Design once, perfect for every screen. Adjust block settings separately for **Desktop**, **Tablet**, and **Mobile**—all inside the Gutenberg editor.

## What Are Responsive Controls?

Responsive Controls let you design your blocks differently for Desktop, Tablet, and Mobile — all inside the Gutenberg editor.

👉 **No extra tools. No coding. Just switch the device view and adjust settings.**

![Responsive Controls Overview](https://wpspectra.com/wp-content/uploads/2024/12/responsive-controls-overview.gif)

---

## Key Benefits

- **Device-specific design:** Tweak spacing, typography, layout, and more per device.
- **Live preview:** Instantly see changes in WordPress' device preview.
- **Smart inheritance:** Smaller devices inherit from larger ones unless you override.

---

## Quick Start

1. **Select a Spectra block** in the Gutenberg editor.
2. **Open responsive panels** like Layout, Typography, Dimensions, Border & Shadow, Background, or Content in the sidebar Settings or Styles tab.
3. **Switch device views** by clicking the device buttons (Desktop/Tablet/Mobile) in any panel header—Desktop is active by default.
4. **Adjust settings** for each device as needed. Your changes apply only to the currently selected device.

![Panel Device Buttons and Responsive Control Indicators](https://wpspectra.com/wp-content/uploads/2024/12/responsive-controls-quick-start.webp)

> **Pro tip:** Look for the monitor-smartphone icon next to control labels—this indicates you can set device-specific values for that control.

---

## Device Buttons & Breakpoints

| Device | Icon | Breakpoint (px) | Inherits From |
|---|---|---:|---|
| Desktop | 🖥️ | ≥ 1024 | — |
| Tablet | 📱 | 768–1023 | Desktop (if unset) |
| Mobile | 📱 | ≤ 767 | Tablet → Desktop (if unset) |

- Device buttons appear **in panel headers** when that panel contains responsive controls.
- Buttons **synchronize** with WordPress' core device preview—clicking either updates both.

---

## How It Works (Workflow)

![Device Switching Workflow](https://wpspectra.com/wp-content/uploads/2024/12/device-switching-workflow.gif)

1. **Select a device** via a Spectra panel button or the WordPress core preview.
2. The **preview switches** to that device, and all device buttons **sync**.
3. **Change values**—they're saved **only** for the active device.
4. **Switch devices** anytime to view/edit that device's values.

> **Active device** is highlighted everywhere.

---

## Inheritance & Reset

Spectra follows a **cascading model** (like CSS):

![Inheritance System and Reset Functionality Demo](https://wpspectra.com/wp-content/uploads/2024/12/inheritance-and-reset-demo.gif)

- **Mobile:** Mobile → (fallback) Tablet → (fallback) Desktop → (fallback) Block defaults  
- **Tablet:** Tablet → (fallback) Desktop → (fallback) Block defaults  
- **Desktop:** Desktop → (fallback) Block defaults

**Reset options**
- **Per control:** Right-click a responsive control → **Reset** clears the current device's value (inherits from parent device).
- **Per panel:** Use the panel menu → **Reset** to clear **all** responsive controls in that panel for the **current device**.

**Examples**
- *Mobile padding reset:* Desktop 20px, Tablet 15px, Mobile 10px → Reset Mobile → becomes **15px** (inherits Tablet).
- *Tablet margin reset:* Desktop 30px, Tablet 20px → Reset Tablet → becomes **30px** (inherits Desktop). Mobile stays unchanged.

> **On Tablet:** "Inherits from Desktop on reset."  
> **On Mobile:** "Inherits from Tablet or Desktop on reset."

---

## Visual Feedback & Accessibility

- **Active device state:** Highlighted device button; ARIA attributes update for screen readers.
- **Help notices:** Context messages on Tablet/Mobile panels explain inheritance.
- **Polished UI:** Subtle hover states and transitions for smooth interaction.

---

## Supported Controls (Core)

The following WordPress core controls are responsive across Spectra blocks:

### Layout
- Flow, Flex, Constrained, Grid  
![Layout Panel Responsive](https://wpspectra.com/wp-content/uploads/2024/12/layout-panel-responsive.webp)

### Typography
- **Font** – Choose the typeface
- **Size** – Adjust font size (px, em, rem, etc.)
- **Appearance** – Font weight & style (e.g., bold, italic)
- **Line height** – Vertical spacing between lines
- **Letter spacing** – Space between characters
- **Decoration** – Text decoration (underline, strikethrough, etc.)
- **Orientation** – Text direction/vertical orientation
- **Letter case** – Transform text case (uppercase, lowercase, capitalize)

![Typography Panel Responsive](https://wpspectra.com/wp-content/uploads/2024/12/typography-panel-responsive.webp)

### Dimensions
- **Padding** – Inner spacing around content
- **Margin** – Outer spacing around elements
- **Block Gap** – Space between child elements
- **Row span** – Number of rows an item spans in a grid
- **Column span** – Number of columns an item spans in a grid
- **Row start** – Starting row position in a grid
- **Column start** – Starting column position in a grid
- **Flex size / width** – Options like Fit, Grow, Fixed

![Dimensions Panel Responsive](https://wpspectra.com/wp-content/uploads/2024/12/dimensions-panel-responsive.webp)

### Border & Shadow
- Border width, style, color, radius
- Shadow (where supported)  
![Border Shadow Panel Responsive](https://wpspectra.com/wp-content/uploads/2024/12/border-shadow-panel-responsive.webp)
---

## Block-Specific Responsive Attributes (Spectra)

![Block Specific Controls Demo](https://wpspectra.com/wp-content/uploads/2024/12/block-specific-controls-demo.gif)

> Values listed below are **per-device configurable** when you see device buttons in the panel header.

### Container
| Panel | Controls |
|---|---|
| Dimensions | Width, Height, Min W, Min H, Max W, Max H |
| Background | **Background Type** – Choose None, Image, or Video<br>**Background Size** – Cover, Contain, Auto sizing options<br>**Background Repeat** – No Repeat, Repeat, Repeat X, Repeat Y<br>**Background Position** – Focal Point Picker for image positioning |

### Google Map
| Panel | Controls |
|---|---|
| Dimensions | Height |

### Text
| Panel | Controls |
|---|---|
| Border & Shadow | **Enable Text Shadow** – Toggle on/off<br>**Shadow Color** – Text shadow color picker<br>**X Offset** – Horizontal shadow shift<br>**Y Offset** – Vertical shadow shift<br>**Blur Radius** – Shadow blur strength |

### Button
| Panel | Controls |
|---|---|
| Dimensions | Icon size, Text-Icon Gap |

### Icon
| Panel | Controls |
|---|---|
| Dimensions | Size |

### Accordion
| Panel | Controls |
|---|---|
| Dimensions | Size |

### Accordion Child Header Icon
| Panel | Controls |
|---|---|
| Dimensions | Size |

### Tabs
| Panel | Controls |
|---|---|
| Dimensions | Size |

### Tabs Child Tab Button
| Panel | Controls |
|---|---|
| Dimensions | Size, Gap |

### Countdown
| Panel | Controls |
|---|---|
| Dimensions | Width, Height, Min W, Min H, Max W, Max H |

### List
| Panel | Controls |
|---|---|
| Dimensions | Icon Size |

### List Child Icon
| Panel | Controls |
|---|---|
| Dimensions | Icon Size |

### Slider
| Panel | Controls |
|---|---|
| General | Slides Per View, Space Between Slides |
| Dimensions | Slider Height, Navigation Size, Arrow Icon Size, Arrow Distance, Dots Position |
| Background | **Background Type** – Choose None, Image, or Video<br>**Background Size** – Cover, Contain, Auto sizing options<br>**Background Repeat** – No Repeat, Repeat, Repeat X, Repeat Y<br>**Background Position** – Focal Point Picker for image positioning |

### Separator
| Panel | Controls |
|---|---|
| Dimensions | Width, Height, Size |

### Modal Child Button
| Panel | Controls |
|---|---|
| Dimensions | Size, Gap |

### Modal Child Icon
| Panel | Controls |
|---|---|
| Dimensions | Size |

### Modal Child Popup Close Icon
| Panel | Controls |
|---|---|
| Dimensions | Size |

### Modal Popup Content
| Panel | Controls |
|---|---|
| Content | Width, Height, Max Height, Content Height |
| Background | **Background Type** – Choose None, Image, or Video<br>**Background Size** – Cover, Contain, Auto sizing options<br>**Background Repeat** – No Repeat, Repeat, Repeat X, Repeat Y<br>**Background Position** – Focal Point Picker for image positioning |

---

## Block-Specific Responsive Attributes (Spectra **Pro**)

### Loop Builder → Pagination Next Button
| Panel | Controls |
|---|---|
| Dimensions | Icon size, Text-Icon Gap |

### Loop Builder → Pagination Previous Button
| Panel | Controls |
|---|---|
| Dimensions | Icon size, Text-Icon Gap |

### Loop Builder → Filter Checkbox
| Panel | Controls |
|---|---|
| Dimensions | Checkbox Size, Items Gap, Label-Checkbox Gap |

### Loop Builder → Filter Button
| Panel | Controls |
|---|---|
| Dimensions | Icon size, Text-Icon Gap |

### Loop Builder → Reset Button
| Panel | Controls |
|---|---|
| Dimensions | Icon size, Text-Icon Gap |

---

## Tips & Best Practices

- **Start at Desktop:** Set solid defaults first; override only where needed on Tablet/Mobile.
- **Keep changes minimal:** The fewer overrides, the easier future edits become.
- **Use Reset often:** If something looks off on Mobile, try resetting to inherit Tablet/Desktop.
- **Preview real content:** Test with long titles, different images, and real copy.

---

## Troubleshooting

- **I don't see device buttons.**  
  That panel may not have responsive controls, or the block doesn't support them for that property.

- **My Mobile value isn't changing.**  
  Check if the control is **locked** by inheritance. If you previously set a Mobile value, **Reset** first to fall back to Tablet/Desktop.

- **Preview doesn't match front end.**  
  Clear cache and ensure your theme/plugins aren't overriding styles at those breakpoints.

---

## FAQ

**Do I have to set values for every device?**  
No. Set Desktop first. Use Tablet/Mobile only when needed—others inherit automatically.

**Does resetting remove all values?**  
Reset removes the **current device's** value only, causing it to inherit from the next larger device (or defaults).

**Are breakpoints configurable?**  
Spectra uses WordPress' standard device ranges listed above.

---

## Reference: Device States

- **Desktop (🖥️)** ≥ 1024px — Base layer, no upward inheritance.  
- **Tablet (📱)** 768–1023px — Inherits from Desktop when unset.  
- **Mobile (📱)** ≤ 767px — Inherits from Tablet, then Desktop when unset.

---