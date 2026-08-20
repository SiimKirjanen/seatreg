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

	/* Both tables are built with inline styles and no classes of their own, so they
	   are told apart by the order they are written in: seats, then what they cost. */

	get bookingTable() {
		return this.content.locator('.seatreg-table-scroll table').first();
	}

	get paymentTable() {
		return this.content.locator('.seatreg-table-scroll table').nth(1);
	}

	/** Only offered where the settings allow one for a booking at this status. */
	get pdfLink() {
		return this.content.locator('a[href*="seatreg=booking-pdf"]');
	}

	/* Only drawn for a booking that has something left to pay. */

	get paymentForms() {
		return this.content.locator('.payment-forms');
	}

	/** @param {string} provider e.g. 'paypal-order' or 'stripe-checkout-session' */
	paymentForm(provider) {
		return this.paymentForms.locator(`form:has(input[value="${provider}"])`);
	}

	customPaymentButton(title) {
		return this.paymentForms.locator('.custom-payment-box').filter({ hasText: title });
	}

	/** Hidden until its button is clicked. Keyed by a payment id the test never sees. */
	customPaymentDescription(description) {
		return this.page.locator('#custom-payment-descriptions div').filter({ hasText: description });
	}

	get resendReceiptButton() {
		return this.page.locator('#send-receipt');
	}

	get successToast() {
		return this.page.locator('.alertify-log-success');
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
