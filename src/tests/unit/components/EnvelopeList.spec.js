import {createLocalVue, shallowMount} from '@vue/test-utils'
import sinon from 'sinon'

import EnvelopeList from '../../../components/EnvelopeList.vue'
import {UNIFIED_ACCOUNT_ID, UNIFIED_INBOX_ID} from '../../../store/constants'

const localVue = createLocalVue()

describe('EnvelopeList', () => {
	afterEach(() => {
		sinon.restore()
	})

	it('fetches envelopes when mounted', () => {
		const fetchEnvelopes = sinon.stub(EnvelopeList.methods, 'fetchEnvelopes')
		fetchEnvelopes.callsFake(() => [])

		const list = shallowMount(EnvelopeList, {
			localVue,
			propsData: {
				account: {
					accountId: 1,
				},
				folder: {
					id: btoa('INBOX'),
				},
				envelopes: [],
			},
		})

		expect(list.vm.$data.loading).to.be.true
		expect(list.vm.$data.envelopes).to.be.empty
		expect(fetchEnvelopes).to.have.been.calledWith(1, btoa('INBOX'))
	})
})
