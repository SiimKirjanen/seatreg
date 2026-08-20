const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');

/**
 * Page object for the page the booker lands on from the verification email
 * (?seatreg=booking-confirm&confirmation-code=<code>&registration=<code>).
 *
 * Same shell as the booking status page, and its title is the one thing that says
 * which way the confirmation went.
 */
class BookingConfirmPage {
	constructor(page) {
		this.page = page;
	}

	get shell() {
		return this.page.locator('.seatreg-page');
	}

	get title() {
		return this.page.locator('.seatreg-card__title');
	}

	get registrationName() {
		return this.page.locator('.seatreg-card__name');
	}

	/** What became of the booking, or why nothing did. */
	get content() {
		return this.page.locator('.seatreg-card__content');
	}

	get bookingStatusLink() {
		return this.content.locator('a[href*="seatreg=booking-status"]');
	}

	/** Follows the address as the plugin sent it, rather than assembling one here. */
	async open(url) {
		await this.page.goto(url);
		await expect(this.shell).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}
}

module.exports = { BookingConfirmPage };
