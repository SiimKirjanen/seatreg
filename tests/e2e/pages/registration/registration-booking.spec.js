const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('../settings/settings-page');
const { LayoutBuilderPage } = require('../layout-builder/layout-builder-page');
const { HomePage } = require('../home/home-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const SEAT_COUNT = 3;

const BOOKER = { firstName: 'Riina', lastName: 'Tamm', email: 'riina.tamm@example.com' };
const MALFORMED_EMAIL = 'riina.tamm.example.com';

const SEAT_PASSWORD = 'letmein7f3a';
const WRONG_PASSWORD = 'notthepassword';

const EMPTY_FIELD = 'Empty field';
const EMAIL_NOT_CORRECT = 'Email address is not correct';
const BOOKING_IS_FULL = 'Booking is full';

/* Making a booking, and everything a visitor does on the way to one, including
   the seat states a completed booking leaves behind.

   A booking only goes through on a registration told not to send any email, so
   the tests that make one call allowBookings() first. */

test.describe('Registration booking', () => {
	let settings;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Registration booking'),
			SEAT_COUNT
		);
	});

	test('takes a seat back out of the booking', async () => {
		await settings.set('maxSeats', '2');
		await settings.save();

		const registration = await settings.openRegistration(code);

		await registration.addSeatToBooking(1);
		await registration.addSeatToBooking(2);
		await registration.openCart();

		await expect(registration.cartItems).toHaveCount(2);

		await registration.removeSeatFromBooking(1);

		await expect(registration.cartItems).toHaveCount(1);
		await expect(registration.seatsInCart).toHaveText('1');

		await expect(registration.seat(1)).not.toHaveAttribute('data-selectedbox');
		await expect(registration.seat(2)).toHaveAttribute('data-selectedbox');

		await registration.removeSeatFromBooking(2);

		await expect(registration.seatsInCart).toHaveText('0');
		await expect(registration.cartInfo).toContainText('empty');

		await expect(registration.checkoutButton).toBeHidden();
	});

	test('refuses another seat once the booking is full', async () => {
		/* A registration takes one seat per booking until it is told otherwise. */
		const registration = await settings.openRegistration(code);

		await registration.addSeatToBooking(1);
		await registration.openSeat(2);

		await expect(registration.seatNotice).toHaveText(BOOKING_IS_FULL);
		await expect(registration.addToBookingButton).toHaveCount(0);
	});

	test('opens a seat that is behind a password', async ({ page }) => {
		const builder = new LayoutBuilderPage(page);

		/* The builder is opened off the registration's Home card, and the setup
		   left this tab on the settings. */
		await new HomePage(page).goto();
		await builder.open(code);

		/* Only a selected seat gets a row in the lock dialog. */
		await builder.selectSeat(1);
		await builder.applySeatLocks({ password: { 1: SEAT_PASSWORD } });
		await builder.save();

		const registration = await builder.openRegistration();

		await registration.openSeat(1);
		await registration.submitSeatPassword(WRONG_PASSWORD);

		await expect(registration.seatPasswordError).toBeVisible();
		await expect(registration.addToBookingButton).toHaveCount(0);

		await registration.submitSeatPassword(SEAT_PASSWORD);

		await expect(registration.addToBookingButton).toBeVisible();
	});

	test('refuses a booking whose details are not right', async () => {
		const registration = await settings.openRegistration(code);

		await registration.bookSeats(1);

		await registration.submitBooking();

		await expect(registration.fieldError('FirstName')).toHaveText(EMPTY_FIELD);

		await registration.checkoutField('FirstName').fill(BOOKER.firstName);
		await registration.checkoutField('LastName').fill(BOOKER.lastName);
		await registration.checkoutField('Email').fill(MALFORMED_EMAIL);

		await registration.submitBooking();

		await expect(registration.fieldError('Email')).toHaveText(EMAIL_NOT_CORRECT);

		/* Neither attempt was sent anywhere: the form is still on screen. */
		await expect(registration.checkoutArea).toBeVisible();
	});

	test('books a seat and hands back the link to its status', async () => {
		await settings.allowBookings();

		const registration = await settings.openRegistration(code);

		await registration.bookSeats(1);
		await fillBookerDetails(registration);
		await registration.submitBooking();

		await expect(registration.bookingConfirmed).toBeVisible();

		/* The address of the new booking's status page is the only thing the
		   registration ever tells the booker about it. */
		const statusUrl = await registration.bookingStatusLink.getAttribute('href');

		expect(statusUrl).toContain(`seatreg=booking-status`);
		expect(statusUrl).toContain(`registration=${code}`);
		await expect(registration.bookingStatusLink).toHaveText(statusUrl);

		/* Nothing repaints the map, so the seat is only seen to be taken on the
		   next visit. */
		await registration.page.reload();

		await expect(registration.seat(1)).toHaveAttribute('data-status', 'bron');

		await registration.openSeat(1);

		await expect(registration.addToBookingButton).toHaveCount(0);
	});

	test('books straight through when bookings are approved automatically', async () => {
		await settings.allowBookings({ approved: true });

		const registration = await settings.openRegistration(code);

		await registration.bookSeats(1);
		await fillBookerDetails(registration);
		await registration.submitBooking();

		await expect(registration.bookingConfirmed).toBeVisible();
		await expect(registration.bookingConfirmedHeader).toContainText('approved');

		await registration.page.reload();

		await expect(registration.seat(1)).toHaveAttribute('data-status', 'tak');
	});
});

/** Fill in the one booking the form is asking about. */
async function fillBookerDetails(registration) {
	await registration.checkoutField('FirstName').fill(BOOKER.firstName);
	await registration.checkoutField('LastName').fill(BOOKER.lastName);
	await registration.checkoutField('Email').fill(BOOKER.email);
}
