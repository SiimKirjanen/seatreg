/**
 * Helpers for the registrations that tests create.
 *
 * Registrations are global to the WordPress install and tests do not clean up
 * after themselves, so every test must work with its own uniquely named
 * registration and never assert on the size or emptiness of the list.
 */

let counter = 0;

/**
 * Build a registration name that cannot collide with another test, another
 * worker, or a previous run.
 *
 * The three parts of the suffix are what those three axes need: a base36
 * millisecond stamp for the run, Playwright's worker index, and a counter for
 * repeated calls inside one worker.
 *
 * @param {string} prefix Human readable prefix so leftovers are identifiable
 * @return {string} A unique registration name, e.g. `E2E mgk3x2ab-0-1`
 */
function uniqueRegistrationName(prefix = 'E2E') {
	counter += 1;

	const run = Date.now().toString(36);
	const worker = process.env.TEST_WORKER_INDEX ?? '0';

	return `${prefix} ${run}-${worker}-${counter}`;
}

/**
 * The query string of a registration's public URL, as built in
 * seatreg_generate_my_registrations_section(). The host and path in front of it
 * depend on the site's permalink settings, so tests assert on this part only.
 *
 * @param {string} code Registration code
 */
function registrationPublicUrlQuery(code) {
	return `?seatreg=registration&c=${code}&page_id=seatreg`;
}

module.exports = { uniqueRegistrationName, registrationPublicUrlQuery };
