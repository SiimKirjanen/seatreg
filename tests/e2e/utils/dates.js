/* The day shapes more than one screen deals in, and the arithmetic for reaching a
   day that is safe to pick. How a single screen writes a date out belongs in that
   screen's own spec. */

/** yyyy-mm-dd, how a day travels in an address and how the calendar stores it. */
function isoDate(date) {
	return [date.getFullYear(), date.getMonth() + 1, date.getDate()]
		.map((part, index) => (index === 0 ? part : String(part).padStart(2, '0')))
		.join('-');
}

/** Read a yyyy-mm-dd day as a local one, which parsing it whole would not do. */
function fromIsoDate(iso) {
	const [year, month, day] = iso.split('-').map(Number);

	return new Date(year, month - 1, day);
}

/** The 15th of a month either side of this one, a day every month has. */
function monthsFromNow(months) {
	const date = new Date();

	date.setDate(15);
	date.setMonth(date.getMonth() + months);

	return date;
}

/** A day of next month, reached without stepping through a shorter one. */
function dayNextMonth(day) {
	const date = new Date();

	date.setDate(1);
	date.setMonth(date.getMonth() + 1);
	date.setDate(day);

	return date;
}

function dayAfter(date) {
	const next = new Date(date);

	next.setDate(next.getDate() + 1);

	return next;
}

module.exports = { isoDate, fromIsoDate, monthsFromNow, dayNextMonth, dayAfter };
