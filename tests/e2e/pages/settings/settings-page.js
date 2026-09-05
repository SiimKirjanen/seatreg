const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('../../utils/timeouts');
const { SEATREG_PAGES, openSeatRegScreen, expectOnSeatRegPage } = require('../../utils/navigation');
const { clickUntil, pickDatepickerDay } = require('../../utils/interactions');
const { visitorContext } = require('../../utils/auth');
const { uniqueBookerEmail } = require('../../utils/mail');
const { imageUpload, uploadThroughMediaModal } = require('../../utils/media');
const { HomePage } = require('../home/home-page');
const { LayoutBuilderPage } = require('../layout-builder/layout-builder-page');
const { RegistrationPage } = require('../registration/registration-page');

/* Both logo pickers give their media modal the same button. */
const LOGO_CONFIRM_LABEL = 'Use as logo';

const ROOM_NAME = 'Settings room';

/* Whatever allowPaidBookings() is not told to use. */
const PAID_BOOKING_PAYMENT = { title: 'Bank transfer', description: 'Pay to the account on the invoice' };

/* Whoever a test that only needs a booking to exist does not name. */
const BOOKER = { firstName: 'Riina', lastName: 'Tamm' };

/* Looked up by name rather than by selector so a spec never has to know which
   section a setting sits in. */
