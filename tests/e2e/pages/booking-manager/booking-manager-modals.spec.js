const { test, expect } = require('@playwright/test');
const { BookingManagerPage } = require('./booking-manager-page');
const { SettingsPage } = require('../settings/settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const SEAT_COUNT = 4;

const SEATS = [
	{ seat: 1, firstName: 'Zoe', lastName: 'Vaher', email: 'zoe.vaher@example.com' },
	{ seat: 2, firstName: 'Anna', lastName: 'Kask', email: 'anna.kask@example.com' },
];

const BOOKER_EMAIL = 'booker@example.com';

/* A seat the layout has and no test books, so the lookup always lists it and the
   edit modal always has somewhere to move a booking to. */
const FREE_SEAT = 4;

const RENAMED_TO = 'Mari';

const COMPANY = { label: 'Company', type: 'text' };
const ANSWERED = 'Alpha';
const ANSWERED_AGAIN = 'Beta';

const SEAT_ALREADY_BOOKED = 'Seat is already booked/pending';

/* The two modals a booking is made and changed in. Both talk in seat ids, which
   are the layout's and not anything a number on screen shows, so every test here
   either reads one off the id lookup or is handed one back by the booking it made. */

test.describe('Booking manager modals', () => {
	let manager;
	let settings;
	let code;

	test.beforeEach(async ({ page }) => {
		manager = new BookingManagerPage(page);
		settings = new SettingsPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Booking manager modals'),
			SEAT_COUNT
		);
	});

	test('adds a booking of several seats under one booking id', async () => {
		await allowSeatsPerBooking(settings, SEATS.length);

		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({
			seats: SEATS,
			status: 'approved',
			bookerEmail: BOOKER_EMAIL,
		});

		expect(bookingId).toMatch(/^[0-9a-f]{40}$/);
		await expect(manager.bookingRow('approved', bookingId)).toHaveCount(SEATS.length);

		/* The modal only ever sent seat ids, so a row standing under a seat
		   number is the server having worked that number out from one. */
		for (const { seat } of SEATS) {
			await expect(manager.bookingRow('approved', bookingId, { seat })).toBeVisible();
		}

		await manager.openStatusTab('pending');

		await expect(manager.bookingRow('pending', bookingId)).toHaveCount(0);
	});

	test('fills the seat id in from the id lookup', async () => {
		await manager.openForRegistration(code);
		await manager.openAddBookingModal();

		const option = manager.seatIdOption(FREE_SEAT);
		const seatId = await option.getAttribute('data-seat-id');
		const roomName = await option.getAttribute('data-room-name');

		await manager.lookUpSeatId(0, FREE_SEAT);

		await expect(manager.addModalField(0, 'seat-id')).toHaveValue(seatId);
		await expect(manager.addModalField(0, 'room')).toHaveValue(roomName);

		await expect(manager.seatIdModal).toBeHidden();
		await expect(manager.addModal).toBeVisible();
	});

	test('refuses a booking on a seat that is already taken', async () => {
		await manager.openForRegistration(code);

		const { seatIds } = await manager.addBooking({ seats: [SEATS[0]] });

		await manager.openAddBookingModal();
		await manager.fillAddBooking({ seats: [SEATS[1]], seatIds });

		const answer = await manager.submitAddBooking();

		expect(answer.data.status).toBe('seat-booked');
		await expect(manager.addModalFieldError(0, 'seat-id')).toHaveText(SEAT_ALREADY_BOOKED);

		/* A booking that was turned down leaves the modal where it is, which is
		   the whole difference from one that went through. */
		await expect(manager.addModal).toBeVisible();
	});

	test('changes the seat, name and extra answers of a booking', async () => {
		await settings.addCustomField(COMPANY);
		await allowSeatsPerBooking(settings, SEATS.length);

		await manager.openForRegistration(code);

		const { bookingId, seatIds } = await manager.addBooking({
			seats: SEATS,
			bookerEmail: BOOKER_EMAIL,
			customFields: { [COMPANY.label]: ANSWERED },
		});

		const freeSeatId = await manager.seatIdFor(FREE_SEAT);

		await manager.openEditModal('pending', bookingId, { seat: SEATS[0].seat });

		await expect(manager.editSeatId).toHaveValue(seatIds[0]);
		await expect(manager.editCustomField(COMPANY.label)).toHaveValue(ANSWERED);

		/* A booking of several seats is asked for the seat's own address as well
		   as the one the whole booking is confirmed to. */
		await expect(manager.editSeatEmail).toHaveValue(SEATS[0].email);
		await expect(manager.editBookerEmail).toHaveValue(BOOKER_EMAIL);

		await manager.editSeatId.fill(freeSeatId);
		await manager.editFirstName.fill(RENAMED_TO);
		await manager.editCustomField(COMPANY.label).fill(ANSWERED_AGAIN);

		await manager.saveEditModal();

		/* The row is written over where it stands, under the seat number the
		   server worked out from the id it was handed. */
		await expect(manager.bookingRow('pending', bookingId, { seat: FREE_SEAT })).toBeVisible();
		await expect(manager.bookedName('pending', bookingId, { seat: FREE_SEAT })).toHaveText(
			`${RENAMED_TO} ${SEATS[0].lastName}`
		);

		await manager.reload();
		await manager.openMoreInfo('pending', bookingId, { seat: FREE_SEAT });

		await expect(manager.bookedName('pending', bookingId, { seat: FREE_SEAT })).toHaveText(
			`${RENAMED_TO} ${SEATS[0].lastName}`
		);
		await expect(
			manager.moreInfoLine('pending', bookingId, COMPANY.label, { seat: FREE_SEAT })
		).toContainText(`${COMPANY.label}: ${ANSWERED_AGAIN}`);
	});
});

/**
 * Let one booking hold more than a seat, and save whatever else the test has
 * already set.
 *
 * A booking added in the manager is held to the registration's own limit the
 * same as one a visitor makes, and that limit is one seat until the registration
 * says otherwise.
 */
async function allowSeatsPerBooking(settings, count) {
	await settings.set('maxSeats', String(count));
	await settings.save();
}
