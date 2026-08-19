const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');

/**
 * Page object for the public registration view
 * (?seatreg=registration&c=<code>&page_id=seatreg).
 *
 * It has no spec of its own yet. The screen exists here so the layout builder
 * and settings specs can assert that what they saved survived, through named
 * locators instead of raw selectors. Its own tests get added to this folder
 * when the screen is covered.
 *
 * Two things about the screen shape the code here:
 *
 * 1. The page paints itself from the saved layout after load, so everything is
 *    asserted with a navigation length timeout rather than assumed present.
 * 2. Several controls are rendered twice, once beside the map and once in a bar
 *    along the bottom for narrow screens, and the width decides which of the two
 *    is on screen (registration/css/registration.scss). The suite runs at
 *    Desktop Chrome width, so the locators here name the ones beside the map -
 *    and nothing opens the "Change room" button the room links collapse behind
 *    below 720px either.
 */
class RegistrationPage {
	constructor(page) {
		this.page = page;
	}

	/* What a visitor is shown instead of the registration. A closed registration
	   and a password protected one both replace the whole page, so neither of
	   these can be on screen at the same time as the rooms below. */

	get closedNotice() {
		return this.page.locator('#center-wrap h2');
	}

	/** Only rendered when a close reason was set in the settings. */
	get closeReason() {
		return this.page.locator('#center-wrap p');
	}

	get passwordForm() {
		return this.page.locator('#pwd-form');
	}

	get passwordInput() {
		return this.page.locator('#reg-pwd');
	}

	/**
	 * Shown over the registration when it takes bookings from logged in
	 * WordPress users only, and never rendered at all for someone who has a
	 * session.
	 */
	get loginNotice() {
		return this.page.locator('#login-notify');
	}

	/**
	 * Shown when the registration is outside the dates or the hours it takes
	 * bookings in. One element serves both: they are branches of the same
	 * condition, and the dates win over the hours.
	 */
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

	/** What the registration says about itself, above the map. */
	get registrationInfo() {
		return this.page.locator('.top-info-bar [data-info="registration"]');
	}

	/** The room being looked at. Only one link carries the mark at a time. */
	get activeRoomLink() {
		return this.page.locator('#room-nav-items .room-nav-link.active-nav-link');
	}

	roomLink(name) {
		return this.roomNavLinks.filter({ hasText: name });
	}

	/**
	 * What the room beside the nav is said to hold: how many seats are open,
	 * pending and confirmed. Drawn from the layout, so they say something even
	 * before anyone has booked.
	 */
	get roomCounts() {
		return this.page.locator('#room-nav-info-inner .info-item');
	}

	/** The registration's own name, which also opens the info dialog. */
	get header() {
		return this.page.locator('#main-header');
	}

	/* What the info dialog says the whole registration holds, across every room.
	   Counted from the layout and the bookings on it. */

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

	/* Controls beside the map. Each of these is rendered a second time in
	   #bottom-wrapper for narrow screens, and that copy is the one the plugin
	   hides from 1024px up - so at the width the suite runs at, these are the
	   ones on screen. */

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

	/** How many seats the registration thinks are in the booking. */
	get seatsInCart() {
		return this.page.locator('#seat-cart .seats-in-cart');
	}

	/* Zoom controls. The same controller is rendered either above the map or
	   below it with the cart, so where it is, is the whole setting. */

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

	/**
	 * Whether the browser really fetched the background image. The src alone
	 * only says where the page looked, not that anything was served from there.
	 */
	async backgroundImageLoaded() {
		return this.roomBackgroundImage.evaluate((img) => img.complete && img.naturalWidth > 0);
	}

	/* Seat dialog. Clicking a seat opens it, and what it offers is how the seat's
	   settings in the layout reach a visitor: a seat that can be booked gets an
	   add to booking button, one that cannot gets a notice instead. */

	get seatDialog() {
		return this.page.locator('#confirm-dialog-mob');
	}

	get addToBookingButton() {
		return this.seatDialog.locator('.add-to-cart');
	}

	get seatNotice() {
		return this.seatDialog.locator('.seat-taken-notify');
	}

	/** Shown only for a seat the layout gave hover text to. */
	get seatHoverText() {
		return this.seatDialog.locator('#confirm-dialog-mob-hover');
	}

	get seatPasswordInput() {
		return this.seatDialog.locator('#seat-password');
	}

	get seatDialogCloseButton() {
		return this.page.locator('#dialog-close-btn');
	}

	/* Cart and booking form. A seat that has been added to the booking goes into
	   the cart, the cart leads to the form, and the form is what most of the
	   booking flow settings decide the shape of. */

	get cartPopup() {
		return this.page.locator('#seat-cart-popup');
	}

	get cartItems() {
		return this.page.locator('#seat-cart-items .cart-item');
	}

