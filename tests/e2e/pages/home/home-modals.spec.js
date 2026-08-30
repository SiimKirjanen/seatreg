const { test, expect } = require('@playwright/test');
const { HomePage } = require('./home-page');
const { TIMEOUTS } = require('../../utils/timeouts');
const { uniqueRegistrationName } = require('../../utils/registrations');

const DELETE_CONFIRM_MESSAGE = 'Do you really want to delete?';

test.describe('Home screen registration modals', () => {
	let homePage;
	let name;
	let code;

	test.beforeEach(async ({ page }) => {
		homePage = new HomePage(page);
		await homePage.goto();

		name = uniqueRegistrationName('Home modal');
		code = await homePage.createRegistration(name);
	});

	/* Every registration renders its own copy of the modals, so opening one has
	   to show that registration's modal and leave the others alone. */
	test('opens the more actions modal of the registration it belongs to', async () => {
		const otherCode = await homePage.createRegistration(uniqueRegistrationName('Home modal other'));

		await homePage.openMoreModal(otherCode);

		const modal = homePage.moreModal(otherCode);
		await expect(modal.locator('.modal-title')).toHaveText('More actions');
		await expect(homePage.moreModalItem(otherCode, 'view-registration-activity')).toHaveText('Logs');
		await expect(homePage.moreModalItem(otherCode, 'view-shortcode')).toHaveText('Shortcode');
		await expect(homePage.moreModalItem(otherCode, 'open-copy-registration')).toHaveText('Copy');
		await expect(homePage.deleteButton(otherCode)).toHaveValue('Delete');
		await expect(homePage.moreModal(code)).toBeHidden();

		await homePage.closeMoreModal(otherCode);
	});

	test('shows the shortcodes for the registration', async () => {
		await homePage.openShortcodeModal(code);

		const shortcodes = homePage.shortcodeModal(code).locator('.shortcode-example');
		await expect(shortcodes.first()).toBeVisible();

		for (const shortcode of await shortcodes.all()) {
			await expect(shortcode).toContainText(`[seatreg code=${code}`);
		}
	});

	test('copies the registration under a new name', async () => {
		const copyName = uniqueRegistrationName('Home modal copy');

		await homePage.copyRegistration(code, copyName);

		await expect(homePage.registrationNameLink(name)).toBeVisible();
		await expect(homePage.registrationNameLink(copyName)).toBeVisible();
	});

	test('shows the activity log of the registration', async () => {
		await homePage.openLogsModal(code);

		await expect(homePage.logsModalEntries).toContainText('Registration created', {
			timeout: TIMEOUTS.NAVIGATION,
		});
	});

	test('deletes the registration', async () => {
		await homePage.openMoreModal(code);

		const message = await homePage.deleteRegistration(code);

		expect(message).toBe(DELETE_CONFIRM_MESSAGE);
		await expect(homePage.registrationCardByCode(code)).toHaveCount(0);
		await expect(homePage.registrationNameLink(name)).toHaveCount(0);
	});
});
