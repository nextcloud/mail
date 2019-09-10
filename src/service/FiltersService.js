import {generateUrl} from 'nextcloud-router'
import HttpClient from 'nextcloud-axios'

const generateFiltersUrl = (accountId, path) => {
	return generateUrl(`/apps/mail/api/accounts/{id}/filters/${path}`, {
		id: accountId,
	})
}

export const getScripts = accountId => {
	const url = generateFiltersUrl(accountId, 'scripts')

	return HttpClient.get(url).then(resp => resp.data.scripts)
}

export const setActiveScript = (accountId, scriptName) => {
	const url = generateFiltersUrl(accountId, 'scripts')

	return HttpClient.put(url, {scriptName}).then(resp => resp.data)
}
