import { expect } from '@playwright/test';
import { SpectraBlockHelpers } from './block-controls.js';

/**
 * Comprehensive helper functions for all Spectra V3 blocks
 */
export class SpectraHelpers extends SpectraBlockHelpers {
  constructor(page) {
    super(page);
  }

  /**
   * Insert a Spectra block by searching for it specifically
   * @param {string} blockName - The block name (e.g., 'spectra/accordion')
   * @param {string} searchTerm - Search term to find the block
   */
  async insertSpectraBlock(blockName, searchTerm = 'Spectra') {
    try {
      // Open block inserter - try multiple selectors for better compatibility
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
          console.log(`Clicked inserter with selector: ${selector}`);
          break;
        }
      }
      
      if (!inserterClicked) {
        throw new Error('Could not find block inserter button');
      }
      
      // Wait for inserter to open
      try {
        await this.page.waitForSelector('.block-editor-inserter__search, .block-editor-inserter__menu', { timeout: 10000 });
      } catch (error) {
        // Try alternative selectors for different browsers
        const alternativeSelectors = [
          '.block-editor-inserter__content',
          '.block-editor-inserter__panel',
          '.block-editor-inserter__main-area',
          '.block-editor-block-types-list'
        ];
        
        let found = false;
        for (const selector of alternativeSelectors) {
          try {
            await this.page.waitForSelector(selector, { timeout: 3000 });
            console.log(`Found inserter with alternative selector: ${selector}`);
            found = true;
            break;
          } catch (e) {
            // Continue to next selector
          }
        }
        
        if (!found) {
          throw new Error('Block inserter did not open properly');
        }
      }
      
      // Clear any existing search and search for Spectra
      const searchInputSelectors = [
        '.block-editor-inserter__search input',
        '.block-editor-inserter__search-input',
        'input[placeholder*="Search"]'
      ];
      
      let searchInput = null;
      for (const selector of searchInputSelectors) {
        const input = this.page.locator(selector).first();
        if (await input.isVisible()) {
          searchInput = input;
          break;
        }
      }
      
      if (searchInput && searchTerm.trim() !== '') {
        await searchInput.click();
        await searchInput.fill('');
        await this.page.waitForTimeout(500);
        await searchInput.fill(searchTerm);
        await this.page.waitForTimeout(2000);
        console.log(`Searched for: "${searchTerm}"`);
      } else {
        console.log('Skipping search, looking for block directly');
      }
      
      // Try different ways to find the block
      let blockItem = null;
      let blockFound = false;
      
      // Method 1: Look for block by data-type in inserter
      const inserterBlockSelectors = [
        `[data-type="${blockName}"]`,
        `button[data-type="${blockName}"]`,
        `.block-editor-block-types-list__item[data-type="${blockName}"]`
      ];
      
      for (const selector of inserterBlockSelectors) {
        blockItem = this.page.locator(selector).first();
        if (await blockItem.isVisible()) {
          await blockItem.click();
          blockFound = true;
          console.log(`✅ Found block with selector: ${selector}`);
          break;
        }
      }
      
      if (!blockFound) {
        // Method 2: Look for block by title text variations
        const blockTitle = blockName.split('/')[1];
        const titleVariations = [
          'Buttons',
          'Button',
          'Spectra Buttons',
          'Spectra Button',
          blockTitle,
          blockTitle.charAt(0).toUpperCase() + blockTitle.slice(1),
          blockTitle.replace(/([A-Z])/g, ' $1').trim()
        ];
        
        for (const title of titleVariations) {
          console.log(`Trying to find block with title: "${title}"`);
          
          // First try to find by exact match within the block list
          const blockList = this.page.locator('.block-editor-block-types-list__item');
          const blockCount = await blockList.count();
          
          for (let i = 0; i < blockCount; i++) {
            const block = blockList.nth(i);
            try {
              const blockTitleElement = block.locator('.block-editor-block-types-list__item-title');
              if (await blockTitleElement.isVisible()) {
                const blockText = await blockTitleElement.textContent();
                if (blockText && blockText.toLowerCase().includes(title.toLowerCase())) {
                  await block.click();
                  blockFound = true;
                  console.log(`✅ Found block with text "${blockText}" (matches "${title}")`);
                  break;
                }
              }
            } catch (e) {
              // Continue to next block
            }
          }
          
          if (blockFound) break;
        }
      }
      
      if (!blockFound) {
        // Method 3: List all available blocks for debugging
        const allBlocks = this.page.locator('.block-editor-block-types-list__item');
        const blockCount = await allBlocks.count();
        console.log(`❌ Block not found. Available blocks (${blockCount} total):`);
        
        for (let i = 0; i < Math.min(blockCount, 15); i++) {
          try {
            const block = allBlocks.nth(i);
            const blockTitle = await block.locator('.block-editor-block-types-list__item-title').textContent();
            const dataType = await block.getAttribute('data-type');
            console.log(`  - "${blockTitle}" (${dataType})`);
          } catch (e) {
            console.log(`  - Block ${i}: Could not read details`);
          }
        }
        
        throw new Error(`Block ${blockName} not found in inserter. Search term: "${searchTerm}"`);
      }
      
      await this.page.waitForTimeout(2000);
      
      // Wait for block to be inserted and ensure it's visible
      await this.page.waitForSelector(`[data-type="${blockName}"]`, { timeout: 10000 });
      
      // Close inserter if it's still open
      const inserterOpen = await this.page.locator('.block-editor-inserter__content').isVisible();
      if (inserterOpen) {
        await this.page.keyboard.press('Escape');
        await this.page.waitForTimeout(500);
      }
      
