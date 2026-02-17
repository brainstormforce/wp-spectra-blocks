import { expect } from '@playwright/test';

/**
 * Helper functions for common Spectra V3 block attribute controls
 */
export class SpectraBlockHelpers {
  constructor(page) {
    this.page = page;
  }

  /**
   * Insert a Spectra block by name
   * @param {string} blockName - The block name (e.g., 'spectra/button')
   * @param {string} blockTitle - The block title for UI interaction
   */
  async insertBlock(blockName, blockTitle) {
    await this.page.click('[aria-label="Block Inserter"]');
    await this.page.fill('[placeholder="Search for blocks and patterns"]', blockTitle);
    await this.page.click(`[data-type="${blockName}"]`);
    await this.page.waitForSelector(`[data-type="${blockName}"]`);
  }

  /**
   * Select a block in the editor
   * @param {string} blockName - The block name to select
   */
  async selectBlock(blockName) {
    await this.page.click(`[data-type="${blockName}"]`);
    await this.page.waitForSelector(`[data-type="${blockName}"].is-selected`);
  }

  /**
   * Click on the Settings tab
   */
  async clickSettingsTab() {
    try {
      const settingsTab = this.page.locator('button[id^="tabs-"][id$="-settings"]');
      await settingsTab.click();
      await this.page.waitForTimeout(500);
      console.log('✅ Settings tab clicked successfully');
    } catch (error) {
      console.log(`⚠️ Could not click Settings tab: ${error.message}`);
      throw error;
    }
  }

  /**
   * Click on the Styles tab
   */
  async clickStylesTab() {
    try {
      const stylesTab = this.page.locator('button[id^="tabs-"][id$="-styles"]');
      await stylesTab.click();
      await this.page.waitForTimeout(500);
      console.log('✅ Styles tab clicked successfully');
    } catch (error) {
      console.log(`⚠️ Could not click Styles tab: ${error.message}`);
      throw error;
    }
  }

  /**
   * Click on Text section using XPath
   */
  async clickTextSection() {
    try {
      const textSection = this.page.locator("//div[text()='Text']");
      await textSection.click();
      await this.page.waitForTimeout(500);
      console.log('✅ Text section clicked successfully');
    } catch (error) {
      console.log(`⚠️ Could not click Text section: ${error.message}`);
      throw error;
    }
  }

  /**
   * Test color picker control
   * @param {string} controlLabel - The label of the color control
   * @param {string} colorValue - The hex color value to set
   */
  async testColorControl(controlLabel, colorValue) {
    try {
      // Ensure we're on the correct tab based on the control type
      if (controlLabel.toLowerCase().includes('text')) {
        await this.clickStylesTab();
        await this.clickTextSection();
      } else if (controlLabel.toLowerCase().includes('background')) {
        await this.clickStylesTab();
      }
      
      // Open color picker
      await this.page.click(`button:has-text("${controlLabel}")`);
      
      // Set custom color
      await this.page.fill('input[type="text"][maxlength="7"]', colorValue);
      await this.page.press('input[type="text"][maxlength="7"]', 'Enter');
      
      // Verify color is applied in editor
      const selectedBlock = await this.page.locator('[data-type*="spectra/"].is-selected');
      await expect(selectedBlock).toHaveCSS('color', this.hexToRgb(colorValue));
    } catch (error) {
      console.log(`⚠️ Could not set color control: ${error.message}`);
      throw error;
    }
  }

  /**
   * Test background color control
   * @param {string} controlLabel - The label of the background color control
   * @param {string} colorValue - The hex color value to set
   */
  async testBackgroundColorControl(controlLabel, colorValue) {
    try {
      // Ensure we're on the Styles tab for background color
      await this.clickStylesTab();
      
      await this.page.click(`button:has-text("${controlLabel}")`);
      await this.page.fill('input[type="text"][maxlength="7"]', colorValue);
      await this.page.press('input[type="text"][maxlength="7"]', 'Enter');
      
      const selectedBlock = await this.page.locator('[data-type*="spectra/"].is-selected');
      await expect(selectedBlock).toHaveCSS('background-color', this.hexToRgb(colorValue));
    } catch (error) {
      console.log(`⚠️ Could not set background color control: ${error.message}`);
      throw error;
    }
  }

  /**
   * Test typography controls
   * @param {Object} typographySettings - Font settings object
   */
  async testTypographyControls(typographySettings) {
    try {
      // Ensure we're on the Styles tab for typography
      await this.clickStylesTab();
      await this.clickTextSection();
      
      const { fontSize, fontFamily, fontWeight, lineHeight } = typographySettings;
      
      // Test font size
      if (fontSize) {
        await this.page.click('button:has-text("Font size")');
        await this.page.fill('input[aria-label="Font size"]', fontSize);
        await this.page.press('input[aria-label="Font size"]', 'Enter');
      }
      
      // Test font family
      if (fontFamily) {
        await this.page.click('button:has-text("Font family")');
        await this.page.click(`button:has-text("${fontFamily}")`);
      }
      
      // Test font weight
      if (fontWeight) {
        await this.page.click('button:has-text("Font weight")');
        await this.page.click(`button:has-text("${fontWeight}")`);
      }
      
      // Test line height
      if (lineHeight) {
        await this.page.click('button:has-text("Line height")');
        await this.page.fill('input[aria-label="Line height"]', lineHeight);
        await this.page.press('input[aria-label="Line height"]', 'Enter');
      }
    } catch (error) {
      console.log(`⚠️ Could not set typography controls: ${error.message}`);
      throw error;
    }
  }

