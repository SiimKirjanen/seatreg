const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const STRIPE_KEY_MISSING = 'Please enter Stripe API key';
const STRIPE_KEY_NOT_SECRET = 'Please provide Stripe API secret key';
const CURRENCY_MISSING = 'Please enter currency code';
const CURRENCY_NOT_VALID = 'Currency code in not valid';

const PAYPAL_CLIENT_ID_MISSING = 'Please enter PayPal client id';
const PAYPAL_CLIENT_SECRET_MISSING = 'Please enter PayPal client secret';
const PAYPAL_BUSINESS_EMAIL_MISSING = 'Please enter PayPal business email';
const PAYPAL_BUTTON_ID_MISSING = 'Please enter PayPal button id';

const STRIPE_SECRET_KEY = 'sk_test_e2e';
const STRIPE_PUBLISHABLE_KEY = 'pk_test_e2e';
const INVALID_CURRENCY_CODE = 'ZZZ';
const CURRENCY_CODE = 'EUR';

const PAYPAL_CLIENT_ID = 'paypal-client-id-e2e';
const PAYPAL_CLIENT_SECRET = 'paypal-client-secret-e2e';
const PAYPAL_BUSINESS_EMAIL = 'payments@example.com';

/* A provider missing something is refused before the form is ever posted. Every
   provider has the same few branches, so they are walked in one test.

   The test ends on a refused save on purpose: saving a filled in Stripe
   configuration makes the plugin call Stripe to create a webhook. */

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

		/* The other two providers are asked for their own credentials, and the
		   currency they all share is already known to be checked. */
		await settings.set('paypalRestEnabled', true);
		await settings.saveExpectingError(PAYPAL_CLIENT_ID_MISSING);

		await settings.set('paypalClientId', PAYPAL_CLIENT_ID);
		await settings.saveExpectingError(PAYPAL_CLIENT_SECRET_MISSING);

		await settings.set('paypalClientSecret', PAYPAL_CLIENT_SECRET);
		await settings.saveExpectingError(CURRENCY_MISSING);

		await settings.set('paypalRestEnabled', false);
		await settings.set('paypalLegacyEnabled', true);
		await settings.saveExpectingError(PAYPAL_BUSINESS_EMAIL_MISSING);

		await settings.set('paypalBusinessEmail', PAYPAL_BUSINESS_EMAIL);
		await settings.saveExpectingError(PAYPAL_BUTTON_ID_MISSING);

		await settings.reload();

		await expect(settings.field('paypalRestEnabled')).not.toBeChecked();
		await expect(settings.field('paypalLegacyEnabled')).not.toBeChecked();
		await expect(settings.field('paypalClientId')).toHaveValue('');
	});
});
