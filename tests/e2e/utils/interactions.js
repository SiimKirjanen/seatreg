const { expect } = require('@playwright/test');
const { TIMEOUTS } = require('./timeouts');

/**
 * Click until the DOM reacts.
 *
 * The plugin binds its handlers to the elements that exist when the script runs
 * instead of delegating, so a click landing before that is silently ignored.
 * Retried only while the target is still missing - once it is open it would be in
 * the way of the next click.
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

/** Acting on a Bootstrap modal mid transition is ignored, leaving it stuck open. */
async function expectModalShown(modal) {
	await expect(modal).toBeVisible();
	await expect(modal).toHaveCSS('opacity', '1');
}

/**
 * The modal turns invisible before Bootstrap has finished tearing it down, and a
 * trigger clicked inside that window is ignored. The backdrop is removed at the
 * end of the teardown, so its absence is what says it is done.
 */
async function expectModalHidden(modal) {
	await expect(modal).toBeHidden();
	await expect(modal.page().locator('.modal-backdrop')).toHaveCount(0);
}

/** Two years either way, past which the picker is not going to arrive. */
const MAX_MONTH_STEPS = 24;

/**
 * Click a day in a jQuery UI datepicker, stepping to its month first.
 *
 * Only one month is on screen at a time and the cells carry the month and year
 * they belong to, so the target cell showing up says the right month is reached.
 */
async function pickDatepickerDay(picker, date) {
	const cell = picker
		.locator(`td[data-month="${date.getMonth()}"][data-year="${date.getFullYear()}"]`)
		.getByText(String(date.getDate()), { exact: true });

	const shownMonth = picker.locator('.ui-datepicker-calendar td[data-month]').first();

	for (let step = 0; step < MAX_MONTH_STEPS && (await cell.count()) === 0; step += 1) {
		const shown = new Date(
			Number(await shownMonth.getAttribute('data-year')),
			Number(await shownMonth.getAttribute('data-month')),
			1
		);
		const target = new Date(date.getFullYear(), date.getMonth(), 1);

		await picker.locator(target > shown ? '.ui-datepicker-next' : '.ui-datepicker-prev').click();
	}

	/* Said plainly here: left to the click, a month that was never reached reads
	   as a day that is somehow unclickable. */
	if ((await cell.count()) === 0) {
		throw new Error(
			`The datepicker did not reach ${date.getFullYear()}-${date.getMonth() + 1} in ${MAX_MONTH_STEPS} steps`
		);
	}

	await cell.click();
}

module.exports = { clickUntil, expectModalShown, expectModalHidden, pickDatepickerDay };
