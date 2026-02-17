/**
 * Global teardown for Playwright tests
 * This runs once after all tests
 */

async function globalTeardown() {
  console.log('🧹 Running global teardown for E2E tests...');
  
  // You can add any cleanup logic here
  // For example: clearing test data, closing connections, etc.
  
  console.log('✅ Global teardown completed');
}

module.exports = globalTeardown;