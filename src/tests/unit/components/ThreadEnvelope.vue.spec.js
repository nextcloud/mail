/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {createLocalVue, shallowMount} from '@vue/test-utils'

import Nextcloud from '../../../mixins/Nextcloud.js'
import ThreadEnvelope from '../../../components/ThreadEnvelope.vue'
import Vuex from 'vuex'

const localVue = createLocalVue()

localVue.use(Vuex)
localVue.mixin(Nextcloud)

describe('ThreadEnvelope', () => {
	let actions
	let getters
	let store

	beforeEach(() => {
		actions = {}
		getters = {
			accounts: () => [
				{
					id: 123,
				},
			],
			getAccount: () => (id) => ({}),
			getEnvelopeTags: () => (id) => ([]),
			getMailbox: () => (id) => ({}),
		}
		store = new Vuex.Store({
			actions,
			getters,
		})
	})

	it('allows toggling seen flag without ACLs', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: undefined }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})

	it('disallows toggling seen flag without s ACL right', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: 'x' }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSeenAcl).toBe(false)
	})

	it('allows toggling seen flag with s ACL right', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: 's' }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasSeenAcl).toBe(true)
	})
	it('allows toggling archive action without ACLs', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: undefined }
				},
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
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: 'te' }
				},
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
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: 'te' }
				},
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
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: undefined }
				},
				archiveMailbox() {
					return { }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasArchiveAcl).toBe(true)
	})

	it('disallows toggling archive action without w ACL right', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: 'x' }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasArchiveAcl).toBe(false)
	})

	it('allows toggling delete action without ACLs', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,

				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: undefined }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('disallows toggling delete action without x ACL right', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: 's' }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasDeleteAcl).toBe(false)
	})
	it('allows toggling delete action with te ACL right', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole:'',
				},
				envelope: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false },
					subject: '',
					dateInt: 1692200926180,
				},
				threadSubject: '',
			},
			computed: {
				mailbox() {
					return { myAcls: 'te' }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasDeleteAcl).toBe(true)
	})
	it('allows toggling favorite, important and spam action with w ACL right', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole: '',
				},
				envelope: {
					accountId: 123,
					from: [{email: 'info@test.com'}],
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
			computed: {
				mailbox() {
					return { myAcls: 'w' }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasWriteAcl).toBe(true)
	})

	it('allows toggling favorite, important and spam action without w ACL right', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole: '',
				},
				envelope: {
					accountId: 123,
					from: [{email: 'info@test.com'}],
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
			computed: {
				mailbox() {
					return { myAcls: 's' }
				},
				archiveMailbox() {
					return { }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasWriteAcl).toBe(false)
	})
	it('allows toggling favorite, important and spam action without ACL right', () => {
		const view = shallowMount(ThreadEnvelope, {
			propsData: {
				account: {},
				mailbox: {
					specialRole: '',
				},
				envelope: {
					accountId: 123,
					from: [{email: 'info@test.com'}],
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
			computed: {
				mailbox() {
					return { myAcls: undefined }
				},
				archiveMailbox() {
					return { }
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasWriteAcl).toBe(true)
	})
})
