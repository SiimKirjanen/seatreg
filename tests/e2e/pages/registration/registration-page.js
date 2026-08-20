const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');

/**
 * Page object for the public registration view
 * (?seatreg=registration&c=<code>&page_id=seatreg).
 *
 * The page paints itself from the saved layout after load, so nothing is assumed
 * present. Several controls are rendered twice, once beside the map and once in a
 * bar along the bottom for narrow screens; the suite runs at Desktop Chrome
 * width, so the locators here name the ones beside the map.
 */
class RegistrationPage {
	constructor(page) {
		this.page = page;
	}

	/* What a visitor is shown instead of the registration. A closed registration
	   and a password protected one both replace the whole page. */

	get closedNotice() {
		return this.page.locator('#center-wrap h2');
	}

	/** Only rendered when a close reason was set. */
	get closeReason() {
		return this.page.locator('#center-wrap p');
	}

	get passwordForm() {
		return this.page.locator('#pwd-form');
	}

	get passwordInput() {
		return this.page.locator('#reg-pwd');
	}

	/** Never rendered at all for someone who has a session. */
	get loginNotice() {
		return this.page.locator('#login-notify');
	}

	/** Serves both the dates and the hours: they are branches of one condition. */
	get timeNotice() {
		return this.page.locator('#time-notify');
	}

	/* Rooms */

	get roomNavLinks() {
		return this.page.locator('#room-nav-items .room-nav-link');
	}

	get roomDescription() {
		return this.page.locator('.top-info-bar [data-info="room"]');
	}

	get registrationInfo() {
		return this.page.locator('.top-info-bar [data-info="registration"]');
	}

	get activeRoomLink() {
		return this.page.locator('#room-nav-items .room-nav-link.active-nav-link');
	}

	roomLink(name) {
		return this.roomNavLinks.filter({ hasText: name });
	}

	/** How many seats are open, pending and confirmed, drawn from the layout. */
	get roomCounts() {
		return this.page.locator('#room-nav-info-inner .info-item');
	}

	/** The registration's own name, which also opens the info dialog. */
	get header() {
		return this.page.locator('#main-header');
	}

	/* What the info dialog says the whole registration holds, across every room. */

	get totalRooms() {
		return this.infoDialog.locator('.total-rooms');
	}

	get totalOpenSeats() {
		return this.infoDialog.locator('.total-open');
	}

	get totalPendingBookings() {
		return this.infoDialog.locator('.total-bron');
	}

	get totalApprovedBookings() {
		return this.infoDialog.locator('.total-tak');
	}

	/* Controls beside the map */

	get infoButton() {
		return this.page.locator('#controls-wrapper .room-nav-extra-info-btn');
	}

	get infoDialog() {
		return this.page.locator('#extra-info');
	}

	/** Opens the cart. Carries whatever caption the settings gave it. */
	get selectionButton() {
		return this.page.locator('#cart-checkout-btn');
	}

	get seatsInCart() {
		return this.page.locator('#seat-cart .seats-in-cart');
	}

	/* Zoom. The same controller is rendered either above the map or below it with
	   the cart, so where it is, is the whole setting. */

	get zoomController() {
		return this.page.locator('#zoom-controller');
	}

	get zoomControllerBelowMap() {
		return this.page.locator('#controls-wrapper #zoom-controller');
	}

	/* Seats */

	get seats() {
		return this.page.locator('#boxes .box');
	}

	seat(number) {
		return this.page.locator(`#boxes .box[data-seat-nr="${number}"]`);
	}

	get textBoxes() {
		return this.page.locator('#boxes .box.text-box');
	}

	get roomBackgroundImage() {
		return this.page.locator('#boxes img.room-image');
	}

	/** The src alone only says where the page looked, not that anything was served. */
	async backgroundImageLoaded() {
		return this.roomBackgroundImage.evaluate((img) => img.complete && img.naturalWidth > 0);
	}

	/* Seat dialog. A seat that can be booked gets an add to booking button, one
	   that cannot gets a notice instead. */

	get seatDialog() {
		return this.page.locator('#confirm-dialog-mob');
	}

	get addToBookingButton() {
		return this.seatDialog.locator('.add-to-cart');
	}

