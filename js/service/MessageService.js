import HttpClient from 'nextcloud-axios'

export function fetchEnvelopes (accountId, folderId) {
	const url = OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages', {
		accountId,
		folderId,
	})

	return HttpClient.get(url).then(resp => resp.data)
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
