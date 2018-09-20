export function fetchAll () {
	return new Promise((res, rej) => {
		setTimeout(() => {
			res([
				{
					id: 1,
					name: 'user@work.tld',
					bullet: '#eea941',
					folders: [
						'1-SU5CT1g='
					],
				}, {
					id: 2,
					bullet: '#4948ee',
					name: 'user.name@private.tld',
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
