const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

const WP_ADMIN_USER = {
	username: 'admin',
	password: 'password',
};

async function loginToWordPress(page, username = WP_ADMIN_USER.username, password = WP_ADMIN_USER.password) {
	await page.goto('/wp-login.php');

	const usernameField = page.getByLabel('Username or Email Address');
	const passwordField = page.getByLabel('Password', { exact: true });

	await expect(usernameField).toBeEditable();
	await usernameField.fill(username);
	await expect(usernameField).toHaveValue(username);

	// Until the show-password toggle exists the password input is still being
	// re-initialized, and keystrokes typed meanwhile get dropped.
	await expect(page.locator('button.wp-hide-pw')).toBeVisible();

	await expect(passwordField).toBeEditable();
	await passwordField.fill(password);
	await expect(passwordField).toHaveValue(password);

	await page.click('#wp-submit');

	await expect(page.locator('#wpadminbar')).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
}

/** A context that never had the admin session. The caller closes it. */
function visitorContext(browser) {
	return browser.newContext({ storageState: { cookies: [], origins: [] } });
}

module.exports = { WP_ADMIN_USER, loginToWordPress, visitorContext };
