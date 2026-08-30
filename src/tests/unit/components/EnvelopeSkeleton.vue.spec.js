/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createLocalVue, mount } from '@vue/test-utils'
import EnvelopeSkeleton from '../../../components/EnvelopeSkeleton.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)

function routerLinkStub(slotProps) {
	return {
		props: ['to', 'custom'],
		render(h) {
			return h('div', this.$scopedSlots.default(slotProps))
		},
	}
}

function mountSkeleton(slotProps) {
	return mount(EnvelopeSkeleton, {
		propsData: {
			name: 'Jane Doe',
			to: { name: 'message', params: { threadId: 1 } },
		},
		stubs: {
			'router-link': routerLinkStub(slotProps),
		},
		localVue,
	})
}

describe('EnvelopeSkeleton', () => {
	it('marks the envelope active on an exact route match', () => {
		const view = mountSkeleton({ href: '/thread/1', navigate: () => {}, isActive: true, isExactActive: true })

		expect(view.find('.list-item__wrapper').classes()).toContain('list-item__wrapper--active')
	})

	it('does not mark the envelope active on a partial route match', () => {
		const view = mountSkeleton({ href: '/thread/1', navigate: () => {}, isActive: true, isExactActive: false })

		expect(view.find('.list-item__wrapper').classes()).not.toContain('list-item__wrapper--active')
	})
})
