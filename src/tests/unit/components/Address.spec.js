import {shallowMount} from '@vue/test-utils'

import Address from '../../../components/Address.vue'

describe('Address', () => {
	let $route

	beforeEach(() => {
		$route = {
			params: {},
			query: {},
		}
	})

	it('renders', () => {
		$route.params = {
			accountId: 1,
			folderId: 'folder1',
		}
		const addr = shallowMount(Address, {
			mocks: {
				$route
			},
			propsData: {
				label: 'Test User',
				email: 'user@domain.com',
			},
		})

		expect(addr.contains('router-link')).to.be.true
		expect(addr.vm.newMessageRoute.name).to.equal('message')
		expect(addr.vm.newMessageRoute.params.accountId).to.equal(1)
		expect(addr.vm.newMessageRoute.params.folderId).to.equal('folder1')
		expect(addr.vm.newMessageRoute.query.to).to.equal('user@domain.com')
	});
});
