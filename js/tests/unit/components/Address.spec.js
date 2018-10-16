import sinon from 'sinon'
import {shallowMount} from '@vue/test-utils'

import Address from '../../../components/Address.vue';

//const renderer = require('vue-server-renderer').createRenderer();

describe('Address', () => {
	it('calls draft and send callback', () => {
		const addr = shallowMount(Address)

		expect(addr.contains('span')).to.be.true
	});
});
