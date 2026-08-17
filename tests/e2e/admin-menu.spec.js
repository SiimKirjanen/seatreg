const { test, expect } = require('@playwright/test');
const { TIMEOUTS } = require('./utils/timeouts');

test.describe('SeatReg admin menu', () => {
	test('should open the SeatReg home page', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=seatreg-welcome');

		// The plugin's top level menu item is registered on activation
		await expect(page.getByRole('link', { name: 'SeatReg', exact: true })).toBeVisible({
			timeout: TIMEOUTS.NAVIGATION,
		});

		// The welcome screen rendered
		await expect(
			page.getByRole('heading', { name: 'Create and manage online registrations' })
		).toBeVisible();
	});
});
