/*
 * @copyright 2022 Greta Doci <gretadoci@gmail.com>
 *
 * @author 2022 Greta Doci <gretadoci@gmail.com>
 * @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import {createLocalVue, shallowMount} from '@vue/test-utils'

import Nextcloud from '../../../mixins/Nextcloud'
import Envelope from '../../../components/Envelope'
import Vuex from 'vuex';

const localVue = createLocalVue()
const $route = {
	params: {
		id: 1
	}
}

localVue.use(Vuex)
localVue.mixin(Nextcloud)

describe('Envelope', () => {
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
		const view = shallowMount(Envelope, {
			mocks: {
				$route,
			},
			propsData: {
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
				},
				account: {sentMailboxId:'1'},
				mailbox: {
					myAcls: undefined,
					databaseId:'3',
					specialRole:''
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: 'x',
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
				},
			},
			store,
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: 's',
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: undefined,
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: 'te',
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: 'te',
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: undefined,
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: 'x',
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: undefined,
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },

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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: 's',
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },

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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					sentMailboxId:'1'
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: 'w',
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: 's',
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
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
				account: {sentMailboxId:'1'},
				mailbox: {
					specialRole:'',
					databaseId:'3',
					myAcls: undefined,
				},
				data: {
					accountId: 123,
					from: [{email:'info@test.com'}],
					flags: { seen:false, flagged:false, $junk:false, answered:false, hasAttachments:false, draft:false, },
				},
			},
			store,
			localVue,
		})

		expect(view.vm.hasWriteAcl).toBe(true)
	})
})
