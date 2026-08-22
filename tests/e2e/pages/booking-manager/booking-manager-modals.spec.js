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

/* An import file is read by position, fifteen columns to a row, so a row of any
   other width is turned away before anything in it is looked at. */
const MALFORMED_CSV = 'Zoe,Vaher,zoe.vaher@example.com\n';
const CSV_WRONG_COLUMN_COUNT = 'Each row must contain exactly 15 columns';

/* The modals a booking is made, changed and moved in bulk through. All of them
   talk in seat ids, which are the layout's and not anything a number on screen
   shows, so every test here either reads one off the id lookup or is handed one
   back by the booking it made. */

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
		await settings.allowSeatsPerBooking(SEATS.length);

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

	/* The round trip the import modal itself describes: the file it takes is the
	   one the booking manager writes. Exported rows carry the seat and room ids
	   the layout gave them, which is the only place a valid one can come from -
	   and a fresh booking id, so a row can be imported back without clashing with
	   the booking it came from. */
	test('imports the bookings from an exported file, and refuses a file it cannot read', async () => {
		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({ seats: [SEATS[0]] });

		const exported = await manager.exportedBookings('csv', { s1: 'on', s2: 'on' });

		expect(exported).toContain(SEATS[0].email);

		/* The seat has to be free again before the file can go back in, and only a
		   permanent delete lets go of it. */
		await manager.applyBookingAction('pending', bookingId, 'delete');
		await manager.openStatusTab('deleted');
		await manager.permanentlyDelete(bookingId);

		await manager.openImportModal();
		await manager.uploadBookingsCsv(MALFORMED_CSV);

		await expect(manager.importError).toContainText(CSV_WRONG_COLUMN_COUNT);

		/* Reopened rather than handed a second file: nothing lets go of the one
		   that was refused until the modal is opened again. */
		await manager.openImportModal();
		await manager.uploadBookingsCsv(exported);

		await expect(manager.importFinalizationModal).toBeVisible();
		await expect(manager.importRows).toHaveCount(1);
		await expect(manager.importSummary).toContainText('total of 1 bookings');

		const answer = await manager.startBookingImport();

		expect(answer.success).toBe(true);

		await manager.reload();
		await manager.openStatusTab('pending');

		/* Back under a booking id of its own: the export writes a new one into
		   every row, which is what lets a file be imported beside what it came
		   from rather than only in place of it. */
		await expect(
			manager
				.statusPanel('pending')
				.locator(`.reg-seat-item[data-booker-email="${SEATS[0].email}"]`)
		).toBeVisible();
	});

	/* The modal filters nothing on screen - it writes the address of the export
	   and opens it - so the address is the whole of what it does. */
	test('builds the export address from the filters that were picked', async () => {
		await manager.openForRegistration(code);
		await manager.openExportFilters('csv');

		await manager.exportFilter('name').fill(SEATS[0].lastName);
		await manager.exportFilter('s2').uncheck();

		const generated = new URL(await manager.generateExportUrl('csv'));

		expect(generated.searchParams.get('seatreg')).toBe('csv');
		expect(generated.searchParams.get('code')).toBe(code);
		expect(generated.searchParams.get('name')).toBe(SEATS[0].lastName);

		/* The boxes carry no value of their own, and an unticked one is left out
		   of the address entirely - which is how the server reads it as off. */
		expect(generated.searchParams.get('s1')).toBe('on');
		expect(generated.searchParams.has('s2')).toBe(false);
	});

	test('changes the seat, name and extra answers of a booking', async () => {
		await settings.addCustomField(COMPANY);
		await settings.allowSeatsPerBooking(SEATS.length);

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