	get seatNotice() {
		return this.seatDialog.locator('.seat-taken-notify');
	}

	get seatHoverText() {
		return this.seatDialog.locator('#confirm-dialog-mob-hover');
	}

	get seatPasswordInput() {
		return this.seatDialog.locator('#seat-password');
	}

	get seatPasswordError() {
		return this.seatDialog.locator('#password-error');
	}

	get seatDialogCloseButton() {
		return this.page.locator('#dialog-close-btn');
	}

	/* A button per option in place of the single add to booking button. Only drawn
	   where there is a way to pay; without one the seat falls back to being free. */

	get priceOptions() {
		return this.seatDialog.locator('.multi-price-wrap .add-to-cart');
	}

	/* Cart and booking form */

	get cartPopup() {
		return this.page.locator('#seat-cart-popup');
	}

	get cartItems() {
		return this.page.locator('#seat-cart-items .cart-item');
	}

	cartItem(number) {
		return this.cartItems.filter({
			has: this.page.locator('.cart-item-nr', { hasText: String(number) }),
		});
	}

	/** With nothing in the cart this is where it says so, in place of the list. */
	get cartInfo() {
		return this.page.locator('#seat-cart-info');
	}

	get checkoutButton() {
		return this.page.locator('#checkout');
	}

	/** Its text carries the currency symbol; data-booking-price is the number alone. */
	get totalPrice() {
		return this.page.locator('#booking-total-price');
	}

	async bookingCost() {
		return this.totalPrice.getAttribute('data-booking-price');
	}

	/* Coupons. Drawn into the cart whenever the registration has them turned on,
	   whatever the seats cost. */

	get couponBox() {
		return this.page.locator('#coupon-apply');
	}

	get couponInput() {
		return this.page.locator('#coupon-code-input');
	}

	/** What the box says about a code it would not take. */
	get couponMessage() {
		return this.page.locator('.coupon-apply-box__message');
	}

	get couponApplied() {
		return this.page.locator('#coupon-applied .coupon-applied-box__message');
	}

	get checkoutArea() {
		return this.page.locator('#checkout-area');
	}

	/** One per seat being booked for, unless the details are entered once. */
	get checkoutItems() {
		return this.page.locator('#checkout-input-area .check-item');
	}

	/** Offers to copy the first entry to the rest. */
	get checkoutSyncSettings() {
		return this.page.locator('#checkout-area .checkout-settings');
	}

	/** @param {string} name FirstName, LastName or Email */
	checkoutField(name) {
		return this.page.locator(`#checkout-input-area [data-field="${name}"]`);
	}

	/** Only asked for when the details are entered per seat. Carries
	    data-field="Email" like the per-seat ones, so checkoutField() also matches it. */
	get primaryEmail() {
		return this.page.locator('#prim-mail');
	}

	get primaryEmailLabel() {
		return this.page.locator('#checkout-input-area label').filter({
			has: this.primaryEmail,
		});
	}

	get customFooterText() {
		return this.page.locator('#checkout-input-area .custom-footer-text');
	}

	/** The control inside it is reached with checkoutField(). */
	customField(label) {
		return this.page.locator(`#checkout-input-area .custom-input[data-label="${label}"]`);
	}

	/** In the order they are asked. */
	customFieldLabels() {
		return this.checkoutItems.first().locator('.custom-input .l-text').allInnerTexts();
	}

	/** Each field carries its own, inside the same label, so it is found through it. */
	fieldError(name) {
		return this.page
			.locator('#checkout-input-area label')
			.filter({ has: this.page.locator(`[data-field="${name}"]`) })
			.locator('.field-error');
	}

	/** Where a booking the server turned down on its own rules says why. */
	get bookingRefusal() {
		return this.page.locator('#request-error');
	}

	/* Nothing else on the page ever shows the new booking's address, so this
	   dialog is the only way a test can learn it. */

	get bookingConfirmed() {
		return this.page.locator('#bookings-confirmed');
	}

	get bookingConfirmedHeader() {
		return this.bookingConfirmed.locator('.booking-confirmed-header');
	}

	get bookingStatusLink() {
		return this.bookingConfirmed.locator('.booking-check-url');
	}

