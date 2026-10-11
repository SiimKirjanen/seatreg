/** The builder and the registration both place a seat with inline left and top, in room pixels. */
async function seatPositions(seats) {
	return seats.evaluateAll((elements) =>
		elements.map((element) => ({
			left: parseInt(element.style.left, 10),
			top: parseInt(element.style.top, 10),
		}))
	);
}

module.exports = { seatPositions };
