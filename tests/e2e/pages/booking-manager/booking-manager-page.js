const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { SEATREG_PAGES, openSeatRegScreen, expectOnSeatRegPage } = require('../../utils/navigation');
const {
	clickUntil,
	expectModalShown,
	expectModalHidden,
	pickDatepickerDay,
} = require('../../utils/interactions');
const { HomePage } = require('../home/home-page');

/**
 * The three lists a booking can be in, by the ending its panel's id gets. Every
 * id starts with the sha1 of the registration's name, which this side cannot
 * know, and none of the endings is hexadecimal.
 */
const STATUS_TABS = { pending: 'bron', approved: 'taken', deleted: 'deleted' };

/** As the checkbox says it. */
const BOOKING_ACTIONS = { delete: 'del', approve: 'confirm', unapprove: 'unapprove' };

/** As the radio posts them. */
const NEW_BOOKING_STATUS = { pending: '1', approved: '2' };

/**
 * Page object for the SeatReg Booking manager screen
 * (admin.php?page=seatreg-management&tab=<code>).
 *
 * Searching, sorting, a bulk action and a permanent delete all end the same way:
 * the server sends the whole list back as markup and the page throws away what
 * was on screen. Nothing may be held on to across one, the two booking modals
 * included, since they are rendered inside the part that is replaced. The tabs
 * are wired up again on the new markup and reopen the list named in the address,
 * so anything that acts on the list still has to say where to look afterwards.
 *
 * Adding a booking does not write it into the list - the page reloads itself two
 * seconds later instead - and it is the only thing here that can send mail, so
 * the confirmation is unticked before every submit.
 *
 * Almost nothing has an id worth using: rows are keyed by the booking rather than
 * the seat, bulk action buttons are divs switched off with a class, and the extra
 * questions in both modals are given the question's own label as an id.
 */
class BookingManagerPage {
	constructor(page) {
		this.page = page;
		this.homePage = new HomePage(page);
	}

	/* Screen shell */

	/**
	 * Everything the screen is drawn in, and replaced as. Scoped to the manager:
	 * Settings and Overview use the same wrapper class, and #seatreg-reg-code
	 * below is not unique across the plugin either.
	 */
	get panel() {
		return this.page.locator('#seatreg-booking-manager .seatreg-tabs-content');
	}

	get registrationName() {
		return this.panel.locator('.management-header .registration-name');
	}

	/** Only rendered while something is waiting to be approved. */
	get pendingNotice() {
		return this.panel.locator('.pending-bookings-count');
	}

	get registrationCode() {
		return this.panel.locator('#seatreg-reg-code');
	}

	registrationTab(code) {
		return this.page.locator(`.nav-tab-wrapper a.nav-tab[href*="tab=${code}"]`);
	}

	/** @param {string} type pdf, xlsx, text or csv */
	exportLink(type) {
		return this.panel.locator(`.file-type-link[data-file-type="${type}"]`);
	}

	get searchInput() {
		return this.panel.locator('.manager-search');
	}

	/** A column heading, which is also what reorders the list by it. */
	columnHeading(order) {
		return this.panel.locator(`.manager-box-link[data-order="${order}"]`);
	}

	get addBookingButton() {
		return this.panel.locator('.add-booking');
	}

	/* The three lists */

	statusTab(status) {
		return this.panel.locator(`ul.etabs li.tab a[href$="${STATUS_TABS[status]}"]`);
	}

	statusPanel(status) {
		return this.panel.locator(`div.tab_container[id$="${STATUS_TABS[status]}"]`);
	}

	/* Bookings. A booking of several seats is several rows carrying one booking
	   id, so a row is asked for by the booking and, when that is not enough, by
	   the seat as well. */

	/**
	 * @param {string} status A key of STATUS_TABS
	 * @param {number} options.seat The seat's number, for a booking of several
	 */
	bookingRow(status, bookingId, { seat } = {}) {
		const rows = this.statusPanel(status).locator(
			`.reg-seat-item[data-booking-id="${bookingId}"]`
		);

		if (seat === undefined) {
			return rows;
		}

		return rows.filter({
			has: this.page.locator('.seat-nr-box').filter({ hasText: new RegExp(`^${seat}$`) }),
		});
	}

	/** In the order the list has them, which is what sorting decides. */
	bookedNames(status) {
		return this.statusPanel(status).locator('.reg-seat-item .full-name').allInnerTexts();
	}

