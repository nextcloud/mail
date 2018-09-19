export function fetchEnvelopes () {
	return new Promise((res, rej) => {
		setTimeout(() => {
			res([
				{
					id: 1,
					subject: 'Message 1',
				},
				{
					id: 2,
					subject: 'Message 2',
				}
			])
		})
	})
}
