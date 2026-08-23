const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

/* The site cannot send mail, so tests/e2e/mu-plugins/mail-log.php captures
   wp_mail() and hands it back through admin-ajax. Capturing is off outside a run,
   since it stops wp_mail() dead for anyone developing against a real mailer.

   The log is shared by every worker, so mail is only ever asked for by address. */

const NO_MAIL_CAPTURE = 'Mail capture is off — is the site running the e2e mu-plugin?';

let counter = 0;

/** A booker address no other test, worker or run can be using. */
function uniqueBookerEmail(prefix = 'booker') {
	counter += 1;

	const run = Date.now().toString(36);
	const worker = process.env.TEST_WORKER_INDEX ?? '0';

	return `${prefix}-${run}-${worker}-${counter}@example.com`;
}

/** Answers false when the endpoint is missing, i.e. the mu-plugin is not mounted. */
async function setMailCapture(page, enabled) {
	const response = await page.request.get(
		`/wp-admin/admin-ajax.php?action=seatreg_e2e_mail_capture&enable=${enabled ? '1' : '0'}`
	);

	if (!response.ok()) {
		return false;
	}

	return (await response.json()).enabled === true;
}

/* Worth asking before a spec that needs it: with capturing off the plugin fails the
   booking itself, long before any assertion about the mail. */
async function mailCaptureEnabled(page) {
	const response = await page.request.get('/wp-admin/admin-ajax.php?action=seatreg_e2e_mail_log');

	if (!response.ok()) {
		return false;
	}

	return !(await response.json()).disabled;
}

/**
 * Whether a spec that reads the mail has to stand down, for `test.skip()`.
 *
 * Off a developer's machine the mu-plugin may simply not be mounted, and standing
 * down says so. On CI auth.setup.js has always switched capturing on, so anything
 * else is a broken run - and skipping would leave every mail test quietly green.
 */
async function shouldSkipWithoutMail(page) {
	if (await mailCaptureEnabled(page)) {
		return false;
	}

	if (process.env.CI) {
		throw new Error(NO_MAIL_CAPTURE);
	}

	return true;
}

/** Everything sent to an address so far, oldest first. */
async function mailSentTo(page, to) {
	const response = await page.request.get(
		`/wp-admin/admin-ajax.php?action=seatreg_e2e_mail_log&to=${encodeURIComponent(to)}`
	);

	expect(response.ok()).toBeTruthy();

	const captured = await response.json();

	if (captured.disabled) {
		throw new Error(NO_MAIL_CAPTURE);
	}

	return captured;
}

/**
 * The newest mail sent to an address.
 *
 * @param {number} options.after Only settle once more than this many have arrived
 */
async function waitForMail(page, to, { after = 0 } = {}) {
	let mail = [];

	await expect
		.poll(
			async () => {
				mail = await mailSentTo(page, to);

				return mail.length;
			},
			{ timeout: TIMEOUTS.NAVIGATION }
		)
		.toBeGreaterThan(after);

	return mail[mail.length - 1];
}

/**
 * The one link in the mail that leads where the test is going; the plugin's emails
 * carry several.
 *
 * @param {string} contains e.g. 'seatreg=booking-confirm'
 */
function linkFromMail(mail, contains) {
	// esc_url() writes the separators as &#038;
	const links = [...mail.message.matchAll(/href=["']([^"']+)["']/g)].map(([, href]) =>
		href.replace(/&(amp|#0?38);/g, '&')
	);
	const link = links.find((href) => href.includes(contains));

	if (!link) {
		throw new Error(`No link containing "${contains}" in mail "${mail.subject}"`);
	}

	return link;
}

module.exports = {
	uniqueBookerEmail,
	setMailCapture,
	shouldSkipWithoutMail,
	NO_MAIL_CAPTURE,
	mailSentTo,
	waitForMail,
	linkFromMail,
};