	bookedName(status, bookingId, options) {
		return this.bookingRow(status, bookingId, options).locator('.full-name');
	}

	bookingIdBox(status, bookingId, options) {
		return this.bookingRow(status, bookingId, options).locator('.booking-id-box');
	}

	editButton(status, bookingId, options) {
		return this.bookingRow(status, bookingId, options).locator('.edit-btn');
	}

	/* What a row is hiding, until openMoreInfo() asks for it. */

	moreInfo(status, bookingId, options) {
		return this.bookingRow(status, bookingId, options).locator('.more-info');
	}

	statusPageLink(status, bookingId, options) {
		return this.moreInfo(status, bookingId, options).locator(
			'a[href*="seatreg=booking-status"]'
		);
	}

	/** @param {string} label What the line is headed, e.g. 'Deletion reason' */
	moreInfoLine(status, bookingId, label, options) {
		return this.moreInfo(status, bookingId, options)
			.locator('div')
			.filter({ hasText: label })
			.first();
	}

	/* Choosing what to do with a booking, and doing it */

	bookingActionCheckbox(status, bookingId, action, options) {
		return this.bookingRow(status, bookingId, options).locator(
			`.bron-action[data-action="${BOOKING_ACTIONS[action]}"]`
		);
	}

	permanentDeleteCheckbox(bookingId, options) {
		return this.bookingRow('deleted', bookingId, options).locator('.permanent-delete-action');
	}

	/**
	 * A div switched off with a class rather than an attribute, so toBeDisabled()
	 * says nothing about it. Only rendered while its list has something in it.
	 */
	bulkActionControl(status) {
		return this.statusPanel(status).locator('.action-control');
	}

	get permanentDeleteControl() {
		return this.statusPanel('deleted').locator('.permanent-delete-control');
	}

	/* alertify's dialog, not the browser's, so page.on('dialog') never sees it.
	   One node is reused for every dialog the plugin puts up. */

	get confirmDialogMessage() {
		return this.page.locator('#alertify .alertify-message');
	}

	get confirmDialogOk() {
		return this.page.locator('#alertify-ok');
	}

	get confirmDialogCancel() {
		return this.page.locator('#alertify-cancel');
	}

	/* Add booking modal. Rendered inside the panel, and not rendered at all on a
	   registration with no layout - which is why every spec here starts from one
	   that has seats. */

	get addModal() {
		return this.panel.locator('#add-booking-modal');
	}

	/** The first seat is the one the rest are cloned from. */
	addModalSeat(index) {
		return this.addModal.locator('.modal-body-item').nth(index);
	}

	/** @param {string} name seat-id, room, first-name, last-name or email */
	addModalField(index, name) {
		return this.addModalSeat(index).locator(`[name="${name}[]"]`);
	}

	addModalFieldError(index, name) {
		return this.addModalSeat(index)
			.locator('.add-modal-input-wrap')
			.filter({ has: this.page.locator(`[name="${name}[]"]`) })
			.locator('.input-error');
	}

	/**
	 * The plugin gives each of these an id equal to the question's own label,
	 * spaces and all, and clones them onto every further seat, so no id picks out
	 * one of them.
	 */
	addModalCustomField(index, label) {
		return this.addModalSeat(index)
			.locator('.modal-custom')
			.filter({ has: this.page.getByText(label, { exact: true }) })
			.locator('.modal-custom-v');
	}

	/** Only shown once the booking holds more than one seat. */
	get addModalPrimaryEmail() {
		return this.addModal.locator('[name="multi-booking-primary-email"]');
	}

	addModalStatus(status) {
		return this.addModal.locator(
			`input[name="booking-status"][value="${NEW_BOOKING_STATUS[status]}"]`
		);
	}

	get addModalSendConfirmation() {
		return this.addModal.locator('[name="send-booking-confirmation"]');
	}

	get addModalAddSeat() {
		return this.addModal.locator('#add-modal-add-seat');
	}

	get addModalSubmit() {
		return this.addModal.locator('#add-booking-btn');
	}

	/* Seat ID lookup. The seats nothing is holding, drawn from the layout. */

	get seatIdModal() {
		return this.panel.locator('#seat-id-modal');
	}

	/** @param {number} seatNumber The number the layout shows on the seat */
	seatIdOption(seatNumber) {
		return this.seatIdModal.locator(
			`[data-action="select-id"][data-seat-number="${seatNumber}"]`
		);
	}

