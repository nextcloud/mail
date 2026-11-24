/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError, showSuccess } from '@nextcloud/dialogs'
import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Envelope from '../../../components/Envelope.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...await importOriginal(),
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))

const $route = {
	params: {
		id: 1,
	},
}

describe('Envelope', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()

		store.accountsUnmapped[123] = { sentMailboxId: '1', archiveMailboxId: 99 }
	})
	it('allows toggling seen flag without ACLs', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('disallows toggling seen flag without s ACL right', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasSeenAcl).toBe(false)
	})

	it('allows toggling seen flag with s ACL right', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})
	it('allows toggling archive action without ACLs', () => {
		store.accountsUnmapped[123].archiveMailboxId = '4'
		store.mailboxes['4'] = { databaseId: '4', myAcls: undefined }

		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has i ACLs for archiving', () => {
		store.accountsUnmapped[123].archiveMailboxId = '4'
		store.mailboxes['4'] = { databaseId: '4', myAcls: 'i' }

		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has no ACLs for archiving', () => {
		store.accountsUnmapped[123].archiveMailboxId = '4'
		store.mailboxes['4'] = { databaseId: '4', myAcls: undefined }

		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has no acls and archive mailbox has i ACL for archiving', () => {
		store.accountsUnmapped[123].archiveMailboxId = '4'
		store.mailboxes['4'] = { databaseId: '4', myAcls: 'i' }

		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('disallows toggling archive action without i ACL right', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasArchiveAcl).toBe(false)
	})

	it('allows toggling delete action without ACLs', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('disallows toggling delete action without x ACL right', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasDeleteAcl).toBe(false)
	})
	it('allows toggling delete action with te ACL right', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('allows toggling favorite, important and spam action with w ACL right', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasWriteAcl).toBe(true)
	})
	it('allows toggling favorite, important and spam action without w ACL right', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasWriteAcl).toBe(false)
	})
	it('allows toggling favorite, important and spam action without ACL right', () => {
		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		expect(view.vm.hasWriteAcl).toBe(true)
	})

	it('hides the tags of server keywords', () => {
		store.tags = {
			1: { id: 1, imapLabel: '$hasattachment', displayName: '$hasattachment' },
			2: { id: 2, imapLabel: '$hasnoattachment', displayName: '$hasnoattachment' },
			3: { id: 3, imapLabel: '$hasnoattachment', displayName: 'Hasnoattachment' },
			4: { id: 4, imapLabel: 'work', displayName: 'Work' },
		}
		store.envelopes[42] = { databaseId: 42, tags: [1, 2, 3, 4] }

		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
				data: {
					databaseId: 42,
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
		})

		expect(view.vm.tags.map((tag) => tag.displayName)).toEqual(['Work'])
	})

	it('removes the window resize listener on unmount', () => {
		const addSpy = vi.spyOn(window, 'addEventListener')
		const removeSpy = vi.spyOn(window, 'removeEventListener')

		const view = shallowMount(Envelope, {
			global: {
				mixins: [Nextcloud],
				mocks: {
					$route,
				},
			},
			props: {
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
		})

		const [, handler] = addSpy.mock.calls.find(([name]) => name === 'resize')
		view.unmount()

		expect(removeSpy).toHaveBeenCalledWith('resize', handler)

		addSpy.mockRestore()
		removeSpy.mockRestore()
	})

	describe('unread state of a thread row', () => {
		beforeEach(() => {
			vi.clearAllMocks()
		})

		const mountRow = (data, layout = 'threaded') => {
			store.preferences['layout-message-view'] = layout
			return shallowMount(Envelope, {
				global: {
					mixins: [Nextcloud],
					mocks: {
						$route,
					},
				},
				props: {
					data: {
						accountId: 123,
						databaseId: 3,
						threadRootId: 'root',
						from: [{ email: 'info@test.com' }],
						flags: { seen: true, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
						...data,
					},
					mailbox: { specialRole: '', databaseId: '3', myAcls: undefined },
				},
			})
		}

		it('is unread when the shown message is unread', () => {
			const view = mountRow({ flags: { seen: false } })

			expect(view.vm.isUnread).toBe(true)
		})

		it('is read when every message of the thread is read', () => {
			const view = mountRow({ threadHasUnread: false })

			expect(view.vm.isUnread).toBe(false)
		})

		it('is unread when an older message of the thread is unread', () => {
			const view = mountRow({ threadHasUnread: true })

			expect(view.vm.isUnread).toBe(true)
		})

		it('ignores the thread state in the singleton layout', () => {
			const view = mountRow({ threadHasUnread: true }, 'singleton')

			expect(view.vm.isUnread).toBe(false)
		})

		it('loads the thread before marking a thread with an unread older message as read', async () => {
			const row = { accountId: 123, databaseId: 3, threadRootId: 'root', flags: { seen: true }, threadHasUnread: true }
			const older = { accountId: 123, databaseId: 1, threadRootId: 'root', flags: { seen: false } }
			store.envelopes = { 1: older, 3: row }
			store.fetchThread = vi.fn().mockResolvedValue([])
			store.toggleEnvelopeSeen = vi.fn()
			const view = mountRow(row)

			await view.vm.onToggleSeen()

			expect(store.fetchThread).toHaveBeenCalledWith(3)
			expect(store.toggleEnvelopeSeen).toHaveBeenCalledWith({ envelope: older, seen: true })
			expect(store.toggleEnvelopeSeen).toHaveBeenCalledWith({ envelope: row, seen: true })
		})

		it('marks a fully read thread as unread without loading it', async () => {
			const row = { accountId: 123, databaseId: 3, threadRootId: 'root', flags: { seen: true }, threadHasUnread: false }
			store.envelopes = { 3: row }
			store.fetchThread = vi.fn()
			store.toggleEnvelopeSeen = vi.fn()
			const view = mountRow(row)

			await view.vm.onToggleSeen()

			expect(store.fetchThread).not.toHaveBeenCalled()
			expect(store.toggleEnvelopeSeen).toHaveBeenCalledWith({ envelope: row, seen: false })
		})

		it('marks nothing and reports an error when the thread cannot be loaded', async () => {
			const row = { accountId: 123, databaseId: 3, threadRootId: 'root', flags: { seen: true }, threadHasUnread: true }
			store.envelopes = { 3: row }
			store.fetchThread = vi.fn().mockRejectedValue(new Error('offline'))
			store.toggleEnvelopeSeen = vi.fn()
			const view = mountRow(row)

			await view.vm.onToggleSeen()

			expect(store.toggleEnvelopeSeen).not.toHaveBeenCalled()
			expect(showError).toHaveBeenCalled()
		})

		it('reloads an already loaded thread before marking it as read', async () => {
			const row = { accountId: 123, databaseId: 3, mailboxId: 7, threadRootId: 'root', flags: { seen: true }, threadHasUnread: true, thread: [1, 3] }
			store.envelopes = { 1: { accountId: 123, databaseId: 1, mailboxId: 7, threadRootId: 'root', flags: { seen: false } }, 3: row }
			store.fetchThread = vi.fn().mockResolvedValue([])
			store.toggleEnvelopeSeen = vi.fn()
			const view = mountRow(row)

			await view.vm.onToggleSeen()

			expect(store.fetchThread).toHaveBeenCalledWith(3)
		})

		it('only marks messages of the same mailbox that are not deleted', async () => {
			const row = { accountId: 123, databaseId: 3, mailboxId: 7, threadRootId: 'root', flags: { seen: true }, threadHasUnread: true }
			const older = { accountId: 123, databaseId: 1, mailboxId: 7, threadRootId: 'root', flags: { seen: false } }
			const sent = { accountId: 123, databaseId: 2, mailboxId: 8, threadRootId: 'root', flags: { seen: false } }
			const deleted = { accountId: 123, databaseId: 4, mailboxId: 7, threadRootId: 'root', flags: { seen: false, deleted: true } }
			store.envelopes = { 1: older, 2: sent, 3: row, 4: deleted }
			store.fetchThread = vi.fn().mockResolvedValue([])
			store.toggleEnvelopeSeen = vi.fn()
			const view = mountRow(row)

			await view.vm.onToggleSeen()

			expect(store.toggleEnvelopeSeen).toHaveBeenCalledTimes(2)
			expect(store.toggleEnvelopeSeen).toHaveBeenCalledWith({ envelope: older, seen: true })
			expect(store.toggleEnvelopeSeen).toHaveBeenCalledWith({ envelope: row, seen: true })
		})

		const quickAction = (...names) => ({ actionSteps: names.map((name) => ({ name })) })

		it('does not mark a thread read when the mark as unread quick action runs on a read row with an unread thread', async () => {
			const row = { accountId: 123, databaseId: 3, threadRootId: 'root', flags: { seen: true }, threadHasUnread: true }
			store.envelopes = { 3: row }
			store.toggleEnvelopeSeen = vi.fn()
			const view = mountRow(row)

			await view.vm.executeQuickAction(quickAction('markAsUnread'))

			expect(store.toggleEnvelopeSeen).not.toHaveBeenCalled()
		})

		it('marks a thread read with the mark as read quick action when only an older message is unread', async () => {
			const row = { accountId: 123, databaseId: 3, threadRootId: 'root', flags: { seen: true }, threadHasUnread: true }
			store.envelopes = { 3: row }
			store.fetchThread = vi.fn().mockResolvedValue([])
			store.toggleEnvelopeSeen = vi.fn()
			const view = mountRow(row)

			await view.vm.executeQuickAction(quickAction('markAsRead'))

			expect(store.toggleEnvelopeSeen).toHaveBeenCalledWith({ envelope: row, seen: true })
			expect(showSuccess).toHaveBeenCalled()
		})

		it('reports a failed thread load of a quick action instead of success', async () => {
			const row = { accountId: 123, databaseId: 3, threadRootId: 'root', flags: { seen: true }, threadHasUnread: true }
			store.envelopes = { 3: row }
			store.fetchThread = vi.fn().mockRejectedValue(new Error('offline'))
			store.toggleEnvelopeSeen = vi.fn()
			const view = mountRow(row)

			await view.vm.executeQuickAction(quickAction('markAsRead', 'markAsUnread'))

			expect(store.toggleEnvelopeSeen).not.toHaveBeenCalled()
			expect(showError).toHaveBeenCalled()
			expect(showSuccess).not.toHaveBeenCalled()
		})

		it('waits for marking as read before running the next quick action step', async () => {
			const row = { accountId: 123, databaseId: 3, threadRootId: 'root', flags: { seen: false }, threadHasUnread: true }
			store.envelopes = { 3: row }
			const calls = []
			const view = mountRow(row)
			store.fetchThread = vi.fn().mockResolvedValue([])
			store.toggleEnvelopeSeen = vi.fn(async ({ seen }) => {
				await Promise.resolve()
				row.flags.seen = seen
				await view.setProps({ data: { ...view.props('data'), flags: { seen }, threadHasUnread: !seen } })
				calls.push(seen)
			})

			await view.vm.executeQuickAction(quickAction('markAsRead', 'markAsUnread'))

			expect(calls).toEqual([true, false])
		})

		it('toggles only the shown message in the singleton layout', async () => {
			const row = { accountId: 123, databaseId: 3, threadRootId: 'root', flags: { seen: true } }
			store.envelopes = { 3: row, 1: { accountId: 123, databaseId: 1, threadRootId: 'root', flags: { seen: false } } }
			store.toggleEnvelopeSeen = vi.fn()
			const view = mountRow(row, 'singleton')

			await view.vm.onToggleSeen()

			expect(store.toggleEnvelopeSeen).toHaveBeenCalledTimes(1)
			expect(store.toggleEnvelopeSeen).toHaveBeenCalledWith({ envelope: view.vm.data, seen: false })
		})
	})
})
