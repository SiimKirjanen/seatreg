const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { SEATREG_PAGES, openSeatRegScreen, expectOnSeatRegPage } = require('../../utils/navigation');
const { HomePage } = require('../home/home-page');

/**
 * The four counters carry no id and no data attribute, so the heading is the only
 * thing telling them apart - and the first is headed Places instead of Seats on a
 * registration that does not count in seats.
 */
const STAT_LABELS = {
	seats: 'Seats',
	open: 'Open',
	confirmed: 'Confirmed',
	pending: 'Pending',
};

/** The doughnut's slices, by the hidden field each is drawn from. */
const CHART_INPUTS = {
	open: '.seats-open-don',
	confirmed: '.seats-taken-don',
	pending: '.seats-bron-don',
};

/**
 * Page object for the SeatReg Overview screen
 * (admin.php?page=seatreg-overview&tab=<code>).
 *
 * Everything on it is one calculation shown four ways, so a layout is what gives
 * it anything to say and bookings are what make it interesting. Picking a room
 * replaces the whole panel with markup fetched from the server, so nothing may be
 * held on to across a switch.
 */
class OverviewPage {
	constructor(page) {
		this.page = page;
		this.homePage = new HomePage(page);
	}

	/* Screen shell */

	/** The panel the whole screen is drawn in, and replaced as. */
	get panel() {
		return this.page.locator('#existing-regs');
	}

	/** Names the registration, or the room, whichever is being looked at. */
	get heading() {
		return this.panel.locator('.reg-overview-top-header');
	}

	get pendingNotice() {
		return this.panel.locator('.reg-overview-top-bron-notify');
	}

	/** Scoped to this screen: the Bookings screen puts the same id on the page. */
	get registrationCode() {
		return this.panel.locator('#seatreg-reg-code');
	}

	registrationTab(code) {
		return this.page.locator(`.nav-tab-wrapper a.nav-tab[href*="tab=${code}"]`);
	}

	/* Rooms */

	/** @param {string} name A room name, or 'Overall' for the whole registration */
	roomListItem(name) {
		return this.panel.locator('.room-list-item').filter({ hasText: name });
	}

	get activeRoomListItem() {
		return this.panel.locator('.room-list-item[data-active="true"]');
	}

	/* Numbers */

	/** @param {string} label A value of STAT_LABELS */
	statValue(label) {
		return this.panel
			.locator('.overview-middle-box')
			.filter({ has: this.page.getByText(label, { exact: true }) })
			.locator('.overview-middle-box-stat');
	}

	/**
	 * Rounded to whole percents for the registration and to two decimals for a
	 * single room, so this does not read back the same shape in both.
	 */
	legendPercent(label) {
		return this.panel
			.locator('.legend-block')
			.filter({ hasText: label })
			.locator('.legend-block-percent');
	}

	/**
	 * The chart is built out of these hidden fields rather than anything a test
	 * could reach, so they are where its numbers can be checked.
	 *
	 * @param {string} kind A key of CHART_INPUTS
	 */
	chartInput(kind) {
		return this.panel.locator(CHART_INPUTS[kind]);
	}

	get doughnut() {
		return this.panel.locator('canvas.stats-doughnut');
	}

	get dates() {
		return this.panel.locator('.reg-overview-top-date .time-stamp');
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

	/**
	 * The panel is fetched again rather than updated, so the reply arriving is
	 * what says the new numbers are on screen. The loading image is not waited on:
	 * a request that fails leaves it there for good.
	 *
	 * @param {string} name A room name, or 'Overall'
	 */
	async selectRoom(name) {
		const fetched = this.page.waitForResponse(
			(response) =>
				response.url().includes('admin-ajax.php') &&
				(response.request().postData() ?? '').includes('seatreg_get_room_stats'),
			{ timeout: TIMEOUTS.NAVIGATION }
		);

		await this.roomListItem(name).click();
		await fetched;

		await expect(this.activeRoomListItem).toHaveText(new RegExp(name));
	}

	/** So a chart that quietly failed to draw is not taken for one that did. */
	async doughnutWasDrawn() {
		return this.doughnut.evaluate((canvas) => {
			const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);

			return pixels.data.some((channel) => channel !== 0);
		});
	}
}

module.exports = { OverviewPage, STAT_LABELS };