const FIELDS = {
	registrationName: { tab: 'general', selector: '#registration-name', kind: 'text' },
	registrationStatus: { tab: 'general', selector: '#registration-status', kind: 'checkbox' },
	closeReason: { tab: 'general', selector: '#registration-close-reason', kind: 'text' },
	registrationPassword: { tab: 'general', selector: '#registration-password', kind: 'text' },
	requireWpLogin: { tab: 'general', selector: '#require-wp-login', kind: 'checkbox' },
	maxSeats: { tab: 'general', selector: '#registration-max-seats', kind: 'text' },
	usingSeats: { tab: 'general', selector: '#using-seats', kind: 'checkbox' },
	roomNounSingular: { tab: 'general', selector: '#room-noun-singular', kind: 'text' },
	roomNounPlural: { tab: 'general', selector: '#room-noun-plural', kind: 'text' },
	wpUserBookingLimit: { tab: 'general', selector: '#wp-user-booking-limit', kind: 'text' },
	wpUserSeatLimit: { tab: 'general', selector: '#wp-user-bookings-seat-limit', kind: 'text' },
	bookingEmailLimit: { tab: 'general', selector: '#bookings-email-limit', kind: 'text' },

	usePending: { tab: 'booking-flow', selector: '#use-pending', kind: 'checkbox' },
	pendingExpiration: { tab: 'booking-flow', selector: '#pending-expiration', kind: 'text' },
	redirectToStatusPage: {
		tab: 'booking-flow',
		selector: '#booking-redirect-status-page',
		kind: 'checkbox',
	},

	usingCalendar: { tab: 'scheduling', selector: '#using-calendar', kind: 'checkbox' },
	startTime: { tab: 'scheduling', selector: '#registration-start-time', kind: 'time' },
	endTime: { tab: 'scheduling', selector: '#registration-end-time', kind: 'time' },

	infoText: { tab: 'booking-flow', selector: '#registration-info-text', kind: 'text' },
	showInfoButton: { tab: 'booking-flow', selector: '#show-info-button', kind: 'checkbox' },
	pdfLogoPosition: { tab: 'booking-flow', selector: '#booking-pdf-logo-position', kind: 'select' },
	showPendingBookingPdf: {
		tab: 'booking-flow',
		selector: '#show-pending-booking-pdf',
		kind: 'checkbox',
	},
	showApprovedBookingPdf: {
		tab: 'booking-flow',
		selector: '#show-approved-booking-pdf',
		kind: 'checkbox',
	},
	onePersonCheckout: { tab: 'booking-flow', selector: '#one-person-checkout', kind: 'checkbox' },
	automaticBookingConfirmDialog: {
		tab: 'booking-flow',
		selector: '#automatic-booking-confirm-dialog',
		kind: 'checkbox',
	},
	requireName: { tab: 'booking-flow', selector: '#require-name', kind: 'checkbox' },
	gmailRequired: { tab: 'booking-flow', selector: '#gmail-required', kind: 'checkbox' },
	zoomOnTop: { tab: 'booking-flow', selector: '#zoom-on-top', kind: 'checkbox' },
	seatSelectionBtnText: {
		tab: 'booking-flow',
		selector: '#seat-selection-btn-text',
		kind: 'text',
	},
	customFooterText: {
		tab: 'booking-flow',
		selector: '#customFooterTextEditor',
		kind: 'richText',
	},

	emailFrom: { tab: 'emails', selector: '#email-from', kind: 'text' },
	approvedEmailTemplate: { tab: 'emails', selector: '#approved-booking-email-template', kind: 'text' },
	emailConfirm: { tab: 'emails', selector: '#email-confirm', kind: 'checkbox' },
	bookerPendingNotification: {
		tab: 'emails',
		selector: '#booker-pending-booking-notification',
		kind: 'checkbox',
	},
	approvedBookingEmail: { tab: 'emails', selector: '#approved-booking-email', kind: 'checkbox' },
	adminBookingNotification: { tab: 'emails', selector: '#booking-notification', kind: 'checkbox' },
	customizeEmailColors: { tab: 'emails', selector: '#customize-email-colors', kind: 'checkbox' },
	emailBackgroundColor: { tab: 'emails', selector: '#email-background-color', kind: 'text' },
	emailHeadingColor: { tab: 'emails', selector: '#email-heading-color', kind: 'text' },
	emailTextColor: { tab: 'emails', selector: '#email-text-color', kind: 'text' },
	emailLogoPosition: { tab: 'emails', selector: '#email-logo-position', kind: 'select' },

	/* The PayPal toggles are id paypal-rest for name paypal-rest-payments, and id
	   paypal for name paypal-payments. */
	currencyCode: { tab: 'payments', selector: '#paypal-currency-code', kind: 'text' },
	stripeEnabled: { tab: 'payments', selector: '#stripe', kind: 'checkbox' },
	stripeApiKey: { tab: 'payments', selector: '#stripe-api-key', kind: 'text' },
	paypalRestEnabled: { tab: 'payments', selector: '#paypal-rest', kind: 'checkbox' },
	paypalClientId: { tab: 'payments', selector: '#paypal-client-id', kind: 'text' },
	paypalClientSecret: { tab: 'payments', selector: '#paypal-client-secret', kind: 'text' },
	paypalLegacyEnabled: { tab: 'payments', selector: '#paypal', kind: 'checkbox' },
	paypalBusinessEmail: { tab: 'payments', selector: '#paypal-business-email', kind: 'text' },
	paypalButtonId: { tab: 'payments', selector: '#paypal-button-id', kind: 'text' },
	/* The legacy custom payment is a single payment held in the settings
	   themselves, unlike the list the custom payment builder keeps. */
	customPaymentEnabled: { tab: 'payments', selector: '#custom-payment', kind: 'checkbox' },
	legacyCustomPaymentTitle: { tab: 'payments', selector: '#custom-payment-title', kind: 'text' },
	legacyCustomPaymentDescription: {
		tab: 'payments',
		selector: '#custom-payment-description',
		kind: 'text',
	},
	paymentInstructions: { tab: 'payments', selector: '#payment-instructions', kind: 'text' },
	enableCoupons: { tab: 'payments', selector: '#enable-coupons', kind: 'checkbox' },

	customizePageColors: { tab: 'pages', selector: '#customize-page-colors', kind: 'checkbox' },
	pageBackgroundColor: { tab: 'pages', selector: '#page-background-color', kind: 'text' },
	pageHeadingColor: { tab: 'pages', selector: '#page-heading-color', kind: 'text' },
	pageTextColor: { tab: 'pages', selector: '#page-text-color', kind: 'text' },
	bookingNotFoundText: { tab: 'pages', selector: '#bookingNotFoundTextEditor', kind: 'richText' },
	bookingStatusStyles: {
		tab: 'pages',
		selector: 'textarea[name="booking-status-custom-styles"]',
		kind: 'text',
	},
	bookingConfirmStyles: {
		tab: 'pages',
		selector: 'textarea[name="booking-confirm-custom-styles"]',
		kind: 'text',
	},

	customStyles: { tab: 'advanced', selector: '#custom-styles', kind: 'text' },
	publicApi: { tab: 'advanced', selector: '#public-api', kind: 'checkbox' },
};

