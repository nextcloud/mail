/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ThreadEnvelope from '../../../components/ThreadEnvelope.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'
import useMainStore from '../../../store/mainStore.js'

describe('ThreadEnvelope', () => {
	let store

	beforeEach(() => {
		setActivePinia(createPinia())

		store = useMainStore()
	})

	it('allows toggling seen flag without ACLs', () => {
		store.mailboxes[3] = { databaseId: 3, myAcls: undefined }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('disallows toggling seen flag without s ACL right', () => {
		store.mailboxes[3] = { databaseId: 3, myAcls: 'x' }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasSeenAcl).toBe(false)
	})

	it('allows toggling seen flag with s ACL right', () => {
		store.mailboxes[3] = { databaseId: 3, myAcls: 's' }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})
	it('allows toggling archive action without ACLs', () => {
		store.accountsUnmapped[123] = { archiveMailboxId: 4 }
		store.mailboxes[3] = { databaseId: 3, myAcls: undefined }
		store.mailboxes[4] = { databaseId: 4, myAcls: undefined }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has i ACLs for archiving', () => {
		store.accountsUnmapped[123] = { archiveMailboxId: 4 }
		store.mailboxes[3] = { databaseId: 3, myAcls: 'te' }
		store.mailboxes[4] = { databaseId: 4, myAcls: 'i' }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has te and archive mailbox has no ACLs for archiving', () => {
		store.accountsUnmapped[123] = { archiveMailboxId: 4 }
		store.mailboxes[3] = { databaseId: 3, myAcls: 'te' }
		store.mailboxes[4] = { databaseId: 4, myAcls: undefined }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('source mailbox has no acls and archive mailbox has i ACL for archiving', () => {
		store.accountsUnmapped[123] = { archiveMailboxId: 4 }
		store.mailboxes[3] = { databaseId: 3, myAcls: undefined }
		store.mailboxes[4] = { databaseId: 4 }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('disallows toggling archive action without w ACL right', () => {
		store.mailboxes[3] = { databaseId: 3, myAcls: 'x' }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasArchiveAcl).toBe(false)
	})

	it('allows toggling delete action without ACLs', () => {
		store.mailboxes[3] = { databaseId: 3, myAcls: undefined }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,

				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('disallows toggling delete action without x ACL right', () => {
		store.mailboxes[3] = { databaseId: 3, myAcls: 's' }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasDeleteAcl).toBe(false)
	})
	it('allows toggling delete action with te ACL right', () => {
		store.mailboxes[3] = { databaseId: 3, myAcls: 'te' }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: { seen: false, flagged: false, $junk: false, answered: false, hasAttachments: false, draft: false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('allows toggling favorite, important and spam action with w ACL right', () => {
		store.mailboxes[3] = { databaseId: 3, myAcls: 'w' }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: {
						seen: false,
						flagged: false,
						$junk: false,
						answered: false,
						hasAttachments: false,
						draft: false,
					},
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasWriteAcl).toBe(true)
	})

	it('allows toggling favorite, important and spam action without w ACL right', () => {
		store.accountsUnmapped[123] = { archiveMailboxId: 4 }
		store.mailboxes[3] = { databaseId: 3, myAcls: 's' }
		store.mailboxes[4] = { databaseId: 4 }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: {
						seen: false,
						flagged: false,
						$junk: false,
						answered: false,
						hasAttachments: false,
						draft: false,
					},
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasWriteAcl).toBe(false)
	})
	it('allows toggling favorite, important and spam action without ACL right', () => {
		store.accountsUnmapped[123] = { archiveMailboxId: 4 }
		store.mailboxes[3] = { databaseId: 3, myAcls: undefined }
		store.mailboxes[4] = { databaseId: 4 }

		const view = shallowMount(ThreadEnvelope, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				mailboxId: 3,
				envelope: {
					accountId: 123,
					from: [{ email: 'info@test.com' }],
					flags: {
						seen: false,
						flagged: false,
						$junk: false,
						answered: false,
						hasAttachments: false,
						draft: false,
					},
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
		})

		expect(view.vm.hasWriteAcl).toBe(true)
	})
})
