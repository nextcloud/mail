/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { limitToContainerWidth } from '../../../util/fitWidth.ts'

describe('limitToContainerWidth', () => {
	it('limits an element to the width of its container', () => {
		const element = document.createElement('table')

		limitToContainerWidth(element)

		expect(element.style.maxWidth).toBe('100%')
	})

	it('keeps a max-width the element sets for itself', () => {
		const element = document.createElement('table')
		element.style.maxWidth = '600px'

		limitToContainerWidth(element)

		expect(element.style.maxWidth).toBe('600px')
	})
})
