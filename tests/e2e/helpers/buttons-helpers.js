import { expect } from '@playwright/test';
import { SpectraBlockUtils } from './spectra-block-utils.js';
import config from '../config.js';

/**
 * Extended helper functions specifically for Buttons block
 */
export class ButtonsBlockHelpers extends SpectraBlockUtils {
  /**
   * Add a button to the buttons block
   * @param {number} position - Position to add the button (optional)
   */
  async addButton(position = null) {
    const buttonsBlock = this.page.locator('[data-type="spectra/buttons"]');
    
    if (position !== null) {
      // Add button at specific position
      const buttons = await buttonsBlock.locator('[data-type="spectra/button"]');
      const targetButton = buttons.nth(position);
      await targetButton.click();
      await this.page.keyboard.press('Enter');
    } else {
      // Add button at the end
      await buttonsBlock.locator('.block-editor-button-block-appender').click();
    }
  }

  /**
   * Remove a button from the buttons block
   * @param {number} index - Index of the button to remove
   */
  async removeButton(index) {
    const button = this.page.locator('[data-type="spectra/buttons"] [data-type="spectra/button"]').nth(index);
    await button.click();
    await this.page.click('[aria-label="Remove Block"]');
  }

  /**
   * Get the count of buttons in the buttons block
   * @returns {number} Number of buttons
   */
  async getButtonCount() {
    return await this.page.locator('[data-type="spectra/buttons"] [data-type="spectra/button"]').count();
  }

  /**
   * Configure a specific button within the buttons block
   * @param {number} index - Index of the button to configure
   * @param {Object} config - Button configuration
   */
  async configureButton(index, config) {
    const button = this.page.locator('[data-type="spectra/buttons"] [data-type="spectra/button"]').nth(index);
    await button.click();
    
    if (config.text) {
      await button.locator('.wp-block-button__link').fill(config.text);
    }
    
    if (config.link) {
      await this.page.click('button:has-text("Link")');
      await this.page.fill('input[aria-label="URL"]', config.link);
      await this.page.press('input[aria-label="URL"]', 'Enter');
    }
    
    if (config.textColor) {
      await this.testColorControl('Text color', config.textColor);
    }
    
    if (config.backgroundColor) {
      await this.testBackgroundColorControl('Background color', config.backgroundColor);
    }
  }

  /**
   * Test layout configuration for buttons block
   * @param {string} orientation - 'horizontal' or 'vertical'
   * @param {string} justification - 'left', 'center', 'right', 'space-between'
   * @param {string} alignment - 'top', 'center', 'bottom'
   */
  async testLayoutConfiguration(orientation, justification, alignment) {
    await this.selectBlock('spectra/buttons');
    
    // Set orientation
    await this.page.click('button[aria-label="Change layout"]');
    await this.page.click(`button[aria-label="${orientation === 'vertical' ? 'Vertical' : 'Horizontal'}"]`);
    
    // Set justification
    await this.page.click('button[aria-label="Change horizontal alignment"]');
    await this.page.click(`button[aria-label="Align ${justification}"]`);
    
    // Set alignment
    if (alignment) {
      await this.page.click('button[aria-label="Change vertical alignment"]');
      await this.page.click(`button[aria-label="Align ${alignment}"]`);
    }
    
    // Verify layout
    const buttonsBlock = this.page.locator('[data-type="spectra/buttons"]');
    const expectedFlexDirection = orientation === 'vertical' ? 'column' : 'row';
    await expect(buttonsBlock).toHaveCSS('flex-direction', expectedFlexDirection);
  }

  /**
   * Test button group spacing
   * @param {string} gapValue - Gap value between buttons
   */
  async testButtonGroupSpacing(gapValue) {
    await this.selectBlock('spectra/buttons');
    await this.page.fill('input[aria-label="Block spacing"]', gapValue);
    
    const buttonsBlock = this.page.locator('[data-type="spectra/buttons"]');
    await expect(buttonsBlock).toHaveCSS('gap', gapValue);
  }

  /**
   * Test hover effects on buttons container
   * @param {Object} hoverSettings - Hover effect settings
   */
  async testButtonsHoverEffects(hoverSettings) {
    await this.selectBlock('spectra/buttons');
    
    if (hoverSettings.backgroundColorHover) {
      await this.page.click('button:has-text("Background Hover")');
      await this.page.fill('input[type="text"][maxlength="7"]', hoverSettings.backgroundColorHover);
      await this.page.press('input[type="text"][maxlength="7"]', 'Enter');
    }
    
    if (hoverSettings.backgroundGradientHover) {
      await this.page.click('button:has-text("Background Hover")');
      await this.page.click('button:has-text("Gradient")');
      await this.page.click(`[aria-label="${hoverSettings.backgroundGradientHover}"]`);
    }
  }

