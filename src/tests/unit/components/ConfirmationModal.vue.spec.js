/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import ConfirmationModal from '../../../components/ConfirmationModal.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

const localVue = createLocalVue()

localVue.use(Vuex)
localVue.mixin(Nextcloud)

describe('ConfirmationModal', () => {

	it('renders with default button text', () => {
		const view = shallowMount(ConfirmationModal, {
			propsData: {},
			localVue,
		})

		expect(view.vm.confirmText).toBe('Confirm')
	})

	it('renders with custom button text', () => {
		const view = shallowMount(ConfirmationModal, {
			propsData: {
				confirmText: 'Subscribe'
			},
			localVue,
		})

		expect(view.vm.confirmText).toBe('Subscribe')
	})
})
