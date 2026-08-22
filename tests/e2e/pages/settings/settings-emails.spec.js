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

/* An email the plugin could not send, or one a booker could not act on, is
   refused before the form is ever posted. The three templates are checked the
   same way, so one stands for all of them.

   How an email is dressed - its colours, its logo - is not covered: it is the
   same wrapper around every one of them and nothing the plugin works out. */

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

		const code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Settings emails receipt'),
			1
		);

		/* Approved on the spot, so the receipt is sent by the booking itself
		   rather than by someone approving it afterwards. */
		await settings.allowBookings({ approved: true });

		await settings.open(code);
		await settings.set('approvedBookingEmail', true);
		await settings.set('approvedEmailTemplate', APPROVED_TEMPLATE);
		await settings.save();

		const booking = await settings.makeBooking(code);

		const receipt = await waitForMail(page, booking.email);

		expect(receipt.message).not.toContain('[booking-id]');

		expect(receipt.message).toContain(booking.id);
		expect(linkFromMail(receipt, 'seatreg=booking-status')).toContain(
			bookingStatusUrlQuery(code, booking.id)
		);

		/* [booking-table] is the only one that expands to more than a value: a
		   table of the booking, a row to a seat. */
		expect(receipt.message).toContain(`${BOOKER.firstName} ${BOOKER.lastName}`);
	});
});
