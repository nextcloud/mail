export function fetchAll (accountId) {
	return new Promise((res, rej) => {
		if (accountId === 1) {
			setTimeout(() => {
				res([
					{
						id: 'SU5CT1g=',
						name: 'Inbox',
						specialUse: 'inbox',
						unread: 2,
					},
				])
			}, 500)
		} else {
			setTimeout(() => {
				res([
					{
						id: 'SU5CT1g=',
						name: 'Inbox',
						specialUse: 'inbox',
						unread: 0,
					},
				])
			}, 500)
		}
	})
}