  /**
   * Test spacing controls (margin, padding)
   * @param {string} controlType - 'margin' or 'padding'
   * @param {Object} spacingValues - Spacing values object
   */
  async testSpacingControls(controlType, spacingValues) {
    try {
      // Ensure we're on the Styles tab for spacing
      await this.clickStylesTab();
      
      const { top, right, bottom, left } = spacingValues;
      
      await this.page.click(`button:has-text("${controlType}")`);
      
      if (top) {
        await this.page.fill(`input[aria-label="${controlType} top"]`, top);
      }
      if (right) {
        await this.page.fill(`input[aria-label="${controlType} right"]`, right);
      }
      if (bottom) {
        await this.page.fill(`input[aria-label="${controlType} bottom"]`, bottom);
      }
      if (left) {
        await this.page.fill(`input[aria-label="${controlType} left"]`, left);
      }
    } catch (error) {
      console.log(`⚠️ Could not set spacing controls: ${error.message}`);
      throw error;
    }
  }

  /**
   * Test border controls
   * @param {Object} borderSettings - Border settings object
   */
  async testBorderControls(borderSettings) {
    try {
      // Ensure we're on the Styles tab for border
      await this.clickStylesTab();
      
      const { width, color, radius, style } = borderSettings;
      
      // Test border width
      if (width) {
        await this.page.click('button:has-text("Border width")');
        await this.page.fill('input[aria-label="Border width"]', width);
      }
      
      // Test border color
      if (color) {
        await this.testColorControl('Border color', color);
      }
      
      // Test border radius
      if (radius) {
        await this.page.click('button:has-text("Border radius")');
        await this.page.fill('input[aria-label="Border radius"]', radius);
      }
      
      // Test border style
      if (style) {
        await this.page.click('button:has-text("Border style")');
        await this.page.click(`button:has-text("${style}")`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set border controls: ${error.message}`);
      throw error;
    }
  }

  /**
   * Test responsive controls
   * @param {string} device - 'desktop', 'tablet', or 'mobile'
   * @param {Function} testFunction - Function to run for the device
   */
  async testResponsiveControls(device, testFunction) {
    // Click responsive device tab
    await this.page.click(`button[aria-label="${device}"]`);
    
    // Run device-specific tests
    await testFunction();
  }

  /**
   * Test icon picker control
   * @param {string} iconName - Name of the icon to select
   */
  async testIconPicker(iconName) {
    await this.page.click('button:has-text("Select Icon")');
    await this.page.waitForSelector('.spectra-icon-picker-modal');
    
    // Search for icon
    await this.page.fill('input[placeholder="Search icons..."]', iconName);
    await this.page.click(`button[aria-label="${iconName}"]`);
    
    // Verify icon is selected
    const selectedBlock = await this.page.locator('[data-type*="spectra/"].is-selected');
    await expect(selectedBlock.locator('svg')).toBeVisible();
  }

  /**
   * Test toggle control
   * @param {string} controlLabel - Label of the toggle control
   * @param {boolean} enabled - Whether to enable or disable
   */
  async testToggleControl(controlLabel, enabled) {
    const toggle = this.page.locator(`button:has-text("${controlLabel}")`);
    const currentState = await toggle.getAttribute('aria-pressed');
    
    if ((currentState === 'true') !== enabled) {
      await toggle.click();
    }
    
    await expect(toggle).toHaveAttribute('aria-pressed', enabled.toString());
  }

  /**
   * Test slider control
   * @param {string} controlLabel - Label of the slider control
   * @param {number} value - Value to set
   */
  async testSliderControl(controlLabel, value) {
    const slider = this.page.locator(`input[aria-label="${controlLabel}"]`);
    await slider.fill(value.toString());
    await expect(slider).toHaveValue(value.toString());
  }

  /**
   * Verify block attributes in HTML comment
   * @param {string} blockName - Block name
   * @param {Object} expectedAttributes - Expected attribute values
   */
  async verifyBlockAttributes(blockName, expectedAttributes) {
    const blockContent = await this.page.locator(`[data-type="${blockName}"]`).innerHTML();
    
    // Extract attributes from HTML comment
    const commentMatch = blockContent.match(/<!--\s*wp:(\S+)\s*({.*?})\s*-->/);
    if (commentMatch) {
      const attributes = JSON.parse(commentMatch[2]);
      
      for (const [key, expectedValue] of Object.entries(expectedAttributes)) {
        expect(attributes[key]).toBe(expectedValue);
      }
    }
  }

  /**
   * Publish post and navigate to frontend
   */
  async publishAndViewFrontend() {
    try {
      // Click the main publish button
      const publishButton = this.page.locator('.editor-post-publish-button__button').first();
      if (await publishButton.isVisible()) {
        await publishButton.click();
      } else {
        // Fallback to text-based selector
        await this.page.click('button:has-text("Publish")').first();
      }
      
      // Wait for the publish panel to appear
      await this.page.waitForSelector('.editor-post-publish-panel', { timeout: 5000 }).catch(() => {
        console.log('Publish panel not found, trying alternative selectors');
      });
      
      // Click the final publish button in the panel
      const finalPublishButton = this.page.locator('.editor-post-publish-panel__header-publish-button button');
      if (await finalPublishButton.isVisible()) {
        await finalPublishButton.click();
      } else {
        // Fallback selector
        await this.page.click('.editor-post-publish-panel button:has-text("Publish")');
      }
      
      // Wait for the post to be published
      await this.page.waitForSelector('.editor-post-publish-panel__header-published', { timeout: 10000 });
      
      // Click View Post link
      const viewPostLink = this.page.locator('.editor-post-publish-panel__header-published a:has-text("View Post")');
      if (await viewPostLink.isVisible()) {
        await viewPostLink.click();
      } else {
        // Alternative selector
        await this.page.click('a:has-text("View Post")');
      }
      
      // Wait for frontend to load
      await this.page.waitForLoadState('networkidle');
      
    } catch (error) {
      console.error(`Failed to publish and view frontend: ${error.message}`);
      
      // Try alternative publish flow
      try {
        // Check if already published
        const updateButton = this.page.locator('button:has-text("Update")');
        if (await updateButton.isVisible()) {
          await updateButton.click();
          await this.page.waitForTimeout(2000);
          
          // Try to find preview/view link
          const viewLink = this.page.locator('a[href*="preview=true"], a:has-text("View Post"), a:has-text("Preview")').first();
          if (await viewLink.isVisible()) {
            await viewLink.click();
            await this.page.waitForLoadState('networkidle');
          }
        }
      } catch (fallbackError) {
        console.error(`Fallback publish also failed: ${fallbackError.message}`);
        throw error;
      }
    }
  }

  /**
   * Preview post in a new tab
   */
  async previewPost() {
    try {
      // Save the post first
      await this.page.keyboard.press('Control+S');
      await this.page.waitForTimeout(2000);
      
      // Look for preview button
      const previewButton = this.page.locator('a[href*="preview=true"], button:has-text("Preview"), .editor-post-preview').first();
      
      if (await previewButton.isVisible()) {
        // Open preview in new tab
        const [newPage] = await Promise.all([
          this.page.context().waitForEvent('page'),
          previewButton.click()
        ]);
        
        await newPage.waitForLoadState('networkidle');
        return newPage;
      } else {
        console.log('Preview button not found');
        return null;
      }
    } catch (error) {
      console.error(`Failed to preview post: ${error.message}`);
      return null;
    }
  }

  /**
   * Verify frontend rendering
   * @param {string} blockSelector - CSS selector for the block on frontend
   * @param {Object} expectedStyles - Expected CSS styles
   */
  async verifyFrontendRendering(blockSelector, expectedStyles) {
    const frontendBlock = this.page.locator(blockSelector);
    await expect(frontendBlock).toBeVisible();
    
    for (const [property, value] of Object.entries(expectedStyles)) {
      await expect(frontendBlock).toHaveCSS(property, value);
    }
  }

  /**
   * Utility function to convert hex to RGB
   * @param {string} hex - Hex color value
   * @returns {string} RGB color value
   */
  hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? 
      `rgb(${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)})` : 
      null;
  }

  /**
   * Test animation extension
   * @param {string} animationType - Type of animation
   * @param {Object} animationSettings - Animation settings
   */
  async testAnimationExtension(animationType, animationSettings) {
    // Enable animations extension
    await this.page.click('button:has-text("Extensions")');
    await this.page.click('button:has-text("Animations")');
    
    // Select animation type
    await this.page.click(`button:has-text("${animationType}")`);
    
    // Configure animation settings
    if (animationSettings.duration) {
      await this.testSliderControl('Duration', animationSettings.duration);
    }
    if (animationSettings.delay) {
      await this.testSliderControl('Delay', animationSettings.delay);
    }
  }

  /**
   * Test image mask extension
   * @param {string} maskType - Type of mask to apply
   */
  async testImageMaskExtension(maskType) {
    await this.page.click('button:has-text("Extensions")');
    await this.page.click('button:has-text("Image Mask")');
    await this.page.click(`button:has-text("${maskType}")`);
    
    // Verify mask is applied
    const selectedBlock = await this.page.locator('[data-type*="spectra/"].is-selected');
    await expect(selectedBlock).toHaveCSS('mask-image', /url\(/);
  }
}