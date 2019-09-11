import {generateUrl} from 'nextcloud-router'
import HttpClient from 'nextcloud-axios'

const generateScriptsUrl = (accountId, path = null) => {
	const url = `/apps/mail/api/accounts/{id}/scripts${path ? `/${path}` : ''}`

	return generateUrl(url, {id: accountId})
}

export const getScripts = accountId => {
	const url = generateScriptsUrl(accountId)

	return HttpClient.get(url).then(resp => resp.data.scripts)
}

export const getScript = (accountId, scriptName) => {
	const url = generateScriptsUrl(accountId, scriptName)

	return HttpClient.get(url).then(resp => resp.data)
}

export const setActiveScript = (accountId, scriptName) => {
	const url = generateScriptsUrl(accountId, 'active')

	return HttpClient.post(url, {scriptName}).then(resp => resp.data)
}

export const saveCustomScript = (accountId, script) => {
	const url = generateScriptsUrl(accountId)
	return HttpClient.post(url, {script}).then(resp => resp.data)
}
