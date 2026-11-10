/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import EnvelopeSkeleton from '../../../components/EnvelopeSkeleton.vue'

const THREAD_ROUTE = { name: 'message', params: { threadId: '42' } }
const THREAD_PATH = '/box/31/thread/42'

/**
 * Mount the skeleton with a router whose resolve() is driven by the test.
 *
 * @param {object} options - test options
 * @param {object|null} options.to - the `to` prop, null for the draft case
 * @param {string} options.currentPath - the path $route is currently on
 * @param {boolean} options.exact - the `exact` prop
 * @param {Function} options.push - the router push spy
 * @return {object} the mounted wrapper
 */
function mountSkeleton({ to = THREAD_ROUTE, currentPath = '/box/31', exact = true, push = vi.fn() } = {}) {
	return shallowMount(EnvelopeSkeleton, {
		global: {
			mocks: {
				$route: { path: currentPath },
				$router: {
					push,
					resolve: () => ({ href: `/index.php/apps/mail${THREAD_PATH}`, path: THREAD_PATH }),
				},
			},
		},
		props: {
			name: 'Zoidberg',
			to,
			exact,
		},
		slots: {
			subname: '<span class="test-subname">Why not Zoidberg?</span>',
		},
		attrs: {
			class: 'envelope draft',
		},
	})
}

describe('EnvelopeSkeleton', () => {
	it('renders the subname when it links to a route', async () => {
		const view = mountSkeleton()

		await view.vm.$nextTick()

		expect(view.find('.list-item-content__inner__subname').exists()).toBe(true)
		expect(view.find('.test-subname').text()).toBe('Why not Zoidberg?')
	})

	it('renders the subname when there is no route, as for drafts', async () => {
		const view = mountSkeleton({ to: null })

		await view.vm.$nextTick()

		expect(view.find('.list-item-content__inner__subname').exists()).toBe(true)
		expect(view.find('.test-subname').text()).toBe('Why not Zoidberg?')
	})

	it('keeps attributes on the root element when there is no route, as for drafts', () => {
		const view = mountSkeleton({ to: null })

		expect(view.element.tagName).toBe('LI')
		expect(view.classes()).toEqual(expect.arrayContaining(['list-item__wrapper', 'envelope', 'draft']))
	})

	it('falls back to the plain href when there is no route', () => {
		const view = mountSkeleton({ to: null })

		expect(view.find('a').attributes('href')).toBe('#')
	})

	it('exposes the resolved route as the anchor href so the link stays a real link', () => {
		const view = mountSkeleton()

		expect(view.find('a').attributes('href')).toBe(`/index.php/apps/mail${THREAD_PATH}`)
	})

	it('navigates on click when it has a route', () => {
		const push = vi.fn()
		const view = mountSkeleton({ push })

		view.find('a').trigger('click')

		expect(push).toHaveBeenCalledWith(THREAD_ROUTE)
	})

	it('does not navigate on click when there is no route, as for drafts', () => {
		const push = vi.fn()
		const view = mountSkeleton({ to: null, push })

		view.find('a').trigger('click')

		expect(push).not.toHaveBeenCalled()
	})

	it('emits the native click so the parent can open a draft', () => {
		const view = mountSkeleton({ to: null })

		view.find('a').trigger('click')

		expect(view.emitted('click')).toHaveLength(1)
	})

	it('does not navigate when a modifier key opens the link in a new tab', () => {
		const push = vi.fn()
		const view = mountSkeleton({ push })

		view.find('a').trigger('click', { ctrlKey: true })

		expect(push).not.toHaveBeenCalled()
	})

	it('is inactive while the route is not the current one', () => {
		const view = mountSkeleton({ currentPath: '/box/31' })

		expect(view.vm.isRouteActive).toBe(false)
	})

	it('is active on an exact match', () => {
		const view = mountSkeleton({ currentPath: THREAD_PATH })

		expect(view.vm.isRouteActive).toBe(true)
	})

	it('is inactive on a nested path when matching exactly', () => {
		const view = mountSkeleton({ currentPath: `${THREAD_PATH}/sub`, exact: true })

		expect(view.vm.isRouteActive).toBe(false)
	})

	it('is active on a nested path when not matching exactly', () => {
		const view = mountSkeleton({ currentPath: `${THREAD_PATH}/sub`, exact: false })

		expect(view.vm.isRouteActive).toBe(true)
	})

	it('does not treat a sibling path with a shared prefix as nested', () => {
		const view = mountSkeleton({ currentPath: `${THREAD_PATH}0`, exact: false })

		expect(view.vm.isRouteActive).toBe(false)
	})

	it('is never active without a route', () => {
		const view = mountSkeleton({ to: null, currentPath: THREAD_PATH })

		expect(view.vm.isRouteActive).toBe(false)
	})
})