	seatIdSearchLink(index) {
		return this.addModalSeat(index).locator('.seat-id-search');
	}

	/* Edit booking modal. One modal for the whole screen, filled in from the row
	   whose pencil was clicked. */

	get editModal() {
		return this.panel.locator('#edit-modal');
	}

	get editSeatId() {
		return this.editModal.locator('#edit-seat');
	}

	get editFirstName() {
		return this.editModal.locator('#edit-fname');
	}

	get editBookerEmail() {
		return this.editModal.locator('#booker-email-change-field');
	}

	/** Only rendered for a booking of several seats. */
	get editSeatEmail() {
		return this.editModal.locator('#email-change-field');
	}

	/** @see addModalCustomField for why the label is what finds it */
	editCustomField(label) {
		return this.editModal
			.locator('.modal-custom')
			.filter({ has: this.page.getByText(label, { exact: true }) })
			.locator('.modal-custom-v');
	}

	get editSubmit() {
		return this.editModal.locator('#edit-update-btn');
	}

	/* Calendar mode. Only rendered when the registration runs on a calendar. */

	/** Written out for a locale rather than left as a date. */
	get calendarDateInput() {
		return this.panel.locator('#booking-manager-calendar-date');
	}

	/**
	 * The day the server drew this list for, as yyyy-mm-dd, and the one place it
	 * can be read: the field on screen is rewritten into a long English date on
	 * load, and the picker's altField names an id that does not exist
	 * (seatreg_admin.js:375), so nothing ever writes to this one.
	 */
	get calendarDateValue() {
		return this.panel.locator('#booking-manager-calendar-date-value');
	}

	/** Only true once a day has been picked. @see pickCalendarDate */
	calendarDateParam() {
		return new URL(this.page.url()).searchParams.get('calendar-date');
	}

	/* Actions */

	/** Lands on whichever registration the plugin picks, not a particular one. */
	async goto() {
		await openSeatRegScreen(this.page, 'Bookings');
	}

