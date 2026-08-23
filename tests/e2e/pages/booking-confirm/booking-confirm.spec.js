const { test, expect } = require('@playwright/test');
const { BookingConfirmPage } = require('./booking-confirm-page');
const { BookingStatusPage } = require('../booking-status/booking-status-page');
const { SettingsPage, BOOKER } = require('../settings/settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const {
	uniqueBookerEmail,
	shouldSkipWithoutMail,
	NO_MAIL_CAPTURE,
	waitForMail,
	linkFromMail,
} = require('../../utils/mail');

const SEAT_COUNT = 1;

const CONFIRMED = 'Booking confirmed';
const NOT_CONFIRMED = 'We could not confirm your booking';

const ALREADY_USED = 'already confirmed/expired/deleted';

/* The other way a booking can be made: with email verification on, submitting the
   form sends a link instead, and the booking only exists once it is followed. Needs
   the captured mail to find that link (tests/e2e/utils/mail.js). */

test.describe('Booking confirm page', () => {
	let settings;
	let bookingConfirm;
	let code;

	test.beforeEach(async ({ page }) => {
		test.skip(await shouldSkipWithoutMail(page), NO_MAIL_CAPTURE);

		settings = new SettingsPage(page);
		bookingConfirm = new BookingConfirmPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Booking confirm'),
			SEAT_COUNT
		);

		await settings.set('emailConfirm', true);
		await settings.set('adminBookingNotification', false);
		await settings.set('bookerPendingNotification', false);
		await settings.save();
	});

	test('confirms the booking from the link in the verification email', async ({ page }) => {
		const bookingStatus = new BookingStatusPage(page);

		const link = await bookSeatForVerification(settings, code, page);

		await bookingConfirm.open(link);

		await expect(bookingConfirm.title).toHaveText(CONFIRMED);

		// The booking the link brought into being, reached the way the booker would
		const statusUrl = await bookingConfirm.bookingStatusLink.getAttribute('href');

		await page.goto(statusUrl);

		await expect(bookingStatus.bookingTable).toContainText(BOOKER.firstName);
	});

	test('turns down a confirmation link that has already been used', async ({ page }) => {
		const link = await bookSeatForVerification(settings, code, page);

		await bookingConfirm.open(link);

		await expect(bookingConfirm.title).toHaveText(CONFIRMED);

		await bookingConfirm.open(link);

		await expect(bookingConfirm.title).toHaveText(NOT_CONFIRMED);
		await expect(bookingConfirm.content).toContainText(ALREADY_USED);
	});
});

/**
 * Book a seat and hand back the confirmation link that was mailed for it.
 *
 * @return {Promise<string>} The address out of the booker's mail
 */
async function bookSeatForVerification(settings, code, adminPage) {
	const email = uniqueBookerEmail();

	const registration = await settings.openRegistration(code);

	await registration.completeBooking({ seats: SEAT_COUNT, ...BOOKER, email });

	// No booking yet, so no address for one - only word that the link is on its way
	await expect(registration.emailVerificationSent).toBeVisible();

	await registration.page.close();

	const mail = await waitForMail(adminPage, email);

	return linkFromMail(mail, 'seatreg=booking-confirm');
}
