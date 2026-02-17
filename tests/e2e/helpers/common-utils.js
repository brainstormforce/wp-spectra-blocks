import { expect } from '@playwright/test';
import config from '../config.js';

/**
 * Common utility functions for WordPress E2E tests
 * Provides reusable functions for login, post creation, navigation, etc.
 */
export class CommonUtils {
  constructor(page) {
    this.page = page;
  }

  /**
   * Login to WordPress admin
   * @param {string} username - Username (optional, uses config default)
   * @param {string} password - Password (optional, uses config default)
   */
  async login(username = config.username, password = config.password) {
    try {
      await this.page.goto(`${config.baseURL}/wp-admin/`);
      
      // Check if already logged in
      const isLoggedIn = await this.page.locator('#wpadminbar').isVisible();
      if (isLoggedIn) {
        console.log('✅ Already logged in');
        return true;
      }

      // Perform login
      await this.page.fill('#user_login', username);
      await this.page.fill('#user_pass', password);
      await this.page.click('#wp-submit');
      
      // Wait for successful login
      await this.page.waitForSelector('#wpadminbar', { timeout: 15000 });
      console.log('✅ Login successful');
      return true;
    } catch (error) {
      console.error(`❌ Login failed: ${error.message}`);
      await this.page.screenshot({ path: 'login-error.png' });
      throw error;
    }
  }

  /**
   * Create a new post and navigate to the editor
   * @param {string} postType - Post type (post, page, etc.)
   * @param {boolean} ensureLogin - Whether to ensure login before creating post
   */
  async createNewPost(postType = 'post', ensureLogin = true) {
    try {
      if (ensureLogin) {
        await this.login();
      }

      await this.page.goto(`${config.baseURL}/wp-admin/post-new.php?post_type=${postType}`);
      
      // Wait for editor with multiple fallback selectors
      const editorSelectors = [
        '.edit-post-layout',
        '.editor-styles-wrapper',
        '.block-editor-writing-flow',
        '.wp-block-post-content',
        '[data-type="core/paragraph"]',
        '.block-editor-block-list__layout'
      ];

      let editorLoaded = false;
      for (const selector of editorSelectors) {
        try {
          await this.page.waitForSelector(selector, { timeout: 8000 });
          console.log(`✅ Editor loaded with selector: ${selector}`);
          editorLoaded = true;
          break;
        } catch (e) {
          continue;
        }
      }

      if (!editorLoaded) {
        await this.page.screenshot({ path: 'editor-load-failure.png' });
        throw new Error('WordPress editor failed to load');
      }

      // Wait for editor to be fully interactive
      await this.page.waitForTimeout(3000);

      // Dismiss any welcome messages or tips
      await this.dismissWelcomeModals();

      console.log(`✅ New ${postType} created and editor loaded`);
      return true;
    } catch (error) {
      console.error(`❌ Create new post failed: ${error.message}`);
      await this.page.screenshot({ path: 'create-post-error.png' });
      throw error;
    }
  }

  /**
   * Dismiss welcome modals and tips
   */
  async dismissWelcomeModals() {
    try {
      // Dismiss welcome modal
      const welcomeModal = this.page.locator('.components-modal__header:has-text("Welcome")');
      if (await welcomeModal.isVisible()) {
        await this.page.click('button[aria-label="Close"]');
      }

      // Dismiss tips modal
      const tipsModal = this.page.locator('.edit-post-tips');
      if (await tipsModal.isVisible()) {
        await this.page.click('button[aria-label="Close"]');
      }

      // Dismiss any other modals
      const genericCloseButtons = this.page.locator('.components-modal__header button[aria-label="Close"]');
      const closeButtonCount = await genericCloseButtons.count();
      for (let i = 0; i < closeButtonCount; i++) {
        const closeButton = genericCloseButtons.nth(i);
        if (await closeButton.isVisible()) {
          await closeButton.click();
        }
      }
    } catch (error) {
      console.log(`⚠️ Could not dismiss all modals: ${error.message}`);
    }
  }