	async open(code) {
		await this.goto();
		await this.registrationTab(code).click();

		await expectOnSeatRegPage(this.page, SEATREG_PAGES.BOOKINGS, { tab: code });
		await expect(this.panel).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/** Through the Home card, whose link carries the code. */
	async openForRegistration(code) {
		await this.homePage.goto();
		await this.homePage.bookingsLink(code).click();

		await expectOnSeatRegPage(this.page, SEATREG_PAGES.BOOKINGS, { tab: code });
		await expect(this.panel).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/** A no-op for the list already open. */
	async openStatusTab(status) {
		await clickUntil(this.statusTab(status), this.statusPanel(status));
	}

	/** Clear the search by asking for nothing. */
	async search(term) {
		const found = this.#rerendered('seatreg_search_bookings');

		await this.searchInput.fill(term);
		await this.searchInput.press('Enter');

		await this.#awaitListSwap(found);
	}

	/** @param {string} order nr, room, name, date, id or payment-status */
	async sortBy(order) {
		const sorted = this.#rerendered('seatreg_get_booking_manager');

		await this.columnHeading(order).click();

		await this.#awaitListSwap(sorted);
	}

	/** Clicked once and only once: the handler slides the panel either way. */
	async openMoreInfo(status, bookingId, options) {
		await this.bookingRow(status, bookingId, options).locator('.show-more-info').click();

		await expect(this.moreInfo(status, bookingId, options)).toBeVisible();
	}

	/**
	 * Only the booking's first row is ticked. The plugin mirrors the choice onto
	 * its other rows, and a spec proving that needs them left alone here.
	 *
	 * @param {string} action A key of BOOKING_ACTIONS
	 */
	async selectBookingAction(status, bookingId, action) {
		const checkbox = this.bookingActionCheckbox(status, bookingId, action).first();

		await checkbox.check();
		await expect(checkbox).toBeChecked();
	}

	/**
	 * A click on the button while it is switched off is quietly dropped rather
	 * than refused, so it is checked to be live first - otherwise this would wait
	 * on a reply that is never sent.
	 */
	async applyBulkAction(status) {
		await expect(this.bulkActionControl(status)).not.toHaveClass(/is-disabled/);

		const applied = this.#rerendered('seatreg_confirm_del_bookings');

		await this.bulkActionControl(status).click();

		await this.#awaitListSwap(applied);
	}

	async applyBookingAction(status, bookingId, action) {
		await this.selectBookingAction(status, bookingId, action);
		await this.applyBulkAction(status);
	}

	/**
	 * The question and both answers are elements on the page rather than a dialog
	 * Playwright can answer. The question is handed back so a spec can say which
	 * one was asked.
	 *
	 * @param {boolean} options.confirm Whether to go through with it
	 * @return {Promise<string>} What it asked
	 */
	async permanentlyDelete(bookingId, { confirm = true } = {}) {
		await this.permanentDeleteCheckbox(bookingId).first().check();
		await expect(this.permanentDeleteControl).not.toHaveClass(/is-disabled/);

		await this.permanentDeleteControl.click();
		await expect(this.confirmDialogMessage).toBeVisible();

		const question = await this.confirmDialogMessage.innerText();

		if (!confirm) {
			await this.confirmDialogCancel.click();
			await expect(this.confirmDialogMessage).toBeHidden();

			return question;
		}

		const deleted = this.#rerendered('seatreg_permanently_delete_booking');

		await this.confirmDialogOk.click();

		await this.#awaitListSwap(deleted);

		return question;
	}

	/** Safe to retry: the plugin shows the modal rather than toggling it. */
	async openAddBookingModal() {
		await clickUntil(this.addBookingButton, this.addModal);
		await expectModalShown(this.addModal);
	}

	/**
	 * The fill order matters. The seat id field is what makes the hidden price
	 * field the server insists on, and the handler writes that field into the
	 * booking's first seat whatever seat was typed in (seatreg_admin.js:1383) -
	 * but Add seat clones the first seat whole. So the first seat is filled
	 * completely, its id last, and the rest are cloned off it and written over.
	 *
	 * @see addBooking for the shape of a seat
	 */
	async fillAddBooking({ seats, seatIds, status = 'pending', bookerEmail, customFields = {} }) {
		for (const [index, seat] of seats.entries()) {
			if (index > 0) {
				await this.addModalAddSeat.click();
				await expect(this.addModalSeat(index)).toBeVisible();
			}

			await this.addModalField(index, 'first-name').fill(seat.firstName);
			await this.addModalField(index, 'last-name').fill(seat.lastName);
			await this.addModalField(index, 'email').fill(seat.email);

			for (const [label, value] of Object.entries(customFields)) {
				await this.addModalCustomField(index, label).fill(value);
			}

			await this.addModalField(index, 'seat-id').fill(seatIds[index]);
		}

		await this.addModalStatus(status).check();

		/* The one thing on this screen that would try to send mail. */
		await this.addModalSendConfirmation.uncheck();

		if (seats.length > 1) {
			await this.addModalPrimaryEmail.fill(bookerEmail);
		}
	}

	/**
	 * The reply is returned rather than judged here: a booking the server turns
	 * down leaves the modal open with the reason beside the field it belongs to.
	 *
	 * @return {Promise<Object>} The parsed reply
	 */
	async submitAddBooking() {
		const answered = this.#rerendered('seatreg_add_booking_with_manager');

		await this.addModalSubmit.click();

		return (await answered).json();
	}

	/**
	 * Put a booking on the screen. One request and one reload whatever it holds,
	 * and the only way to put one straight into the Approved list.
	 *
	 * The reply is looked at before anything is waited for, because a booking the
	 * server refuses never reloads the page.
	 *
	 * @param {Array<{seat: number, firstName: string, lastName: string, email: string}>} booking.seats
	 * @param {string} [booking.status] 'pending' or 'approved'
	 * @param {string} [booking.bookerEmail] Needed for more than one seat; the
	 *                                       single seat's own address otherwise
	 * @param {Object<string, string>} [booking.customFields] Value by question label
	 * @return {Promise<{bookingId: string, seatIds: string[]}>}
	 */
	async addBooking({ seats, status = 'pending', bookerEmail, customFields = {} }) {
		const booker = bookerEmail ?? seats[0].email;
		const seatIds = [];

		for (const { seat } of seats) {
			seatIds.push(await this.seatIdFor(seat));
		}

		await this.openAddBookingModal();
		await this.fillAddBooking({ seats, seatIds, status, bookerEmail: booker, customFields });

		const reloaded = this.#reloaded();
		const answer = await this.submitAddBooking();

		if (answer.success !== true) {
			throw new Error(
				`The booking manager refused the booking: ${JSON.stringify(answer.data)}`
			);
		}

		await reloaded;
		await this.page.waitForLoadState('domcontentloaded');
		await expect(this.panel).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

		await this.openStatusTab(status);

		/* The id is made on the server, so the reloaded list is the first place it
		   can be read. The booker's address is what finds the row: it is on every
		   row of the booking and nothing else in the registration shares it. */
		const seeded = this.statusPanel(status)
			.locator(`.reg-seat-item[data-booker-email="${booker}"]`)
			.first();

		await expect(seeded).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

		return { bookingId: await seeded.getAttribute('data-booking-id'), seatIds };
	}

	/**
	 * Read off the lookup's markup rather than picked from it, which keeps seeding
	 * clear of its buttons. @see lookUpSeatId
	 */
	async seatIdFor(seatNumber) {
		return this.seatIdOption(seatNumber).getAttribute('data-seat-id');
	}

	/**
	 * Only works on a screen that has just loaded: the lookup's buttons are bound
	 * to the elements that were there at the time rather than delegated
	 * (seatreg_admin.js:1372), so any re-render leaves them dead.
	 *
	 * The lookup closes on top of the add modal, which still owns a backdrop, so
	 * expectModalHidden would fail here for a reason of its own.
	 */
	async lookUpSeatId(index, seatNumber) {
		await this.seatIdSearchLink(index).click();
		await expectModalShown(this.seatIdModal);

		await this.seatIdOption(seatNumber).click();

		await expect(this.seatIdModal).toBeHidden();
		await expect(this.addModalField(index, 'seat-id')).not.toHaveValue('');
	}

	/** The Deleted list carries no pencil, so there is nothing to open there. */
	async openEditModal(status, bookingId, options) {
		await clickUntil(this.editButton(status, bookingId, options), this.editModal);
		await expectModalShown(this.editModal);
	}

	/** @return {Promise<Object>} The parsed reply. @see submitAddBooking */
	async submitEditModal() {
		const answered = this.#rerendered('seatreg_edit_booking');

		await this.editSubmit.click();

		return (await answered).json();
	}

	/** The plugin leaves the modal open over the row it has just written into. */
	async saveEditModal() {
		const answer = await this.submitEditModal();

		await this.closeModal(this.editModal);

		return answer;
	}

	async closeModal(modal) {
		await modal.locator('.modal-footer [data-dismiss="modal"]').click();
		await expectModalHidden(modal);
	}

	/**
	 * Also what puts the address right: until a day has been picked the screen has
	 * written a date into it that nothing can be filtered by, so a calendar
	 * registration is asked for a day before anything is seeded on it.
	 *
	 * @param {Date} date
	 */
	async pickCalendarDate(date) {
		await this.calendarDateInput.click();

		/* jQuery UI keeps one popup for every datepicker on the page. */
		const picker = this.page.locator('#ui-datepicker-div');
		await expect(picker).toBeVisible();

		const reloaded = this.#reloaded();

		await pickDatepickerDay(picker, date);
		await reloaded;

		await this.page.waitForLoadState('domcontentloaded');
		await expect(this.panel).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	async reload() {
		await this.page.reload();
		await expect(this.panel).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/** Matched on the action in the body: all of these go to the same endpoint. */
	#rerendered(action) {
		return this.page.waitForResponse(
			(response) =>
				response.url().includes('admin-ajax.php') &&
				(response.request().postData() ?? '').includes(action),
			{ timeout: TIMEOUTS.NAVIGATION }
		);
	}

	/**
	 * The page drops a loading image into the wrapper before it asks and puts the
	 * new list in by emptying that wrapper, so the image having gone is the swap
	 * having happened. Only ever waited for with the reply already in hand: on its
	 * own it would wait for ever, because a failed request leaves it there.
	 */
	async #awaitListSwap(fetched) {
		await fetched;

		await expect(this.panel.locator('.ajax_loader')).toHaveCount(0, {
			timeout: TIMEOUTS.NAVIGATION,
		});
	}

	#reloaded() {
		return this.page.waitForResponse(
			(response) =>
				response.request().method() === 'GET' &&
				response.url().includes(`page=${SEATREG_PAGES.BOOKINGS}`),
			{ timeout: TIMEOUTS.NAVIGATION }
		);
	}
}

module.exports = { BookingManagerPage, STATUS_TABS };
