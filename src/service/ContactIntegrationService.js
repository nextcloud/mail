/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export function findMatches(mail) {
	const url = generateUrl('/apps/mail/api/contactIntegration/match/{mail}', {
		mail,
	})

	return Axios.get(url).then((resp) => resp.data)
}

export function addToContact(id, mailAddr) {
	const url = generateUrl('/apps/mail/api/contactIntegration/add')

	return Axios.put(url, { uid: id, mail: mailAddr }).then((resp) => resp.data)
}

export function newContact(name, mailAddr) {
	const url = generateUrl('/apps/mail/api/contactIntegration/new')

	return Axios.put(url, { contactName: name, mail: mailAddr }).then((resp) => resp.data)
}

export function autoCompleteByName(term) {
	const url = generateUrl('/apps/mail/api/contactIntegration/autoComplete/{term}', {
		term,
	})

	return Axios.get(url).then((resp) => resp.data)
}
