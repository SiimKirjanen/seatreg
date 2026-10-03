const { test, expect } = require('@playwright/test');
const { SettingsPage, BOOKER } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const { validateToken, bookings } = require('../../utils/public-api');
const { isoDate } = require('../../utils/dates');

/* The public API, each part checked where an app would meet it. */

test.describe('Settings advanced', () => {
	let settings;
	let name;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		name = uniqueRegistrationName('Settings advanced');
		code = await settings.openForNewRegistrationWithSeats(name, 1);
	});

	test('hides an API token until it is asked for, and removes it once confirmed', async () => {
		const token = await settings.createApiToken();

		const secret = await token.getAttribute('data-token');
		const masked = await token.getAttribute('data-token-hidden');

		/* The mask is the plugin's own doing: the token's length, with all but
		   the first few characters covered over. */
		expect(masked).toHaveLength(secret.length);
		expect(masked).toMatch(/^.{3}●+$/);
		await expect(token.locator('.token')).toHaveText(masked);

		await token.locator('.toggle-token').click();

		await expect(token.locator('.token')).toHaveText(secret);

		await token.locator('.toggle-token').click();

		await expect(token.locator('.token')).toHaveText(masked);

		await settings.removeApiToken(token, { confirm: false });

		await expect(settings.apiTokens).toHaveCount(1);

		await settings.removeApiToken(token);

		await expect(settings.apiTokens).toHaveCount(0);
	});

	test('lets a token read the registration only while the API is on', async ({ request }) => {
		const token = await settings.createApiToken();
		const secret = await token.getAttribute('data-token');

		await settings.set('publicApi', true);
		await settings.save();

		const answered = await validateToken(request, secret);

		expect(answered.ok()).toBe(true);
		expect((await answered.json()).registrationName).toBe(name);

		await settings.set('publicApi', false);
		await settings.save();

		const refused = await validateToken(request, secret);

		expect(refused.status()).toBe(403);
	});

	/* What the API is for: validate-token only says the token is good, this is the
	   one endpoint that hands the bookings over, and it is what both the companion
	   app and the Android app are built on. */
	test('reads the registration bookings with a token', async ({ request }) => {
		const token = await settings.createApiToken();
		const secret = await token.getAttribute('data-token');

		await settings.set('publicApi', true);
		await settings.allowBookings();

		const booking = await settings.makeBooking(code);

		const answered = await bookings(request, secret, isoDate(new Date()));

		expect(answered.ok()).toBe(true);

		const seated = (await answered.json()).bookings.find(
			(row) => row.booking_id === booking.id
		);

		expect(seated).toBeTruthy();
		expect(seated.first_name).toBe(BOOKER.firstName);
		expect(seated.booker_email).toBe(booking.email);

		/* The room the seat was drawn in is put on the row by the endpoint - it is
		   in the layout rather than on the booking. */
		expect(seated.room_name).toBeTruthy();
	});
});
