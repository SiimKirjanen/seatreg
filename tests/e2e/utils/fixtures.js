const { expect } = require('@playwright/test');

/* Site state a spec needs but no SeatReg screen makes: a post to put a shortcode
   in, a user who is not an administrator. Made through
   tests/e2e/mu-plugins/fixtures.php rather than clicked together in wp-admin,
   which would be the block editor and the user screen for no coverage at all.

   Nothing is cleaned up, so everything made here is named uniquely. */

let counter = 0;

function uniqueSuffix() {
	counter += 1;

	const run = Date.now().toString(36);
	const worker = process.env.TEST_WORKER_INDEX ?? '0';

	return `${run}-${worker}-${counter}`;
}

async function askFixture(page, action, params) {
	const query = new URLSearchParams({ action: `seatreg_e2e_${action}`, ...params });

	const response = await page.request.get(`/wp-admin/admin-ajax.php?${query}`);

	expect(
		response.ok(),
		`The e2e fixtures mu-plugin refused ${action}: ${await response.text()}`
	).toBeTruthy();

	return response.json();
}

/**
 * A published post, so a shortcode in it is rendered for whoever opens the address.
 *
 * @return {Promise<{id: number, url: string}>}
 */
function createPost(page, { title, content }) {
	return askFixture(page, 'create_post', { title, content });
}

/**
 * A user of the role named, with an address and a password of their own.
 *
 * @return {Promise<{username: string, password: string}>} How to sign in as them
 */
async function createUser(page, { role, prefix = 'seatreg-e2e' }) {
	const username = `${prefix}-${uniqueSuffix()}`;
	const password = `pw-${uniqueSuffix()}`;

	await askFixture(page, 'create_user', { username, password, role });

	return { username, password };
}

module.exports = { createPost, createUser };