/** By the caption the builder lists them under. */
const CUSTOM_FIELD_TYPES = {
	text: 'Text',
	checkbox: 'Checkbox',
	select: 'Select',
};

/**
 * Page object for the SeatReg Settings screen
 * (admin.php?page=seatreg-options&tab=<code>).
 *
 * The screen is one form split into section tab panels. A hidden panel keeps its
 * fields in the DOM, so they can be read from anywhere but only filled once their
 * section is open - which is what set() does. Saving is a plain form post that
 * redirects back here leaving no notice, so save() waits for the reloaded screen.
 *
 * Custom fields, custom payments and coupons are builders rather than settings:
 * each is drawn into a list of its own, and only the save button's click turns
 * that list into the value that gets posted.
 */
class SettingsPage {
	constructor(page) {
		this.page = page;
		this.homePage = new HomePage(page);
	}

	/* Screen shell */

	get heading() {
		return this.page.locator('h4.settings-heading');
	}

	get form() {
		return this.page.locator('#seatreg-settings-form');
	}

	get saveButton() {
		return this.page.locator('#seatreg-settings-submit');
	}

	/** alertify hides its toasts on a timer, so the newest is the one just raised. */
	get errorToast() {
		return this.page.locator('.alertify-log-error').last();
	}

	registrationTab(code) {
		return this.page.locator(`.nav-tab-wrapper a.nav-tab[href*="tab=${code}"]`);
	}

	/* Booking flow summary. The plugin's own reading of the settings, above the
	   tab bar, so it belongs to no section. */

	get bookingFlowSummary() {
		return this.page.locator('#booking-flow-summary');
	}

	get bookingFlowSummaryToggle() {
		return this.form.locator('summary.booking-flow-details__summary');
	}

	/** @param {string} title e.g. 'Making a booking' */
	summaryGroup(title) {
		return this.bookingFlowSummary
			.locator('.flow-group')
			.filter({ has: this.page.getByText(title, { exact: true }) });
	}

	/** @param {string} target The setting's selector, as the link names it */
	summaryJumpLink(target) {
		return this.bookingFlowSummary.locator(`.flow-jump[data-target="${target}"]`);
	}

	/* Sections */

	sectionTab(tab) {
		return this.form.locator(`.settings-tab[data-tab="${tab}"]`);
	}

	sectionPanel(tab) {
		return this.form.locator(`.settings-tab-panel[data-tab-panel="${tab}"]`);
	}

	get activeSectionPanel() {
		return this.form.locator('.settings-tab-panel--active');
	}

	/* Settings */

