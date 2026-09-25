const { test, expect } = require('@playwright/test');
const { HomePage } = require('./home-page');
const { SettingsPage } = require('../settings/settings-page');
const { TIMEOUTS } = require('../../utils/timeouts');
const { uniqueRegistrationName } = require('../../utils/registrations');

const DELETE_CONFIRM_MESSAGE = 'Do you really want to delete?';

const SEAT_COUNT = 2;
const INFO_TEXT = 'Doors open at 18:00.';

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

	test('shows the shortcodes for the registration', async () => {
		await homePage.openShortcodeModal(code);

		const shortcodes = homePage.shortcodeModal(code).locator('.shortcode-example');
		await expect(shortcodes.first()).toBeVisible();

		for (const shortcode of await shortcodes.all()) {
			await expect(shortcode).toContainText(`[seatreg code=${code}`);
		}
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

/* A copy is made for what it brings along, so it is opened the way a visitor meets
   it: the seats are the layout, the info text is the settings. */
test.describe('Home screen registration copy', () => {
	test('copies the registration with its layout and settings under a new name', async ({
		page,
	}) => {
		const homePage = new HomePage(page);
		const settings = new SettingsPage(page);
		const sourceName = uniqueRegistrationName('Home modal source');
		const source = await settings.openForNewRegistrationWithSeats(sourceName, SEAT_COUNT);

		await settings.set('infoText', INFO_TEXT);
		await settings.save();

		await homePage.goto();

		const copy = await homePage.copyRegistration(source, uniqueRegistrationName('Home modal copy'));

		expect(copy).not.toBe(source);
		await expect(homePage.registrationNameLink(sourceName)).toBeVisible();

		const registration = await settings.openRegistration(copy);

		await expect(registration.seats).toHaveCount(SEAT_COUNT);
		await expect(registration.registrationInfo).toHaveText(INFO_TEXT);
	});
});