  /**
   * Save post as draft
   */
  async saveDraft() {
    try {
      await this.page.click('button:has-text("Save draft")');
      await this.page.waitForSelector('button:has-text("Saved")', { timeout: 10000 });
      console.log('✅ Post saved as draft');
    } catch (error) {
      console.error(`❌ Save draft failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Publish post
   */
  async publishPost() {
    try {
      // Click publish button
      await this.page.click('button:has-text("Publish")');
      
      // Handle publish panel if it appears
      const publishPanel = this.page.locator('.editor-post-publish-panel');
      if (await publishPanel.isVisible()) {
        await this.page.click('.editor-post-publish-panel button:has-text("Publish")');
      }
      
      // Wait for confirmation
      await this.page.waitForSelector('.components-snackbar:has-text("published")', { timeout: 10000 });
      console.log('✅ Post published successfully');
    } catch (error) {
      console.error(`❌ Publish failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Open block inserter
   */
  async openBlockInserter() {
    try {
      const inserterSelectors = [
        '[aria-label="Block Inserter"]',
        '[aria-label="Toggle block inserter"]',
        '.edit-post-header-toolbar__inserter-toggle',
        '.editor-document-tools__inserter-toggle',
        'button.block-editor-inserter__toggle'
      ];
      
      let inserterClicked = false;
      for (const selector of inserterSelectors) {
        const inserter = this.page.locator(selector).first();
        if (await inserter.isVisible()) {
          await inserter.click();
          inserterClicked = true;
          console.log(`✅ Block inserter opened with selector: ${selector}`);
          break;
        }
      }
      
      if (!inserterClicked) {
        throw new Error('Could not find block inserter button');
      }
      
      // Wait for inserter to open
      await this.page.waitForSelector('.block-editor-inserter__search, .block-editor-inserter__menu', { timeout: 10000 });
      return true;
    } catch (error) {
      console.error(`❌ Open block inserter failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Search for a block in the inserter
   * @param {string} searchTerm - Term to search for
   */
  async searchForBlock(searchTerm) {
    try {
      const searchInput = this.page.locator('.block-editor-inserter__search input').first();
      if (await searchInput.isVisible()) {
        await searchInput.fill(searchTerm);
        await this.page.waitForTimeout(2000);
        console.log(`✅ Searched for block: ${searchTerm}`);
      }
    } catch (error) {
      console.error(`❌ Search for block failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Insert a block by name
   * @param {string} blockName - Name of the block to insert
   * @param {string} searchTerm - Search term (optional)
   */
  async insertBlock(blockName, searchTerm = null) {
    try {
      await this.openBlockInserter();
      
      if (searchTerm) {
        await this.searchForBlock(searchTerm);
      }

      // Look for the block in the inserter
      const blockItems = this.page.locator('.block-editor-block-types-list__item');
      const blockCount = await blockItems.count();

      for (let i = 0; i < Math.min(blockCount, 20); i++) {
        const block = blockItems.nth(i);
        const titleElement = block.locator('.block-editor-block-types-list__item-title');

        if (await titleElement.isVisible()) {
          const title = await titleElement.textContent();
          if (title && (title.toLowerCase().includes(blockName.toLowerCase()) || 
                       (searchTerm && title.toLowerCase().includes(searchTerm.toLowerCase())))) {
            await block.click();
            await this.page.waitForTimeout(3000);
            console.log(`✅ Block inserted: ${title}`);
            return true;
          }
        }
      }

      throw new Error(`Block not found: ${blockName}`);
    } catch (error) {
      console.error(`❌ Insert block failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Navigate to frontend/preview
   */
  async goToFrontend() {
    try {
      // Try to find preview/view link
      const previewSelectors = [
        'a:has-text("Preview")',
        'a:has-text("View")',
        'a:has-text("View Post")',
        'a:has-text("View Page")'
      ];

      for (const selector of previewSelectors) {
        const previewLink = this.page.locator(selector).first();
        if (await previewLink.isVisible()) {
          await previewLink.click();
          console.log('✅ Navigated to frontend');
          return true;
        }
      }

      throw new Error('Could not find preview/view link');
    } catch (error) {
      console.error(`❌ Navigate to frontend failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Wait for element with multiple selectors
   * @param {string[]} selectors - Array of selectors to try
   * @param {number} timeout - Timeout in milliseconds
   */
  async waitForAnySelector(selectors, timeout = 10000) {
    const promises = selectors.map(selector => 
      this.page.waitForSelector(selector, { timeout }).catch(() => null)
    );
    
    const result = await Promise.race(promises);
    if (!result) {
      throw new Error(`None of the selectors were found: ${selectors.join(', ')}`);
    }
    return result;
  }

  /**
   * Click element with multiple selectors
   * @param {string[]} selectors - Array of selectors to try
   */
  async clickAnySelector(selectors) {
    for (const selector of selectors) {
      const element = this.page.locator(selector).first();
      if (await element.isVisible()) {
        await element.click();
        return true;
      }
    }
    throw new Error(`None of the selectors were clickable: ${selectors.join(', ')}`);
  }

  /**
   * Take a screenshot with timestamp
   * @param {string} name - Base name for the screenshot
   */
  async takeScreenshot(name) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `${name}-${timestamp}.png`;
    await this.page.screenshot({ path: filename, fullPage: true });
    console.log(`📸 Screenshot taken: ${filename}`);
  }

  /**
   * Wait for network to be idle
   * @param {number} timeout - Timeout in milliseconds
   */
  async waitForNetworkIdle(timeout = 5000) {
    try {
      await this.page.waitForLoadState('networkidle', { timeout });
      console.log('✅ Network is idle');
    } catch (error) {
      console.log(`⚠️ Network did not become idle: ${error.message}`);
    }
  }

  /**
   * Clear all blocks from the editor
   */
  async clearAllBlocks() {
    try {
      // Select all blocks
      const modifier = process.platform === 'darwin' ? 'Meta' : 'Control';
      await this.page.keyboard.press(`${modifier}+A`);
      await this.page.keyboard.press('Delete');
      console.log('✅ All blocks cleared');
    } catch (error) {
      console.error(`❌ Clear blocks failed: ${error.message}`);
      throw error;
    }
  }

  /**
   * Get current URL
   */
  async getCurrentUrl() {
    return this.page.url();
  }

  /**
   * Reload page and wait for editor
   */
  async reloadAndWaitForEditor() {
    try {
      await this.page.reload();
      await this.page.waitForSelector('.edit-post-layout', { timeout: 15000 });
      await this.dismissWelcomeModals();
      console.log('✅ Page reloaded and editor ready');
    } catch (error) {
      console.error(`❌ Reload failed: ${error.message}`);
      throw error;
    }
  }
}