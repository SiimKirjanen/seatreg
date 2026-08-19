const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
	testDir: './tests/e2e',
	/* Every test builds the registration it works on, and some build a layout
	   and walk a booking on top of that, so the default 30s is not enough for
	   the longest of them once the workers are all busy. */
	timeout: 60000,
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: process.env.CI ? 4 : undefined,
	reporter: [
		['list'],
		['html', { open: 'never' }]
	],

	use: {
		baseURL: 'http://localhost:8889',
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},

	projects: [
		{
			name: 'setup',
			testMatch: /.*\.setup\.js/,
		},
		{
			name: 'chromium',
			use: {
				...devices['Desktop Chrome'],
				storageState: 'playwright/.auth/user.json',
			},
			dependencies: ['setup'],
		},
	],
});
