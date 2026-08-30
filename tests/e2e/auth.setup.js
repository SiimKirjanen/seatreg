const { test: setup } = require('@playwright/test');
const { mkdirSync } = require('fs');
const { dirname } = require('path');
const { loginToWordPress } = require('./utils/auth');
const { setMailCapture } = require('./utils/mail');

const authFile = 'playwright/.auth/user.json';

setup('authenticate', async ({ page }) => {
	// Ensure the auth directory exists
	mkdirSync(dirname(authFile), { recursive: true });

	// Login to WordPress
	await loginToWordPress(page);

	// Save authentication state
	await page.context().storageState({ path: authFile });

	// Capture mail for the run; mail.teardown.js puts it back
	await setMailCapture(page, true);
});
