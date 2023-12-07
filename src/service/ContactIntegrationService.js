/*
 * @copyright 2021 Kristian Lebold <kristian@lebold.info>
 *
 * @author 2021 Kristian Lebold <kristian@lebold.info>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const findMatches = (mail) => {
	const url = generateUrl('/apps/mail/api/contactIntegration/match/{mail}', {
		mail,
	})

	return Axios.get(url).then((resp) => resp.data)
}

export const addToContact = (id, mailAddr) => {
	const url = generateUrl('/apps/mail/api/contactIntegration/add')

	return Axios.put(url, { uid: id, mail: mailAddr }).then((resp) => resp.data)
}

export const newContact = (name, mailAddr) => {
	const url = generateUrl('/apps/mail/api/contactIntegration/new')

	return Axios.put(url, { contactName: name, mail: mailAddr }).then((resp) => resp.data)
}

export const autoCompleteByName = (term) => {
	const url = generateUrl('/apps/mail/api/contactIntegration/autoComplete/{term}', {
		term,
	})

	return Axios.get(url).then((resp) => resp.data)
}
