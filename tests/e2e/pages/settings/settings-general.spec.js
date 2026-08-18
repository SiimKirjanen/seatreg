const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const CLOSE_REASON = 'The event has sold out.';
const REGISTRATION_PASSWORD = 'letmein7f3a';
const WRONG_PASSWORD = 'notthepassword';

/* The General tab decides who gets to see the registration at all, so every one
   of these is checked on the registration itself. The tab's remaining settings
   are limits that only a booking can show, and they get covered when the booking
   flow is. */

test.describe('Settings general', () => {
	let settings;
	let name;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		name = uniqueRegistrationName('Settings general');
		code = await settings.openForNewRegistration(name);
	});

	test('closes the registration and tells visitors why', async () => {
		await settings.set('registrationStatus', false);
		await settings.set('closeReason', CLOSE_REASON);

		await settings.save();

		const registration = await settings.openRegistration(code);

		await expect(registration.closedNotice).toContainText(name);
		await expect(registration.closeReason).toHaveText(CLOSE_REASON);
	});

	test('asks visitors for the registration password', async () => {
		await settings.set('registrationPassword', REGISTRATION_PASSWORD);

		await settings.save();

		const registration = await settings.openRegistration(code);

		await expect(registration.passwordForm).toBeVisible();

		await registration.submitPassword(WRONG_PASSWORD);
		await expect(registration.passwordForm).toBeVisible();

		await registration.submitPassword(REGISTRATION_PASSWORD);
		await expect(registration.passwordForm).toHaveCount(0);
	});

	/* The notice is rendered by the page, not by anything a visitor can dismiss,
	   so both halves of this are just what the registration comes back with. */
	test('takes bookings from logged in WordPress users only', async ({ browser }) => {
		await settings.set('requireWpLogin', true);

		await settings.save();

		const visitor = await settings.openRegistrationAsVisitor(browser, code);

		await expect(visitor.loginNotice).toBeVisible();

		await visitor.page.context().close();

		/* The same registration, opened by someone who is logged in. */
		const registration = await settings.openRegistration(code);

		await expect(registration.loginNotice).toHaveCount(0);
	});
});
