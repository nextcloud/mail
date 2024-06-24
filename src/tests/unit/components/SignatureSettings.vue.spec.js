/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {createLocalVue, shallowMount} from '@vue/test-utils'
import Vuex from 'vuex'
import Nextcloud from '../../../mixins/Nextcloud.js'
import SignatureSettings from '../../../components/SignatureSettings.vue'

const localVue = createLocalVue()

localVue.use(Vuex)
localVue.mixin(Nextcloud)

describe('SignatureSettings', () => {

	it('Show warning for large signatures', () => {
		const wrapper = shallowMount(SignatureSettings, {
			localVue,
			propsData: {
				account: {
					aliases: [],
					signature: String('<p>Lorem ipsum</p>').repeat(120000),
				},
			},
		})

		expect(wrapper.vm.isLargeSignature).toBeTruthy()
	})

})
