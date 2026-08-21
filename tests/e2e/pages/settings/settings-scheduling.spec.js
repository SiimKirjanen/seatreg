const { test, expect } = require('@playwright/test');
const { SettingsPage } = require('./settings-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const { siteLocalTime } = require('../../utils/site');
const { isoDate, monthsFromNow, dayNextMonth } = require('../../utils/dates');

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const CALENDAR_DAYS = [10, 20];

/* When the tab is set to dates on either side of today, and when it is set to
   dates behind it. Both are the 15th, a day every month has, so adding months
   cannot land on one that does not exist. */
const AHEAD = [1, 2];
const BEHIND = [-2, -1];

/* Two conversions worth following - a date turned into the milliseconds that get
   stored, and back - and two ways of shutting a visitor out, both of which the
   registration renders without needing a layout. */

test.describe('Settings scheduling', () => {
	let settings;
	let code;

	test.beforeEach(async ({ page }) => {
		settings = new SettingsPage(page);

		code = await settings.openForNewRegistration(uniqueRegistrationName('Settings scheduling'));
	});

	test('offers the calendar dates only while the calendar is on', async () => {
		await settings.openSection('scheduling');

		await expect(settings.calendarDatesGroup).toBeHidden();

		await settings.set('usingCalendar', true);

		await expect(settings.calendarDatesGroup).toBeVisible();
		await expect(settings.calendarDatesPicker).toBeVisible();

		await settings.set('usingCalendar', false);

		await expect(settings.calendarDatesGroup).toBeHidden();
	});

	test('keeps the calendar dates that were picked', async () => {
		const dates = CALENDAR_DAYS.map(dayNextMonth);
		const stored = dates.map(isoDate);

		await settings.set('usingCalendar', true);
		await settings.pickCalendarDates(dates);

		for (const date of stored) {
			await expect(settings.calendarDateChip(date)).toBeVisible();
		}

		await settings.save();

		await expect(settings.field('usingCalendar')).toBeChecked();
		await expect(settings.calendarDates).toHaveValue(stored.join(','));

		/* The chips are drawn from that value again on load, so they say the
		   picker was handed back what was saved. */
		for (const date of stored) {
			await expect(settings.calendarDateChip(date)).toBeVisible();
		}
	});

	test('keeps the registration dates that were picked', async () => {
		const [start, end] = AHEAD.map(monthsFromNow);

		await settings.pickRegistrationDate('start', start);
		await settings.pickRegistrationDate('end', end);

		await expect(settings.registrationDateValue('start')).toHaveValue(String(noonUtc(start)));
		await expect(settings.registrationDateValue('end')).toHaveValue(String(noonUtc(end)));

		await settings.save();

		/* The stored milliseconds are what comes back, and the date on screen is
		   written out of them again on load. */
		await expect(settings.registrationDateValue('start')).toHaveValue(String(noonUtc(start)));
		await expect(settings.registrationDateInput('start')).toHaveValue(displayedDate(start));
		await expect(settings.registrationDateValue('end')).toHaveValue(String(noonUtc(end)));
		await expect(settings.registrationDateInput('end')).toHaveValue(displayedDate(end));
	});

	test('shuts the registration before its start date and after its end date', async () => {
		const [opensOn, closesOn] = AHEAD.map(monthsFromNow);

		await settings.pickRegistrationDate('start', opensOn);
		await settings.pickRegistrationDate('end', closesOn);
		await settings.save();

		const beforeItOpens = await settings.openRegistration(code);

		await expect(beforeItOpens.timeNotice).toBeVisible();
		await expect(beforeItOpens.timeNotice).toContainText(noticedDate(opensOn));

		await beforeItOpens.page.close();

		const [openedOn, closedOn] = BEHIND.map(monthsFromNow);

		await settings.open(code);
		await settings.pickRegistrationDate('start', openedOn);
		await settings.pickRegistrationDate('end', closedOn);
		await settings.save();

		const afterItClosed = await settings.openRegistration(code);

		await expect(afterItClosed.timeNotice).toBeVisible();
		await expect(afterItClosed.timeNotice).toContainText(noticedDate(closedOn));
	});

	/* The daily hours are judged by the site's clock, which is not necessarily
	   the one these tests run on, so the window is chosen from the site's own
	   time. It is left to be the only restriction the registration has: a start
	   or end date would be answered for first. */
	test('shuts the registration outside its daily hours', async ({ page }) => {
		const siteTime = await siteLocalTime(page);
		const [opensAt, closesAt] = siteTime.hours >= 2 ? ['00:30', '01:30'] : ['22:30', '23:30'];

		await settings.open(code);
		await settings.set('startTime', opensAt);
		await settings.set('endTime', closesAt);
		await settings.save();

		await expect(settings.field('startTime')).toHaveValue(opensAt);
		await expect(settings.field('endTime')).toHaveValue(closesAt);

		const registration = await settings.openRegistration(code);

		await expect(registration.timeNotice).toBeVisible();
		await expect(registration.timeNotice).toContainText(opensAt);
		await expect(registration.timeNotice).toContainText(closesAt);
	});
});

/**
 * The milliseconds a picked day is stored as. Both pickers put it at noon UTC so
 * the day survives whatever timezone either end of the trip is in.
 */
function noonUtc(date) {
	return Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), 12, 0, 0);
}

/** dd.mm.yyyy, how a stored date is written back into the field on screen. */
function displayedDate(date) {
	return [date.getDate(), date.getMonth() + 1]
		.map((part) => String(part).padStart(2, '0'))
		.concat(date.getFullYear())
		.join('.');
}

/** How the registration names a date in the notice it shuts visitors out with. */
function noticedDate(date) {
	return `${MONTHS[date.getMonth()]} ${date.getDate()} ${date.getFullYear()}`;
}
