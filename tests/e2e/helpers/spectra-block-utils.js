import { expect } from '@playwright/test';
import { CommonUtils } from './common-utils.js';

/**
 * Specialized helper functions for Spectra blocks
 * Extends CommonUtils with Spectra-specific functionality
 */
export class SpectraBlockUtils extends CommonUtils {
  constructor(page) {
    super(page);
  }

  /**
   * Insert a Spectra block by name
   * @param {string} blockName - The block name (e.g., 'spectra/buttons')
   * @param {string} searchTerm - Search term to find the block
   */
  async insertSpectraBlock(blockName, searchTerm = 'Spectra') {
    try {
      await this.openBlockInserter();
      await this.searchForBlock(searchTerm);

      // Look for the specific Spectra block
      const blockItems = this.page.locator('.block-editor-block-types-list__item');
      const blockCount = await blockItems.count();

      for (let i = 0; i < Math.min(blockCount, 20); i++) {
        const block = blockItems.nth(i);
        const titleElement = block.locator('.block-editor-block-types-list__item-title');

        if (await titleElement.isVisible()) {
          const title = await titleElement.textContent();
          // Check if title matches the search term or contains the block name
          if (title && (title.toLowerCase().includes(searchTerm.toLowerCase()) || 
                       title.toLowerCase().includes(blockName.split('/')[1]))) {
            await block.click();
            await this.page.waitForTimeout(3000);
            console.log(`✅ Spectra block inserted: ${title}`);
            
            // Wait for the block to be fully loaded
            await this.page.waitForSelector(`[data-type="${blockName}"]`, { timeout: 5000 });
            return this.page.locator(`[data-type="${blockName}"]`).first();
          }
        }
      }

      throw new Error(`Spectra block not found: ${blockName}`);
    } catch (error) {
      console.error(`❌ Insert Spectra block failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Insert Spectra buttons block
   */
  async insertButtonsBlock() {
    try {
      return await this.insertSpectraBlock('spectra/buttons', 'Spectra Buttons');
    } catch (error) {
      console.error(`❌ Insert buttons block failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Select a specific block by type
   * @param {string} blockType - Block type to select
   */
  async selectBlock(blockType) {
    try {
      const block = this.page.locator(`[data-type="${blockType}"]`).first();
      await block.click();
      await this.page.waitForTimeout(500);
      console.log(`✅ Block selected: ${blockType}`);
      return block;
    } catch (error) {
      console.error(`❌ Select block failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Configure button text
   * @param {Locator} buttonElement - Button element locator
   * @param {string} text - Text to set
   */
  async setButtonText(buttonElement, text) {
    try {
      await buttonElement.click();
      await this.page.waitForTimeout(500);

      // Try different approaches to set button text
      const textSelectors = [
        '[contenteditable="true"]',
        '.wp-block-button__link',
        'span[contenteditable="true"]',
        'div[contenteditable="true"]'
      ];

      for (const selector of textSelectors) {
        const textElement = buttonElement.locator(selector).first();
        if (await textElement.isVisible()) {
          await textElement.click();
          await this.page.waitForTimeout(200);
          
          // Clear and set new text
          const modifier = process.platform === 'darwin' ? 'Meta' : 'Control';
          await this.page.keyboard.press(`${modifier}+A`);
          await this.page.keyboard.type(text);
          console.log(`✅ Button text set: ${text}`);
          return true;
        }
      }

      throw new Error('Could not find text element in button');
    } catch (error) {
      console.error(`❌ Set button text failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Configure button link
   * @param {Locator} buttonElement - Button element locator
   * @param {string} url - URL to set
   * @param {boolean} openInNewTab - Whether to open in new tab
   */
  async setButtonLink(buttonElement, url, openInNewTab = false) {
    try {
      await buttonElement.click();
      await this.page.waitForTimeout(500);

      // Try to find and click link button in toolbar
      const linkSelectors = [
        '.block-editor-block-toolbar button[aria-label*="Link"]',
        '.block-editor-block-toolbar button[aria-label*="link"]',
        'button[aria-label="Link"]',
        'button:has-text("Link")'
      ];

      let linkConfigured = false;
      for (const selector of linkSelectors) {
        const linkButton = this.page.locator(selector).first();
        if (await linkButton.isVisible()) {
          await linkButton.click();
          await this.page.waitForTimeout(1000);

          // Look for URL input
          const urlInputSelectors = [
            '.block-editor-url-popover input',
            '.block-editor-url-input input',
            'input[type="url"]',
            'input[placeholder*="URL"]',
            'input[aria-label*="URL"]'
          ];

          for (const inputSelector of urlInputSelectors) {
            const urlInput = this.page.locator(inputSelector).first();
            if (await urlInput.isVisible()) {
              await urlInput.fill(url);
              await this.page.keyboard.press('Enter');
              linkConfigured = true;
              console.log(`✅ Button link set: ${url}`);
              break;
            }
          }

          if (linkConfigured) {
            // Configure new tab if requested
            if (openInNewTab) {
              await this.setLinkTarget(true);
            }
            break;
          }
        }
      }

      if (!linkConfigured) {
        throw new Error('Could not configure button link');
      }

      return true;
    } catch (error) {
      console.error(`❌ Set button link failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Set link target (new tab)
   * @param {boolean} openInNewTab - Whether to open in new tab
   */
  async setLinkTarget(openInNewTab = true) {
    try {
      const targetSelectors = [
        'button:has-text("Open in new tab")',
        'input[type="checkbox"] + label:has-text("new tab")',
        'label:has-text("Open in new tab")',
        'input[type="checkbox"][aria-label*="new tab"]'
      ];

      for (const selector of targetSelectors) {
        const targetControl = this.page.locator(selector).first();
        if (await targetControl.isVisible()) {
          if (openInNewTab) {
            await targetControl.click();
          }
          console.log(`✅ Link target configured: ${openInNewTab ? 'new tab' : 'same tab'}`);
          return true;
        }
      }
    } catch (error) {
      console.log(`⚠️ Link target configuration failed: ${error.message}`);
    }
  }

  /**
   * Add a button to buttons block
   * @param {Locator} buttonsBlock - Buttons block locator
   */
  async addButtonToBlock(buttonsBlock) {
    try {
      // Try to find button appender
      const appenderSelectors = [
        '.block-editor-button-block-appender',
        'button[aria-label*="Add"]',
        '.wp-block-button__inline-link'
      ];

      for (const selector of appenderSelectors) {
        const appender = buttonsBlock.locator(selector).first();
        if (await appender.isVisible()) {
          await appender.click();
          await this.page.waitForTimeout(1000);
          console.log('✅ Button added to block');
          return true;
        }
      }

      // Fallback: try keyboard method
      await buttonsBlock.click();
      await this.page.keyboard.press('ArrowRight');
      await this.page.keyboard.press('Enter');
      await this.page.waitForTimeout(1000);
      console.log('✅ Button added via keyboard');
      return true;
    } catch (error) {
      console.error(`❌ Add button failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Test color control
   * @param {string} colorLabel - Label of the color control
   * @param {string} colorValue - Color value to set
   */
  async testColorControl(colorLabel, colorValue) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const colorControl = inspector.locator(`text=${colorLabel}`).first();
      
      if (await colorControl.isVisible()) {
        await colorControl.click();
        await this.page.waitForTimeout(500);

        // Try to find color input
        const colorInputSelectors = [
          'input[type="text"][maxlength="7"]',
          'input[type="color"]',
          '.components-color-picker input'
        ];

        for (const selector of colorInputSelectors) {
          const colorInput = this.page.locator(selector).first();
          if (await colorInput.isVisible()) {
            await colorInput.fill(colorValue);
            await this.page.keyboard.press('Enter');
            console.log(`✅ Color control set: ${colorLabel} = ${colorValue}`);
            return true;
          }
        }
      }

      throw new Error(`Color control not found: ${colorLabel}`);
    } catch (error) {
      console.error(`❌ Color control failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Verify block exists on frontend
   * @param {string} blockClass - CSS class of the block on frontend
   */
  async verifyBlockOnFrontend(blockClass) {
    try {
      await this.goToFrontend();
      await this.page.waitForSelector(blockClass, { timeout: 10000 });
      await expect(this.page.locator(blockClass)).toBeVisible();
      console.log(`✅ Block verified on frontend: ${blockClass}`);
      return true;
    } catch (error) {
      console.error(`❌ Frontend verification failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Test button accessibility
   */
  async testButtonAccessibility() {
    try {
      // Test keyboard navigation
      await this.page.keyboard.press('Tab');
      const focusedElement = await this.page.locator(':focus');
      
      // Check if focused element is a button
      const tagName = await focusedElement.evaluate(el => el.tagName.toLowerCase());
      const role = await focusedElement.getAttribute('role');
      
      if (tagName === 'button' || tagName === 'a' || role === 'button') {
        console.log('✅ Button is keyboard accessible');
        return true;
      }
      
      throw new Error('Button is not keyboard accessible');
    } catch (error) {
      console.error(`❌ Accessibility test failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Get button count in buttons block
   * @param {Locator} buttonsBlock - Buttons block locator
   */
  async getButtonCount(buttonsBlock) {
    try {
      const count = await buttonsBlock.locator('[data-type="spectra/button"]').count();
      console.log(`Button count: ${count}`);
      return count;
    } catch (error) {
      console.error(`❌ Get button count failed: ${error.message}`);
      return 0;
    }
  }

  /**
   * Setup test environment (login, create post, dismiss modals)
   */
  async setupTestEnvironment() {
    try {
      await this.login();
      await this.createNewPost();
      console.log('✅ Test environment setup complete');
      return true;
    } catch (error) {
      console.error(`❌ Test environment setup failed: ${error.message}`);
      throw error;
    }
  }
}