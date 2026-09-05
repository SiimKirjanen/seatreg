const { test, expect } = require('@playwright/test');
const { OverviewPage, STATS, PARTS } = require('./overview-page');
const { SettingsPage } = require('../settings/settings-page');
const { LayoutBuilderPage } = require('../layout-builder/layout-builder-page');
const { HomePage } = require('../home/home-page');
const { uniqueRegistrationName } = require('../../utils/registrations');
const { monthsFromNow } = require('../../utils/dates');

const SEAT_COUNT = 3;

const OVERALL = 'Overall';
const BALCONY = { name: 'Balcony', seats: 2 };

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

		await expect(overview.statValue(STATS.seats)).toHaveText(String(SEAT_COUNT));
		await expect(overview.statValue(STATS.open)).toHaveText(String(SEAT_COUNT));
		await expect(overview.statValue(STATS.confirmed)).toHaveText('0');
		await expect(overview.statValue(STATS.pending)).toHaveText('0');
	});

	test('counts a pending booking apart from an approved one', async () => {
		await settings.allowBookings();
		await settings.makeBooking(code, { seats: [1] });

		await settings.open(code);
		await settings.allowBookings({ approved: true });
		await settings.makeBooking(code, { seats: [2] });

		await overview.openForRegistration(code);

		await expect(overview.statValue(STATS.seats)).toHaveText(String(SEAT_COUNT));
		await expect(overview.statValue(STATS.pending)).toHaveText('1');
		await expect(overview.statValue(STATS.confirmed)).toHaveText('1');
		await expect(overview.statValue(STATS.open)).toHaveText('1');

		await expect(overview.legendPercent(PARTS.open)).toHaveText('33%');
		await expect(overview.legendPercent(PARTS.confirmed)).toHaveText('33%');
		await expect(overview.legendPercent(PARTS.pending)).toHaveText('33%');

		/* The room list says the same thing as a fraction of the whole. */
		await expect(overview.roomCount(OVERALL)).toHaveText(`2 / ${SEAT_COUNT}`);

		/* The chart is drawn from the same numbers, in the legend's order. */
		expect(await overview.chartState()).toEqual({ type: 'doughnut', data: [1, 1, 1] });
		expect(await overview.chartWasDrawn()).toBe(true);
	});

	test('shows the numbers of the room that was picked', async ({ page }) => {
		const builder = new LayoutBuilderPage(page);

		await new HomePage(page).goto();
		await builder.open(code);
		await builder.addRoom(BALCONY.name);
		await builder.placeSeats(BALCONY.seats);
		await builder.save();

		await overview.openForRegistration(code);

		await expect(overview.statValue(STATS.seats)).toHaveText(
			String(SEAT_COUNT + BALCONY.seats)
		);

		await overview.selectRoom(BALCONY.name);

		await expect(overview.panelHeading).toHaveText(BALCONY.name);
		await expect(overview.statValue(STATS.seats)).toHaveText(String(BALCONY.seats));
		await expect(overview.statValue(STATS.open)).toHaveText(String(BALCONY.seats));

		/* The room list is a tablist, so it is walkable without the mouse.
		   Home reaches Overall past whatever rooms the registration already had. */
		await overview.selectRoomWithKeyboard('Home');

		await expect(overview.selectedRoomTab).toContainText(OVERALL);
		await expect(overview.statValue(STATS.seats)).toHaveText(
			String(SEAT_COUNT + BALCONY.seats)
		);
	});

	test('redraws the chart in the shape that was picked, room by room', async ({ page }) => {
		const builder = new LayoutBuilderPage(page);

		await new HomePage(page).goto();
		await builder.open(code);
		await builder.addRoom(BALCONY.name);
		await builder.placeSeats(BALCONY.seats);
		await builder.save();

		await overview.openForRegistration(code);

		/* Column and bar are the same Chart.js type on different axes, so the
		   type alone does not tell them apart - that they both draw does. */
		for (const shape of ['pie', 'column', 'bar']) {
			await overview.pickChartType(shape);

			expect(await overview.chartWasDrawn()).toBe(true);
		}

		/* A room's canvas is hidden until its tab is picked, which is the case
		   Chart.js cannot size, so it is drawn on the way in rather than up front.
		   Each panel has its own buttons, so the shape has to survive the switch. */
		await overview.selectRoom(BALCONY.name);

		await expect(overview.pressedChartType).toHaveAttribute('data-chart-type', 'bar');
		expect(await overview.chartState()).toEqual({
			type: 'bar',
			data: [0, 0, BALCONY.seats],
		});
		expect(await overview.chartWasDrawn()).toBe(true);
	});

	test('links its counters at the matching tab of the booking manager', async () => {
		await overview.open(code);

		await expect(overview.statLink(STATS.pending)).toHaveAttribute(
			'href',
			new RegExp(`page=seatreg-management&tab=${code}#\\w+bron$`)
		);
		await expect(overview.statLink(STATS.confirmed)).toHaveAttribute(
			'href',
			new RegExp(`page=seatreg-management&tab=${code}#\\w+taken$`)
		);
	});

	test('writes out the dates the registration runs between', async () => {
		const [start, end] = AHEAD.map(monthsFromNow);

		await settings.open(code);
		await settings.pickRegistrationDate('start', start);
		await settings.pickRegistrationDate('end', end);
		await settings.save();

		await overview.open(code);

		/* Written out by the site rather than the browser, so the day is whatever
		   the stored milliseconds are in the site's own timezone. */
		await expect(overview.dates).toContainText(writtenOut(start));
		await expect(overview.dates).toContainText(writtenOut(end));
	});
});

/**
 * The day as the screen writes it out, `M j Y`.
 *
 * Both pickers store the day at noon UTC, and the site turns that back into a
 * date in the timezone it is set to - UTC unless someone changed it - so the
 * expected day is worked out the same way rather than assumed.
 */
function writtenOut(date) {
	const stored = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), 12));

	return [
		MONTHS[stored.getUTCMonth()],
		stored.getUTCDate(),
		stored.getUTCFullYear(),
	].join(' ');
}
