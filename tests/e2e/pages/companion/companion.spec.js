const { test, expect } = require('@playwright/test');
const { CompanionPage } = require('./companion-page');

const NOT_ENABLED = 'Companion is not enabled';

/* The companion app is a second way into the bookings, so what is worth covering
   is the gate rather than the app: everything behind it is an Expo build that
   lives in its own repository and talks to the public API.

   Serial, and it puts the option back, because the switch belongs to the site and
   not to a registration - left on, it would answer for every other worker too. */

test.describe.serial('SeatReg Companion screen', () => {
	let companion;

	test.beforeEach(async ({ page }) => {
		companion = new CompanionPage(page);

		await companion.goto();
	});

	test('opens the companion app only while it is enabled', async ({ page }) => {
		/* Set rather than assumed: nothing says which way a killed run left it. */
		await companion.setEnabled(false);
		await companion.openApp();

		await expect(page.locator('body')).toHaveText(NOT_ENABLED);

		await companion.goto();
		await companion.setEnabled(true);
		await companion.openApp();

		await expect(companion.appRoot).toBeAttached();

		/* Puts the site back for whoever runs next, and walks the way back at the
		   same time. */
		await companion.goto();
		await companion.setEnabled(false);
		await companion.openApp();

		await expect(page.locator('body')).toHaveText(NOT_ENABLED);
	});
});
