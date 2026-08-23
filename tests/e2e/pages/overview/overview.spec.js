const { test, expect } = require('@playwright/test');
const { OverviewPage, STAT_LABELS } = require('./overview-page');
const { SettingsPage } = require('../settings/settings-page');
const { LayoutBuilderPage } = require('../layout-builder/layout-builder-page');
const { HomePage } = require('../home/home-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const { monthsFromNow } = require('../../utils/dates');

const SEAT_COUNT = 3;

const OVERALL = 'Overall';
const BALCONY = { name: 'Balcony', seats: 2 };

const NO_START_DATE = 'Start date not set';
const NO_END_DATE = 'End date not set';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/* Both of the registration's dates are a month either side of today. */
const AHEAD = [1, 2];

/* The screen is one calculation shown four ways, so these tests are about that
   arithmetic reaching the screen.

   The date picker a calendar registration gets is left out: it opens on today
   whatever days the registration was opened for. */

test.describe('SeatReg Overview screen', () => {
	let overview;
	let settings;
	let name;
	let code;

	test.beforeEach(async ({ page }) => {
		overview = new OverviewPage(page);
		settings = new SettingsPage(page);

		name = uniqueRegistrationName('Overview');
		code = await settings.openForNewRegistrationWithSeats(name, SEAT_COUNT);
	});

	test('shows the statistics of the registration picked in the registration tabs', async () => {
		await overview.open(code);

		await expect(overview.registrationTab(code)).toHaveClass(/nav-tab-active/);
		await expect(overview.heading).toHaveText(name);
		await expect(overview.registrationCode).toHaveValue(code);

		await expect(overview.statValue(STAT_LABELS.seats)).toHaveText(String(SEAT_COUNT));
		await expect(overview.statValue(STAT_LABELS.open)).toHaveText(String(SEAT_COUNT));
		await expect(overview.statValue(STAT_LABELS.confirmed)).toHaveText('0');
		await expect(overview.statValue(STAT_LABELS.pending)).toHaveText('0');

		await expect(overview.pendingNotice).toContainText('0');
	});

	test('counts a pending booking apart from an approved one', async () => {
		await settings.allowBookings();
		await settings.makeBooking(code, { seats: [1] });

		await settings.open(code);
		await settings.allowBookings({ approved: true });
		await settings.makeBooking(code, { seats: [2] });

		await overview.openForRegistration(code);

		await expect(overview.statValue(STAT_LABELS.seats)).toHaveText(String(SEAT_COUNT));
		await expect(overview.statValue(STAT_LABELS.pending)).toHaveText('1');
		await expect(overview.statValue(STAT_LABELS.confirmed)).toHaveText('1');
		await expect(overview.statValue(STAT_LABELS.open)).toHaveText('1');

		await expect(overview.pendingNotice).toContainText('1');

		await expect(overview.legendPercent(STAT_LABELS.open)).toHaveText('33%');
		await expect(overview.legendPercent(STAT_LABELS.confirmed)).toHaveText('33%');
		await expect(overview.legendPercent(STAT_LABELS.pending)).toHaveText('33%');

		/* The doughnut is drawn from these rather than from the counters. */
		await expect(overview.chartInput('open')).toHaveValue('1');
		await expect(overview.chartInput('confirmed')).toHaveValue('1');
		await expect(overview.chartInput('pending')).toHaveValue('1');

		expect(await overview.doughnutWasDrawn()).toBe(true);
	});

	test('shows the numbers of the room that was picked', async ({ page }) => {
		const builder = new LayoutBuilderPage(page);

		await new HomePage(page).goto();
		await builder.open(code);
		await builder.addRoom(BALCONY.name);
		await builder.placeSeats(BALCONY.seats);
		await builder.save();

		await overview.openForRegistration(code);

		await expect(overview.statValue(STAT_LABELS.seats)).toHaveText(
			String(SEAT_COUNT + BALCONY.seats)
		);

		await overview.selectRoom(BALCONY.name);

		await expect(overview.heading).toHaveText(BALCONY.name);
		await expect(overview.statValue(STAT_LABELS.seats)).toHaveText(String(BALCONY.seats));
		await expect(overview.statValue(STAT_LABELS.open)).toHaveText(String(BALCONY.seats));

		await overview.selectRoom(OVERALL);

		await expect(overview.heading).toHaveText(name);
		await expect(overview.statValue(STAT_LABELS.seats)).toHaveText(
			String(SEAT_COUNT + BALCONY.seats)
		);
	});

	test('writes out the dates the registration runs between', async () => {
		await overview.open(code);

		await expect(overview.dates).toHaveText([NO_START_DATE, NO_END_DATE]);

		const [start, end] = AHEAD.map(monthsFromNow);

		await settings.open(code);
		await settings.pickRegistrationDate('start', start);
		await settings.pickRegistrationDate('end', end);
		await settings.save();

		await overview.open(code);

		/* Stored as milliseconds and written out by the page, so the day is
		   whatever those milliseconds are in the browser's own timezone. */
		await expect(overview.dates.first()).toContainText(writtenOut(start));
		await expect(overview.dates.last()).toContainText(writtenOut(end));
	});
});

/**
 * The day as the screen writes it out, dd.Mon.yyyy.
 *
 * Both pickers store the day at noon UTC, and the page turns that back into a
 * date in whatever timezone the browser is in - so the expected day is worked
 * out the same way rather than assumed.
 */
function writtenOut(date) {
	const stored = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), 12));

	return [
		String(stored.getDate()).padStart(2, '0'),
		MONTHS[stored.getMonth()],
		stored.getFullYear(),
	].join('.');
}
