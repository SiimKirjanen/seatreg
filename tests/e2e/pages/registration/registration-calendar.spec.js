const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('../settings/settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');

const SEAT_COUNT = 2;

/* Two days next month, far enough apart that neither can be reached by
   miscounting, and late enough that the picker opens on a month holding both. */
const OPEN_DAYS = [10, 20];

/* A registration on a calendar is booked for one day at a time. The day is
   carried in the address, so a visitor can be sent straight to one - and a day
   that was never opened gets a notice instead of a map. */

test.describe('Registration calendar', () => {
	let settings;
	let code;
	let openDays;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		code = await settings.openForNewRegistrationWithSeats(
			uniqueRegistrationName('Registration calendar'),
			SEAT_COUNT
		);

		openDays = OPEN_DAYS.map(dayNextMonth);

		await settings.set('usingCalendar', true);
		await settings.pickCalendarDates(openDays);
		await settings.save();
	});

	test('picks a day and keeps it in the address', async ({ page }) => {
		const [firstDay, secondDay] = openDays.map(isoDate);

		const registration = await settings.homePage.openRegistrationWith(code, {
			'calendar-date': firstDay,
		});

		await expect(registration.calendarDate).toHaveText(writtenOut(openDays[0]));
		await expect(registration.seats).toHaveCount(SEAT_COUNT);

		await registration.pickCalendarDate(secondDay);

		await expect(registration.calendarDate).toHaveText(writtenOut(openDays[1]));

		/* The day is put back into the address, so the visitor can keep it. */
		expect(new URL(page.url()).searchParams.get('calendar-date')).toBe(secondDay);

		/* The map is fetched again for the day that was chosen. */
		await expect(registration.seats).toHaveCount(SEAT_COUNT);
	});

	test('turns visitors away on a day it is not open for', async () => {
		const closedDay = isoDate(dayNextMonth(15));

		const registration = await settings.homePage.openRegistrationWith(code, {
			'calendar-date': closedDay,
		});

		await expect(registration.registrationMessage).toBeVisible();

		/* No map at all on a day nothing can be booked for. */
		await expect(registration.seats).toHaveCount(0);
	});
});

function dayNextMonth(day) {
	const date = new Date();

	date.setDate(1);
	date.setMonth(date.getMonth() + 1);
	date.setDate(day);

	return date;
}

/** yyyy-mm-dd, how the day travels in the address and in the picker. */
function isoDate(date) {
	return [date.getFullYear(), date.getMonth() + 1, date.getDate()]
		.map((part, index) => (index === 0 ? part : String(part).padStart(2, '0')))
		.join('-');
}

/** How the registration writes the day out for the site's language. */
function writtenOut(date) {
	return new Intl.DateTimeFormat('en', {
		year: 'numeric',
		month: 'long',
		day: 'numeric',
	}).format(date);
}
