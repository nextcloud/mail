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
import { showError, showUndo } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { html, plain } from '../../util/text'
import { UNDO_DELAY } from '../constants'

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

	/**
	 * Send an outbox message right now.
	 *
	 * @param {object} store Vuex destructuring object
	 * @param {Function} store.commit Vuex commit object
	 * @param {object} store.getters Vuex getters object
	 * @param {object} data Action data
	 * @param {number} data.id Id of outbox message to send
	 * @param {boolean} data.force Force sending a message even if it has no sendAt timestamp
	 * @return {Promise<boolean>} Resolves to false if sending was skipped
	 */
	async sendMessage({ commit, getters }, { id, force = false }) {
		// Skip if the message has been deleted/undone in the meantime
		const message = getters.getMessage(id)
		logger.debug('Sending message ' + id, { message, force })
		if (!force && (!message || !message.sendAt)) {
			logger.debug('Skipped sending message that was undone')
			return false
		}

		if (message.sendAt * 1000 > new Date().getTime() + UNDO_DELAY) {
			logger.debug('Skipped sending message that is scheduled for the future')
			return false
		}

		try {
			await OutboxService.sendMessage(id)
			logger.debug(`Outbox message ${id} sent`)
		} catch (error) {
			logger.error(`Failed to send message ${id} from outbox`, { error })
			throw error
		}

		commit('deleteMessage', { id })
		return true
	},

	/**
	 * Wait for UNDO_DELAY before sending the message and show a toast with an undo action.
	 *
	 * @param {object} store Vuex destructuring object
	 * @param {Function} store.dispatch Vuex dispatch object
	 * @param {object} store.getters Vuex getters object
	 * @param {object} data Action data
	 * @param {number} data.id Id of outbox message to send
	 * @return {Promise<boolean>} Resolves to false if sending was skipped. Resolves after UNDO_DELAY has elapsed and the message dispatch was triggered. Warning: This might take a long time, depending on UNDO_DELAY.
	 */
	async sendMessageWithUndo({ getters, dispatch }, { id }) {
		return new Promise((resolve, reject) => {
			const message = getters.getMessage(id)

			showUndo(
				t('mail', 'Message sent'),
				async () => {
					logger.info('Attempting to stop sending message ' + message.id)
					const stopped = await dispatch('stopMessage', { message })
					logger.info('Message ' + message.id + ' stopped', { message: stopped })
					await dispatch('startComposerSession', {
						type: 'outbox',
						data: {
							...message,
							// The composer expects rich body data and not just a string
							body: message.isHtml ? html(message.body) : plain(message.body),
						},
					}, { root: true })
				}, {
					timeout: UNDO_DELAY,
					close: true,
				}
			)

			setTimeout(async () => {
				try {
					const wasSent = await dispatch('sendMessage', { id: message.id })
					resolve(wasSent)
				} catch (error) {
					showError(t('mail', 'Could not send message'))
					logger.error('Could not delay-send message ' + message.id, { message })
					reject(error)
				}
			}, UNDO_DELAY)
		})
	},
}