	/** @param {string} name A key of FIELDS */
	field(name) {
		return this.page.locator(this.#fieldConfig(name).selector);
	}

	/* Booking flow */

	/**
	 * Carries no id of its own: what it posts is the payment status it names, and
	 * the plugin reads the box being there at all as the answer.
	 */
	get pendingExpirationProcessing() {
		return this.form.locator(
			'input[name="pending-expiration-payment-statuses[]"][value="processing"]'
		);
	}

	/** Only rendered while WP-Cron looks like it is not running. */
	get cronWarning() {
		return this.sectionPanel('booking-flow').locator('.alert-warning');
	}

	/* Scheduling. Each registration date is a pair: a dd.mm.yyyy display input
	   that has no name and is never posted, and the hidden field holding the unix
	   milliseconds that are. Only the datepicker writes the hidden one. */

	/** @param {string} which 'start' or 'end' */
	registrationDateInput(which) {
		return this.page.locator(`#registration-${which}-timestamp`);
	}

	registrationDateValue(which) {
		return this.page.locator(`#${which}-timestamp`);
	}

	get calendarDatesGroup() {
		return this.form.locator('.form-group').filter({ has: this.page.locator('#calendar-dates') });
	}

	/** Part of the form, unlike the registration dates' popup picker. */
	get calendarDatesPicker() {
		return this.page.locator('#calendar-dates-picker');
	}

	get calendarDates() {
		return this.page.locator('#calendar-dates');
	}

	/** @param {string} date A yyyy-mm-dd date, as the picker stores it */
	calendarDateChip(date) {
		return this.page
			.locator('#calendar-dates-list .calendar-date-chip')
			.filter({ has: this.page.locator(`[data-date="${date}"]`) });
	}

	/* Pages */

	get pageLogo() {
		return this.page.locator('#page-logo');
	}

	get pageLogoPreview() {
		return this.page.locator('#page-logo-preview');
	}

	get pageLogoSelectButton() {
		return this.page.locator('#page-logo-select');
	}

	get pageLogoRemoveButton() {
		return this.page.locator('#page-logo-remove');
	}

	/* Emails. Picked the same way the page logo is; what a logo does once it is
	   picked is covered from there. */

	get emailLogo() {
		return this.page.locator('#email-logo');
	}

	get emailLogoSelectButton() {
		return this.page.locator('#email-logo-select');
	}

	/* Payments */

	get customPaymentList() {
		return this.form.locator('#custom-payments .existing-custom-payments');
	}

	/** The row carries the payment's id, so it is found by the title its input holds. */
	customPayment(title) {
		return this.customPaymentList
			.locator('.custom-payment')
			.filter({ has: this.page.locator(`[data-id="custom-payment-title"][value="${title}"]`) });
	}

	/* An icon is a file input of the plugin's own rather than a media modal, and
	   it uploads the moment it is given a file - so the row holds an image before
	   anything is saved. */

	customPaymentIconInput(title) {
		return this.customPayment(title).locator('[data-action="custom-payment-icon-upload"]');
	}

	customPaymentIcon(title) {
		return this.customPayment(title).locator('.current-custom-payment-icon__img');
	}

	get newCustomPaymentTitle() {
		return this.form.locator('#new-custom-payment [data-id="new-custom-payment-title"]');
	}

	get newCustomPaymentDescription() {
		return this.form.locator('#new-custom-payment [data-id="new-custom-payment-description"]');
	}

	get couponList() {
		return this.form.locator('.existing-coupons');
	}

	coupon(code) {
		return this.couponList.locator('.coupon-box').filter({
			has: this.page.locator('[data-target="coupon-code"]', { hasText: code }),
		});
	}

	couponDiscount(code) {
		return this.coupon(code).locator('[data-target="discount-value"]');
	}

	get newCouponCode() {
		return this.form.locator('#new-coupon-code');
	}

	get newCouponDiscount() {
		return this.form.locator('#new-coupon-discount');
	}

	/* Advanced */

	get customFieldList() {
		return this.form.locator('.existing-custom-fields');
	}

	customField(label) {
		return this.customFieldList.locator(`.custom-container[data-label="${label}"]`);
	}

	/** In the order they are listed, which is the order they reach a booker in. */
	customFieldLabels() {
		return this.customFieldList.locator('.custom-container .l-text').allInnerTexts();
	}

	get newCustomFieldLabel() {
		return this.form.locator('.cust-field-create .cust-input-label');
	}

	get newCustomFieldType() {
		return this.form.locator('.cust-field-create .custom-field-select');
	}

	get newCustomFieldOptionName() {
		return this.form.locator('.cust-field-create .option-name');
	}

	get newCustomFieldOptions() {
		return this.form.locator('.cust-field-create .existing-options .option-value');
	}

	/**
	 * Only a saved select field has one: the pencil that opens it is rendered by
	 * the server. The dialog is put on the body rather than in the form.
	 */
	get editOptionsDialog() {
		return this.page.locator('dialog');
	}

	get editOptionsError() {
		return this.editOptionsDialog.locator('#error-message');
	}

	get editOptionInputs() {
		return this.editOptionsDialog.locator('#options-list input');
	}

	editOptionValues() {
		return this.editOptionInputs.evaluateAll((inputs) => inputs.map((input) => input.value));
	}

	/* API tokens. Created and deleted at once over AJAX, not on save. */

	get apiTokens() {
		return this.page.locator('#public-api-tokens .token-box');
	}

	get createApiTokenButton() {
		return this.page.locator('#create-api-token');
	}

	/* Actions */

	/** Lands on whichever registration the plugin picks, not a particular one. */
	async goto() {
		await openSeatRegScreen(this.page, 'Settings');
	}

	async open(code) {
		await this.goto();
		await this.registrationTab(code).click();
		await expectOnSeatRegPage(this.page, SEATREG_PAGES.SETTINGS, { tab: code });
	}

	/**
	 * Create a registration on Home and open its settings. Goes through the Home
	 * card because the menu opens the oldest registration rather than this one.
	 *
	 * @return {Promise<string>} The registration's code
	 */
	async openForNewRegistration(name) {
		await this.homePage.goto();

		const code = await this.homePage.createRegistration(name);

		await this.homePage.settingsLink(code).click();
		await expectOnSeatRegPage(this.page, SEATREG_PAGES.SETTINGS, { tab: code });
		await expect(this.form).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });

