const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { BookingStatusPage } = require('../booking-status/booking-status-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

/* Colours nothing else on the page uses, and what the browser computes them to.
   The three defaults are the plugin's own (SEATREG_PAGE_DEFAULT_* in
   php/constants.php), which is what a page falls back to. */
const COLORS = {
	background: { value: '#102030', computed: 'rgb(16, 32, 48)' },
	heading: { value: '#ff5722', computed: 'rgb(255, 87, 34)' },
	text: { value: '#e0d5c0', computed: 'rgb(224, 213, 192)' },
};
const DEFAULT_COLORS = {
	background: { value: '#f4f6fa', computed: 'rgb(244, 246, 250)' },
	heading: { value: '#1a2233', computed: 'rgb(26, 34, 51)' },
	text: { value: '#3d4759', computed: 'rgb(61, 71, 89)' },
};

const NOT_FOUND_TEXT = 'Write to us and we will look your booking up.';

/* One rule for each of the two boxes, each hiding a different part of the page
   shell, so what took effect says which box it came from. */
const STATUS_PAGE_RULE = '.seatreg-card__name{display:none}';
const CONFIRM_PAGE_RULE = '.seatreg-card__title{display:none}';

/* The standalone pages a booker lands on. The booking status page is the one
   reachable without a booking, so it stands for all three.

   The payment return page's text is not covered: visiting that page starts a
   payment. */

test.describe('Settings pages', () => {
	let settings;
	let bookingStatus;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);
		bookingStatus = new BookingStatusPage(page);

		code = await settings.openForNewRegistration(uniqueRegistrationName('Settings pages'));
	});

	test('paints the pages a booker lands on in the colors that were picked', async () => {
		await settings.set('customizePageColors', true);
		await settings.set('pageBackgroundColor', COLORS.background.value);
		await settings.set('pageHeadingColor', COLORS.heading.value);
		await settings.set('pageTextColor', COLORS.text.value);
		await settings.save();

		/* Nothing stores the checkbox. It comes back on because the three colours
		   were saved, which is also what enables the inputs. */
		await expect(settings.field('customizePageColors')).toBeChecked();
		await expect(settings.field('pageBackgroundColor')).toBeEnabled();
		await expect(settings.field('pageBackgroundColor')).toHaveValue(COLORS.background.value);
		await expect(settings.field('pageHeadingColor')).toHaveValue(COLORS.heading.value);
		await expect(settings.field('pageTextColor')).toHaveValue(COLORS.text.value);

		await bookingStatus.goto(code, noSuchBooking());

		await expect(bookingStatus.shell).toHaveCSS('background-color', COLORS.background.computed);
		await expect(bookingStatus.shell).toHaveCSS('color', COLORS.text.computed);
		await expect(bookingStatus.title).toHaveCSS('color', COLORS.heading.computed);

		/* Turning the customising off is what clears the colours: the inputs go
		   disabled, so they stop being posted at all. */
		await settings.open(code);
		await settings.set('customizePageColors', false);
		await settings.save();

		await expect(settings.field('customizePageColors')).not.toBeChecked();
		await expect(settings.field('pageBackgroundColor')).toBeDisabled();
		await expect(settings.field('pageBackgroundColor')).toHaveValue(
			DEFAULT_COLORS.background.value
		);
		await expect(settings.field('pageHeadingColor')).toHaveValue(DEFAULT_COLORS.heading.value);
		await expect(settings.field('pageTextColor')).toHaveValue(DEFAULT_COLORS.text.value);

		await bookingStatus.goto(code, noSuchBooking());

		await expect(bookingStatus.shell).toHaveCSS(
			'background-color',
			DEFAULT_COLORS.background.computed
		);
		await expect(bookingStatus.shell).toHaveCSS('color', DEFAULT_COLORS.text.computed);
		await expect(bookingStatus.title).toHaveCSS('color', DEFAULT_COLORS.heading.computed);
	});

	test('shows the page logo to the booker', async () => {
		await settings.selectPageLogo();
		await settings.save();

		await settings.openSection('pages');
		await expect(settings.pageLogoPreview).toBeVisible();

		const previewUrl = await settings.pageLogoPreview.getAttribute('src');

		await bookingStatus.goto(code, noSuchBooking());

		await expect(bookingStatus.logo).toHaveAttribute('src', previewUrl);
		expect(await bookingStatus.logoLoaded()).toBe(true);

		await settings.open(code);
		await settings.openSection('pages');
		await settings.pageLogoRemoveButton.click();
		await settings.save();

		await settings.openSection('pages');
		await expect(settings.pageLogo).toHaveValue('');
		await expect(settings.pageLogoPreview).toBeHidden();

		await bookingStatus.goto(code, noSuchBooking());

		await expect(bookingStatus.logo).toHaveCount(0);
	});

	test('shows the extra text when a booking cannot be found', async () => {
		await settings.set('bookingNotFoundText', NOT_FOUND_TEXT);
		await settings.save();

		/* What comes back is not what was typed: the text is stored wrapped in a
		   paragraph, so the field is asked what it contains rather than what it
		   equals. */
		expect(await settings.field('bookingNotFoundText').inputValue()).toContain(NOT_FOUND_TEXT);

		await bookingStatus.goto(code, noSuchBooking());

		await expect(bookingStatus.content).toContainText('Booking not found.');
		await expect(bookingStatus.content).toContainText(NOT_FOUND_TEXT);
	});

	test('applies the custom styles to the page they were written for', async () => {
		await settings.set('bookingStatusStyles', STATUS_PAGE_RULE);
		await settings.set('bookingConfirmStyles', CONFIRM_PAGE_RULE);
		await settings.save();

		await expect(settings.field('bookingStatusStyles')).toHaveValue(STATUS_PAGE_RULE);
		await expect(settings.field('bookingConfirmStyles')).toHaveValue(CONFIRM_PAGE_RULE);

		await bookingStatus.goto(code, noSuchBooking());

		await expect(bookingStatus.registrationName).toBeHidden();
		await expect(bookingStatus.title).toBeVisible();
	});
});

/**
 * A booking id nothing can match. The page renders for it all the same: what it
 * looks like comes from the registration, and a missing booking only changes
 * what is written in the card.
 */
function noSuchBooking() {
	return `e2e-no-such-booking-${Date.now().toString(36)}`;
}
