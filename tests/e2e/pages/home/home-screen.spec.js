const { test, expect } = require('@playwright/test');
const { HomePage } = require('./home-page');
const { SEATREG_PAGES, SEATREG_MENU_ITEMS, getSeatRegMenuItem } = require('../../utils/navigation');
const { uniqueRegistrationName, registrationPublicUrlQuery } = require('../../utils/registrations');
const { escapeForRegExp } = require('../../utils/text');

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
});
