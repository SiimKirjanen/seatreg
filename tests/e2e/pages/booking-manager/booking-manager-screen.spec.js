const { test, expect } = require('@playwright/test');
const { createHash } = require('crypto');
const { BookingManagerPage, STATUS_TABS } = require('./booking-manager-page');
const { SettingsPage } = require('../settings/settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const SEAT_COUNT = 4;

/* Two seats of one booking, named so that ordering them by name and ordering
   them by seat number cannot come out the same way. */
const SEATS = [
	{ seat: 1, firstName: 'Zoe', lastName: 'Vaher', email: 'zoe.vaher@example.com' },
	{ seat: 2, firstName: 'Anna', lastName: 'Kask', email: 'anna.kask@example.com' },
];

const BOOKER_EMAIL = 'booker@example.com';

const FILE_TYPES = ['pdf', 'xlsx', 'text', 'csv'];

/* The screen opens on Pending, so these are the three moves that are moves. */
const TAB_ORDER = ['approved', 'deleted', 'pending'];

/* The screen around the bookings: which registration is being looked at, which
   list is open, and what the manager is asked to show of them. What happens to a
   booking once it is on screen is the other two specs. */

test.describe('SeatReg Booking manager screen', () => {
	let manager;
	let settings;
	let name;
	let code;

	test.beforeEach(async ({ page }) => {
		manager = new BookingManagerPage(page);
		settings = new SettingsPage(page);

		name = uniqueRegistrationName('Booking manager');
		code = await settings.openForNewRegistrationWithSeats(name, SEAT_COUNT);
	});

	test('shows the bookings of the registration picked in the registration tabs', async () => {
		await manager.open(code);

		await expect(manager.registrationTab(code)).toHaveClass(/nav-tab-active/);
		await expect(manager.registrationName).toHaveText(name);
		await expect(manager.registrationCode).toHaveValue(code);

		for (const type of FILE_TYPES) {
			await expect(manager.exportLink(type)).toHaveAttribute(
				'href',
				new RegExp(`\\?seatreg=${type}&code=${code}$`)
			);
		}
	});

	test('moves between the pending, approved and deleted lists', async ({ page }) => {
		await manager.openForRegistration(code);

		for (const status of TAB_ORDER) {
			await manager.openStatusTab(status);

			const id = panelId(name, STATUS_TABS[status]);

			await expect(manager.statusPanel(status)).toHaveAttribute('id', id);

			/* The list being looked at is put into the address, so one of them
			   can be linked to. */
			expect(new URL(page.url()).hash).toBe(`#${id}`);

			for (const other of Object.keys(STATUS_TABS)) {
				if (other !== status) {
					await expect(manager.statusPanel(other)).toBeHidden();
				}
			}
		}
	});

	test('finds a booking by what it was booked under', async () => {
		await allowSeatsPerBooking(settings, SEATS.length);

		await manager.openForRegistration(code);

		const { bookingId } = await manager.addBooking({
			seats: SEATS,
			bookerEmail: BOOKER_EMAIL,
		});

		await manager.search(SEATS[1].lastName);

		await expect(manager.bookingRow('pending', bookingId, { seat: 2 })).toBeVisible();
		await expect(manager.bookingRow('pending', bookingId, { seat: 1 })).toHaveCount(0);

		await manager.search('');

		await expect(manager.bookingRow('pending', bookingId, { seat: 1 })).toBeVisible();
		await expect(manager.bookingRow('pending', bookingId, { seat: 2 })).toBeVisible();
	});

	test('orders the bookings by the column that was picked', async () => {
		await allowSeatsPerBooking(settings, SEATS.length);

		await manager.openForRegistration(code);

		await manager.addBooking({ seats: SEATS, bookerEmail: BOOKER_EMAIL });

		const bySeat = SEATS.map(fullName);
		const byName = [...bySeat].reverse();

		/* Newest first and then by seat, which is what the screen opens on. */
		expect(await manager.bookedNames('pending')).toEqual(bySeat);

		await manager.sortBy('name');

		expect(await manager.bookedNames('pending')).toEqual(byName);

		await manager.sortBy('nr');

		expect(await manager.bookedNames('pending')).toEqual(bySeat);
	});

	test('filters the bookings by the day the manager is looking at', async () => {
		await settings.open(code);
		await settings.set('usingCalendar', true);
		await settings.save();

		await manager.openForRegistration(code);

		/* The day the server put the screen on. Working it out here instead
		   would mean guessing the site's timezone. */
		const today = fromIso(await manager.calendarDateValue.inputValue());
		const tomorrow = dayAfter(today);

		/* Asked for before anything is booked on it: until a day has been
		   picked the screen has left a date in the address that no booking can
		   be filtered by. */
		await manager.pickCalendarDate(today);

		const { bookingId } = await manager.addBooking({ seats: [SEATS[0]] });

		await manager.pickCalendarDate(tomorrow);

		expect(manager.calendarDateParam()).toBe(isoDate(tomorrow));
		await expect(manager.calendarDateValue).toHaveValue(isoDate(tomorrow));
		await expect(manager.bookingRow('pending', bookingId)).toHaveCount(0);

		await manager.pickCalendarDate(today);

		await expect(manager.bookingRow('pending', bookingId)).toBeVisible();
	});
});

/**
 * Let one booking hold more than a seat.
 *
 * A booking added in the manager is held to the registration's own limit the
 * same as one a visitor makes, and that limit is one seat until the registration
 * says otherwise. Only the tests that book several seats pay for it.
 */
async function allowSeatsPerBooking(settings, count) {
	await settings.set('maxSeats', String(count));
	await settings.save();
}

/**
 * The id the plugin gives one of the three panels: the sha1 of the registration's
 * name with its spaces replaced, and the list's own ending after it.
 */
function panelId(registrationName, ending) {
	const hash = createHash('sha1').update(registrationName.replace(/ /g, '_')).digest('hex');

	return hash + ending;
}

function fullName({ firstName, lastName }) {
	return `${firstName} ${lastName}`;
}

/** yyyy-mm-dd, how the day travels in the address and in the picker. */
function isoDate(date) {
	return [date.getFullYear(), date.getMonth() + 1, date.getDate()]
		.map((part, index) => (index === 0 ? part : String(part).padStart(2, '0')))
		.join('-');
}

/** Read a yyyy-mm-dd day as a local one, which parsing it whole would not do. */
function fromIso(iso) {
	const [year, month, day] = iso.split('-').map(Number);

	return new Date(year, month - 1, day);
}

function dayAfter(date) {
	const next = new Date(date);

	next.setDate(next.getDate() + 1);

	return next;
}
