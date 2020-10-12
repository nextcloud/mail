/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Holger Dehnhardt <holger@dehnhardt.org>
 *
 * @license GNU AGPL version 3 or any later version
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

import {generateUrl} from '@nextcloud/router'
import Axios from '@nextcloud/axios'

export const updateSieveAccount = (data) => {
	const url = generateUrl('/apps/mail/api/sieve/{id}/account', {
		id: data.accountId,
	})

	return Axios.put(url, data).then((resp) => resp.data)
}

export const listScripts = (accountId) => {
	const url = generateUrl('/apps/mail/api/sieve/{id}/account', {
		id: accountId,
	})

	return Axios.get(url).then((resp) => resp.data)
}

export const getScriptContent = (accountId, scriptName) => {
	const url = generateUrl('/apps/mail/api/sieve/{id}/script/{scriptName}', {
		id: accountId,
		scriptName,
	})

	return Axios.get(url).then((resp) => resp.data)
}

export const putScriptContent = (accountId, scriptName, install, scriptContent) => {
	const url = generateUrl('/apps/mail/api/sieve/{id}/script/{scriptName}', {
		id: accountId,
		scriptName,
	})
	const data = {
		install,
		scriptContent,
	}
	return Axios.put(url, data).then((resp) => resp.data)
}
