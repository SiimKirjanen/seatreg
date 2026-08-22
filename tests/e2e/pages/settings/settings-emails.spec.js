const { test, expect } = require('@playwright/test');
const { SettingsPage, BOOKER } = require('./settings-page');
const { uniqueRegistrationName, bookingStatusUrlQuery } = require('../../utils/registrations');
const {
	shouldSkipWithoutMail,
	NO_MAIL_CAPTURE,
	waitForMail,
	linkFromMail,
} = require('../../utils/mail');

const EMAIL_FROM_NOT_VALID = 'Email FROM address is not correct';
const EMAIL_TEMPLATE_INCOMPLETE = 'Email template is missing required keywords';

const MALFORMED_EMAIL = 'bookings.example.com';
const TEMPLATE_WITHOUT_STATUS_LINK = 'Your booking [booking-id] has been approved.';

/* Every keyword the approved receipt is allowed to carry, so one mail says
   whether the substitution runs at all. */
const APPROVED_TEMPLATE =
	'Your booking [booking-id] is confirmed. [status-link] [booking-table]';

/* Colours nothing else in the wrapper uses, so finding one in the message says
   where it came from. The defaults are the plugin's own (SEATREG_EMAIL_DEFAULT_*
   in php/constants.php), which is what an email falls back to. */
const EMAIL_COLORS = { background: '#102030', heading: '#ff5722', text: '#e0d5c0' };
const DEFAULT_EMAIL_COLORS = { background: '#eef1f6', heading: '#1a2233', text: '#3d4759' };

const LOGO_POSITION = 'right';

/* An email the plugin could not send, or one a booker could not act on, is
   refused before the form is ever posted. The three templates are checked the
   same way, so one stands for all of them.

   The subjects, and the QR code the approved receipt can carry, are left alone
   for the same reason: each is the same mechanism as one already followed here. */

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

	/* The keywords are the whole of what a template can do, and a receipt that
	   reached a booker with them still written out would be the plugin's most
	   visible failure. Only the approved receipt takes all three. */
	test('fills the keywords of the approved receipt in', async ({ page }) => {
		test.skip(await shouldSkipWithoutMail(page), NO_MAIL_CAPTURE);

		const { code, booking, receipt } = await sendApprovedReceipt(
			settings,
			page,
			uniqueRegistrationName('Settings emails receipt'),
			() => settings.set('approvedEmailTemplate', APPROVED_TEMPLATE)
		);

		expect(receipt.message).not.toContain('[booking-id]');

		expect(receipt.message).toContain(booking.id);
		expect(linkFromMail(receipt, 'seatreg=booking-status')).toContain(
			bookingStatusUrlQuery(code, booking.id)
		);

		/* [booking-table] is the only one that expands to more than a value: a
		   table of the booking, a row to a seat. */
		expect(receipt.message).toContain(`${BOOKER.firstName} ${BOOKER.lastName}`);
	});

	/* The wrapper every one of the plugin's emails is written into. What is worth
	   following is not that the colours are the ones that were picked - they are
	   substituted straight in - but that the master switch decides whether any of
	   it is posted at all, the same way the Pages tab's does. */
	test('dresses the booker email in the colors and logo it was given', async ({ page }) => {
		test.skip(await shouldSkipWithoutMail(page), NO_MAIL_CAPTURE);

		const { code, receipt } = await sendApprovedReceipt(
			settings,
			page,
			uniqueRegistrationName('Settings emails appearance'),
			async () => {
				await settings.set('customizeEmailColors', true);
				await settings.set('emailBackgroundColor', EMAIL_COLORS.background);
				await settings.set('emailHeadingColor', EMAIL_COLORS.heading);
				await settings.set('emailTextColor', EMAIL_COLORS.text);
				await settings.set('emailLogoPosition', LOGO_POSITION);

				await settings.selectEmailLogo();
			}
		);

		expect(receipt.message).toContain(EMAIL_COLORS.background);
		expect(receipt.message).toContain(EMAIL_COLORS.heading);
		expect(receipt.message).toContain(EMAIL_COLORS.text);

		/* The logo is attached as an embedded image the message only points at,
		   and capturing stops wp_mail() before the mailer that would attach it is
		   built - so the reference, and where it was told to sit, are all there is
		   to read here. */
		expect(receipt.message).toContain('cid:emaillogo');
		expect(receipt.message).toContain(`text-align:${LOGO_POSITION}`);

		/* Nothing stores the checkbox: turning the customising off is what clears
		   the colours, because the inputs go disabled and stop being posted. */
		await settings.open(code);
		await settings.set('customizeEmailColors', false);
		await settings.save();

		await expect(settings.field('customizeEmailColors')).not.toBeChecked();
		await expect(settings.field('emailBackgroundColor')).toBeDisabled();
		await expect(settings.field('emailBackgroundColor')).toHaveValue(
			DEFAULT_EMAIL_COLORS.background
		);
		await expect(settings.field('emailHeadingColor')).toHaveValue(DEFAULT_EMAIL_COLORS.heading);
		await expect(settings.field('emailTextColor')).toHaveValue(DEFAULT_EMAIL_COLORS.text);
	});
});

/**
 * Send one approved receipt and hand back what the booker got. Approved on the
 * spot, so the receipt is sent by the booking itself rather than by someone
 * approving it afterwards.
 *
 * @param {function} dress What to set before the save that turns the receipt on
 * @return {Promise<{code: string, booking: Object, receipt: Object}>}
 */
async function sendApprovedReceipt(settings, page, name, dress) {
	const code = await settings.openForNewRegistrationWithSeats(name, 1);

	await settings.allowBookings({ approved: true });

	await settings.open(code);
	await settings.set('approvedBookingEmail', true);

	await dress();

	await settings.save();

	const booking = await settings.makeBooking(code);

	return { code, booking, receipt: await waitForMail(page, booking.email) };
}
