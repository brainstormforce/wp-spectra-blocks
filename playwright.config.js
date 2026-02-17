import { defineConfig, devices } from '@playwright/test';
import * as dotenv from 'dotenv';

// Load environment variables from .env file
dotenv.config();

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig( {
	// Test directory
	testDir: './tests/e2e',

	// Run tests in files in parallel
	fullyParallel: false,

	// Fail the build on CI if you accidentally left test.only in the source code.
	forbidOnly: !!process.env.CI,

	// Retry on CI only
	retries: process.env.CI ? 2 : 0,

	// Run tests sequentially
	workers: 1,

	// Reporter to use. See https://playwright.dev/docs/test-reporters
	reporter: 'html',

	// Shared settings for all the projects below.
	use: {
		// Base URL to use in actions like `await page.goto('/')`.
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',

		// Collect trace when retrying the failed test.
		trace: 'on-first-retry',

		// Take screenshot on failure
		screenshot: 'only-on-failure',

		// Video on failure
		video: 'retain-on-failure',

		// Set viewport
		viewport: { width: 1280, height: 720 },
	},

	// Configure projects for major browsers
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},

		{
			name: 'firefox',
			use: { ...devices['Desktop Firefox'] },
		},

		{
			name: 'webkit',
			use: { ...devices['Desktop Safari'] },
		},

		/* Test against mobile viewports. */
		{
			name: 'Mobile Chrome',
			use: { ...devices['Pixel 5'] },
			testIgnore: ['**/*editor*.spec.js', '**/wp-admin/**'],
			testMatch: '**/*frontend*.spec.js',
		},
		{
			name: 'Mobile Safari',
			use: { ...devices['iPhone 12'] },
			testIgnore: ['**/*editor*.spec.js', '**/wp-admin/**'],
			testMatch: '**/*frontend*.spec.js',
		},

		/* Test against branded browsers. */
		// {
		//   name: 'Microsoft Edge',
		//   use: { ...devices['Desktop Edge'], channel: 'msedge' },
		// },
		// {
		//   name: 'Google Chrome',
		//   use: { ...devices['Desktop Chrome'], channel: 'chrome' },
		// },
	],

	// Run your local dev server before starting the tests
	// webServer: {
	//   command: 'npm run start',
	//   url: 'http://127.0.0.1:3000',
	//   reuseExistingServer: !process.env.CI,
	// },

	// Global setup and teardown
	globalSetup: require.resolve( './tests/e2e/config/global-setup.js' ),
	globalTeardown: require.resolve( './tests/e2e/config/global-teardown.js' ),

	// Timeout settings
	timeout: 30000,
	expect: {
		timeout: 10000,
	},
} );