		return code;
	}

	/**
	 * Create a registration with something to book, and open its settings.
	 *
	 * @return {Promise<string>} The registration's code
	 */
	async openForNewRegistrationWithSeats(name, seatCount) {
		const builder = new LayoutBuilderPage(this.page);

		const code = await builder.openForNewRegistration(name);

		await builder.nameFirstRoom(ROOM_NAME);
		await builder.placeSeats(seatCount);
		await builder.save();

		await this.open(code);

		return code;
	}

	/**
	 * Reopen the registration's layout, which is opened off its Home card.
	 *
	 * @return {Promise<LayoutBuilderPage>}
	 */
	async openLayout(code) {
		const builder = new LayoutBuilderPage(this.page);

		await this.homePage.goto();
		await builder.open(code);

		return builder;
	}

	/** Prices belong to the seat, so this goes through the layout. Only means
	    anything once allowPaidBookings() has given the registration a way to be paid. */
	async priceSeats(code, price, seatCount) {
		const builder = await this.openLayout(code);

		await builder.lassoSelectSeats(1, seatCount);
		await builder.setSeatPrices(price);
		await builder.save();
	}

	/**
	 * Let one booking hold more than a seat, and save whatever else the caller has
	 * already set.
	 *
	 * A booking added in the manager is held to this limit the same as one a
	 * visitor makes, and it is one seat until the registration says otherwise.
	 */
	async allowSeatsPerBooking(count) {
		await this.set('maxSeats', String(count));
		await this.save();
	}

	/**
	 * Put the registration in a state where a booking can actually be made.
	 *
	 * Email verification is the blocker: with it on the booking is written at a
	 * status filtered out of everything until the booker follows a link out of their
	 * mail. The rest of the emails are off to keep unrelated tests off the mail log.
	 *
	 * @param {boolean} options.approved Whether bookings skip the pending state
	 */
	async allowBookings({ approved = false } = {}) {
		await this.set('emailConfirm', false);
		await this.set('adminBookingNotification', false);

		if (approved) {
			await this.set('usePending', false);
			await this.set('approvedBookingEmail', false);
		} else {
			await this.set('bookerPendingNotification', false);
		}

		await this.save();
	}

	/**
	 * The same, for a registration whose seats are going to cost something. A custom
	 * payment counts as payments being on and needs an account nowhere. Save it
	 * before opening the builder, which only offers to price a seat once one is on.
	 */
	async allowPaidBookings({ approved = false, currency = 'EUR', payment = PAID_BOOKING_PAYMENT } = {}) {
		await this.addCustomPayment(payment);
		await this.set('currencyCode', currency);

		await this.allowBookings({ approved });

		return payment;
	}

	async openBookingFlowSummary() {
		await clickUntil(this.bookingFlowSummaryToggle, this.bookingFlowSummary);
	}

	/**
	 * Show a section of the form. A no-op for the section already on screen.
	 *
	 * @param {string} tab One of the data-tab values, e.g. 'payments'
	 */
	async openSection(tab) {
		await clickUntil(this.sectionTab(tab), this.sectionPanel(tab));
	}

	/** @param {string} name A key of FIELDS */
	async set(name, value) {
		const config = this.#fieldConfig(name);

		await this.openSection(config.tab);

		const field = this.page.locator(config.selector);

		if (config.kind === 'checkbox') {
			await field.setChecked(value);

			return;
		}

		if (config.kind === 'select') {
			await field.selectOption(value);

			return;
		}

		if (config.kind === 'time') {
			/* The clock widget opens a popup on focus, and closing it is when it
			   decides what the field is worth - a value it never saw typed that
			   reads as a round hour past midnight is thrown away. The assertion
			   says so plainly rather than leaving a later one to fail. */
			await field.fill(value);
			await field.blur();

			await expect(field).toHaveValue(value);

			return;
		}

		if (config.kind === 'richText') {
			/* TinyMCE's Visual mode hides the textarea behind an iframe; its Text
			   mode hands it back and is what makes it the thing that gets posted.
			   The editor opens in Visual mode once its section is shown, so a tab
			   clicked before that is undone again. */
			await clickUntil(this.page.locator(`${config.selector}-html`), field);

			await field.fill(value);

			return;
		}

		await field.fill(value);
	}

	/**
	 * The field on screen carries no name; what gets posted is the timestamp the
	 * picker writes into the hidden field beside it.
	 *
	 * @param {string} which 'start' or 'end'
	 */
	async pickRegistrationDate(which, date) {
		await this.openSection('scheduling');
		await this.registrationDateInput(which).click();

		/* jQuery UI keeps one popup for every datepicker on the page. */
		const picker = this.page.locator('#ui-datepicker-div');
		await expect(picker).toBeVisible();

		await pickDatepickerDay(picker, date);
		await expect(picker).toBeHidden();
	}

	/** @param {Date[]} dates */
	async pickCalendarDates(dates) {
		await this.openSection('scheduling');

		for (const date of dates) {
			await pickDatepickerDay(this.calendarDatesPicker, date);
		}
	}

	/**
	 * An image is uploaded rather than picked out of the library, so the test
	 * knows which one it got.
	 *
	 * @return {Promise<string>} The attachment's title
	 */
	async #selectLogo(tab, button, field) {
		await this.openSection(tab);
		await button.click();

		const title = await uploadThroughMediaModal(this.page, LOGO_CONFIRM_LABEL);

		await expect(field).not.toHaveValue('');

		return title;
	}

	/** @see #selectLogo */
	async selectPageLogo() {
		return this.#selectLogo('pages', this.pageLogoSelectButton, this.pageLogo);
	}

	/** @see #selectLogo */
	async selectEmailLogo() {
		return this.#selectLogo('emails', this.emailLogoSelectButton, this.emailLogo);
	}

	/**
	 * Give a custom payment the icon a booker is shown beside its name. Only works
	 * on a payment the builder has already drawn a row for.
	 */
	async uploadCustomPaymentIcon(title) {
		await this.openSection('payments');

		await this.customPaymentIconInput(title).setInputFiles(imageUpload('payment-icon'));

		await expect(this.customPaymentIcon(title)).toBeVisible();
	}

	/**
	 * @param {string} field.label     The name to ask for it under
	 * @param {string} field.type      A key of CUSTOM_FIELD_TYPES
	 * @param {string[]} field.options The choices a select field offers
	 */
	async #fillNewCustomField({ label, type, options = [] }) {
		await this.openSection('advanced');

		await this.newCustomFieldLabel.fill(label);
		await this.newCustomFieldType.selectOption({ label: CUSTOM_FIELD_TYPES[type] });

		for (const option of options) {
			await this.newCustomFieldOptionName.fill(option);
			await this.form.locator('.cust-field-create .add-select-option').click();
		}

		if (options.length) {
			await expect(this.newCustomFieldOptions).toHaveCount(options.length);
		}
	}

	/** @see #fillNewCustomField for the shape of the field */
	async addCustomField(field) {
		await this.#fillNewCustomField(field);
		await this.form.locator('.cust-field-create .apply-custom-field').click();

		await expect(this.customField(field.label)).toBeVisible();
	}

	/**
	 * The field never leaves the box it is built in, so the toast is the only sign
	 * anything happened and the list not growing is the other half of it.
	 */
	async addCustomFieldExpectingError(field, message) {
		const listed = await this.customFieldList.locator('.custom-container').count();

		await this.#fillNewCustomField(field);
		await this.form.locator('.cust-field-create .apply-custom-field').click();

		await expect(this.errorToast).toHaveText(message);
		await expect(this.customFieldList.locator('.custom-container')).toHaveCount(listed);
	}

	async addCustomPayment({ title, description }) {
		await this.openSection('payments');

		await this.newCustomPaymentTitle.fill(title);
		await this.newCustomPaymentDescription.fill(description);
		await this.form.locator('#create-custom-payment').click();

		await expect(this.customPayment(title)).toBeVisible();
	}

	async addCustomPaymentExpectingError({ title, description }, message) {
		const listed = await this.customPaymentList.locator('.custom-payment').count();

		await this.openSection('payments');

		await this.newCustomPaymentTitle.fill(title);
		await this.newCustomPaymentDescription.fill(description);
		await this.form.locator('#create-custom-payment').click();

		await expect(this.errorToast).toHaveText(message);
		await expect(this.customPaymentList.locator('.custom-payment')).toHaveCount(listed);
	}

	/**
	 * Only works on a saved payment: the Remove button the builder draws carries
	 * no action for the handler to match on (seatreg_admin.js:2097 against :2103).
	 */
	async removeCustomPayment(title) {
		await this.openSection('payments');

		await this.customPayment(title).locator('[data-action="remove-custom-payment"]').click();

		await expect(this.customPayment(title)).toHaveCount(0);
	}

	async addCoupon({ code, discount }) {
		await this.openSection('payments');

		await this.newCouponCode.fill(code);
		await this.newCouponDiscount.fill(discount);
		await this.form.locator('.coupon-create [data-action="add-coupon"]').click();

		await expect(this.coupon(code)).toBeVisible();
	}

	async addCouponExpectingError({ code, discount }, message) {
		const listed = await this.couponList.locator('.coupon-box').count();

		await this.openSection('payments');

		await this.newCouponCode.fill(code);
		await this.newCouponDiscount.fill(discount);
		await this.form.locator('.coupon-create [data-action="add-coupon"]').click();

		await expect(this.errorToast).toHaveText(message);
		await expect(this.couponList.locator('.coupon-box')).toHaveCount(listed);
	}

	/**
	 * The checkboxes carry no ids and what each posts is the thing itself, so they
	 * are found by value.
	 *
	 * @param {string[]} values 'name', and the label of any custom field
	 */
	async showBookingData(values) {
		await this.openSection('booking-flow');

		for (const value of values) {
			await this.form
				.locator(`input[name="show-booking-data-registration[]"][value="${value}"]`)
				.check();
		}
	}

	/** @param {boolean} options.confirm Whether to go through with it */
	async removeCustomField(label, { confirm = true } = {}) {
		this.page.once('dialog', (dialog) => (confirm ? dialog.accept() : dialog.dismiss()));

		await this.customField(label).locator('.remove-cust-item').click();
	}

	async moveCustomFieldDown(label) {
		await this.customField(label).locator('.custom-container-move-down').click();
	}

	async openEditOptions(label) {
		await this.openSection('advanced');
		await this.customField(label).locator('.edit-options').click();

		await expect(this.editOptionsDialog).toBeVisible();
	}

	/** @return {Promise<import('@playwright/test').Locator>} The new token's box */
	async createApiToken() {
		await this.openSection('advanced');

		const listed = await this.apiTokens.count();

		await this.createApiTokenButton.click();
		await expect(this.apiTokens).toHaveCount(listed + 1);

		return this.apiTokens.last();
	}

	/** @param {import('@playwright/test').Locator} token A token box */
	async removeApiToken(token, { confirm = true } = {}) {
		this.page.once('dialog', (dialog) => (confirm ? dialog.accept() : dialog.dismiss()));

		await token.locator('.remove-token').click();
	}

	/** The reloaded screen is the only sign the save went through. */
	async save() {
		const reloaded = this.page.waitForResponse(
			(response) =>
				response.request().method() === 'GET' &&
				response.url().includes(`page=${SEATREG_PAGES.SETTINGS}`),
			{ timeout: TIMEOUTS.NAVIGATION }
		);

		await this.saveButton.click();
		await reloaded;

		await this.page.waitForLoadState('domcontentloaded');
		await expect(this.form).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/**
	 * The check runs on the button's click and calls preventDefault(), so a
	 * blocked save never leaves the page - which is what makes the toast proof of
	 * anything. The url is checked too, because a post the server refuses ends on
	 * a wp_die page instead.
	 */
	async saveExpectingError(message) {
		const urlBeforeSubmit = this.page.url();

		await this.saveButton.click();

		await expect(this.errorToast).toHaveText(message);
		expect(this.page.url()).toBe(urlBeforeSubmit);
	}

	async reload() {
		await this.page.reload();
		await expect(this.form).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
	}

	/**
	 * Settings has no link to the registration, so this goes through Home.
	 *
	 * @return {Promise<RegistrationPage>} The registration in its own tab
	 */
	async openRegistration(code) {
		await this.homePage.goto();

		return this.homePage.openRegistration(code);
	}

	/**
	 * Make one booking on the registration and come back, for a test whose subject
	 * is what the booking leaves behind rather than the making of it. Only for a
	 * registration allowBookings() has already opened the way for.
	 *
	 * The booker is given an address of their own, so the mail log can be read for
	 * this booking alone (tests/e2e/utils/mail.js).
	 *
	 * @param {number|number[]} booking.seats @see RegistrationPage#bookSeats
	 * @return {Promise<{id: string, email: string}>} How to look the booking up
	 */
	async makeBooking(code, { seats = 1, email = uniqueBookerEmail(), ...details } = {}) {
		const registration = await this.openRegistration(code);

		await registration.completeBooking({ seats, ...BOOKER, ...details, email });

		await expect(registration.bookingConfirmed).toBeVisible();

		const id = await registration.bookingId();

		await registration.page.close();

		return { id, email };
	}

	/**
	 * Open the registration in a browser that never had the admin session. The
	 * address comes off the Home card because the path in front of a
	 * registration's query string depends on the site's permalink settings.
	 *
	 * The returned page's context stays open until the caller closes it.
	 *
	 * @return {Promise<RegistrationPage>}
	 */
	async openRegistrationAsVisitor(browser, code) {
		await this.homePage.goto();

		const registrationUrl = await this.homePage.registrationLink(code).getAttribute('href');

		const context = await visitorContext(browser);
		const visitorPage = await context.newPage();

		await visitorPage.goto(registrationUrl);
		await visitorPage.waitForLoadState('domcontentloaded');

		return new RegistrationPage(visitorPage);
	}

	#fieldConfig(name) {
		const config = FIELDS[name];

		if (!config) {
			throw new Error(`Unknown SeatReg setting: ${name}`);
		}

		return config;
	}
}

module.exports = { SettingsPage, FIELDS, PAID_BOOKING_PAYMENT, BOOKER };
