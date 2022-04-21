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
	async fetchMessages({ getters, commit }) {
		const existingMessageIds = getters.getAllMessages.map(msg => msg.id)
		const { messages } = await OutboxService.fetchMessages()

		for (const message of messages) {
			if (existingMessageIds.indexOf(message.id) === -1) {
				commit('addMessage', { message })
			} else {
				commit('updateMessage', { message })
			}
		}

		for (const existingMessageId of existingMessageIds) {
			if (!messages.find(msg => msg.id === existingMessageId)) {
				commit('deleteMessage', { id: existingMessageId })
			}
		}

		return messages
	},

	async deleteMessage({ commit }, { id }) {
		try {
			await OutboxService.deleteMessage(id)
		} catch (e) {
			if (e.response?.status === 404) {
				// This is fine
			} else {
				throw e
			}
		}
		commit('deleteMessage', { id })
	},

	async enqueueMessage({ commit }, { message }) {
		message = await OutboxService.enqueueMessage(message)
		commit('addMessage', { message })

		// Future drafts/sends after an error should go through outbox logic
		commit('convertComposerMessageToOutbox', { message }, {
			root: true,
		})

		return message
	},

	async stopMessage({ commit }, { message }) {
		commit('stopMessage', { message })
		const updatedMessage = await OutboxService.updateMessage({
			...message,
			sentAt: undefined,
		}, message.id)
		commit('updateMessage', { message: updatedMessage })
		return updatedMessage
	},

	async updateMessage({ commit }, { message, id }) {
		const updatedMessage = await OutboxService.updateMessage(message, id)
		commit('updateMessage', { message: updatedMessage })
		return updatedMessage
	},

	async sendMessage({ commit, getters }, { id, force = false }) {
		// Skip if the message has been deleted/undone in the meantime
		const message = getters.getMessage(id)
		logger.debug('Sending message ' + id, { message, force })
		if (!force && (!message || !message.sendAt)) {
			logger.debug('Skipped sending message that was undone')
			return
		}

		try {
			await OutboxService.sendMessage(id)
			logger.debug(`Outbox message ${id} sent`)
		} catch (error) {
			logger.error(`Failed to send message ${id} from outbox`, { error })
			throw error
		}

		commit('deleteMessage', { id })
	},
}
