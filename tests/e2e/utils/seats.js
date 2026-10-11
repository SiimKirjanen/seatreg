/** The builder and the registration both place a seat with inline left and top, in room pixels. */
async function seatPositions(seats) {
	return seats.evaluateAll((elements) =>
		elements.map((element) => ({
			left: parseInt(element.style.left, 10),
			top: parseInt(element.style.top, 10),
		}))
	);
}

/**
 * How far a seat is turned, and how its number ends up turned on screen once
 * the seat's own turn is added to it, both in whole degrees.
 */
async function seatAngles(seat) {
	return seat.evaluate((element) => {
		const degrees = (matrix) => Math.round((Math.atan2(matrix.b, matrix.a) * 180) / Math.PI);
		const seatMatrix = new DOMMatrix(getComputedStyle(element).transform);
		const numberMatrix = new DOMMatrix(getComputedStyle(element.querySelector('.seat-number')).transform);

		return { seat: degrees(seatMatrix), number: degrees(seatMatrix.multiply(numberMatrix)) };
	});
}

module.exports = { seatPositions, seatAngles };
