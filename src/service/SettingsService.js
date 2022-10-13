/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const getProvisioningSettings = (config) => {
	const url = generateUrl('/apps/mail/api/settings/provisioning')

	return axios.get(url, config).then((resp) => resp.data)
}

export const provisionAll = () => {
	const url = generateUrl('/apps/mail/api/settings/provisioning/all')

	return axios.put(url).then((resp) => resp.data)
}

export const createProvisioningSettings = (config) => {
	const url = generateUrl('/apps/mail/api/settings/provisioning')
	const data = {
		data: config,
	}
	return axios.post(url, data).then((resp) => resp.data)
}

export const updateProvisioningSettings = (config) => {
	const url = generateUrl('/apps/mail/api/settings/provisioning/{id}', {
		id: config.id,
	})
	const data = {
		data: config,
	}
	return axios.post(url, data).then((resp) => resp.data)
}

export const disableProvisioning = (id) => {
	const url = generateUrl('/apps/mail/api/settings/provisioning/{id}', {
		id,
	})
	return axios.delete(url).then((resp) => resp.data)
}

export const setAntiSpamEmail = (email) => {
	return axios.post(generateUrl('/apps/mail/api/settings/antispam'), { spam: email.spam, ham: email.ham })
		.then((resp) => resp.data)
}

export const deleteAntiSpamEmail = () => {
	return axios.delete(generateUrl('/apps/mail/api/settings/antispam'))
		.then((resp) => resp.data)
}

export const updateAllowNewMailAccounts = (allowed) => {
	const url = generateUrl('/apps/mail/api/settings/allownewaccounts')
	const data = {
		allowed,
	}
	return axios.post(url, data).then((resp) => resp.data)
}
