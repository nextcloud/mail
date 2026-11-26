/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Envelope from '../../../components/Envelope.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

vi.mock('@nextcloud/dialogs', async (importOriginal) => ({
	...(await importOriginal()),
	showSuccess: vi.fn(),
	showWarning: vi.fn(),
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

	describe('junk state', () => {
		const mountWithMailbox = (mailboxId, flags) => {
			return shallowMount(Envelope, {
				global: {
					mixins: [Nextcloud],
					mocks: {
						$route,
					},
				},
				props: {
					mailbox: {
						myAcls: undefined,
						databaseId: mailboxId,
					},
					data: {
						accountId: 123,
						mailboxId,
						from: [{ email: 'info@test.com' }],
						flags: { seen: false, flagged: false, answered: false, hasAttachments: false, draft: false, ...flags },
					},
				},
			})
		}

		it('treats an unflagged message in the junk mailbox as junk', () => {
			store.mailboxes[5] = { databaseId: 5, specialRole: 'junk' }

			const view = mountWithMailbox(5, { $junk: false })

			expect(view.vm.isJunk).toBe(true)
		})

		it('does not treat an unflagged message in the inbox as junk', () => {
			store.mailboxes[3] = { databaseId: 3, specialRole: 'inbox' }

			const view = mountWithMailbox(3, { $junk: false })

			expect(view.vm.isJunk).toBe(false)
		})

		it('treats a flagged message outside the junk mailbox as junk', () => {
			store.mailboxes[3] = { databaseId: 3, specialRole: 'inbox' }

			const view = mountWithMailbox(3, { $junk: true })

			expect(view.vm.isJunk).toBe(true)
		})

		it('does not toggle an unflagged junk-folder message from the quick action', async () => {
			store.preferences['layout-message-view'] = 'list'
			store.mailboxes[5] = { databaseId: 5, specialRole: 'junk', myAcls: undefined }
			store.toggleEnvelopeJunk = vi.fn()
			const view = mountWithMailbox(5, { $junk: false, seen: true })

			await view.vm.executeQuickAction({ actionSteps: [{ name: 'markAsSpam' }] })

			expect(store.toggleEnvelopeJunk).not.toHaveBeenCalled()
		})

		it('marks only the unflagged siblings of a thread root that is in the junk folder', async () => {
			store.preferences['layout-message-view'] = 'threaded'
			store.accountsUnmapped[123] = { id: 123, sentMailboxId: '1', archiveMailboxId: 99, junkMailboxId: 10, mailboxes: [] }
			store.mailboxes[5] = { databaseId: 5, specialRole: 'junk', myAcls: undefined }
			store.mailboxes[3] = { databaseId: 3, specialRole: 'inbox', myAcls: undefined }
			const flags = { seen: true, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false }
			const root = { databaseId: 1, accountId: 123, mailboxId: 5, threadRootId: 'thread', dateInt: 1, from: [{ email: 'info@test.com' }], flags }
			const sibling = { ...root, databaseId: 2, mailboxId: 3 }
			store.getEnvelopesByThreadRootId = vi.fn().mockReturnValue([root, sibling])
			store.toggleEnvelopeJunk = vi.fn()
			const view = shallowMount(Envelope, {
				global: {
					mixins: [Nextcloud],
					mocks: {
						$route,
					},
				},
				props: {
					mailbox: { myAcls: undefined, databaseId: 5 },
					data: root,
				},
			})

			await view.vm.executeQuickAction({ actionSteps: [{ name: 'markAsSpam' }] })

			await vi.waitFor(() => expect(store.toggleEnvelopeJunk).toHaveBeenCalledWith({ envelope: sibling, removeEnvelope: true }))
			expect(store.toggleEnvelopeJunk).toHaveBeenCalledTimes(1)
		})

		it('toggles an unflagged junk-folder message from the menu', async () => {
			store.accountsUnmapped[123] = { id: 123, sentMailboxId: '1', archiveMailboxId: 99, junkMailboxId: 10, mailboxes: [] }
			store.mailboxes[5] = { databaseId: 5, specialRole: 'junk', myAcls: undefined }
			store.toggleEnvelopeJunk = vi.fn()
			const view = mountWithMailbox(5, { $junk: false, seen: true })

			await view.vm.onToggleJunk()

			expect(store.toggleEnvelopeJunk).toHaveBeenCalledWith({ envelope: view.props('data'), removeEnvelope: false })
		})
	})

	describe('account label', () => {
		const mountEnvelope = (mailbox) => shallowMount(Envelope, {
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
					databaseId: '3',
					specialRole: '',
					...mailbox,
				},
			},
		})

		it('shows the label of the account in a combined mailbox', () => {
			store.accountsUnmapped[123] = { sentMailboxId: '1', emailAddress: 'jane@example.com', unifiedLabel: 'Work', unifiedLabelColor: '#0082c9' }

			const view = mountEnvelope({ isUnified: true })

			expect(view.vm.accountLabel).toMatchObject({ text: 'Work', color: '#0082c9', description: 'Received in jane@example.com' })
			expect(view.vm.accountLabel.textColor).toContain('#0082c9')
			expect(view.vm.accountLabel.textColor).not.toBe('#0082c9')
		})

		it('shows the label of the account in the priority inbox', () => {
			store.accountsUnmapped[123] = { sentMailboxId: '1', emailAddress: 'jane@example.com', unifiedLabel: 'Work', unifiedLabelColor: '#0082c9' }

			const view = mountEnvelope({ isPriorityInbox: true })

			expect(view.vm.accountLabel).toMatchObject({ text: 'Work' })
		})

		it('updates the label when the account label changes', async () => {
			store.addAccountMutation({ id: 123, accountId: 123, sentMailboxId: '1', emailAddress: 'jane@example.com', mailboxes: [], aliases: [] })
			const view = mountEnvelope({ isUnified: true })
			const before = view.vm.accountLabel

			store.accountsUnmapped[123].unifiedLabel = 'Work'
			await view.vm.$nextTick()

			expect(before).toBeNull()
			expect(view.vm.accountLabel).toMatchObject({ text: 'Work' })
		})

		it('does not show the label outside combined mailboxes', () => {
			store.accountsUnmapped[123] = { sentMailboxId: '1', emailAddress: 'jane@example.com', unifiedLabel: 'Work', unifiedLabelColor: '#0082c9' }

			const view = mountEnvelope({ isUnified: false })

			expect(view.vm.accountLabel).toBeNull()
		})

		it('shows nothing when the account has no label and no color', () => {
			store.accountsUnmapped[123] = { sentMailboxId: '1', emailAddress: 'jane@example.com', unifiedLabel: '', unifiedLabelColor: '' }

			const view = mountEnvelope({ isUnified: true })

			expect(view.vm.accountLabel).toBeNull()
		})

		it('keeps the color without a label, shown as a dot', () => {
			store.accountsUnmapped[123] = { sentMailboxId: '1', emailAddress: 'jane@example.com', unifiedLabel: '', unifiedLabelColor: '#0082c9' }

			const view = mountEnvelope({ isUnified: true })

			expect(view.vm.accountLabel).toMatchObject({ text: '', color: '#0082c9' })
		})
	})
})
