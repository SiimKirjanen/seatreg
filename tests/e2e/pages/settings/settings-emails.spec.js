const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const EMAIL_FROM_NOT_VALID = 'Email FROM address is not correct';
const EMAIL_TEMPLATE_INCOMPLETE = 'Email template is missing required keywords';

const MALFORMED_EMAIL = 'bookings.example.com';
const TEMPLATE_WITHOUT_STATUS_LINK = 'Your booking [booking-id] has been approved.';

/* An email the plugin could not send, or one a booker could not act on, is
   refused before the form is ever posted. The three templates are checked the
   same way, so one stands for all of them.

   What the emails themselves look like is not something this suite can see. */

test.describe('Settings emails', () => {
	let settings;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		await settings.openForNewRegistration(uniqueRegistrationName('Settings emails'));
	});

	test('refuses to save email settings the plugin cannot send with', async () => {
		await settings.set('emailFrom', MALFORMED_EMAIL);

		await settings.saveExpectingError(EMAIL_FROM_NOT_VALID);

		/* A template without its status link would reach the booker with no way
		   back to their booking. */
		await settings.set('emailFrom', '');
		await settings.set('approvedEmailTemplate', TEMPLATE_WITHOUT_STATUS_LINK);

		await settings.saveExpectingError(EMAIL_TEMPLATE_INCOMPLETE);

		await settings.reload();

		await expect(settings.field('emailFrom')).toHaveValue('');
		await expect(settings.field('approvedEmailTemplate')).toHaveValue('');
	});
});
