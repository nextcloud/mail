/*
 * @copyright 2024 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2024 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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

import { createLocalVue, shallowMount } from '@vue/test-utils'
import { loadState } from '@nextcloud/initial-state'

import Nextcloud from '../../../mixins/Nextcloud.js'
import EventModal from '../../../components/EventModal.vue'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

describe('EventModal', () => {

	it('renders default values', () => {
		const view = shallowMount(EventModal, {
			localVue,
			propsData: {
				envelope: {
					subject: 'Sub?',
					previewText: 'prev',
				},
			},
		})

		expect(view.vm.enabledThreadSummary).toBe(false)
		expect(view.vm.eventTitle).toBe('Sub?')
		expect(view.vm.description).toBe('prev')
	})
})
