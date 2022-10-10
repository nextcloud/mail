/*
 * @copyright 2022 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author 2022 Daniel Kesselberg <mail@danielkesselberg.de>
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
import Vuex from 'vuex'
import Nextcloud from '../../../mixins/Nextcloud'
import SignatureSettings from '../../../components/SignatureSettings'

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
