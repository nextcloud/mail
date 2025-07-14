/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import Vue from 'vue'

import * as OutboxService from '../service/OutboxService.js'
import logger from '../logger.js'
import { showError, showSuccess, showUndo } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { UNDO_DELAY } from './constants.js'
import useMainStore from './mainStore.js'

export default defineStore('outbox', {
	state: () => {
		return {
			messageList: [],
			messages: {},
			mainStore: useMainStore(),
		}
	},
	getters: {
		getAllMessages: (state) => state.messageList.map(id => state.messages[id]),
	},
	actions: {
		getMessage(id) {
			return this.messages[id]
		},

		addMessageMutation({ message }) {
			const existing = this.messages[message.id] ?? {}
			Vue.set(this.messages, message.id, Object.assign({}, existing, message))
			// Add the message only if it's new
			if (this.messageList.indexOf(message.id) === -1) {
				this.messageList.unshift(message.id)
			}
		},

		deleteMessageMutation({ id }) {
			this.messageList = this.messageList.filter(i => i !== id)
			Vue.delete(this.messages, id)
		},

		stopMessageMutation({ message }) {
			Vue.delete(message, 'sendAt')
		},

		updateMessageMutation({ message }) {
			const existing = this.messages[message.id] ?? {}
			Vue.set(this.messages, message.id, Object.assign(existing, message))
			// Add the message only if it's new
			if (this.messageList.indexOf(message.id) === -1) {
				this.messageList.unshift(message.id)
			}
		},

		async fetchMessages() {
			const existingMessageIds = this.getAllMessages.map(msg => msg.id)
			const { messages } = await OutboxService.fetchMessages()

			for (const message of messages) {
				if (existingMessageIds.indexOf(message.id) === -1) {
					this.addMessageMutation({ message })
				} else {
					this.updateMessageMutation({ message })
				}
			}

			for (const existingMessageId of existingMessageIds) {
				if (!messages.find(msg => msg.id === existingMessageId)) {
					this.deleteMessageMutation({ id: existingMessageId })
				}
			}

			return messages
		},

		async deleteMessage({ id }) {
			try {
				await OutboxService.deleteMessage(id)
			} catch (e) {
				if (e.response?.status === 404) {
					// This is fine
				} else {
					throw e
				}
			}
			this.deleteMessageMutation({ id })
		},

		async enqueueMessage({ message }) {
			this.addMessageMutation({ message })

			// Future drafts/sends after an error should go through outbox logic
			this.mainStore.convertComposerMessageToOutboxMutation({ message }, {
				root: true,
			})

			return message
		},

		async enqueueFromDraft({ id, draftMessage }) {
			const message = await OutboxService.enqueueMessageFromDraft(id, draftMessage)

			this.addMessageMutation({ message })

			// Future drafts/sends after an error should go through outbox logic
			this.mainStore.convertComposerMessageToOutboxMutation({ message }, {
				root: true,
			})

			return message
		},

		async stopMessage({ message }) {
			this.stopMessageMutation({ message })
			const updatedMessage = await OutboxService.updateMessage({
				...message,
				sentAt: undefined,
			}, message.id)
			this.updateMessageMutation({ message: updatedMessage })
			return updatedMessage
		},

		async updateMessage({ message, id }) {
			const updatedMessage = await OutboxService.updateMessage(message, id)
			this.updateMessageMutation({ message: updatedMessage })
			return updatedMessage
		},

		/**
		 * Send an outbox message right now.
		 *
		 * @param {object} data Action data
		 * @param {number} data.id Id of outbox message to send
		 * @param {boolean} data.force Force sending a message even if it has no sendAt timestamp
		 * @return {Promise<boolean>} Resolves to false if sending was skipped
		 */
		async sendMessage({ id, force = false }) {
			// Skip if the message has been deleted/undone in the meantime
			const message = this.getMessage(id)
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
				const m = error.response.data.data[0]
				this.updateMessageMutation({ message: m })
				logger.error(`Failed to send message ${id} from outbox`, { error })
				throw error
			}

			this.deleteMessageMutation({ id })
			return true
		},

		/**
		 * Wait for UNDO_DELAY before sending the message and show a toast with an undo action.
		 *
		 * @param {object} data Action data
		 * @param {number} data.id Id of outbox message to send
		 * @return {Promise<boolean>} Resolves to false if sending was skipped. Resolves after UNDO_DELAY has elapsed and the message dispatch was triggered. Warning: This might take a long time, depending on UNDO_DELAY.
		 */
		async sendMessageWithUndo({ id }) {
			this.mainStore.hideMessageComposerMutation()

			return new Promise((resolve, reject) => {
				const message = this.getMessage(id)

				showUndo(
					t('mail', 'Sending message…'),
					async () => {
						logger.info('Attempting to stop sending message ' + message.id)
						const stopped = await this.stopMessage({ message })
						logger.info('Message ' + message.id + ' stopped', { message: stopped })
						await this.mainStore.startComposerSession({
							type: 'outbox',
							data: { ...message },
						}, { root: true })
					},
					{
						timeout: UNDO_DELAY,
						close: true,
					},
				)

				setTimeout(async () => {
					try {
						const wasSent = await this.sendMessage({ id: message.id, force: false })
						if (wasSent) {
							showSuccess(t('mail', 'Message sent'))
						}
						resolve(wasSent)
					} catch (error) {
						showError(t('mail', 'Could not send message'))
						logger.error('Could not delay-send message ' + message.id, { message })
						reject(error)
					}
				}, UNDO_DELAY)
			})
		},

		/**
		 * "Send" a message
		 * The backend chain will handle the actual copying
		 * We need different toast texts and can do this without UNDO.
		 *
		 * @param {object} data Action data
		 * @param {number} data.id Id of outbox message to send
		 */
		async copyMessageToSentMailbox({ id }) {
			const message = this.getMessage(id)

			try {
				await this.sendMessage({ id: message.id, force: false })
				showSuccess(t('mail', 'Message copied to "Sent" folder'))
			} catch (error) {
				showError(t('mail', 'Could not copy message to "Sent" folder'))
				logger.error('Could not copy message to "Sent" folder ' + message.id, { message })
			}
		},
	},
})
