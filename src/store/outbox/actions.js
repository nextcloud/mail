/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import * as OutboxService from '../../service/OutboxService'
import logger from '../../logger'

export default {
	async fetchMessages({ commit }) {
		const { messages } = await OutboxService.fetchMessages()
		for (const message of messages) {
			commit('addMessage', { message })
		}
		return messages
	},

	async deleteMessage({ commit }, { id }) {
		await OutboxService.deleteMessage(id)
		commit('deleteMessage', { id })
	},

	async enqueueMessage({ commit }, { message }) {
		message = await OutboxService.enqueueMessage(message)
		commit('addMessage', { message })
		return message
	},

	async updateMessage({ commit }, { message, id }) {
		const updatedMessage = await OutboxService.updateMessage(message, id)
		commit('updateMessage', { message: updatedMessage })
		return updatedMessage
	},

	async sendMessage({ commit, getters }, { id }) {
		// Skip if the message has been deleted/undone in the meantime
		if (!getters.getMessage(id)) {
			logger.debug('Skipped sending message that was undone')
			return
		}

		try {
			await OutboxService.sendMessage(id)
		} catch (error) {
			logger.error(`Failed to send message ${id} from outbox`)
			return
		}

		commit('deleteMessage', id)
	},
}
