/**
 * Global setup for Playwright tests
 * This runs once before all tests
 */

async function globalSetup(config) {
  console.log('🚀 Running global setup for E2E tests...');
  
  // You can add any global setup logic here
  // For example: setting up test database, clearing cache, etc.
  
  console.log('✅ Global setup completed');
}

module.exports = globalSetup;