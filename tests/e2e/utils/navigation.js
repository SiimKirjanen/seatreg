const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

/* The only place that knows SeatReg's admin URLs and menu structure. Screens are
   reached by clicking the plugin's menu; the dashboard is the only URL typed. */

const SEATREG_PAGES = {
	HOME: 'seatreg-welcome',
	OVERVIEW: 'seatreg-overview',
	SETTINGS: 'seatreg-options',
	BOOKINGS: 'seatreg-management',
	TOOLS: 'seatreg-tools',
	COMPANION: 'seatreg-companion-app',
};

/** In the order they are registered. */
const SEATREG_MENU_ITEMS = [
	{ label: 'Home', page: SEATREG_PAGES.HOME },
	{ label: 'Overview', page: SEATREG_PAGES.OVERVIEW },
	{ label: 'Settings', page: SEATREG_PAGES.SETTINGS },
	{ label: 'Bookings', page: SEATREG_PAGES.BOOKINGS },
	{ label: 'Tools', page: SEATREG_PAGES.TOOLS },
	{ label: 'Companion', page: SEATREG_PAGES.COMPANION },
];

function menuItemPage(label) {
	const item = SEATREG_MENU_ITEMS.find((menuItem) => menuItem.label === label);

	if (!item) {
		throw new Error(`Unknown SeatReg menu item: ${label}`);
	}

	return item.page;
}

async function gotoWpAdmin(page) {
	await page.goto('/wp-admin/');
	await expect(page.locator('#wpadminbar')).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
}

function getSeatRegMenu(page) {
	return page.getByRole('link', { name: 'SeatReg', exact: true });
}

/** Scoped to the plugin's menu: WordPress core reuses these labels in its own. */
function getSeatRegMenuItem(page, name) {
	return page
		.locator(`#toplevel_page_${SEATREG_PAGES.HOME}`)
		.getByRole('link', { name, exact: true });
}

/**
 * On screens other than SeatReg's own the submenu is a flyout, so the top level
 * item has to be hovered before its items can be clicked.
 *
 * The plugin's admin scripts are parser blocking footer scripts, so
 * DOMContentLoaded leaves the screen wired up and safe to click on.
 */
async function openSeatRegMenuItem(page, name) {
	await getSeatRegMenu(page).hover();

	const menuItem = getSeatRegMenuItem(page, name);
	await expect(menuItem).toBeInViewport({ timeout: TIMEOUTS.DEFAULT });
	await menuItem.click();

	await page.waitForLoadState('domcontentloaded');
}

async function openSeatRegScreen(page, label) {
	await gotoWpAdmin(page);
	await openSeatRegMenuItem(page, label);
	await expectOnSeatRegPage(page, menuItemPage(label));
}

/** @param {Object} params Query parameters that must also be present */
async function expectOnSeatRegPage(page, slug, params = {}) {
	await expect(page).toHaveURL(new RegExp(`page=${slug}`), { timeout: TIMEOUTS.NAVIGATION });

	for (const [key, value] of Object.entries(params)) {
		await expect(page).toHaveURL(new RegExp(`${key}=${value}`));
	}
}

module.exports = {
	SEATREG_PAGES,
	SEATREG_MENU_ITEMS,
	gotoWpAdmin,
	getSeatRegMenu,
	getSeatRegMenuItem,
	openSeatRegMenuItem,
	openSeatRegScreen,
	expectOnSeatRegPage,
};
