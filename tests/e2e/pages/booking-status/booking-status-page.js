const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');

/**
 * Page object for the booking status page
 * (?seatreg=booking-status&registration=<code>&id=<bookingId>).
 *
 * The shell it is built from - the card, its logo, name and title - is shared
 * with the booking confirm and the payment return page
 * (php/services/SeatregPublicPageService.php), so what is asserted through it
 * holds for those two as well.
 */
class BookingStatusPage {
	constructor(page) {
		this.page = page;
	}

	/* Page shell */

	get shell() {
		return this.page.locator('.seatreg-page');
	}

	get title() {
		return this.page.locator('.seatreg-card__title');
	}

	get registrationName() {
		return this.page.locator('.seatreg-card__name');
	}

	/** Only rendered when the registration was given a page logo. */
	get logo() {
		return this.page.locator('img.seatreg-card__logo');
	}

	/** The booking, or what is shown in place of one. */
	get content() {
		return this.page.locator('.seatreg-card__content');
	}

	/* Actions */

	/**
	 * The one address the suite builds itself: the admin has no link to this page
	 * and the only one that exists is emailed to the booker. A booking id that
	 * matches nothing still renders the page, since its look comes from the
	 * registration.
	 */
	async goto(code, bookingId) {
		await this.page.goto(`/?seatreg=booking-status&registration=${code}&id=${bookingId}`);
		await expect(this.shell).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/** The src alone only says where the page looked, not that anything was served. */
	async logoLoaded() {
		return this.logo.evaluate((img) => img.complete && img.naturalWidth > 0);
	}
}

module.exports = { BookingStatusPage };