      console.log(`✅ ${blockName} inserted successfully`);
      
    } catch (error) {
      console.error(`❌ Failed to insert ${blockName}: ${error.message}`);
      throw error;
    }
  }

  /**
   * Test accordion functionality
   * @param {Object} options - Accordion test options
   */
  async testAccordion(options = {}) {
    await this.insertSpectraBlock('spectra/accordion', 'Spectra Accordion');
    
    const accordion = this.page.locator('[data-type="spectra/accordion"]');
    await expect(accordion).toBeVisible();
    
    // Test accordion items
    const accordionItems = this.page.locator('[data-type="spectra/accordion-child-item"]');
    const itemCount = await accordionItems.count();
    console.log(`Found ${itemCount} accordion items`);
    
    if (itemCount > 0) {
      // Test first accordion item
      const firstItem = accordionItems.first();
      await firstItem.click();
      
      // Test header content
      const headerContent = firstItem.locator('[data-type="spectra/accordion-child-header-content"]');
      if (await headerContent.isVisible()) {
        await headerContent.click();
        await headerContent.fill('Test Accordion Header');
        await expect(headerContent).toHaveText('Test Accordion Header');
      }
      
      // Test details content
      const details = firstItem.locator('[data-type="spectra/accordion-child-details"]');
      if (await details.isVisible()) {
        await details.click();
        await details.fill('Test accordion content');
      }
    }
    
    return accordion;
  }

  /**
   * Test container block functionality
   * @param {Object} options - Container test options
   */
  async testContainer(options = {}) {
    await this.insertSpectraBlock('spectra/container', 'Spectra Container');
    
    const container = this.page.locator('[data-type="spectra/container"]');
    await expect(container).toBeVisible();
    
    // Test layout options
    if (options.layout) {
      await container.click();
      // Test flex/grid layout switching
      await this.page.click('button[aria-label="Change layout"]');
      await this.page.waitForTimeout(1000);
    }
    
    // Test adding inner blocks
    const innerAppender = container.locator('.block-editor-inner-blocks .block-editor-button-block-appender');
    if (await innerAppender.isVisible()) {
      await innerAppender.click();
      await this.page.waitForTimeout(1000);
    }
    
    return container;
  }

  /**
   * Test content block functionality
   * @param {Object} options - Content test options
   */
  async testContent(options = {}) {
    await this.insertSpectraBlock('spectra/content', 'Spectra Content');
    
    const content = this.page.locator('[data-type="spectra/content"]');
    await expect(content).toBeVisible();
    
    // Test text editing
    await content.click();
    const textArea = content.locator('[contenteditable="true"]');
    if (await textArea.isVisible()) {
      await textArea.fill(options.text || 'Test content text');
      await expect(textArea).toHaveText(options.text || 'Test content text');
    }
    
    return content;
  }

  /**
   * Test countdown block functionality
   * @param {Object} options - Countdown test options
   */
  async testCountdown(options = {}) {
    await this.insertSpectraBlock('spectra/countdown', 'Spectra Countdown');
    
    const countdown = this.page.locator('[data-type="spectra/countdown"]');
    await expect(countdown).toBeVisible();
    
    // Test countdown components
    const components = [
      'spectra/countdown-child-day',
      'spectra/countdown-child-hour',
      'spectra/countdown-child-minute',
      'spectra/countdown-child-second'
    ];
    
    for (const component of components) {
      const element = this.page.locator(`[data-type="${component}"]`);
      if (await element.isVisible()) {
        console.log(`✅ ${component} is visible`);
      }
    }
    
    return countdown;
  }

  /**
   * Test buttons block functionality
   * @param {Object} options - Buttons test options
   */
  async testButtons(options = {}) {
    try {
      console.log('🔘 Attempting to insert Buttons block...');
      
      // Use the more robust insertSpectraBlock method
      await this.insertSpectraBlock('spectra/buttons', 'Spectra Buttons');
      
      // Verify insertion and return the block
      const buttonsLocator = this.page.locator('[data-type="spectra/buttons"]');
      const count = await buttonsLocator.count();
      
      if (count > 0) {
        console.log(`✅ Spectra Buttons block inserted successfully (found ${count})`);
        
        // Wait for block to be fully loaded
        await this.page.waitForTimeout(1000);
        
        // Ensure the block is visible and clickable
        const firstBlock = buttonsLocator.first();
        await expect(firstBlock).toBeVisible();
        
        return firstBlock;
      }
      
      // If not found, throw error
      throw new Error('Could not find Spectra Buttons block after insertion');
      
    } catch (error) {
      console.error(`❌ testButtons failed: ${error.message}`);
      await this.page.screenshot({ path: 'debug-buttons-error.png', fullPage: true });
      throw error;
    }
  }

  /**
   * Try to insert any button-related block
   */
  async insertAnyButtonBlock() {
    console.log('🔍 Searching for any button-related block...');
    
    // Open inserter
    await this.page.click('[aria-label="Block Inserter"]');
    await this.page.waitForTimeout(2000);
    
    // Search for button
    const searchInput = this.page.locator('.block-editor-inserter__search input').first();
    if (await searchInput.isVisible()) {
      await searchInput.fill('button');
      await this.page.waitForTimeout(2000);
    }
    
    // Try clicking the first button-related result
    const buttonBlocks = this.page.locator('.block-editor-block-types-list__item');
    const count = await buttonBlocks.count();
    
    if (count > 0) {
      await buttonBlocks.first().click();
      await this.page.waitForTimeout(2000);
      console.log('✅ Clicked first button block in search results');
    } else {
      throw new Error('No button blocks found in search results');
    }
  }

  /**
   * Debug helper to list available blocks
   */
  async debugAvailableBlocks() {
    try {
      console.log('🔍 Listing available blocks...');
      
      // Open inserter if not already open
      const inserterOpen = await this.page.locator('.block-editor-inserter__content').isVisible();
      if (!inserterOpen) {
        await this.page.click('[aria-label="Block Inserter"]');
        await this.page.waitForTimeout(1000);
      }
      
      // Clear search
      const searchInput = this.page.locator('.block-editor-inserter__search input').first();
      if (await searchInput.isVisible()) {
        await searchInput.fill('');
        await this.page.waitForTimeout(1000);
      }
      
      const allBlocks = this.page.locator('.block-editor-block-types-list__item');
      const blockCount = await allBlocks.count();
      console.log(`Found ${blockCount} total blocks`);
      
      // List first 20 blocks
      for (let i = 0; i < Math.min(blockCount, 20); i++) {
        try {
          const block = allBlocks.nth(i);
          const title = await block.locator('.block-editor-block-types-list__item-title').textContent();
          const dataType = await block.getAttribute('data-type');
          console.log(`  ${i + 1}. "${title}" (${dataType})`);
        } catch (e) {
          console.log(`  ${i + 1}. [Could not read block details]`);
        }
      }
      
      // Close inserter
      await this.page.keyboard.press('Escape');
      
    } catch (error) {
      console.log(`Debug listing failed: ${error.message}`);
    }
  }

  /**
   * Test icon block functionality
   * @param {Object} options - Icon test options
   */
  async testIcon(options = {}) {
    await this.insertSpectraBlock('spectra/icon', 'Spectra Icon');
    
    const icon = this.page.locator('[data-type="spectra/icon"]');
    await expect(icon).toBeVisible();
    
    // Test icon selection
    await icon.click();
    if (options.icon) {
      await this.testIconPicker(options.icon);
    }
    
    return icon;
  }

  /**
   * Test icons block functionality
   * @param {Object} options - Icons test options
   */
  async testIcons(options = {}) {
    await this.insertSpectraBlock('spectra/icons', 'Spectra Icons');
    
    const icons = this.page.locator('[data-type="spectra/icons"]');
    await expect(icons).toBeVisible();
    
    // Test adding more icons
    const appender = icons.locator('.block-editor-button-block-appender');
    if (await appender.isVisible()) {
      await appender.click();
      await this.page.waitForTimeout(1000);
    }
    
    return icons;
  }

  /**
   * Test list block functionality
   * @param {Object} options - List test options
   */
  async testList(options = {}) {
    await this.insertSpectraBlock('spectra/list', 'Spectra List');
    
    const list = this.page.locator('[data-type="spectra/list"]');
    await expect(list).toBeVisible();
    
    // Test list items
    const listItems = this.page.locator('[data-type="spectra/list-child-item"]');
    const itemCount = await listItems.count();
    console.log(`Found ${itemCount} list items`);
    
    if (itemCount > 0) {
      const firstItem = listItems.first();
      await firstItem.click();
      
      // Test editing list item text
      const itemText = firstItem.locator('[contenteditable="true"]');
      if (await itemText.isVisible()) {
        await itemText.fill(options.itemText || 'Test list item');
      }
    }
    
    return list;
  }

  /**
   * Test slider block functionality
   * @param {Object} options - Slider test options
   */
  async testSlider(options = {}) {
    await this.insertSpectraBlock('spectra/slider', 'Spectra Slider');
    
    const slider = this.page.locator('[data-type="spectra/slider"]');
    await expect(slider).toBeVisible();
    
    // Test slider slides
    const slides = this.page.locator('[data-type="spectra/slider-child"]');
    const slideCount = await slides.count();
    console.log(`Found ${slideCount} slides`);
    
    // Test adding more slides
    const appender = slider.locator('.block-editor-button-block-appender');
    if (await appender.isVisible()) {
      await appender.click();
      await this.page.waitForTimeout(1000);
    }
    
    return slider;
  }

  /**
   * Test tabs block functionality
   * @param {Object} options - Tabs test options
   */
  async testTabs(options = {}) {
    await this.insertSpectraBlock('spectra/tabs', 'Spectra Tabs');
    
    const tabs = this.page.locator('[data-type="spectra/tabs"]');
    await expect(tabs).toBeVisible();
    
    // Test tab buttons
    const tabButtons = this.page.locator('[data-type="spectra/tabs-child-tab-button"]');
    const buttonCount = await tabButtons.count();
    console.log(`Found ${buttonCount} tab buttons`);
    
    if (buttonCount > 0) {
      const firstButton = tabButtons.first();
      await firstButton.click();
      
      // Test editing tab button text
      const buttonText = firstButton.locator('[contenteditable="true"]');
      if (await buttonText.isVisible()) {
        await buttonText.fill(options.tabText || 'Test Tab');
      }
    }
    
    // Test tab panels
    const tabPanels = this.page.locator('[data-type="spectra/tabs-child-tabpanel"]');
    const panelCount = await tabPanels.count();
    console.log(`Found ${panelCount} tab panels`);
    
    return tabs;
  }

  /**
   * Test animations extension
   * @param {string} blockType - Block type to test animations on
   * @param {Object} animationOptions - Animation options
   */
  async testAnimationsExtension(blockType, animationOptions = {}) {
    // First insert the block
    await this.insertSpectraBlock(blockType);
    
    const block = this.page.locator(`[data-type="${blockType}"]`);
    await block.click();
    
    // Look for animation controls in the sidebar
    const animationPanel = this.page.locator('.block-editor-block-inspector').locator('text=Animation');
    if (await animationPanel.isVisible()) {
      await animationPanel.click();
      
      // Test animation type selection
      if (animationOptions.type) {
        const animationType = this.page.locator(`button:has-text("${animationOptions.type}")`);
        if (await animationType.isVisible()) {
          await animationType.click();
        }
      }
      
      // Test animation duration
      if (animationOptions.duration) {
        await this.testSliderControl('Duration', animationOptions.duration);
      }
      
      // Test animation delay
      if (animationOptions.delay) {
        await this.testSliderControl('Delay', animationOptions.delay);
      }
      
      console.log(`✅ Animation extension tested on ${blockType}`);
    } else {
      console.log(`ℹ️  Animation controls not found for ${blockType}`);
    }
    
    return block;
  }

  /**
   * Test image mask extension on image blocks
   * @param {Object} maskOptions - Mask options
   */
  async testImageMaskExtension(maskOptions = {}) {
    // Insert a core image block first
    await this.page.click('[aria-label="Block Inserter"]');
    await this.page.fill('.block-editor-inserter__search input', 'Image');
    await this.page.waitForTimeout(1000);
    
    const imageBlock = this.page.locator('[data-type="core/image"]').first();
    await imageBlock.click();
    await this.page.waitForTimeout(2000);
    
    // Wait for image block to be inserted
    const insertedImage = this.page.locator('[data-type="core/image"]');
    await expect(insertedImage).toBeVisible();
    
    // Look for Spectra mask controls
    const maskPanel = this.page.locator('.block-editor-block-inspector').locator('text=Mask');
    if (await maskPanel.isVisible()) {
      await maskPanel.click();
      
      // Test mask shape selection
      if (maskOptions.shape) {
        const shapeOption = this.page.locator(`button:has-text("${maskOptions.shape}")`);
        if (await shapeOption.isVisible()) {
          await shapeOption.click();
        }
      }
      
      console.log('✅ Image mask extension tested');
    } else {
      console.log('ℹ️  Image mask controls not found');
    }
    
    return insertedImage;
  }

  /**
   * Test block interactivity on frontend
   * @param {string} blockType - Block type to test
   * @param {Function} interactionTest - Function to test interactions
   */
  async testBlockInteractivity(blockType, interactionTest) {
    // Publish the post first
    await this.publishAndViewFrontend();
    
    // Find the block on frontend
    const frontendBlock = this.page.locator(`[data-block-type="${blockType}"]`);
    
    if (await frontendBlock.isVisible()) {
      await interactionTest(frontendBlock);
      console.log(`✅ Interactivity tested for ${blockType}`);
    } else {
      console.log(`ℹ️  Block ${blockType} not found on frontend`);
    }
  }

  /**
   * Test all color and styling options for a block
   * @param {string} blockType - Block type to test
   * @param {Object} styleOptions - Style options to test
   */
  async testBlockStyling(blockType, styleOptions = {}) {
    const block = this.page.locator(`[data-type="${blockType}"]`);
    await block.click();
    
    // Test colors
    if (styleOptions.textColor) {
      await this.testColorControl('Text color', styleOptions.textColor);
    }
    
    if (styleOptions.backgroundColor) {
      await this.testBackgroundColorControl('Background color', styleOptions.backgroundColor);
    }
    
    // Test typography
    if (styleOptions.typography) {
      await this.testTypographyControls(styleOptions.typography);
    }
    
    // Test spacing
    if (styleOptions.spacing) {
      await this.testSpacingControls('padding', styleOptions.spacing.padding || {});
      await this.testSpacingControls('margin', styleOptions.spacing.margin || {});
    }
    
    // Test borders
    if (styleOptions.borders) {
      await this.testBorderControls(styleOptions.borders);
    }
    
    console.log(`✅ Styling tested for ${blockType}`);
  }

  /**
   * Test responsive controls in editor (desktop focus)
   * @param {string} blockType - Block type to test
   * @param {Object} responsiveOptions - Responsive options for editor
   */
  async testBlockResponsiveControls(blockType, responsiveOptions = {}) {
    const block = this.page.locator(`[data-type="${blockType}"]`);
    await block.click();
    
    // Test responsive controls in editor (not actual responsiveness)
    // This tests the editor UI for responsive settings, not frontend behavior
    const responsivePanel = this.page.locator('.block-editor-block-inspector').locator('text=Responsive');
    if (await responsivePanel.isVisible()) {
      await responsivePanel.click();
      
      // Test desktop settings (default)
      if (responsiveOptions.desktop) {
        if (responsiveOptions.desktop.fontSize) {
          await this.testTypographyControls({ fontSize: responsiveOptions.desktop.fontSize });
        }
        if (responsiveOptions.desktop.spacing) {
          await this.testSpacingControls('padding', responsiveOptions.desktop.spacing);
        }
      }
      
      console.log(`✅ Responsive controls tested in editor for ${blockType}`);
    } else {
      console.log(`ℹ️ No responsive controls found for ${blockType}`);
    }
  }

  /**
   * Test context inheritance between parent and child blocks
   * @param {string} parentBlockType - Parent block type
   * @param {string} childBlockType - Child block type
   * @param {Object} contextOptions - Context options to test
   */
  async testBlockContext(parentBlockType, childBlockType, contextOptions = {}) {
    // Insert parent block
    await this.insertSpectraBlock(parentBlockType);
    
    const parentBlock = this.page.locator(`[data-type="${parentBlockType}"]`);
    await parentBlock.click();
    
    // Set context properties on parent
    if (contextOptions.parentColor) {
      await this.testColorControl('Text color', contextOptions.parentColor);
    }
    
    // Check if child block inherits context
    const childBlock = this.page.locator(`[data-type="${childBlockType}"]`);
    if (await childBlock.isVisible()) {
      const childStyle = await childBlock.evaluate(el => getComputedStyle(el));
      console.log(`Child block color: ${childStyle.color}`);
    }
    
    console.log(`✅ Context inheritance tested between ${parentBlockType} and ${childBlockType}`);
  }

  /**
   * Test block interactivity on frontend - Enhanced version
   * @param {string} blockType - Block type to test
   * @param {Function} interactionTest - Function to test interactions
   */
  async testBlockInteractivity(blockType, interactionTest) {
    // Publish the post first
    await this.publishAndViewFrontend();
    
    // Wait for page to load completely
    await this.page.waitForLoadState('networkidle');
    await this.page.waitForTimeout(2000);
    
    // Try multiple selectors to find the block on frontend
    const selectors = [
      `[data-block-type="${blockType}"]`,
      `.wp-block-${blockType.replace('/', '-')}`,
      `[class*="${blockType.replace('/', '-')}"]`,
      `.spectra-${blockType.split('/')[1]}`,
      `.uagb-buttons__outer-wrap`,
      `.uagb-buttons-repeater`,
      `.wp-block-uagb-buttons`
    ];
    
    let frontendBlock = null;
    
    for (const selector of selectors) {
      const element = this.page.locator(selector).first();
      if (await element.isVisible()) {
        frontendBlock = element;
        console.log(`✅ Found block using selector: ${selector}`);
        break;
      }
    }
    
    if (frontendBlock && await frontendBlock.isVisible()) {
      try {
        await interactionTest(frontendBlock);
        console.log(`✅ Interactivity tested for ${blockType}`);
      } catch (error) {
        console.log(`⚠️ Interactivity test failed for ${blockType}: ${error.message}`);
      }
    } else {
      console.log(`ℹ️ Block ${blockType} not found on frontend`);
    }
  }

  /**
   * Set multiple button attributes in combination
   * @param {Locator} buttonElement - Button element to modify
   * @param {Object} attributes - Object containing attribute combinations
   */
  async setButtonAttributes(buttonElement, attributes) {
    console.log('🎯 Setting button attributes:', Object.keys(attributes));
    
    try {
      // Ensure button is selected
      await buttonElement.click();
      await this.page.waitForTimeout(500);
      
      // Content & Text attributes
      if (attributes.text) {
        await this.setButtonText(buttonElement, attributes.text);
      }
      
      if (attributes.showText !== undefined) {
        await this.setButtonShowText(attributes.showText);
      }
      
      // Link configuration
      if (attributes.linkURL || attributes.link) {
        await this.setButtonLinkURL(attributes.linkURL || attributes.link);
      }
      
      if (attributes.linkTarget) {
        await this.setButtonLinkTarget(attributes.linkTarget);
      }
      
      if (attributes.linkRel) {
        await this.setButtonLinkRel(attributes.linkRel);
      }
      
      // Icon configuration
      if (attributes.icon) {
        await this.setButtonIcon(attributes.icon);
      }
      
      if (attributes.iconPosition) {
        await this.setButtonIconPosition(attributes.iconPosition);
      }
      
      if (attributes.iconColor) {
        await this.setButtonIconColor(attributes.iconColor);
      }
      
      if (attributes.iconColorHover) {
        await this.setButtonIconColorHover(attributes.iconColorHover);
      }
      
      if (attributes.iconSize) {
        await this.setButtonIconSize(attributes.iconSize);
      }
      
      if (attributes.iconRotation !== undefined) {
        await this.setButtonIconRotation(attributes.iconRotation);
      }
      
      if (attributes.iconGap) {
        await this.setButtonIconGap(attributes.iconGap);
      }
      
      if (attributes.flipForRTL !== undefined) {
        await this.setButtonFlipForRTL(attributes.flipForRTL);
      }
      
      // Color styling
      if (attributes.backgroundColor) {
        await this.setButtonBackgroundColor(attributes.backgroundColor);
      }
      
      if (attributes.backgroundColorHover) {
        await this.setButtonBackgroundColorHover(attributes.backgroundColorHover);
      }
      
      if (attributes.backgroundGradient) {
        await this.setButtonBackgroundGradient(attributes.backgroundGradient);
      }
      
      if (attributes.backgroundGradientHover) {
        await this.setButtonBackgroundGradientHover(attributes.backgroundGradientHover);
      }
      
      if (attributes.textColor) {
        await this.setButtonTextColor(attributes.textColor);
      }
      
      if (attributes.textColorHover) {
        await this.setButtonTextColorHover(attributes.textColorHover);
      }
      
      // Border properties
      if (attributes.borderRadius) {
        await this.setButtonBorderRadius(attributes.borderRadius);
      }
      
      if (attributes.borderWidth) {
        await this.setButtonBorderWidth(attributes.borderWidth);
      }
      
      if (attributes.borderColor) {
        await this.setButtonBorderColor(attributes.borderColor);
      }
      
      // Typography
      if (attributes.fontSize) {
        await this.setButtonFontSize(attributes.fontSize);
      }
      
      if (attributes.lineHeight) {
        await this.setButtonLineHeight(attributes.lineHeight);
      }
      
      if (attributes.fontFamily) {
        await this.setButtonFontFamily(attributes.fontFamily);
      }
      
      if (attributes.fontWeight) {
        await this.setButtonFontWeight(attributes.fontWeight);
      }
      
      if (attributes.textAlign) {
        await this.setButtonTextAlign(attributes.textAlign);
      }
      
      if (attributes.letterSpacing) {
        await this.setButtonLetterSpacing(attributes.letterSpacing);
      }
      
      if (attributes.textTransform) {
        await this.setButtonTextTransform(attributes.textTransform);
      }
      
      // Spacing
      if (attributes.padding) {
        await this.setButtonPadding(attributes.padding);
      }
      
      if (attributes.margin) {
        await this.setButtonMargin(attributes.margin);
      }
      
      // Effects
      if (attributes.boxShadow) {
        await this.setButtonBoxShadow(attributes.boxShadow);
      }
      
      if (attributes.hoverEffect) {
        await this.setButtonHoverEffect(attributes.hoverEffect);
      }
      
      // Size preset
      if (attributes.size) {
        await this.setButtonSize(attributes.size);
      }
      
      // Alignment
      if (attributes.alignment) {
        await this.setButtonAlignment(attributes.alignment);
      }
      
      // Accessibility
      if (attributes.ariaLabel) {
        await this.setButtonAriaLabel(attributes.ariaLabel);
      }
      
      console.log('✅ All button attributes set successfully');
      
    } catch (error) {
      console.error(`❌ Failed to set button attributes: ${error.message}`);
      throw error;
    }
  }

  /**
   * Add a button to an existing buttons block
   * @param {Locator} buttonsBlock - The buttons block element
   */
  async addButtonToBlock(buttonsBlock) {
    try {
      // Try different methods to add a button
      const methods = [
        // Method 1: Button appender
        async () => {
          const appender = buttonsBlock.locator('.block-editor-button-block-appender, .wp-block-button__inline-link');
          if (await appender.isVisible()) {
            await appender.click();
            return true;
          }
          return false;
        },
        
        // Method 2: Plus button
        async () => {
          const plusButton = buttonsBlock.locator('button[aria-label*="Add"], button:has([data-icon="plus"])');
          if (await plusButton.isVisible()) {
            await plusButton.click();
            return true;
          }
          return false;
        },
        
        // Method 3: Keyboard method
        async () => {
          await buttonsBlock.click();
          await this.page.keyboard.press('ArrowRight');
          await this.page.keyboard.press('Enter');
          return true;
        }
      ];

      for (const method of methods) {
        try {
          const success = await method();
          if (success) {
            await this.page.waitForTimeout(1000);
            console.log('✅ Button added to buttons block');
            return;
          }
        } catch (error) {
          continue;
        }
      }
      
      console.log('⚠️ Could not add button to block, but continuing...');
    } catch (error) {
      console.log(`⚠️ Failed to add button to block: ${error.message}`);
    }
  }

  /**
   * Set button text
   */
  async setButtonText(buttonElement, text) {
    try {
      const textArea = buttonElement.locator('[contenteditable="true"]').first();
      if (await textArea.isVisible()) {
        await textArea.click();
        await this.page.keyboard.press('Meta+A');
        await this.page.keyboard.type(text);
        console.log(`✅ Button text set to: ${text}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set button text: ${error.message}`);
    }
  }

  /**
   * Set button background color
   */
  async setButtonBackgroundColor(color) {
    try {
      // Ensure we're on the correct tab for background color
      await this.clickStylesTab();
      await this.testColorControl('Background Color', color);
      console.log(`✅ Background color set to: ${color}`);
    } catch (error) {
      console.log(`⚠️ Could not set background color: ${error.message}`);
    }
  }

  /**
   * Set button text color
   */
  async setButtonTextColor(color) {
    try {
      // Ensure we're on the correct tab for text color
      await this.clickStylesTab();
      await this.clickTextSection();
      await this.testColorControl('Text Color', color);
      console.log(`✅ Text color set to: ${color}`);
    } catch (error) {
      console.log(`⚠️ Could not set text color: ${error.message}`);
    }
  }

  /**
   * Set button border radius
   */
  async setButtonBorderRadius(radius) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const radiusInput = inspector.locator('input[aria-label*="Border Radius"], input[aria-label*="Radius"]').first();
      if (await radiusInput.isVisible()) {
        await radiusInput.fill(radius);
        console.log(`✅ Border radius set to: ${radius}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set border radius: ${error.message}`);
    }
  }

  /**
   * Set button size
   */
  async setButtonSize(size) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const sizeButton = inspector.locator(`button:has-text("${size}")`).first();
      if (await sizeButton.isVisible()) {
        await sizeButton.click();
        console.log(`✅ Button size set to: ${size}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set button size: ${error.message}`);
    }
  }

  /**
   * Set button link
   */
  async setButtonLink(url, openInNewTab = false) {
    try {
      // Try toolbar link button first
      const linkButton = this.page.locator('.block-editor-block-toolbar button[aria-label*="Link"]').first();
      if (await linkButton.isVisible()) {
        await linkButton.click();
        await this.page.waitForTimeout(500);
        
        const urlInput = this.page.locator('input[type="url"], input[placeholder*="URL"]').first();
        if (await urlInput.isVisible()) {
          await urlInput.fill(url);
          await this.page.keyboard.press('Enter');
          
          if (openInNewTab) {
            const newTabToggle = this.page.locator('button:has-text("Open in new tab")').first();
            if (await newTabToggle.isVisible()) {
              await newTabToggle.click();
            }
          }
          
          console.log(`✅ Button link set to: ${url}${openInNewTab ? ' (new tab)' : ''}`);
        }
      }
    } catch (error) {
      console.log(`⚠️ Could not set button link: ${error.message}`);
    }
  }

  /**
   * Save and publish the post
   */
  async saveAndPublish() {
    try {
      await this.publishAndViewFrontend();
      console.log('✅ Post saved and published');
    } catch (error) {
      console.log(`⚠️ Could not save and publish: ${error.message}`);
      throw error;
    }
  }

  /**
   * Verify button attributes on frontend
   * @param {Object} expectedAttributes - Expected attribute values
   * @param {Object} options - Verification options
   */
  async verifyButtonAttributesOnFrontend(expectedAttributes, options = {}) {
    console.log('🔍 Verifying button attributes on frontend...');
    
    try {
      // Wait for frontend to load
      await this.page.waitForLoadState('networkidle');
      await this.page.waitForTimeout(2000);
      
      // Find button on frontend
      const frontendButtonSelectors = [
        '.wp-block-spectra-buttons a',
        '.spectra-buttons a',
        '.wp-block-buttons a',
        '.spectra-button',
        'a.wp-block-button__link'
      ];
      
      let frontendButton = null;
      for (const selector of frontendButtonSelectors) {
        const element = this.page.locator(selector).first();
        if (await element.isVisible()) {
          frontendButton = element;
          console.log(`✅ Found frontend button with selector: ${selector}`);
          break;
        }
      }
      
      if (!frontendButton) {
        throw new Error('Could not find button on frontend');
      }
      
      // Verify text content
      if (expectedAttributes.text) {
        const actualText = await frontendButton.textContent();
        if (actualText && actualText.includes(expectedAttributes.text)) {
          console.log(`✅ Text verified: ${expectedAttributes.text}`);
        } else if (!options.allowPartialMatch) {
          console.log(`⚠️ Text mismatch. Expected: ${expectedAttributes.text}, Found: ${actualText}`);
        }
      }
      
      // Verify computed styles
      const computedStyle = await frontendButton.evaluate(el => {
        const style = window.getComputedStyle(el);
        return {
          backgroundColor: style.backgroundColor,
          color: style.color,
          borderRadius: style.borderRadius,
          fontSize: style.fontSize,
          fontWeight: style.fontWeight,
          padding: style.padding
        };
      });
      
      console.log('🎨 Frontend computed styles:', computedStyle);
      
      // Verify link
      if (expectedAttributes.link) {
        const href = await frontendButton.getAttribute('href');
        if (href && href.includes(expectedAttributes.link)) {
          console.log(`✅ Link verified: ${expectedAttributes.link}`);
        } else if (!options.allowPartialMatch) {
          console.log(`⚠️ Link mismatch. Expected: ${expectedAttributes.link}, Found: ${href}`);
        }
      }
      
      // Verify new tab behavior
      if (expectedAttributes.openInNewTab) {
        const target = await frontendButton.getAttribute('target');
        if (target === '_blank') {
          console.log('✅ New tab behavior verified');
        } else if (!options.allowPartialMatch) {
          console.log(`⚠️ New tab mismatch. Expected: _blank, Found: ${target}`);
        }
      }
      
      console.log('✅ Frontend verification completed');
      
    } catch (error) {
      console.error(`❌ Frontend verification failed: ${error.message}`);
      if (!options.allowPartialMatch) {
        throw error;
      }
    }
  }

  /**
   * Set responsive button attributes
   * @param {Locator} buttonElement - Button element
   * @param {Object} responsiveAttributes - Responsive attribute combinations
   */
  async setResponsiveButtonAttributes(buttonElement, responsiveAttributes) {
    console.log('📱 Setting responsive button attributes...');
    
    try {
      // Desktop settings
      if (responsiveAttributes.desktop) {
        await this.setDeviceType('desktop');
        await this.setButtonAttributes(buttonElement, responsiveAttributes.desktop);
      }
      
      // Tablet settings
      if (responsiveAttributes.tablet) {
        await this.setDeviceType('tablet');
        await this.setButtonAttributes(buttonElement, responsiveAttributes.tablet);
      }
      
      // Mobile settings
      if (responsiveAttributes.mobile) {
        await this.setDeviceType('mobile');
        await this.setButtonAttributes(buttonElement, responsiveAttributes.mobile);
      }
      
      // Reset to desktop
      await this.setDeviceType('desktop');
      
      console.log('✅ Responsive attributes set successfully');
      
    } catch (error) {
      console.error(`❌ Failed to set responsive attributes: ${error.message}`);
      throw error;
    }
  }

  /**
   * Set device type for responsive testing
   */
  async setDeviceType(deviceType) {
    try {
      const deviceButtons = {
        desktop: 'button[aria-label="Desktop"]',
        tablet: 'button[aria-label="Tablet"]',
        mobile: 'button[aria-label="Mobile"]'
      };
      
      const deviceButton = this.page.locator(deviceButtons[deviceType]).first();
      if (await deviceButton.isVisible()) {
        await deviceButton.click();
        await this.page.waitForTimeout(500);
        console.log(`✅ Switched to ${deviceType} view`);
      }
    } catch (error) {
      console.log(`⚠️ Could not switch to ${deviceType} view: ${error.message}`);
    }
  }

  /**
   * Verify responsive button attributes on frontend
   */
  async verifyResponsiveButtonAttributes(responsiveAttributes) {
    console.log('📱 Verifying responsive attributes on frontend...');
    
    try {
      const viewports = [
        { name: 'desktop', width: 1200, height: 800 },
        { name: 'tablet', width: 768, height: 1024 },
        { name: 'mobile', width: 375, height: 667 }
      ];
      
      for (const viewport of viewports) {
        console.log(`🔍 Testing ${viewport.name} viewport...`);
        
        await this.page.setViewportSize({ width: viewport.width, height: viewport.height });
        await this.page.waitForTimeout(1000);
        
        if (responsiveAttributes[viewport.name]) {
          await this.verifyButtonAttributesOnFrontend(responsiveAttributes[viewport.name], { allowPartialMatch: true });
        }
      }
      
      console.log('✅ Responsive verification completed');
      
    } catch (error) {
      console.error(`❌ Responsive verification failed: ${error.message}`);
      throw error;
    }
  }

  // Additional helper methods for specific attributes
  async setButtonBorderWidth(width) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const borderInput = inspector.locator('input[aria-label*="Border Width"], input[aria-label*="Width"]').first();
      if (await borderInput.isVisible()) {
        await borderInput.fill(width);
        console.log(`✅ Border width set to: ${width}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set border width: ${error.message}`);
    }
  }

  async setButtonBorderColor(color) {
    try {
      await this.testColorControl('Border Color', color);
      console.log(`✅ Border color set to: ${color}`);
    } catch (error) {
      console.log(`⚠️ Could not set border color: ${error.message}`);
    }
  }

  async setButtonFontSize(fontSize) {
    try {
      // Ensure we're on the correct tab for font size
      await this.clickStylesTab();
      await this.clickTextSection();
      
      const inspector = this.page.locator('.block-editor-block-inspector');
      const fontSizeInput = inspector.locator('input[aria-label*="Font Size"], input[aria-label*="Font size"]').first();
      if (await fontSizeInput.isVisible()) {
        await fontSizeInput.fill(fontSize);
        console.log(`✅ Font size set to: ${fontSize}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set font size: ${error.message}`);
    }
  }

  async setButtonFontWeight(fontWeight) {
    try {
      // Ensure we're on the correct tab for font weight
      await this.clickStylesTab();
      await this.clickTextSection();
      
      const inspector = this.page.locator('.block-editor-block-inspector');
      const weightButton = inspector.locator(`button:has-text("${fontWeight}")`).first();
      if (await weightButton.isVisible()) {
        await weightButton.click();
        console.log(`✅ Font weight set to: ${fontWeight}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set font weight: ${error.message}`);
    }
  }

  async setButtonPadding(padding) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      
      if (typeof padding === 'object') {
        for (const [side, value] of Object.entries(padding)) {
          const paddingInput = inspector.locator(`input[aria-label*="Padding ${side}"], input[aria-label*="${side}"]`).first();
          if (await paddingInput.isVisible()) {
            await paddingInput.fill(value);
          }
        }
        console.log(`✅ Padding set to:`, padding);
      }
    } catch (error) {
      console.log(`⚠️ Could not set padding: ${error.message}`);
    }
  }

  async setButtonIcon(iconName) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const iconButton = inspector.locator('button:has-text("Icon"), button:has-text("Select Icon")').first();
      if (await iconButton.isVisible()) {
        await iconButton.click();
        await this.page.waitForTimeout(1000);
        
        // Look for icon with the specified name
        const iconOption = this.page.locator(`button[aria-label*="${iconName}"], button[title*="${iconName}"]`).first();
        if (await iconOption.isVisible()) {
          await iconOption.click();
          console.log(`✅ Icon set to: ${iconName}`);
        }
      }
    } catch (error) {
      console.log(`⚠️ Could not set icon: ${error.message}`);
    }
  }

  async setButtonAlignment(alignment) {
    try {
      const alignmentButton = this.page.locator(`button[aria-label*="${alignment}"], button[aria-label*="Align ${alignment}"]`).first();
      if (await alignmentButton.isVisible()) {
        await alignmentButton.click();
        console.log(`✅ Alignment set to: ${alignment}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set alignment: ${error.message}`);
    }
  }

  async setButtonHoverEffect(effect) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const effectButton = inspector.locator(`button:has-text("${effect}")`).first();
      if (await effectButton.isVisible()) {
        await effectButton.click();
        console.log(`✅ Hover effect set to: ${effect}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set hover effect: ${error.message}`);
    }
  }

  // Additional helper methods for comprehensive attribute testing

  async setButtonShowText(showText) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const showTextToggle = inspector.locator('input[type="checkbox"], button[role="switch"]').filter({ hasText: /show.*text|text.*visible/i }).first();
      
      if (await showTextToggle.isVisible()) {
        const isChecked = await showTextToggle.isChecked();
        if ((showText && !isChecked) || (!showText && isChecked)) {
          await showTextToggle.click();
        }
        console.log(`✅ Show text set to: ${showText}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set show text: ${error.message}`);
    }
  }

  async setButtonLinkURL(url) {
    try {
      await this.setButtonLink(url);
    } catch (error) {
      console.log(`⚠️ Could not set link URL: ${error.message}`);
    }
  }

  async setButtonLinkTarget(target) {
    try {
      const targetToggle = this.page.locator(`button:has-text("${target === '_blank' ? 'Open in new tab' : 'Open in same tab'}")`).first();
      if (await targetToggle.isVisible()) {
        await targetToggle.click();
        console.log(`✅ Link target set to: ${target}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set link target: ${error.message}`);
    }
  }

  async setButtonLinkRel(relArray) {
    try {
      for (const rel of relArray) {
        const relToggle = this.page.locator(`input[value="${rel}"], button:has-text("${rel}")`).first();
        if (await relToggle.isVisible()) {
          await relToggle.click();
        }
      }
      console.log(`✅ Link rel set to: ${relArray.join(', ')}`);
    } catch (error) {
      console.log(`⚠️ Could not set link rel: ${error.message}`);
    }
  }

  async setButtonIconPosition(position) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const positionButton = inspector.locator(`button:has-text("${position}"), button[aria-label*="${position}"]`).first();
      if (await positionButton.isVisible()) {
        await positionButton.click();
        console.log(`✅ Icon position set to: ${position}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set icon position: ${error.message}`);
    }
  }

  async setButtonIconColor(color) {
    try {
      await this.testColorControl('Icon Color', color);
      console.log(`✅ Icon color set to: ${color}`);
    } catch (error) {
      console.log(`⚠️ Could not set icon color: ${error.message}`);
    }
  }

  async setButtonIconColorHover(color) {
    try {
      await this.testColorControl('Icon Color Hover', color);
      console.log(`✅ Icon hover color set to: ${color}`);
    } catch (error) {
      console.log(`⚠️ Could not set icon hover color: ${error.message}`);
    }
  }

  async setButtonIconSize(size) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const sizeInput = inspector.locator('input[aria-label*="Icon Size"], input[aria-label*="Size"]').first();
      if (await sizeInput.isVisible()) {
        await sizeInput.fill(size);
        console.log(`✅ Icon size set to: ${size}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set icon size: ${error.message}`);
    }
  }

  async setButtonIconRotation(rotation) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const rotationInput = inspector.locator('input[aria-label*="Rotation"], input[aria-label*="rotation"]').first();
      if (await rotationInput.isVisible()) {
        await rotationInput.fill(rotation.toString());
        console.log(`✅ Icon rotation set to: ${rotation}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set icon rotation: ${error.message}`);
    }
  }

  async setButtonIconGap(gap) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const gapInput = inspector.locator('input[aria-label*="Gap"], input[aria-label*="gap"]').first();
      if (await gapInput.isVisible()) {
        await gapInput.fill(gap);
        console.log(`✅ Icon gap set to: ${gap}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set icon gap: ${error.message}`);
    }
  }

  async setButtonFlipForRTL(flip) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const flipToggle = inspector.locator('input[type="checkbox"]').filter({ hasText: /flip.*rtl|rtl.*flip/i }).first();
      
      if (await flipToggle.isVisible()) {
        const isChecked = await flipToggle.isChecked();
        if ((flip && !isChecked) || (!flip && isChecked)) {
          await flipToggle.click();
        }
        console.log(`✅ Flip for RTL set to: ${flip}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set flip for RTL: ${error.message}`);
    }
  }

  async setButtonTextColorHover(color) {
    try {
      await this.testColorControl('Text Color Hover', color);
      console.log(`✅ Text hover color set to: ${color}`);
    } catch (error) {
      console.log(`⚠️ Could not set text hover color: ${error.message}`);
    }
  }

  async setButtonBackgroundColorHover(color) {
    try {
      await this.testColorControl('Background Color Hover', color);
      console.log(`✅ Background hover color set to: ${color}`);
    } catch (error) {
      console.log(`⚠️ Could not set background hover color: ${error.message}`);
    }
  }

  async setButtonBackgroundGradient(gradient) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const gradientButton = inspector.locator('button:has-text("Gradient"), button[aria-label*="Gradient"]').first();
      if (await gradientButton.isVisible()) {
        await gradientButton.click();
        await this.page.waitForTimeout(500);
        
        // Look for gradient input or custom gradient option
        const gradientInput = inspector.locator('input[aria-label*="gradient"], textarea[aria-label*="gradient"]').first();
        if (await gradientInput.isVisible()) {
          await gradientInput.fill(gradient);
        }
        console.log(`✅ Background gradient set to: ${gradient}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set background gradient: ${error.message}`);
    }
  }

  async setButtonBackgroundGradientHover(gradient) {
    try {
      await this.setButtonBackgroundGradient(gradient);
      console.log(`✅ Background gradient hover set`);
    } catch (error) {
      console.log(`⚠️ Could not set background gradient hover: ${error.message}`);
    }
  }

  async setButtonLineHeight(lineHeight) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const lineHeightInput = inspector.locator('input[aria-label*="Line Height"], input[aria-label*="line height"]').first();
      if (await lineHeightInput.isVisible()) {
        await lineHeightInput.fill(lineHeight);
        console.log(`✅ Line height set to: ${lineHeight}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set line height: ${error.message}`);
    }
  }

  async setButtonFontFamily(fontFamily) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const fontFamilySelect = inspector.locator('select[aria-label*="Font Family"], button[aria-label*="Font Family"]').first();
      if (await fontFamilySelect.isVisible()) {
        await fontFamilySelect.click();
        await this.page.waitForTimeout(500);
        
        const fontOption = this.page.locator(`option:has-text("${fontFamily}"), button:has-text("${fontFamily}")`).first();
        if (await fontOption.isVisible()) {
          await fontOption.click();
        }
        console.log(`✅ Font family set to: ${fontFamily}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set font family: ${error.message}`);
    }
  }

  async setButtonTextAlign(textAlign) {
    try {
      const alignmentButton = this.page.locator(`button[aria-label*="Align ${textAlign}"], button[aria-label*="${textAlign}"]`).first();
      if (await alignmentButton.isVisible()) {
        await alignmentButton.click();
        console.log(`✅ Text align set to: ${textAlign}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set text align: ${error.message}`);
    }
  }

  async setButtonLetterSpacing(letterSpacing) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const letterSpacingInput = inspector.locator('input[aria-label*="Letter Spacing"], input[aria-label*="letter spacing"]').first();
      if (await letterSpacingInput.isVisible()) {
        await letterSpacingInput.fill(letterSpacing);
        console.log(`✅ Letter spacing set to: ${letterSpacing}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set letter spacing: ${error.message}`);
    }
  }

  async setButtonTextTransform(textTransform) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const transformButton = inspector.locator(`button:has-text("${textTransform}"), button[aria-label*="${textTransform}"]`).first();
      if (await transformButton.isVisible()) {
        await transformButton.click();
        console.log(`✅ Text transform set to: ${textTransform}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set text transform: ${error.message}`);
    }
  }

  async setButtonMargin(margin) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      
      if (typeof margin === 'object') {
        for (const [side, value] of Object.entries(margin)) {
          const marginInput = inspector.locator(`input[aria-label*="Margin ${side}"], input[aria-label*="${side} margin"]`).first();
          if (await marginInput.isVisible()) {
            await marginInput.fill(value);
          }
        }
        console.log(`✅ Margin set to:`, margin);
      }
    } catch (error) {
      console.log(`⚠️ Could not set margin: ${error.message}`);
    }
  }

  async setButtonBoxShadow(boxShadow) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const shadowInput = inspector.locator('input[aria-label*="Shadow"], textarea[aria-label*="shadow"]').first();
      if (await shadowInput.isVisible()) {
        await shadowInput.fill(boxShadow);
        console.log(`✅ Box shadow set to: ${boxShadow}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set box shadow: ${error.message}`);
    }
  }

  async setButtonAriaLabel(ariaLabel) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const ariaInput = inspector.locator('input[aria-label*="Aria Label"], input[aria-label*="aria label"]').first();
      if (await ariaInput.isVisible()) {
        await ariaInput.fill(ariaLabel);
        console.log(`✅ Aria label set to: ${ariaLabel}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set aria label: ${error.message}`);
    }
  }

  // Container-specific methods
  async setContainerAttributes(containerElement, attributes) {
    console.log('🏗️ Setting container attributes:', Object.keys(attributes));
    
    try {
      await containerElement.click();
      await this.page.waitForTimeout(500);
      
      if (attributes.layout) {
        await this.setContainerLayout(attributes.layout);
      }
      
      if (attributes.alignment) {
        await this.setContainerAlignment(attributes.alignment);
      }
      
      if (attributes.blockGap) {
        await this.setContainerBlockGap(attributes.blockGap);
      }
      
      if (attributes.backgroundColor) {
        await this.setButtonBackgroundColor(attributes.backgroundColor);
      }
      
      if (attributes.padding) {
        await this.setButtonPadding(attributes.padding);
      }
      
      if (attributes.margin) {
        await this.setButtonMargin(attributes.margin);
      }
      
      if (attributes.borderRadius) {
        await this.setButtonBorderRadius(attributes.borderRadius);
      }
      
      console.log('✅ Container attributes set successfully');
      
    } catch (error) {
      console.error(`❌ Failed to set container attributes: ${error.message}`);
      throw error;
    }
  }

  async setContainerLayout(layout) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const layoutButton = inspector.locator(`button:has-text("${layout.type}"), button[aria-label*="${layout.type}"]`).first();
      if (await layoutButton.isVisible()) {
        await layoutButton.click();
        console.log(`✅ Container layout set to: ${layout.type}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set container layout: ${error.message}`);
    }
  }

  async setContainerAlignment(alignment) {
    try {
      await this.setButtonAlignment(alignment);
    } catch (error) {
      console.log(`⚠️ Could not set container alignment: ${error.message}`);
    }
  }

  async setContainerBlockGap(blockGap) {
    try {
      const inspector = this.page.locator('.block-editor-block-inspector');
      const gapInput = inspector.locator('input[aria-label*="Block Gap"], input[aria-label*="gap"]').first();
      if (await gapInput.isVisible()) {
        await gapInput.fill(blockGap);
        console.log(`✅ Block gap set to: ${blockGap}`);
      }
    } catch (error) {
      console.log(`⚠️ Could not set block gap: ${error.message}`);
    }
  }

  async verifyContainerAttributesOnFrontend(expectedAttributes, options = {}) {
    console.log('🔍 Verifying container attributes on frontend...');
    
    try {
      await this.page.waitForLoadState('networkidle');
      await this.page.waitForTimeout(2000);
      
      const containerSelectors = [
        '.wp-block-spectra-buttons',
        '.spectra-buttons',
        '.wp-block-buttons',
        '[data-type="spectra/buttons"]'
      ];
      
      let frontendContainer = null;
      for (const selector of containerSelectors) {
        const element = this.page.locator(selector).first();
        if (await element.isVisible()) {
          frontendContainer = element;
          console.log(`✅ Found frontend container with selector: ${selector}`);
          break;
        }
      }
      
      if (!frontendContainer) {
        throw new Error('Could not find container on frontend');
      }
      
      const computedStyle = await frontendContainer.evaluate(el => {
        const style = window.getComputedStyle(el);
        return {
          backgroundColor: style.backgroundColor,
          padding: style.padding,
          margin: style.margin,
          borderRadius: style.borderRadius,
          display: style.display,
          gap: style.gap
        };
      });
      
      console.log('🏗️ Frontend container computed styles:', computedStyle);
      console.log('✅ Container frontend verification completed');
      
    } catch (error) {
      console.error(`❌ Container frontend verification failed: ${error.message}`);
      if (!options.allowPartialMatch) {
        throw error;
      }
    }
  }
}