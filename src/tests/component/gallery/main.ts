/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/// <reference types="vite/client" />
import { createPinia, PiniaVuePlugin } from 'pinia'
import Vue from 'vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

import '@nextcloud/dialogs/style.css'
import '../../../directives/drag-and-drop/styles/drag-and-drop.scss'

const root = document.querySelector('#root')
if (!root) {
	throw new Error('Gallery is missing its #root element')
}

let vm: Vue | undefined
let errors: string[] = []

const stories = import.meta.glob('../**/*.story.ts')
const id = (path: string) => path.replace(/^(\.\.\/)+/, '').replace(/\.story\.\w+$/, '')

async function resolve(storyId: string) {
	const sep = storyId.lastIndexOf('/')
	const [path, name] = [storyId.slice(0, sep), storyId.slice(sep + 1)]
	const file = Object.keys(stories).find((f) => id(f) === path || id(f).endsWith('/' + path))
	const mod = (file && await stories[file]()) as Record<string, any> | undefined
	return mod?.[name] ?? mod?.default
}

Vue.use(PiniaVuePlugin)
Vue.mixin(Nextcloud)
Vue.config.errorHandler = (error) => {
	// eslint-disable-next-line no-console -- This ok, because it is for tests.
	console.error('[vue error]', error)
	errors.push(String(error))
}
Vue.config.warnHandler = (warning) => {
	// eslint-disable-next-line no-console -- This ok, because it is for tests.
	console.warn('[vue warn]', warning)
}

function clearAndThrowErrors() {
	if (errors.length) {
		const oldErrors = errors
		errors = []
		throw JSON.stringify(oldErrors, null, 2)
	}
}

async function mount({ story: storyId, props }: { props?: Record<string, unknown>, story: string }) {
	const story = await resolve(storyId)
	if (!story) {
		throw new Error(`Unknown story: ${storyId}\nStory files:\n  ${Object.keys(stories).join('\n  ')}`)
	}
	props = props ?? {}

	if (vm) {
		Object.assign(vm, { story, props })
	} else {
		vm = new Vue({
			data: () => ({ story, props }),
			pinia: createPinia(),
			render(h) {
				return h(story, { props })
			},
		}).$mount()
		root?.appendChild(vm.$el)
	}

	await Vue.nextTick()
	clearAndThrowErrors()
}

async function unmount() {
	vm?.$destroy()
	vm?.$el.remove()
	vm = undefined
	clearAndThrowErrors()
}

Object.assign(window, { mount, unmount })
