// Configuration with defaults
const config = {
  // WordPress site configuration
  baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
  username: process.env.WP_ADMIN_USER || process.env.WP_USERNAME || 'admin',
  password: process.env.WP_ADMIN_PASSWORD || process.env.WP_PASSWORD || 'password',
  
  // Test configuration
  headless: process.env.HEADLESS === 'true',
  slowMo: parseInt(process.env.SLOW_MO) || 0,
  
  // Timeouts
  timeout: parseInt(process.env.TIMEOUT) || 30000,
  navigationTimeout: parseInt(process.env.NAVIGATION_TIMEOUT) || 30000,
};

// Validate required configuration
const requiredConfigs = ['baseURL', 'username', 'password'];
for (const configKey of requiredConfigs) {
  if (!config[configKey]) {
    throw new Error(`Missing required configuration: ${configKey}. Please check your .env file.`);
  }
}

module.exports = config;