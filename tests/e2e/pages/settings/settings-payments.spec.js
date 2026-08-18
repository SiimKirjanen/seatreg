const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const STRIPE_KEY_MISSING = 'Please enter Stripe API key';
const STRIPE_KEY_NOT_SECRET = 'Please provide Stripe API secret key';
const CURRENCY_MISSING = 'Please enter currency code';
const CURRENCY_NOT_VALID = 'Currency code in not valid';

const STRIPE_SECRET_KEY = 'sk_test_e2e';
const STRIPE_PUBLISHABLE_KEY = 'pk_test_e2e';
const INVALID_CURRENCY_CODE = 'ZZZ';
const CURRENCY_CODE = 'EUR';

/* A payment provider that is missing something is refused before the form is
   ever posted, by a check on the save button that calls preventDefault(). Each
   provider has the same few branches, so they are walked in one test rather
   than one test each.

   The test ends on a refused save on purpose. Saving a filled in Stripe
   configuration makes the plugin call Stripe to create a webhook, which is not
   something the suite should be doing. */

test.describe('Settings payments', () => {
	let settings;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		await settings.openForNewRegistration(uniqueRegistrationName('Settings payments'));
	});

	test('refuses to save a payment provider that is missing its credentials', async () => {
		await settings.set('stripeEnabled', true);

		await settings.saveExpectingError(STRIPE_KEY_MISSING);

		await settings.set('stripeApiKey', STRIPE_SECRET_KEY);
		await settings.saveExpectingError(CURRENCY_MISSING);

		await settings.set('currencyCode', INVALID_CURRENCY_CODE);
		await settings.saveExpectingError(CURRENCY_NOT_VALID);

		/* With the currency accepted the key itself is looked at, and a
		   publishable key is not the one the plugin needs. */
		await settings.set('currencyCode', CURRENCY_CODE);
		await settings.set('stripeApiKey', STRIPE_PUBLISHABLE_KEY);
		await settings.saveExpectingError(STRIPE_KEY_NOT_SECRET);

		await settings.reload();

		await expect(settings.field('stripeEnabled')).not.toBeChecked();
		await expect(settings.field('stripeApiKey')).toHaveValue('');
		await expect(settings.field('currencyCode')).toHaveValue('');
	});
});
