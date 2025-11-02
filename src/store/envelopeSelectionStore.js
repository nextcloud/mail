/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError, showSuccess, showUndo } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { defineStore } from 'pinia'
import Vue from 'vue'
import logger from '../logger.js'
import * as OutboxService from '../service/OutboxService.js'
import { UNDO_DELAY } from './constants.js'
import useMainStore from './mainStore.js'

export default defineStore('outbox', {
	state: () => {
		return {
			selection: [],
			showMoveModal: false,
			showTagModal: false,
			lastToggledIndex: undefined,
			defaultView: false,
			showQuickActionsSettings: false,
			mainStore: useMainStore(),
			envelopes: [],
			skipTransition: false,
			searchQuery: '',
		}
	},
	getters: {
		sortOrder() {
			return this.mainStore.getPreference('sort-order', 'newest')
		},

		sortedEnvelops() {
			if (this.sortOrder === 'oldest') {
				return [...this.envelopes].sort((a, b) => {
					return a.dateInt < b.dateInt ? -1 : 1
				})
			}
			return [...this.envelopes]
		},

		selectMode() {
			// returns true when in selection mode (where the user selects several emails at once)
			return this.selection.length > 0
		},

		isAtLeastOneSelectedRead() {
			return this.selectedEnvelopes.some((env) => env.flags.seen === true)
		},

		isAtLeastOneSelectedUnread() {
			return this.selectedEnvelopes.some((env) => env.flags.seen === false)
		},

		isAtLeastOneSelectedImportant() {
			// returns true if at least one selected message is marked as important
			return this.selectedEnvelopes.some((env) => {
				return this.mainStore
					.getEnvelopeTags(env.databaseId)
					.some((tag) => tag.imapLabel === '$label1')
			})
		},

		isAtLeastOneSelectedUnimportant() {
			// returns true if at least one selected message is not marked as important
			return this.selectedEnvelopes.some((env) => {
				return !this.mainStore
					.getEnvelopeTags(env.databaseId)
					.some((tag) => tag.imapLabel === '$label1')
			})
		},

		isAtLeastOneSelectedJunk() {
			// returns true if at least one selected message is marked as junk
			return this.selectedEnvelopes.some((env) => {
				return env.flags.$junk
			})
		},

		isAtLeastOneSelectedNotJunk() {
			// returns true if at least one selected message is not marked as not junk
			return this.selectedEnvelopes.some((env) => {
				return !env.flags.$junk
			})
		},

		isAtLeastOneSelectedFavorite() {
			return this.selectedEnvelopes.some((env) => env.flags.flagged)
		},

		isAtLeastOneSelectedUnFavorite() {
			return this.selectedEnvelopes.some((env) => !env.flags.flagged)
		},

		selectedEnvelopes() {
			return this.sortedEnvelops.filter((env) => this.selection.includes(env.databaseId))
		},

		hasMultipleAccounts() {
			const mailboxIds = this.sortedEnvelops.map((envelope) => envelope.mailboxId)
			return Array.from(new Set(mailboxIds)).length > 1
		},

		listTransitionName() {
			return this.skipTransition ? 'disabled' : 'list'
		},
	},
	actions: {
		isEnvelopeSelected(idx) {
			if (this.selection.length === 0) {
				return false
			}

			return this.selection.includes(idx)
		},

		markSelectedRead() {
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.toggleEnvelopeSeen({
					envelope,
					seen: true,
				})
			})
			this.unselectAll()
		},

		markSelectedUnread() {
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.toggleEnvelopeSeen({
					envelope,
					seen: false,
				})
			})
			this.unselectAll()
		},

		markSelectionImportant() {
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.markEnvelopeImportantOrUnimportant({
					envelope,
					addTag: true,
				})
			})
			this.unselectAll()
		},

		markSelectionUnimportant() {
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.markEnvelopeImportantOrUnimportant({
					envelope,
					addTag: false,
				})
			})
			this.unselectAll()
		},

		async markSelectionJunk() {
			for (const envelope of this.selectedEnvelopes) {
				if (!envelope.flags.$junk) {
					await this.mainStore.toggleEnvelopeJunk({
						envelope,
						removeEnvelope: await this.mainStore.moveEnvelopeToJunk(envelope),
					})
				}
			}
			this.unselectAll()
		},

		async markSelectionNotJunk() {
			for (const envelope of this.selectedEnvelopes) {
				if (envelope.flags.$junk) {
					await this.mainStore.toggleEnvelopeJunk({
						envelope,
						removeEnvelope: await this.mainStore.moveEnvelopeToJunk(envelope),
					})
				}
			}
			this.unselectAll()
		},

		favoriteAll() {
			const favFlag = !this.isAtLeastOneSelectedUnFavorite
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.markEnvelopeFavoriteOrUnfavorite({
					envelope,
					favFlag,
				})
			})
			this.unselectAll()
		},

		unFavoriteAll() {
			const favFlag = !this.isAtLeastOneSelectedFavorite
			this.selectedEnvelopes.forEach((envelope) => {
				this.mainStore.markEnvelopeFavoriteOrUnfavorite({
					envelope,
					favFlag,
				})
			})
			this.unselectAll()
		},

		async deleteAllSelected() {
			let nextEnvelopeToNavigate
			let isAllSelected

			if (this.selectedEnvelopes.length === this.sortedEnvelops.length) {
				isAllSelected = true
			} else {
				const indexSelectedEnvelope = this.selectedEnvelopes.findIndex((selectedEnvelope) => selectedEnvelope.databaseId === this.$route.params.threadId)

				// one of threads is selected
				if (indexSelectedEnvelope !== -1) {
					const lastSelectedEnvelope = this.selectedEnvelopes[this.selectedEnvelopes.length - 1]
					const diff = this.sortedEnvelops.filter((envelope) => envelope === lastSelectedEnvelope || !this.selectedEnvelopes.includes(envelope))
					const lastIndex = diff.indexOf(lastSelectedEnvelope)
					nextEnvelopeToNavigate = diff[lastIndex === 0 ? 1 : lastIndex - 1]
				}
			}

			await Promise.all(this.selectedEnvelopes.map(async (envelope) => {
				logger.info(`deleting thread ${envelope.threadRootId}`)
				await this.mainStore.deleteThread({
					envelope,
				})
			})).catch(async (error) => {
				showError(await matchError(error, {
					[NoTrashMailboxConfiguredError.getName()]() {
						return t('mail', 'No trash folder configured')
					},
					default(error) {
						logger.error('could not delete message', error)
						return t('mail', 'Could not delete message')
					},
				}))
			})
			if (nextEnvelopeToNavigate) {
				await this.$router.push({
					name: 'message',
					params: {
						mailboxId: this.$route.params.mailboxId,
						threadId: nextEnvelopeToNavigate.databaseId,
					},
				})

				// Get new messages
				await this.mainStore.fetchNextEnvelopes({
					mailboxId: this.mailbox.databaseId,
					query: this.searchQuery,
					quantity: this.selectedEnvelopes.length,
				})
			} else if (isAllSelected) {
				await this.$router.push({
					name: 'mailbox',
					params: {
						mailboxId: this.$route.params.mailboxId,
					},
				})
			}
			this.unselectAll()
		},

		setEnvelopeSelected(envelope, selected) {
			const alreadySelected = this.selection.includes(envelope.databaseId)
			if (selected && !alreadySelected) {
				envelope.flags.selected = true
				this.selection.push(envelope.databaseId)
			} else if (!selected && alreadySelected) {
				envelope.flags.selected = false
				this.selection.splice(this.selection.indexOf(envelope.databaseId), 1)
			}
		},

		onEnvelopeSelectToggle(envelope, index, selected) {
			this.lastToggledIndex = index
			this.setEnvelopeSelected(envelope, selected)
		},

		onEnvelopeSelectMultiple(envelope, index) {
			const lastToggledIndex = this.lastToggledIndex
				?? this.findSelectionIndex(parseInt(this.$route.params.threadId))
				?? undefined
			if (lastToggledIndex === undefined) {
				return
			}

			const start = Math.min(lastToggledIndex, index)
			const end = Math.max(lastToggledIndex, index)
			const selected = this.selection.includes(envelope.databaseId)
			for (let i = start; i <= end; i++) {
				this.setEnvelopeSelected(this.sortedEnvelops[i], !selected)
			}
			this.lastToggledIndex = index
		},

		unselectAll() {
			this.sortedEnvelops.forEach((env) => {
				env.flags.selected = false
			})
			this.selection = []
		},

		onOpenMoveModal() {
			this.showMoveModal = true
		},

		onOpenTagModal() {
			this.showTagModal = true
		},

		onCloseTagModal() {
			this.showTagModal = false
		},

		async forwardSelectedAsAttachment() {
			await this.mainStore.startComposerSession({
				forwardedMessages: [...this.selection],
			})
			this.unselectAll()
		},

		onCloseMoveModal() {
			this.showMoveModal = false
			this.unselectAll()
		},

		/**
		 * Find the envelope list index of a given envelope's database id.
		 *
		 * @param {number} databaseId of the given envelope
		 * @return {number|undefined} Index or undefined if not found in the envelope list
		 */
		findSelectionIndex(databaseId) {
			for (const [index, envelope] of this.sortedEnvelops.entries()) {
				if (envelope.databaseId === databaseId) {
					return index
				}
			}

			return undefined
		},
	},
})