	/** Shown in its place when the booker has to confirm by email: there is no
	    booking to hand an address for until they follow the link. */
	get emailVerificationSent() {
		return this.page.locator('#email-conf');
	}

	/* Calendar mode. Only rendered when the registration runs on a calendar. */

	get calendarDate() {
		return this.page.locator('#calendar-date');
	}

	get calendarDateSelection() {
		return this.page.locator('#calendar-date-selection');
	}

	/** Shown in place of the map on a day the registration is not open on. */
	get registrationMessage() {
		return this.page.locator('#registration-message');
	}

	/* Legends */

	legend(name) {
		return this.page.locator('#legends .legend-div').filter({ hasText: name });
	}

	seatsWithLegend(name) {
		return this.page.locator(`#boxes .box[data-legend="${name}"]`);
	}

	/* Actions */

	/** In the order the builder set, not the order the rooms were created in. */
	async roomNames() {
		await expect(this.roomNavLinks.first()).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

		return this.roomNavLinks.allInnerTexts();
	}

	/** The form posts back to the registration, so a wrong password lands on it again. */
	async submitPassword(password) {
		await this.passwordInput.fill(password);
		await this.passwordForm.locator('input[type="submit"]').click();
		await this.page.waitForLoadState('domcontentloaded');
	}

	async openSeat(number) {
		await this.seat(number).click();
		await expect(this.seatDialog).toBeVisible();
	}

	async closeSeatDialog() {
		await this.seatDialogCloseButton.click();
		await expect(this.seatDialog).toBeHidden();
	}

	async openRoom(name) {
		await this.roomLink(name).click();

		await expect(this.roomLink(name)).toHaveClass(/active-nav-link/);
	}

	/**
	 * Where the zoom and move buttons have put the map. Between them they do one
	 * thing - write a transform onto the seats - so this is the whole of it.
	 *
	 * @return {Promise<{scale: number, x: number, y: number}>}
	 */
	async mapPosition() {
		return this.page.locator('#boxes').evaluate((boxes) => {
			const transform = getComputedStyle(boxes).transform;
			/* A map nothing has moved yet carries no transform at all. */
			const matrix = new DOMMatrixReadOnly(transform === 'none' ? '' : transform);

			return { scale: matrix.a, x: matrix.e, y: matrix.f };
		});
	}

	/** @param {string} direction 'in' or 'out' */
	async zoomMap(direction) {
		await this.zoomController.locator(`.zoom-action[data-zoom="${direction}"]`).click();
		await this.#waitForMapToSettle();
	}

	/**
	 * Only the directions that move it back toward where it started do anything:
	 * a map already at its corner cannot go further up or left.
	 *
	 * @param {string} direction up, down, left or right
	 */
	async moveMap(direction) {
		await this.zoomController.locator(`.move-action[data-move="${direction}"]`).click();
		await this.#waitForMapToSettle();
	}

	/**
	 * Every zoom and pan is animated, so a reading taken when the click returns is
	 * of somewhere the map was only passing through. Two readings the same is what
	 * says it has arrived.
	 */
	async #waitForMapToSettle() {
		let previous = null;

