/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import store from '../store/index.js'

const checkTranslationServiceAvailability = function() {
	/// TODO
	// const response = await axios.post(generateOcsUrl('taskprocessing/tasktypes'), {
	// 	appId: 'mail',
	// })

	const isAvailable = true
	const languages = [
		{
			from: 'en',
			fromLabel: 'English',
			to: 'fr',
			toLabel: 'French',
		},
		{
			from: 'en',
			fromLabel: 'English',
			to: 'de',
			toLabel: 'German',
		},
		{
			from: 'fr',
			fromLabel: 'French',
			to: 'en',
			toLabel: 'English',
		},
	]

	store.commit('enableTranslation', isAvailable)
	store.commit('setTranslationLanguages', languages)
}

const translateText = async function(text, fromLanguage, toLanguage) {
	try {
		const scheduleResponse = await axios.post(generateOcsUrl('taskprocessing/schedule'), {
			input: {
				origin_language: fromLanguage ?? null,
				input: text,
				target_language: toLanguage,
			},
			type: 'core:text2text:translate',
			appId: 'mail',
		})
		const task = scheduleResponse.data.ocs.data.task
		const getTaskOutput = async (task) => {
			if (task.output) {
				return task.output.output
			}
			await new Promise(resolve => setTimeout(resolve, 2000))
			const taskResponse = await axios.get(generateOcsUrl(`taskprocessing/task/${task.id}`))
			return getTaskOutput(taskResponse.data.ocs.data.task)
		}
		return await getTaskOutput(task)
	} catch (e) {
		console.error('Failed to translate', e)
	}
}

export { checkTranslationServiceAvailability, translateText }
