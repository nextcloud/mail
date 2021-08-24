import { PRIORITY_INBOX_ID, UNIFIED_INBOX_ID } from './constants'
import Vue from 'vue'
import { normalizedEnvelopeListId } from './normalization'
import orderBy from 'lodash/fp/orderBy'

const state = {
	lists: {
		[UNIFIED_INBOX_ID]: {},
		[PRIORITY_INBOX_ID]: {},
	},
}

const mutations = {
	addAccount(state, account) {
		const mailboxes = account.mailboxes || []
		mailboxes.map(mailboxId => Vue.set(state.lists, mailboxId, {}))
	},
	addMailbox(state, { mailbox }) {
		Vue.set(state.lists, mailbox.databaseId, {})
	},
	addThread(state, { mailboxId, query, envelope }) {
		const listId = normalizedEnvelopeListId(query)
		const list = state.lists[mailboxId][listId] || []

		const index = list.findIndex(
			(item) => item.threadRootId === envelope.threadRootId)
		const item = {
			threadRootId: envelope.threadRootId,
			messageId: envelope.databaseId,
			dateInt: envelope.dateInt,
		}

		if (index === -1) {
			list.push(item)
		} else {
			list[index] = item
		}

		orderBy(list, 'dateInt', 'desc')
		Vue.set(state.lists[mailboxId], listId, list)
	},
	removeThread(state, { mailboxId, envelopeId }) {
		const lists = state.lists[mailboxId]
		const removeEnvelopeById = (item) => item.messageId !== envelopeId

		for (let list in lists) {
			list = list.filter(removeEnvelopeById)
		}

		Vue.set(state.lists, mailboxId, lists)
	},
}

const actions = {}

const getters = {
	getThreads: (state, getters, rootState) => (mailboxId, query) => {
		const listId = normalizedEnvelopeListId(query)
		const list = state.lists[mailboxId][listId] || []

		return list.map((item) => rootState.envelopes[item.messageId])
	},
}

export default { state, mutations, actions, getters }