		await expect
			.poll(async () => {
				const current = JSON.stringify(await this.mapPosition());
				const settled = current === previous;

				previous = current;

				return settled;
			})
			.toBe(true);
	}

	/** @param {number} number The seat's number, as the cart lists it */
	async removeSeatFromBooking(number) {
		const item = this.cartItem(number);

		await item.locator('.remove-cart-item').click();
		await expect(item).toHaveCount(0);
	}

	/**
	 * The answer is checked with the server, and a right one reopens the dialog
	 * with the seat now on offer, so nothing is waited for beyond the request.
	 */
	async submitSeatPassword(password) {
		await this.seatPasswordInput.fill(password);
		await this.seatDialog.locator('#password-check').click();
	}

	/**
	 * The picker is a modal put on the body, and only its Apply button commits the
	 * choice. Picking a day refetches the map for it.
	 *
	 * @param {string} date A yyyy-mm-dd day the registration is open on
	 */
	async pickCalendarDate(date) {
		await this.calendarDateSelection.click();

		const picker = this.page.locator('.pignose-calendar-wrapper');
		await expect(picker).toBeVisible();

		await picker.locator(`.pignose-calendar-unit[data-date="${date}"] a`).click();
		await picker.locator('.pignose-calendar-button-apply').click();

		await expect(picker).toBeHidden();
		await expect(this.page.locator('#calendar-date-change-loading')).toBeHidden();
	}

	/**
	 * A booking the server refuses shows a native alert rather than anything on
	 * the page (registration.js:1679-1693), which would leave the test waiting on
	 * a dialog nobody answered. What the page does next is left to the caller.
	 */
	async submitBooking() {
		this.page.once('dialog', (dialog) => dialog.accept());

		await this.page.locator('#checkout-confirm-btn').click();
	}

	/**
	 * The button pulses on a loop that never ends, so it will never hold still
	 * long enough for a click to wait for it. The animation only scales it, so
	 * where it stands is the middle either way.
	 */
	async openInfoDialog() {
		await expect(this.infoButton).toBeVisible();
		await this.infoButton.click({ force: true });

		await expect(this.infoDialog).toBeVisible();
	}

	/** Whether the cart then opens by itself is a setting, so it is not waited for. */
	async addSeatToBooking(number) {
		await this.openSeat(number);
		await this.addToBookingButton.click();
		await expect(this.seatDialog).toBeHidden();
	}

	/**
	 * Take the seat whose dialog is open, at one of the prices it is offered under.
	 *
	 * @param {number} option Which of the prices, counting from 1
	 */
	async pickPriceOption(option) {
		await this.priceOptions.nth(option - 1).click();
		await expect(this.seatDialog).toBeHidden();
	}

	async openCart() {
		await this.selectionButton.click();
		await expect(this.cartPopup).toBeVisible();
	}

	/**
	 * The code is checked with the server, and one it will not take leaves the box
	 * where it is with a message instead, so which of the two happened is left to
	 * the caller.
	 */
	async applyCoupon(code) {
		const answered = this.page.waitForResponse(
			(response) =>
				response.url().includes('admin-ajax.php') &&
				(response.request().postData() ?? '').includes('seatreg_check_coupon'),
			{ timeout: TIMEOUTS.NAVIGATION }
		);

		await this.couponInput.fill(code);
		await this.page.locator('#apply-coupon-btn').click();

		await answered;
	}

	/**
	 * The booker's details are not written onto the map but into the seat's
	 * tooltip, one row per thing the settings made public.
	 */
	async seatTooltip(number) {
		return this.seat(number).getAttribute('data-powertip');
	}

	/**
	 * Walk from an empty map to the booking form. A caller asking for more seats
	 * than the registration allows has to have raised the limit first.
	 */
	/**
	 * Answer the booking form for every seat taken. Fields are scoped to their own
	 * block, since the primary email cannot be told apart from the form as a whole.
	 *
	 * @param {Object} options.customFields Answers keyed by the field's label
	 */
	async fillBooking({ firstName, lastName, email, customFields = {} }) {
		const seats = await this.checkoutItems.count();

		for (let seat = 0; seat < seats; seat += 1) {
			const block = this.checkoutItems.nth(seat);

			await block.locator('[data-field="FirstName"]').fill(firstName);
			await block.locator('[data-field="LastName"]').fill(lastName);
			await block.locator('[data-field="Email"]').fill(email);

			for (const [label, value] of Object.entries(customFields)) {
				await block.locator(`[data-field="${label}"]`).fill(value);
			}
		}

		if (await this.primaryEmail.count()) {
			await this.primaryEmail.fill(email);
		}
	}

	async bookSeats(count) {
		for (let number = 1; number <= count; number += 1) {
			await this.addSeatToBooking(number);
		}

		await expect(this.seatsInCart).toHaveText(String(count));

		await this.openCart();
		await this.openCheckout();
	}

	/** The form is drawn seat by seat when the dialog opens. */
	async openCheckout() {
		await this.page.locator('#checkout').click();

		await expect(this.checkoutArea).toBeVisible();
		await expect(this.checkoutItems.first()).toBeVisible();
	}

	/**
	 * For asserting that something never reached a visitor. The layout arrives as
	 * JSON in the markup, so a value that leaked shows up here even when no
	 * element renders it.
	 */
	async html() {
		return this.page.content();
	}
}

module.exports = { RegistrationPage };