	/**
	 * A seat in the cart, found by the number it is listed under.
	 */
	cartItem(number) {
		return this.cartItems.filter({
			has: this.page.locator('.cart-item-nr', { hasText: String(number) }),
		});
	}

	/**
	 * What the cart says about itself. With nothing in it this is where it says
	 * so, in place of the list.
	 */
	get cartInfo() {
		return this.page.locator('#seat-cart-info');
	}

	/** Goes on to the booking form. Taken away while the cart is empty. */
	get checkoutButton() {
		return this.page.locator('#checkout');
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

	/**
	 * A field of the booking form.
	 *
	 * @param {string} name FirstName, LastName or Email
	 */
	checkoutField(name) {
		return this.page.locator(`#checkout-input-area [data-field="${name}"]`);
	}

	/**
	 * The one address a booking of several seats is confirmed to, and the label
	 * naming it. Only asked for when the details are entered per seat.
	 */
	get primaryEmailLabel() {
		return this.page.locator('#checkout-input-area label').filter({
			has: this.page.locator('#prim-mail'),
		});
	}

	get customFooterText() {
		return this.page.locator('#checkout-input-area .custom-footer-text');
	}

	/**
	 * An extra question the registration asks, by the name it was created under.
	 * The control inside it is reached with checkoutField().
	 */
	customField(label) {
		return this.page.locator(`#checkout-input-area .custom-input[data-label="${label}"]`);
	}

	/** The extra questions of one booking, in the order they are asked. */
	customFieldLabels() {
		return this.checkoutItems.first().locator('.custom-input .l-text').allInnerTexts();
	}

	/**
	 * What the form says about a field it will not accept.
	 *
	 * Every field carries its own, kept out of sight until there is something to
	 * say, and it sits beside the field inside the same label - so it is found
	 * through the field rather than by position.
	 */
	fieldError(name) {
		return this.page
			.locator('#checkout-input-area label')
			.filter({ has: this.page.locator(`[data-field="${name}"]`) })
			.locator('.field-error');
	}

	/* What a booking that went through leaves on screen. Nothing else on the
	   page ever shows the new booking's address, so this dialog is the only way
	   a test can learn it. */

	get bookingConfirmed() {
		return this.page.locator('#bookings-confirmed');
	}

	get bookingConfirmedHeader() {
		return this.bookingConfirmed.locator('.booking-confirmed-header');
	}

	get bookingStatusLink() {
		return this.bookingConfirmed.locator('.booking-check-url');
	}

	/* Calendar mode. Only rendered when the registration runs on a calendar. */

	/** The day being looked at, written out for the site's language. */
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

	/**
	 * The room names in the order the page shows them, which is the room order
	 * set in the builder and not the order the rooms were created in.
	 */
	async roomNames() {
		await expect(this.roomNavLinks.first()).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

		return this.roomNavLinks.allInnerTexts();
	}

	/**
	 * Answer the password the registration is asking for. The form posts back to
	 * the registration itself, so a wrong password lands on the same form again.
	 */
	async submitPassword(password) {
		await this.passwordInput.fill(password);
		await this.passwordForm.locator('input[type="submit"]').click();
		await this.page.waitForLoadState('domcontentloaded');
	}

	/** Click a seat to see what the registration offers for it. */
	async openSeat(number) {
		await this.seat(number).click();
		await expect(this.seatDialog).toBeVisible();
	}

	async closeSeatDialog() {
		await this.seatDialogCloseButton.click();
		await expect(this.seatDialog).toBeHidden();
	}

	/**
	 * Look at another room. Everything under the nav is redrawn for it, so the
	 * link carrying the mark is what says the change has happened.
	 */
	async openRoom(name) {
		await this.roomLink(name).click();

		await expect(this.roomLink(name)).toHaveClass(/active-nav-link/);
	}

	/**
	 * Where the map has been put by the zoom and move buttons: how far it is
	 * scaled, and how far it has been pushed from its corner.
	 *
	 * Both buttons do one thing between them - write a transform onto the seats
	 * - so this is the whole of what either of them can be seen to do.
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

	/**
	 * Zoom the map and wait for it to have got there.
	 *
	 * @param {string} direction 'in' or 'out'
	 */
	async zoomMap(direction) {
		await this.zoomController.locator(`.zoom-action[data-zoom="${direction}"]`).click();
		await this.#waitForMapToSettle();
	}

	/**
	 * Pan the map and wait for it to have got there.
	 *
	 * Only the directions that move it back toward where it started do
	 * anything: a map already at its corner cannot go further up or left.
	 *
	 * @param {string} direction up, down, left or right
	 */
	async moveMap(direction) {
		await this.zoomController.locator(`.move-action[data-move="${direction}"]`).click();
		await this.#waitForMapToSettle();
	}

	/**
	 * Wait for the map to stop moving.
	 *
	 * Every zoom and every pan is animated over a few hundred milliseconds, so
	 * the transform is still on its way when the click returns and a reading
	 * taken then is of somewhere it was only passing through. Two readings the
	 * same is what says it has arrived.
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

	/**
	 * Take a seat back out of the booking.
	 *
	 * @param {number} number The seat's number, as the cart lists it
	 */
	async removeSeatFromBooking(number) {
		const item = this.cartItem(number);

		await item.locator('.remove-cart-item').click();
		await expect(item).toHaveCount(0);
	}

	/**
	 * Answer the password a seat is asking for.
	 *
	 * The answer is checked with the server, and a right one reopens the dialog
	 * for the same seat with the seat now on offer - so nothing is waited for
	 * here beyond the request being answered.
	 */
	async submitSeatPassword(password) {
		await this.seatPasswordInput.fill(password);
		await this.seatDialog.locator('#password-check').click();
	}

	/** What the dialog says about a password it did not accept. */
	get seatPasswordError() {
		return this.seatDialog.locator('#password-error');
	}

	/**
	 * Choose the day to book for.
	 *
	 * The picker is a modal put on the body rather than anything inside the
	 * page, and only its Apply button commits the choice - closing it any other
	 * way leaves the day alone. Picking one refetches the map for it, so the
	 * loading dialog going away is what says it is done.
	 *
	 * @param {string} date A yyyy-mm-dd day the registration is open on
	 */
	async pickCalendarDate(date) {
		await this.calendarDateSelection.click();

		const picker = this.page.locator('.pignose-calendar-wrapper');
		await expect(picker).toBeVisible();

		await picker.locator(`.pignose-calendar-unit[data-date="${date}"] a`).click();
		await picker.locator('.pignose-calendar-button-apply').click();

		/* The picker is put away rather than taken off the page. */
		await expect(picker).toBeHidden();
		await expect(this.page.locator('#calendar-date-change-loading')).toBeHidden();
	}

	/**
	 * Send the booking off.
	 *
	 * A booking the server refuses to take shows a native alert rather than
	 * anything on the page (registration.js:1679-1693), which would leave the
	 * test waiting on a dialog nobody answered - so one is accepted here, and
	 * whatever the page does next is left to the caller to assert.
	 */
	async submitBooking() {
		this.page.once('dialog', (dialog) => dialog.accept());

		await this.page.locator('#checkout-confirm-btn').click();
	}

	/**
	 * Read what the registration says about itself.
	 *
	 * The button pulses, on a three second loop that never ends
	 * (.big-display-btn in registration.scss), so it is never going to hold
	 * still long enough for a click to be allowed to wait for it to. It is
	 * checked to be on screen first and then clicked where it stands, which is
	 * the middle either way - the animation only scales it.
	 */
	async openInfoDialog() {
		await expect(this.infoButton).toBeVisible();
		await this.infoButton.click({ force: true });

		await expect(this.infoDialog).toBeVisible();
	}

	/**
	 * Put a seat in the booking, which is the step before the cart.
	 *
	 * Whether the cart then opens by itself is a setting, so nothing is waited
	 * for here beyond the seat dialog closing again.
	 */
	async addSeatToBooking(number) {
		await this.openSeat(number);
		await this.addToBookingButton.click();
		await expect(this.seatDialog).toBeHidden();
	}

	async openCart() {
		await this.selectionButton.click();
		await expect(this.cartPopup).toBeVisible();
	}

	/**
	 * Walk the whole way from an empty map to the booking form.
	 *
	 * The registration only lets a booking hold as many seats as its settings
	 * allow, so a caller asking for more than that has to have said so first.
	 *
	 * @param {number} count How many seats to book, numbered from 1
	 */
	async bookSeats(count) {
		for (let number = 1; number <= count; number += 1) {
			await this.addSeatToBooking(number);
		}

		await expect(this.seatsInCart).toHaveText(String(count));

		await this.openCart();
		await this.openCheckout();
	}

	/**
	 * Go from the cart to the booking form.
	 *
	 * The form is not in the page: it is drawn seat by seat when the dialog
	 * opens, so the first field arriving is what says it is ready to be read.
	 */
	async openCheckout() {
		await this.page.locator('#checkout').click();

		await expect(this.checkoutArea).toBeVisible();
		await expect(this.checkoutItems.first()).toBeVisible();
	}

	/**
	 * The whole page's HTML, for asserting that something never reached a
	 * visitor. The layout arrives as JSON in the markup, so a value that leaked
	 * would show up here even when no element renders it.
	 */
	async html() {
		return this.page.content();
	}
}

module.exports = { RegistrationPage };
