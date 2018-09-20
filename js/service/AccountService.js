export function fetchAll () {
	return new Promise((res, rej) => {
		setTimeout(() => {
			res([
				{
					id: 1,
					folders: [
						'1-SU5CT1g='
					],
				}, {
					id: 2,
					folders: [],
				}
			])
		}, 800);
	})
}

export function fetch () {
	return new Promise((res, rej) => {
		setTimeout(() => {
			res({
				id: 3,
			})
		}, 800);
	})
}
