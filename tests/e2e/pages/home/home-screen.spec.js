const { test, expect } = require('@playwright/test');
const { HomePage } = require('./home-page');
const { SettingsPage } = require('../settings/settings-page');
const { BookingManagerPage } = require('../booking-manager/booking-manager-page');
const {
	SEATREG_PAGES,
	SEATREG_MENU_ITEMS,
	getSeatRegMenu,
	getSeatRegMenuItem,
} = require('../../utils/navigation');
const { uniqueRegistrationName, registrationPublicUrlQuery } = require('../../utils/registrations');
const { escapeForRegExp } = require('../../utils/text');
const { loginToWordPress, visitorContext } = require('../../utils/auth');
const { createUser } = require('../../utils/fixtures');
const { uniqueBookerEmail } = require('../../utils/mail');
const { fromIsoDate } = require('../../utils/dates');

/* What WordPress says to anyone the plugin's capabilities do not cover. */
const NOT_ALLOWED = 'Sorry, you are not allowed to access this page.';

function daysFromToday(days) {
	const date = new Date();

	date.setDate(date.getDate() + days);

	return date;
}

test.describe('SeatReg Home screen', () => {
	let homePage;

	test.beforeEach(async ({ page }) => {
		homePage = new HomePage(page);
		await homePage.goto();
	});

	test('renders the home screen with the create registration form', async () => {
		await expect(homePage.heading).toBeVisible();
		await expect(homePage.createForm).toBeVisible();
		await expect(homePage.nameInput).toBeEditable();
		await expect(homePage.createButton).toHaveValue('Create new registration');
	});

	/* Every beforeEach already gets here through the menu, so only the items are
	   left to cover. */
	test('lists every SeatReg screen in its menu', async ({ page }) => {
		for (const item of SEATREG_MENU_ITEMS) {
			await expect(getSeatRegMenuItem(page, item.label)).toBeVisible();
		}
	});

	/* The other half of that: the plugin adds its two capabilities to
	   administrators alone, and every screen is behind one of them. An editor has
	   the run of wp-admin and none of SeatReg. */
	test('keeps a non-administrator out of the SeatReg screens', async ({ page, browser }) => {
		const editor = await createUser(page, { role: 'editor' });

		const context = await visitorContext(browser);
		const editorPage = await context.newPage();

		await loginToWordPress(editorPage, editor.username, editor.password);

		await expect(getSeatRegMenu(editorPage)).toHaveCount(0);

		/* The menu only hides the way in. The screen itself is what has to turn
		   them away, since its address is no secret. */
		await editorPage.goto(`/wp-admin/admin.php?page=${SEATREG_PAGES.HOME}`);

		await expect(editorPage.locator('body')).toContainText(NOT_ALLOWED);

		await context.close();
	});

	test('refuses to submit an empty registration name', async ({ page }) => {
		const urlBeforeSubmit = page.url();

		await homePage.createButton.click();

		await expect(homePage.errorToast).toHaveText('Please enter registration name');
		expect(page.url()).toBe(urlBeforeSubmit);
	});

	test('creates a registration and lists it', async () => {
		const name = uniqueRegistrationName('Home create');

		const code = await homePage.createRegistration(name);

		expect(code).toBeTruthy();
		await expect(homePage.registrationsHeader).toHaveText('Created registrations');
		await expect(homePage.registrationNameLink(name)).toHaveText(name);
	});

	test('shows the links of a registration', async () => {
		const name = uniqueRegistrationName('Home links');
		const code = await homePage.createRegistration(name);

		const adminLinks = [
			[homePage.overviewLink(code), SEATREG_PAGES.OVERVIEW],
			[homePage.settingsLink(code), SEATREG_PAGES.SETTINGS],
			[homePage.bookingsLink(code), SEATREG_PAGES.BOOKINGS],
		];

		for (const [link, slug] of adminLinks) {
			await expect(link).toHaveAttribute('href', new RegExp(`page=${slug}&tab=${code}`));
		}

		await expect(homePage.layoutButton(code)).toBeVisible();
		await expect(homePage.moreLink(code)).toBeVisible();

		const publicUrlQuery = registrationPublicUrlQuery(code);

		for (const link of [homePage.registrationNameLink(name), homePage.registrationLink(code)]) {
			await expect(link).toHaveAttribute('href', new RegExp(escapeForRegExp(publicUrlQuery)));
			await expect(link).toHaveAttribute('target', '_blank');
		}
	});

	/* Only the two states a user switches between - the date driven ones are the same rule. */
	test("shows a registration's status on its card", async ({ page }) => {
		const settingsPage = new SettingsPage(page);
		const name = uniqueRegistrationName('Home status');
		const code = await settingsPage.openForNewRegistration(name);

		await homePage.goto();
		await expect(homePage.statusBadge(code)).toHaveText('Open');

		await settingsPage.open(code);
		await settingsPage.set('registrationStatus', false);
		await settingsPage.save();

		await homePage.goto();
		await expect(homePage.statusBadge(code)).toHaveText('Closed');
	});

	/* The card counts the site's today, which the machine running the test need not
	   agree on, so the dates are picked far enough out that a day either way cannot
	   decide the outcome. */
	test('counts one calendar date and says so when today is not one', async ({ page }) => {
		const settingsPage = new SettingsPage(page);
		const name = uniqueRegistrationName('Home calendar');
		const code = await settingsPage.openForNewRegistration(name);

		await settingsPage.set('usingCalendar', true);
		await settingsPage.pickCalendarDates([daysFromToday(3)]);
		await settingsPage.save();

		await homePage.goto();
		await expect(homePage.calendarIcon(code)).toBeVisible();
		await expect(homePage.footerNotice(code)).toHaveText('Bookings are not taken today');
		await expect(homePage.countedDate(code)).toHaveCount(0);

		await settingsPage.open(code);
		await settingsPage.pickCalendarDates([daysFromToday(-1), daysFromToday(0), daysFromToday(1)]);
		await settingsPage.save();

		await homePage.goto();
		await expect(homePage.footerNotice(code)).toHaveCount(0);
		await expect(homePage.countedDate(code)).toBeVisible();
		await expect(homePage.bookingCount(code, 'pending')).toHaveText('0');
	});

	/* A day can go off the calendar after it was booked, and the booking manager
	   goes on listing what was booked for it. The notice cannot swallow the counts
	   or the card would contradict the screen it links to. The day booked is the
	   site's own today - the one the card counts - read off the booking manager
	   rather than guessed at from the machine running the test. */
	test('keeps the counts on a day it no longer takes bookings for', async ({ page }) => {
		const settingsPage = new SettingsPage(page);
		const manager = new BookingManagerPage(page);
		const name = uniqueRegistrationName('Home closed day');
		const code = await settingsPage.openForNewRegistrationWithSeats(name, 1);

		await settingsPage.set('usingCalendar', true);
		await settingsPage.pickCalendarDates([daysFromToday(3)]);
		await settingsPage.save();

		await manager.openForRegistration(code);
		await manager.pickCalendarDate(fromIsoDate(await manager.calendarDateValue.inputValue()));
		await manager.addBooking({
			seats: [
				{
					seat: 1,
					firstName: 'Closed',
					lastName: 'Day',
					email: uniqueBookerEmail('home-closed-day'),
				},
			],
		});

		await homePage.goto();
		await expect(homePage.footerNotice(code)).toBeVisible();
		await expect(homePage.bookingCount(code, 'pending')).toHaveText('1');
	});
});
