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

module.exports = { WP_ADMIN_USER, loginToWordPress };
