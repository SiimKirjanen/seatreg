const { test, expect } = require('@playwright/test');
const { SettingsPage, BOOKER } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const CLOSE_REASON = 'The event has sold out.';
const REGISTRATION_PASSWORD = 'letmein7f3a';
const WRONG_PASSWORD = 'notthepassword';

const SEAT_COUNT = 3;

const BOOKER_EMAIL = 'riina.tamm@example.com';

/* The wording a registration that does not count in seats uses for them. */
const PLACE_SELECTED = 'place selected';

/* What a registration that is not about rooms can call them instead. */
const ROOM_NOUN = 'stall';
const ROOM_NOUN_PLURAL = 'stalls';

/* Who gets to see the registration at all, and how much of it any one booker may
   take, so every one of these is checked on the registration itself. The limits
   are checked by making a booking and then trying to go past them. */

test.describe('Settings general', () => {
	let settings;
	let name;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		name = uniqueRegistrationName('Settings general');
		code = await settings.openForNewRegistration(name);
	});

	test('closes the registration and tells visitors why', async () => {
		await settings.set('registrationStatus', false);
		await settings.set('closeReason', CLOSE_REASON);

		await settings.save();

		const registration = await settings.openRegistration(code);

		await expect(registration.closedNotice).toContainText(name);
		await expect(registration.closeReason).toHaveText(CLOSE_REASON);
	});

	test('asks visitors for the registration password', async () => {
		await settings.set('registrationPassword', REGISTRATION_PASSWORD);

		await settings.save();

		const registration = await settings.openRegistration(code);

		await expect(registration.passwordForm).toBeVisible();

		await registration.submitPassword(WRONG_PASSWORD);
		await expect(registration.passwordForm).toBeVisible();

		await registration.submitPassword(REGISTRATION_PASSWORD);
		await expect(registration.passwordForm).toHaveCount(0);
	});

	/* The notice is rendered by the page, not by anything a visitor can dismiss,
	   so both halves of this are just what the registration comes back with. */
	test('takes bookings from logged in WordPress users only', async ({ browser }) => {
		await settings.set('requireWpLogin', true);

		await settings.save();

		const visitor = await settings.openRegistrationAsVisitor(browser, code);

		await expect(visitor.loginNotice).toBeVisible();

		await visitor.page.context().close();

		const registration = await settings.openRegistration(code);

		await expect(registration.loginNotice).toHaveCount(0);
	});

	/* The setting has no effect of its own beyond the word: it decides whether
	   the registration talks about seats or about places, everywhere it talks
	   about them at all. The cart is the cheapest place a visitor meets it. */
	test('calls the places places when the registration is not using seats', async () => {
		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Settings general seats'),
			SEAT_COUNT
		);

		await settings.set('usingSeats', false);
		await settings.save();

		const registration = await settings.openRegistration(code);

		await registration.addSeatToBooking(1);
		await registration.openCart();

		await expect(registration.cartInfo).toContainText(PLACE_SELECTED);
	});

	/* The word replaces "room" wherever either side talks about one. The builder is
	   rendered before a registration is chosen and repainted once its layout loads,
	   and the registration view gets the word from the page it is served on, so one
	   of each is checked. */
	test('calls a room whatever the registration calls it', async () => {
		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Settings general room noun'),
			SEAT_COUNT
		);

		await settings.set('roomNounSingular', ROOM_NOUN);
		await settings.set('roomNounPlural', ROOM_NOUN_PLURAL);
		await settings.save();

		const builder = await settings.openLayout(code);

		await expect(builder.addRoomButton).toContainText(ROOM_NOUN);

		const registration = await settings.openRegistration(code);

		await registration.header.click();

		await expect(registration.infoDialog).toContainText(ROOM_NOUN_PLURAL);
	});

	test('turns away a booker who is over the booking limit for their email address', async () => {
		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Settings general email limit'),
			SEAT_COUNT
		);

		await settings.set('bookingEmailLimit', '1');
		await settings.allowBookings();

		const registration = await settings.openRegistration(code);

		await bookSeat(registration, 1);

		await expect(registration.bookingConfirmed).toBeVisible();

		/* The same address coming back for a second booking is what the limit is
		   counted against, so the seat being a different one changes nothing. */
		await registration.page.reload();
		await bookSeat(registration, 2);

		await expect(registration.bookingRefusal).toBeVisible();

		/* The dialog a booking that went through would open is on the page
		   either way, so it is its being out of sight that says this one did
		   not. */
		await expect(registration.bookingConfirmed).toBeHidden();
	});

	test('turns away a WordPress user who is over their booking limit', async () => {
		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Settings general user limit'),
			SEAT_COUNT
		);

		/* The limit is counted per WordPress user, so there has to be one. */
		await settings.set('requireWpLogin', true);
		await settings.set('wpUserBookingLimit', '1');
		await settings.allowBookings();

		const registration = await settings.openRegistration(code);

		await bookSeat(registration, 1);

		await expect(registration.bookingConfirmed).toBeVisible();

		/* A second booking by the same user, under an address the email limit
		   has nothing against. */
		await registration.page.reload();
		await bookSeat(registration, 2, `second.${BOOKER_EMAIL}`);

		await expect(registration.bookingRefusal).toBeVisible();

		/* The dialog a booking that went through would open is on the page
		   either way, so it is its being out of sight that says this one did
		   not. */
		await expect(registration.bookingConfirmed).toBeHidden();
	});

	/* The sibling of the limit above, and counted differently: that one counts
	   bookings, this one counts the seats across all of them, so it is only
	   reached by a booking of more than one seat. */
	test('turns away a WordPress user who is over their total seat limit', async () => {
		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Settings general seat limit'),
			SEAT_COUNT
		);

		await settings.set('requireWpLogin', true);
		await settings.set('wpUserSeatLimit', '2');
		await settings.set('maxSeats', String(SEAT_COUNT));
		await settings.allowBookings();

		const registration = await settings.openRegistration(code);

		await registration.completeBooking({ seats: [1, 2], ...BOOKER, email: BOOKER_EMAIL });

		await expect(registration.bookingConfirmed).toBeVisible();

		/* The two seats already booked have used the allowance up, so a third
		   is refused however few bookings it would make. */
		await registration.page.reload();
		await bookSeat(registration, 3);

		await expect(registration.bookingRefusal).toContainText(
			'Allowed number of total booked seats per user is 2'
		);
		await expect(registration.bookingConfirmed).toBeHidden();
	});
});

/**
 * Walk one seat all the way to a submitted booking. Kept local because these
 * tests come back and book the same registration a second time, which is what
 * the limits are counted against.
 */
function bookSeat(registration, seatNumber, email = BOOKER_EMAIL) {
	return registration.completeBooking({ seats: [seatNumber], ...BOOKER, email });
}
