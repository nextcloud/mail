export function fetchAll () {
	return new Promise((res, rej) => {
		setTimeout(() => {
			res([
				{
					id: 1,
					folders: [],
				}, {
					id: 2,
					folders: [],
				}
			])
		}, 1500);
	})
}

export function fetch () {
	return new Promise((res, rej) => {
		setTimeout(() => {
			res({
				id: 3,
			})
		}, 1500);
	})
}