  /**
   * Test buttons block alignment options
   * @param {string} alignmentType - 'left', 'center', 'right', 'wide', 'full'
   */
  async testButtonsAlignment(alignmentType) {
    await this.selectBlock('spectra/buttons');
    
    await this.page.click('button[aria-label="Change alignment"]');
    
    switch (alignmentType) {
      case 'left':
        await this.page.click('button[aria-label="Align left"]');
        break;
      case 'center':
        await this.page.click('button[aria-label="Align center"]');
        break;
      case 'right':
        await this.page.click('button[aria-label="Align right"]');
        break;
      case 'wide':
        await this.page.click('button[aria-label="Wide width"]');
        break;
      case 'full':
        await this.page.click('button[aria-label="Full width"]');
        break;
    }
    
    // Verify alignment
    const buttonsBlock = this.page.locator('[data-type="spectra/buttons"]');
    if (alignmentType === 'wide') {
      await expect(buttonsBlock).toHaveClass(/alignwide/);
    } else if (alignmentType === 'full') {
      await expect(buttonsBlock).toHaveClass(/alignfull/);
    } else {
      await expect(buttonsBlock).toHaveCSS('text-align', alignmentType);
    }
  }

  /**
   * Verify buttons block renders correctly on frontend
   * @param {Object} expectedStyles - Expected CSS styles
   * @param {number} expectedButtonCount - Expected number of buttons
   */
  async verifyButtonsFrontendRendering(expectedStyles, expectedButtonCount) {
    const buttonsContainer = this.page.locator('.wp-block-spectra-buttons');
    await expect(buttonsContainer).toBeVisible();
    
    // Verify container styles
    for (const [property, value] of Object.entries(expectedStyles)) {
      await expect(buttonsContainer).toHaveCSS(property, value);
    }
    
    // Verify button count
    if (expectedButtonCount) {
      await expect(buttonsContainer.locator('.wp-block-spectra-button')).toHaveCount(expectedButtonCount);
    }
  }

  /**
   * Test buttons block with different WordPress themes
   * @param {string} themeName - Name of the theme to test
   */
  async testButtonsWithTheme(themeName) {
    // Switch to theme (this would need to be implemented based on your test setup)
    await this.page.goto(`${config.baseURL}/wp-admin/themes.php`);
    await this.page.click(`button:has-text("Activate"):near(:text("${themeName}"))`);
    
    // Go back to editor
    await this.page.goto(`${config.baseURL}/wp-admin/post-new.php`);
    await this.page.waitForSelector('.edit-post-layout');
    
    // Insert and test buttons block
    await this.insertBlock('spectra/buttons', 'Buttons');
    await this.configureButton(0, { text: 'Theme Test Button' });
    
    // Verify it works with the theme
    await this.publishAndViewFrontend();
    await expect(this.page.locator('.wp-block-spectra-buttons')).toBeVisible();
  }

  /**
   * Test buttons block accessibility features
   */
  async testButtonsAccessibility() {
    await this.insertBlock('spectra/buttons', 'Buttons');
    await this.addButton();
    
    // Configure buttons with accessible labels
    await this.configureButton(0, { text: 'Download PDF' });
    await this.configureButton(1, { text: 'Contact Support' });
    
    await this.publishAndViewFrontend();
    
    // Test keyboard navigation
    await this.page.keyboard.press('Tab');
    await expect(this.page.locator('.wp-block-spectra-buttons .wp-block-spectra-button').first()).toBeFocused();
    
    await this.page.keyboard.press('Tab');
    await expect(this.page.locator('.wp-block-spectra-buttons .wp-block-spectra-button').last()).toBeFocused();
    
    // Test screen reader compatibility
    const buttons = this.page.locator('.wp-block-spectra-buttons .wp-block-spectra-button');
    await expect(buttons.first()).toHaveAttribute('role', 'button');
    await expect(buttons.last()).toHaveAttribute('role', 'button');
  }

  /**
   * Test buttons block performance with many buttons
   * @param {number} buttonCount - Number of buttons to create
   */
  async testButtonsPerformance(buttonCount) {
    await this.insertBlock('spectra/buttons', 'Buttons');
    
    // Add multiple buttons
    for (let i = 1; i < buttonCount; i++) {
      await this.addButton();
    }
    
    // Configure each button
    for (let i = 0; i < buttonCount; i++) {
      await this.configureButton(i, { text: `Button ${i + 1}` });
    }
    
    // Measure performance
    const startTime = Date.now();
    await this.publishAndViewFrontend();
    const endTime = Date.now();
    
    // Verify all buttons rendered
    await expect(this.page.locator('.wp-block-spectra-buttons .wp-block-spectra-button')).toHaveCount(buttonCount);
    
    // Log performance metrics
    console.log(`Buttons block with ${buttonCount} buttons rendered in ${endTime - startTime}ms`);
  }
}