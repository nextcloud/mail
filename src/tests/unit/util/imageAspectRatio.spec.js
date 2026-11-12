/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { keepAttributeAspectRatio } from '../../../util/imageAspectRatio.ts'

/**
 * @param {string} markup the img element
 * @return {HTMLImageElement}
 */
function image(markup) {
	const container = document.createElement('div')
	container.innerHTML = markup
	return container.firstElementChild
}

describe('keepAttributeAspectRatio', () => {
	beforeEach(() => {
		vi.spyOn(window, 'getComputedStyle').mockImplementation((element) => ({
			height: element.style.height || `${element.getAttribute('height')}px`,
		}))
	})

	afterEach(() => {
		vi.restoreAllMocks()
	})

	it('derives the height from the attribute ratio', () => {
		const img = image('<img width="1141" height="1038">')

		keepAttributeAspectRatio(img)

		expect(img.style.aspectRatio).toBe('1141 / 1038')
		expect(img.style.height).toBe('auto')
	})

	it('keeps the ratio of a stretched spacer', () => {
		const img = image('<img width="600" height="1">')

		keepAttributeAspectRatio(img)

		expect(img.style.aspectRatio).toBe('600 / 1')
	})

	it('leaves an inline height alone', () => {
		const img = image('<img width="400" height="300" style="height: 200px">')

		keepAttributeAspectRatio(img)

		expect(img.style.height).toBe('200px')
		expect(img.style.aspectRatio).toBe('')
	})

	it('leaves a height from the message stylesheet alone', () => {
		const img = image('<img width="400" height="300">')
		window.getComputedStyle.mockReturnValue({ height: '100px' })

		keepAttributeAspectRatio(img)

		expect(img.style.height).toBe('')
		expect(img.style.aspectRatio).toBe('')
	})

	it.each([
		'<img width="400">',
		'<img height="300">',
		'<img width="100%" height="300">',
		'<img width="0" height="300">',
	])('leaves %s alone', (markup) => {
		const img = image(markup)

		keepAttributeAspectRatio(img)

		expect(img.style.height).toBe('')
		expect(img.style.aspectRatio).toBe('')
	})
})
