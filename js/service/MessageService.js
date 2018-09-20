export function fetchEnvelopes () {
	return new Promise((res, rej) => {
		setTimeout(() => {
			res([
				{
					id: '1',
					from: 'Sender 1',
					subject: 'Message 1',
					envelopes: ['1-SU5CT1g=-1', '1-SU5CT1g=-2']
				},
				{
					id: '2',
					from: 'Sender 2',
					subject: 'Message 2',
					envelopes: ['1-SU5CT1g=-1', '1-SU5CT1g=-2']
				},
				{
					id: '3',
					from: 'Sender 3',
					subject: 'Message 3',
					envelopes: ['1-SU5CT1g=-1', '1-SU5CT1g=-2']
				}
			])
		}, 800)
	})
}

export function fetchMessage (accountId, folderId, id) {
	return new Promise((res, rej) => {
		setTimeout(() => {
			res({
				id: id,
				from: [
					{
						label: 'Backbone Marionette',
						email: 'backbone.marionette@frameworks.js',
					}
				],
				to: [
					{
						label: 'React',
						email: 'react@frameworks.js',
					},
					{
						label: 'Angular',
						email: 'angular@frameworks.js',
					}
				],
				cc: [
					{
						label: 'Underscore Jayes',
						email: 'underscore@frameworks.js',
					}
				],
				subject: 'Do you enjoy the Vue?',
				hasHtmlBody: false,
				body: 'Henlo!',
				signature: 'Backbone Marionette',
			})
		}, 1500)
	})
}
