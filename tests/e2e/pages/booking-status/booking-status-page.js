const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');

/**
 * Page object for the booking status page
 * (?seatreg=booking-status&registration=<code>&id=<bookingId>).
 *
 * It has no spec of its own yet. The screen exists here so the settings specs
 * can assert that what they saved reached a booker, the same arrangement the
 * registration page object was introduced under. Its own tests get added to this
 * folder when the screen is covered.
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
	 * Open the status page of a booking.
	 *
	 * This is the one address the suite builds itself. The admin has no link to
	 * the page anywhere, and the only link that exists is the one emailed to a
	 * booker, so there is nothing to click through to it. The address is the
	 * plain query string seatreg_get_registration_status_url() builds, which is
	 * the same whatever the site's permalink settings are.
	 *
	 * A booking id that matches nothing still renders the page: the registration
	 * is what its look is read from, and a missing booking only changes what is
	 * written in the card.
	 */
	async goto(code, bookingId) {
		await this.page.goto(`/?seatreg=booking-status&registration=${code}&id=${bookingId}`);
		await expect(this.shell).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/**
	 * Whether the browser really fetched the logo. The src alone only says where
	 * the page looked, not that anything was served from there.
	 */
	async logoLoaded() {
		return this.logo.evaluate((img) => img.complete && img.naturalWidth > 0);
	}
}

module.exports = { BookingStatusPage };
