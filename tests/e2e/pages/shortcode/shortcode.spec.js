const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('../settings/settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const { createPost } = require('../../utils/fixtures');

const SEAT_COUNT = 2;

const IFRAME_HEIGHT = 600;

/* Anything that is not a registration code the plugin will recognise. */
const UNKNOWN_CODE = 'not-a-registration-code';
const INVALID_CODE = 'Invalid registration code';

/* The shortcode is the only way a registration reaches a page of the site's own,
   and the only part of the plugin a visitor meets without a ?seatreg= address.

   The Home screen's shortcode modal is covered from home-modals.spec.js: it shows
   the text to copy, which is a different thing from the text working. */

test.describe('SeatReg shortcode', () => {
	let settings;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Shortcode'),
			SEAT_COUNT
		);
	});

	test('embeds the registration in a page, and says why when it cannot', async ({ page }) => {
		const post = await createPost(page, {
			title: uniqueRegistrationName('Shortcode post'),
			content: `[seatreg code="${code}" height="${IFRAME_HEIGHT}"]`,
		});

		await page.goto(post.url);

		/* The id carries a uniqid, so the frame can only be asked for by its shape
		   - which is also what the stylesheet the shortcode registers hangs off. */
		const frame = page.locator('iframe[id^="seatreg-shortcode-"]');

		await expect(frame).toBeVisible();
		await expect(frame).toHaveAttribute(
			'src',
			new RegExp(`seatreg=registration&c=${code}.*page_id=seatreg`)
		);

		/* The address alone only says where the frame was pointed. The seats are
		   the registration having been served into it. */
		await expect(
			page.frameLocator('iframe[id^="seatreg-shortcode-"]').locator('#boxes .box')
		).toHaveCount(SEAT_COUNT);

		const unknown = await createPost(page, {
			title: uniqueRegistrationName('Shortcode unknown'),
			content: `[seatreg code="${UNKNOWN_CODE}" height="${IFRAME_HEIGHT}"]`,
		});

		await page.goto(unknown.url);

		/* The reason replaces the shortcode where it stood, so it is read off the
		   page rather than out of any element of the plugin's. */
		await expect(page.locator('body')).toContainText(INVALID_CODE);
		await expect(page.locator('iframe[id^="seatreg-shortcode-"]')).toHaveCount(0);
	});
});
