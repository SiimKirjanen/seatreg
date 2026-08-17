const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

/**
 * Interaction helpers for the SeatReg admin screens.
 */

/**
 * Click until the DOM reacts.
 *
 * The plugin binds its handlers to the elements that exist when the script runs
 * instead of delegating, so a click landing before that is silently ignored.
 * Retried only while the target is still missing - once it is open it would be
 * in the way of the next click.
 *
 * @param {number} options.reaction How long one click gets to show its effect
 * @param {number} options.timeout  Budget for all the attempts together
 */
async function clickUntil(
	trigger,
	target,
	{ reaction = TIMEOUTS.REACTION, timeout = TIMEOUTS.NAVIGATION } = {}
) {
	await expect(async () => {
		if (!(await target.isVisible())) {
			await trigger.click();
		}

		await expect(target).toBeVisible({ timeout: reaction });
	}).toPass({ timeout, intervals: [200, 500, 1000] });
}

/**
 * Wait for a Bootstrap modal to be fully faded in.
 *
 * Acting on one mid transition is ignored, which leaves it stuck on screen.
 */
async function expectModalShown(modal) {
	await expect(modal).toBeVisible();
	await expect(modal).toHaveCSS('opacity', '1');
}

/**
 * Wait for a Bootstrap modal to be fully faded out.
 *
 * The modal turns invisible before Bootstrap has finished tearing it down, and
 * a trigger clicked inside that window is ignored. That matters wherever the
 * plugin opens a dialog with .modal('toggle'), because the ignored click is
 * indistinguishable from one that opened and closed it again. The backdrop is
 * removed at the end of the teardown, so its absence is what says it is done.
 */
async function expectModalHidden(modal) {
	await expect(modal).toBeHidden();
	await expect(modal.page().locator('.modal-backdrop')).toHaveCount(0);
}

module.exports = { clickUntil, expectModalShown, expectModalHidden };
