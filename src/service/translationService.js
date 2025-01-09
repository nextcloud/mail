/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import store from '../store/index.js'

const fetchAvailableLanguages = async function() {
	try {
		const response = await axios.get(generateOcsUrl('taskprocessing/tasktypes'))
		const inputLanguages = response.data.ocs.data.types['core:text2text:translate'].inputShapeEnumValues[0]
		const outputLanguages = response.data.ocs.data.types['core:text2text:translate'].inputShapeEnumValues[1]
		store.commit('setTranslationInputLanguages', inputLanguages)
		store.commit('setTranslationOutputLanguages', outputLanguages)
	} catch (e) {
		console.error('Failed to fetch available languages', e)
	}
}

const translateText = async function(text, fromLanguage, toLanguage) {
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
}

export { fetchAvailableLanguages, translateText }
