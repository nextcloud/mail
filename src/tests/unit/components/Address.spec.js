import sinon from 'sinon'
import {shallowMount} from '@vue/test-utils'

import Address from '../../../components/Address.vue';

describe('Address', () => {
	it('renders', () => {
		const addr = shallowMount(Address)

		expect(addr.contains('span')).to.be.true
	});
});
