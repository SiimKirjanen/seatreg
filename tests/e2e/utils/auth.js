const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

/**
 * WordPress authentication utilities for e2e testing
 */

const WP_ADMIN_USER = {
	username: 'admin',
	password: 'password',
};

/**
 * Login to WordPress admin
 */
async function loginToWordPress(page, username = WP_ADMIN_USER.username, password = WP_ADMIN_USER.password) {
	await page.goto('/wp-login.php');

	const usernameField = page.getByLabel('Username or Email Address');
	const passwordField = page.getByLabel('Password', { exact: true });

	await expect(usernameField).toBeEditable();
	await usernameField.fill(username);
	await expect(usernameField).toHaveValue(username);

	// Wait for WP's login script to render the show-password toggle. Until this
	// button exists the password input is still being re-initialized, and any
	// keystrokes typed in the meantime get dropped.
	await expect(page.locator('button.wp-hide-pw')).toBeVisible();

	await expect(passwordField).toBeEditable();
	await passwordField.fill(password);
	await expect(passwordField).toHaveValue(password);

	await page.click('#wp-submit');

	await expect(page.locator('#wpadminbar')).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
}

/**
 * A browser context with nobody logged in.
 *
 * Every context in the suite starts with the admin's session restored into it,
 * so a setting that only does something to a visitor without one cannot be seen
 * working from the tests' own browser. This is a context that never had the
 * session. The caller closes it when it is done with it.
 *
 * @param {import('@playwright/test').Browser} browser The `browser` fixture
 */
function visitorContext(browser) {
	return browser.newContext({ storageState: { cookies: [], origins: [] } });
}

module.exports = { WP_ADMIN_USER, loginToWordPress, visitorContext };
