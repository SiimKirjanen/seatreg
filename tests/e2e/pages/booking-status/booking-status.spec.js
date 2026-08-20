const { test, expect } = require('@playwright/test');
const { BookingStatusPage } = require('./booking-status-page');
const { SettingsPage, PAID_BOOKING_PAYMENT } = require('../settings/settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const {
	uniqueBookerEmail,
	mailCaptureEnabled,
	NO_MAIL_CAPTURE,
	mailSentTo,
	waitForMail,
} = require('../../utils/mail');

const SEAT_COUNT = 2;

const BOOKER = { firstName: 'Riina', lastName: 'Tamm' };

const CUSTOM_FIELD = { label: 'Meal', type: 'text' };
const MEAL = 'Vegetarian';

const SEAT_PRICE = 25;

const PAYPAL = {
	businessEmail: 'payments@example.com',
	buttonId: 'ABCD1234EFGH',
};

/* The page the booker is handed after booking. Its address is the whole credential
   - there is no lookup form - so every test here makes a booking first.

   How the page is dressed, and what it says when there is no such booking, belong
   to the settings that decide them and are covered from settings-pages.spec.js. */

test.describe('Booking status page', () => {
	let settings;
	let bookingStatus;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);
		bookingStatus = new BookingStatusPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Booking status'),
			SEAT_COUNT
		);
	});

	test('shows the booking it was made for', async () => {
		await settings.addCustomField(CUSTOM_FIELD);
		await settings.set('maxSeats', String(SEAT_COUNT));
		await settings.allowBookings();

		const booking = await bookSeats(settings, code, SEAT_COUNT, { [CUSTOM_FIELD.label]: MEAL });

		await bookingStatus.goto(code, booking.id);

		await expect(bookingStatus.content).toContainText(booking.id);

		for (let number = 1; number <= SEAT_COUNT; number += 1) {
			await expect(bookingStatus.bookingTable).toContainText(String(number));
		}

		await expect(bookingStatus.bookingTable).toContainText(BOOKER.firstName);
		await expect(bookingStatus.bookingTable).toContainText(BOOKER.lastName);
		await expect(bookingStatus.bookingTable).toContainText(MEAL);
	});

	test('offers the ways the registration takes payment', async () => {
		await settings.allowPaidBookings();

		await settings.open(code);
		await settings.set('paypalLegacyEnabled', true);
		await settings.set('paypalBusinessEmail', PAYPAL.businessEmail);
		await settings.set('paypalButtonId', PAYPAL.buttonId);
		await settings.save();

		await settings.priceSeats(code, SEAT_PRICE, SEAT_COUNT);

		const booking = await bookSeats(settings, code, 1);

		await bookingStatus.goto(code, booking.id);

		// The forms are only ever read: submitting one would send the run to PayPal
		await expect(bookingStatus.paymentTable).toContainText(String(SEAT_PRICE));

		await expect(bookingStatus.paymentForm('_xclick')).toHaveAttribute(
			'action',
			/paypal\.com/
		);

		const customPayment = bookingStatus.customPaymentButton(PAID_BOOKING_PAYMENT.title);

		await expect(customPayment).toBeVisible();
		await expect(
			bookingStatus.customPaymentDescription(PAID_BOOKING_PAYMENT.description)
		).toBeHidden();

		await customPayment.click();

		await expect(
			bookingStatus.customPaymentDescription(PAID_BOOKING_PAYMENT.description)
		).toBeVisible();
	});

	test('sends the booking receipt again', async ({ page }) => {
		// The only one here that needs a receipt to have gone out at all
		test.skip(!(await mailCaptureEnabled(page)), NO_MAIL_CAPTURE);

		await settings.allowBookings({ approved: true });
		await settings.set('approvedBookingEmail', true);
		await settings.save();

		const booking = await bookSeats(settings, code, 1);

		// The receipt the booking sent, which is what puts the button on the page
		await waitForMail(page, booking.email);

		await bookingStatus.goto(code, booking.id);
		await bookingStatus.resendReceiptButton.click();

		await expect(bookingStatus.successToast).toBeVisible();

		const received = await mailSentTo(page, booking.email);

		expect(received).toHaveLength(2);
	});

	test('offers the booking as a PDF only while the settings allow one', async () => {
		await settings.allowBookings({ approved: true });

		const booking = await bookSeats(settings, code, 1);

		await bookingStatus.goto(code, booking.id);

		/* Asserted but never followed: generating a PDF caches the absolute font
		   path under php/libs/tfpdf/, which the container and the host share, so
		   each would break PDF generation for the other. */
		await expect(bookingStatus.pdfLink).toHaveAttribute('href', new RegExp(`id=${booking.id}`));

		// Approved is the status the plugin offers a PDF for out of the box
		await settings.open(code);
		await settings.set('showApprovedBookingPdf', false);
		await settings.save();

		await bookingStatus.goto(code, booking.id);

		await expect(bookingStatus.pdfLink).toHaveCount(0);
	});
});

/**
 * Make one booking and hand back what is needed to look it up.
 *
 * @param {Object} customFields Answers keyed by the field's label
 * @return {Promise<{id: string, email: string}>}
 */
async function bookSeats(settings, code, seatCount, customFields = {}) {
	const email = uniqueBookerEmail();

	const registration = await settings.openRegistration(code);

	await registration.bookSeats(seatCount);
	await registration.fillBooking({ ...BOOKER, email, customFields });
	await registration.submitBooking();

	await expect(registration.bookingConfirmed).toBeVisible();

	const statusUrl = await registration.bookingStatusLink.getAttribute('href');

	await registration.page.close();

	return { id: new URL(statusUrl).searchParams.get('id'), email };
}
