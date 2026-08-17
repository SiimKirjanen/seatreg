const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

/**
 * Navigation utilities for the SeatReg admin screens.
 *
 * This is the only place that knows SeatReg's admin URLs and menu structure.
 * Specs and page objects should navigate through these helpers so a slug
 * change only needs updating here.
 *
 * Screens are reached by clicking the plugin's menu, the way a user does. The
 * dashboard is the only URL the suite types.
 */

/** Admin page slugs, as registered in php/seatreg_admin_panel.php */
const SEATREG_PAGES = {
	HOME: 'seatreg-welcome',
	OVERVIEW: 'seatreg-overview',
	SETTINGS: 'seatreg-options',
	BOOKINGS: 'seatreg-management',
	TOOLS: 'seatreg-tools',
	COMPANION: 'seatreg-companion-app',
};

/** Submenu items and the page each one opens, in the order they are registered */
const SEATREG_MENU_ITEMS = [
	{ label: 'Home', page: SEATREG_PAGES.HOME },
	{ label: 'Overview', page: SEATREG_PAGES.OVERVIEW },
	{ label: 'Settings', page: SEATREG_PAGES.SETTINGS },
	{ label: 'Bookings', page: SEATREG_PAGES.BOOKINGS },
	{ label: 'Tools', page: SEATREG_PAGES.TOOLS },
	{ label: 'Companion', page: SEATREG_PAGES.COMPANION },
];

/**
 * The page a submenu item opens.
 */
function menuItemPage(label) {
	const item = SEATREG_MENU_ITEMS.find((menuItem) => menuItem.label === label);

	if (!item) {
		throw new Error(`Unknown SeatReg menu item: ${label}`);
	}

	return item.page;
}

/**
 * Navigate to the WordPress dashboard.
 */
async function gotoWpAdmin(page) {
	await page.goto('/wp-admin/');
	await expect(page.locator('#wpadminbar')).toBeVisible({ timeout: TIMEOUTS.NAVIGATION });
}

/**
 * The plugin's top level admin menu link.
 */
function getSeatRegMenu(page) {
	return page.getByRole('link', { name: 'SeatReg', exact: true });
}

/**
 * A SeatReg submenu link. Scoped to the plugin's menu because WordPress core
 * uses the same labels (Home, Settings, Tools) in its own menus.
 */
function getSeatRegMenuItem(page, name) {
	return page
		.locator(`#toplevel_page_${SEATREG_PAGES.HOME}`)
		.getByRole('link', { name, exact: true });
}

/**
 * Click a SeatReg submenu item and wait for the target screen to load.
 *
 * On screens other than SeatReg's own, the submenu is collapsed and only
 * rendered as a flyout while the top level item is hovered, so it has to be
 * opened before its items can be clicked.
 *
 * The plugin's admin scripts are parser blocking footer scripts, so waiting for
 * DOMContentLoaded leaves the screen wired up and safe to click on.
 */
async function openSeatRegMenuItem(page, name) {
	await getSeatRegMenu(page).hover();

	const menuItem = getSeatRegMenuItem(page, name);
	await expect(menuItem).toBeInViewport({ timeout: TIMEOUTS.DEFAULT });
	await menuItem.click();

	await page.waitForLoadState('domcontentloaded');
}

/**
 * Open a SeatReg screen the way a user does: dashboard, then the plugin's menu.
 *
 * @param {string} label One of the SEATREG_MENU_ITEMS labels
 */
async function openSeatRegScreen(page, label) {
	await gotoWpAdmin(page);
	await openSeatRegMenuItem(page, label);
	await expectOnSeatRegPage(page, menuItemPage(label));
}

/**
 * Assert the browser is on the given SeatReg admin page.
 *
 * @param {Object} params Query parameters that must also be present
 */
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
