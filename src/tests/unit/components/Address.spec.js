import { createLocalVue, shallowMount } from '@vue/test-utils'
import VTooltip from 'v-tooltip'

import Address from '../../../components/Address.vue'

const localVue = createLocalVue()
localVue.use(VTooltip)

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
			mailboxId: 12,
		}
		const addr = shallowMount(Address, {
			localVue,
			mocks: {
				$route,
			},
			propsData: {
				label: 'Test User',
				email: 'user@domain.com',
			},
		})

		expect(addr.vm.newMessageRoute.name).to.equal('message')
		expect(addr.vm.newMessageRoute.params.mailboxId).to.equal(12)
		expect(addr.vm.newMessageRoute.query.to).to.equal('user@domain.com')
	})
})
