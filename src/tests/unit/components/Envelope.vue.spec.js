/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Envelope from '../../../components/Envelope.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

const localVue = createLocalVue()
const $route = {
	params: {
		id: 1,
	},
}

const pinia = createPinia()

localVue.mixin(Nextcloud)

describe('Envelope', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()

		store.accountsUnmapped[123] = { sentMailboxId: '1' }
	})
	it('allows toggling seen flag without ACLs', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
				account: { sentMailboxId: '1' },
				mailbox: {
					myAcls: undefined,
					databaseId: '3',
					specialRole: '',
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('disallows toggling seen flag without s ACL right', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: 'x',
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			store,
			pinia,
			localVue,
		})

		expect(view.vm.hasSeenAcl).toBe(false)
	})

	it('allows toggling seen flag with s ACL right', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: 's',
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})
	it('allows toggling archive action without ACLs', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: undefined,
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			computed: {
				archiveMailbox() {
					return { myAcls: undefined }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has i ACLs for archiving', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: 'te',
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			computed: {
				archiveMailbox() {
					return { myAcls: 'i' }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has no ACLs for archiving', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: 'te',
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			computed: {
				archiveMailbox() {
					return { myAcls: undefined }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has no acls and archive mailbox has i ACL for archiving', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: undefined,
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			computed: {
				archiveMailbox() {
					return { myAcls: 'i' }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('disallows toggling archive action without i ACL right', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: 'x',
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasArchiveAcl).toBe(false)
	})

	it('allows toggling delete action without ACLs', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: undefined,
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },

				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('disallows toggling delete action without x ACL right', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: 's',
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },

				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasDeleteAcl).toBe(false)
	})
	it('allows toggling delete action with te ACL right', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					sentMailboxId: '1',
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('allows toggling favorite, important and spam action with w ACL right', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: 'w',
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasWriteAcl).toBe(true)
	})
	it('allows toggling favorite, important and spam action without w ACL right', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: 's',
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasWriteAcl).toBe(false)
	})
	it('allows toggling favorite, important and spam action without ACL right', () => {
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					specialRole: '',
					databaseId: '3',
					myAcls: undefined,
				},
				data: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasWriteAcl).toBe(true)
	})
})

describe('Envelope junk state', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()

		store.accountsUnmapped[123] = { id: 123, sentMailboxId: '1', archiveMailboxId: 99, junkMailboxId: 10, mailboxes: [] }
		store.mailboxes[3] = { databaseId: 3, specialRole: 'inbox', myAcls: undefined }
		store.mailboxes[5] = { databaseId: 5, specialRole: 'junk', myAcls: undefined }
		store.toggleEnvelopeJunk = vi.fn()
		store.moveEnvelopeToJunk = vi.fn().mockResolvedValue(true)
	})

	const mountInMailbox = (mailboxId, flags = {}) => {
		return shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					myAcls: undefined,
					databaseId: mailboxId,
				},
				data: {
					databaseId: 1,
					accountId: 123,
					mailboxId,
					threadRootId: 'thread',
					dateInt: 1,
					from: [{ email: 'info@test.com' }],
					flags: { seen: true, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false, ...flags },
				},
			},
			store,
			localVue,
		})
	}

	it('treats an unflagged message in the junk mailbox as junk', () => {
		const view = mountInMailbox(5)

		expect(view.vm.isJunk).toBe(true)
	})

	it('does not treat an unflagged message in the inbox as junk', () => {
		const view = mountInMailbox(3)

		expect(view.vm.isJunk).toBe(false)
	})

	it('treats a flagged message outside the junk mailbox as junk', () => {
		const view = mountInMailbox(3, { $junk: true })

		expect(view.vm.isJunk).toBe(true)
	})

	it('does not treat a message with $notjunk in the junk mailbox as junk', () => {
		const view = mountInMailbox(5, { $notjunk: true })

		expect(view.vm.isJunk).toBe(false)
	})

	it('does not toggle an unflagged junk-folder message from the quick action', async () => {
		const view = mountInMailbox(5)

		await view.vm.executeQuickAction({ actionSteps: [{ name: 'markAsSpam' }] })

		expect(store.toggleEnvelopeJunk).not.toHaveBeenCalled()
	})

	it('marks only the non-junk siblings of a thread as spam from the quick action', async () => {
		const root = {
			databaseId: 1,
			accountId: 123,
			mailboxId: 5,
			threadRootId: 'thread',
			dateInt: 1,
			from: [{ email: 'info@test.com' }],
			flags: { seen: true, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
		}
		const sibling = { ...root, databaseId: 2, mailboxId: 3 }
		store.getEnvelopesByThreadRootId = vi.fn().mockReturnValue([root, sibling])
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				mailbox: {
					myAcls: undefined,
					databaseId: 5,
				},
				data: root,
			},
			store,
			localVue,
		})

		await view.vm.executeQuickAction({ actionSteps: [{ name: 'markAsSpam' }] })

		await vi.waitFor(() => expect(store.toggleEnvelopeJunk).toHaveBeenCalledWith({ envelope: sibling, removeEnvelope: true }))
		expect(store.toggleEnvelopeJunk).toHaveBeenCalledTimes(1)
	})
})
