/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLanguage } from '@nextcloud/l10n'
import { detectForeignLanguage } from '../../../util/languageDetection.ts'

vi.mock('@nextcloud/l10n', () => ({ getLanguage: vi.fn() }))

const GERMAN = 'Hallo Marie, ich wollte fragen ob wir uns nächste Woche treffen können um über das Projekt zu sprechen.'
const ENGLISH = 'Hi Marie, I wanted to ask whether we could meet next week to discuss the project status and next steps.'
const CHINESE = '你好，我想问一下我们下周是否可以见面讨论一下这个项目的进展情况以及下一步的具体计划安排，非常感谢您抽出时间来帮助我们完成这项工作。'
const ARABIC = 'مرحبا ماري، أردت أن أسألك إذا كان بإمكاننا الاجتماع الأسبوع المقبل لمناقشة حالة المشروع والخطوات التالية.'

describe('languageDetection', () => {
	beforeEach(() => {
		getLanguage.mockReturnValue('en')
	})

	it('detects a foreign language', async () => {
		const detected = await detectForeignLanguage(GERMAN)

		expect(detected).toBe('de')
	})

	it('ignores a message in the user language', async () => {
		const detected = await detectForeignLanguage(ENGLISH)

		expect(detected).toBeNull()
	})

	it('ignores the region of the user language', async () => {
		getLanguage.mockReturnValue('en_GB')

		const detected = await detectForeignLanguage(ENGLISH)

		expect(detected).toBeNull()
	})

	it('ignores a message that is too short to detect reliably', async () => {
		const detected = await detectForeignLanguage('Bonjour Marie, à demain.')

		expect(detected).toBeNull()
	})

	it('maps macrolanguages to the code the UI uses', async () => {
		expect(await detectForeignLanguage(CHINESE)).toBe('zh')
		expect(await detectForeignLanguage(ARABIC)).toBe('ar')
	})

	it('gives up when the user language is not detectable', async () => {
		getLanguage.mockReturnValue('is')

		const detected = await detectForeignLanguage(GERMAN)

		expect(detected).toBeNull()
	})

	it('strips markup and decodes entities before detecting', async () => {
		const body = `<html><head><style>.a{color:red}</style></head><body><p>${GERMAN.replace('nächste', 'n&auml;chste')}</p><a href="https://example.com/tracking/link">Klick</a></body></html>`

		const detected = await detectForeignLanguage(body)

		expect(detected).toBe('de')
	})
})
