const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { SEATREG_PAGES, openSeatRegScreen, expectOnSeatRegPage } = require('../../utils/navigation');
const { HomePage } = require('../home/home-page');

/** The four counters, by the data-stat each carries. */
const STATS = {
	seats: 'seats',
	open: 'open',
	confirmed: 'confirmed',
	pending: 'pending',
};

/** The doughnut's slices and the bar's segments, by the name each is drawn under. */
const PARTS = {
	confirmed: 'confirmed',
	pending: 'pending',
	open: 'open',
};

/**
 * Page object for the SeatReg Overview screen
 * (admin.php?page=seatreg-overview&tab=<code>).
 *
 * Everything on it is one calculation shown four ways, so a layout is what gives
 * it anything to say and bookings are what make it interesting. Every room's
 * numbers are rendered up front and picking one only swaps which panel is shown,
 * so nothing is fetched and there is nothing to wait for.
 */
class OverviewPage {
	constructor(page) {
		this.page = page;
		this.homePage = new HomePage(page);
	}

	/* Screen shell */

	get panel() {
		return this.page.locator('#seatreg-overview');
	}

	/** Names the registration the screen is showing. */
	get heading() {
		return this.panel.locator('.seatreg-overview__title');
	}

	get status() {
		return this.panel.locator('.seatreg-registration-card__badge');
	}

	get dates() {
		return this.panel.locator('.seatreg-overview__dates');
	}

	/** Registration, Settings and Bookings, in that order. */
	link(name) {
		return this.panel.locator('.seatreg-overview__links a', { hasText: name });
	}

	registrationTab(code) {
		return this.page.locator(`.nav-tab-wrapper a.nav-tab[href*="tab=${code}"]`);
	}

	/* Rooms */

	/** @param {string} name A room name, or 'Overall' for the whole registration */
	roomTab(name) {
		return this.panel
			.locator('.seatreg-overview__room')
			.filter({ has: this.page.getByText(name, { exact: true }) });
	}

	get selectedRoomTab() {
		return this.panel.locator('.seatreg-overview__room[aria-selected="true"]');
	}

	/** The booked-out-of-total the room list puts beside each room. */
	roomCount(name) {
		return this.roomTab(name).locator('.seatreg-overview__room-count');
	}

	/* Numbers, read from whichever panel is showing */

	get visiblePanel() {
		return this.panel.locator('.seatreg-overview__panel:not([hidden])');
	}

	get panelHeading() {
		return this.visiblePanel.locator('.seatreg-overview__panel-title');
	}

	/** @param {string} stat A value of STATS */
	statValue(stat) {
		return this.visiblePanel.locator(`[data-stat="${stat}"] .seatreg-overview__stat-value`);
	}

	/** @param {string} stat A value of STATS */
	statLink(stat) {
		return this.visiblePanel.locator(`a[data-stat="${stat}"]`);
	}

	/** @param {string} part A value of PARTS */
	legendPercent(part) {
		return this.visiblePanel
			.locator(`.seatreg-overview__legend-row--${part} .seatreg-overview__legend-percent`);
	}

	/** @param {string} shape One of doughnut, pie, column, bar */
	chartTypeButton(shape) {
		return this.visiblePanel.locator(`.seatreg-overview__chart-type[data-chart-type="${shape}"]`);
	}

	/** Every panel carries its own set, so this reads the one that is showing. */
	get pressedChartType() {
		return this.visiblePanel.locator('.seatreg-overview__chart-type[aria-pressed="true"]');
	}

	get canvas() {
		return this.visiblePanel.locator('.seatreg-overview__canvas');
	}

	/**
	 * Chart.js draws to a canvas, so the numbers it was given are not in the DOM.
	 * The type it is currently drawing and the values it holds come from the
	 * instance itself.
	 */
	async chartState() {
		return this.canvas.evaluate((canvas) => {
			const chart = window.Chart.getChart(canvas);

			return chart && { type: chart.config.type, data: chart.data.datasets[0].data };
		});
	}

	/** So a chart that quietly failed to draw is not taken for one that did. */
	async chartWasDrawn() {
		return this.canvas.evaluate((canvas) => {
			const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);

			return pixels.data.some((channel) => channel !== 0);
		});
	}

	/* Actions */

	/** Lands on whichever registration the plugin picks, not a particular one. */
	async goto() {
		await openSeatRegScreen(this.page, 'Overview');
	}

	async open(code) {
		await this.goto();
		await this.registrationTab(code).click();
		await expectOnSeatRegPage(this.page, SEATREG_PAGES.OVERVIEW, { tab: code });
	}

	/** Through the Home card, whose link carries the code. */
	async openForRegistration(code) {
		await this.homePage.goto();
		await this.homePage.overviewLink(code).click();

		await expectOnSeatRegPage(this.page, SEATREG_PAGES.OVERVIEW, { tab: code });
		await expect(this.panel).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/** @param {string} shape One of doughnut, pie, column, bar */
	async pickChartType(shape) {
		await this.chartTypeButton(shape).click();
	}

	/** @param {string} name A room name, or 'Overall' */
	async selectRoom(name) {
		await this.roomTab(name).click();
		await expect(this.selectedRoomTab).toContainText(name);
	}

	/** Walks the room list with the keyboard, from whichever room is selected. */
	async selectRoomWithKeyboard(key) {
		await this.selectedRoomTab.press(key);
	}
}

module.exports = { OverviewPage, STATS, PARTS };
