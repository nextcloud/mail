/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import useMainStore from '../store/mainStore.js'

const fetchAvailableLanguages = async function() {
	const mainStore = useMainStore()
	try {
		const response = await axios.get(generateOcsUrl('taskprocessing/tasktypes'))
		let inputLanguages = []
		let outputLanguages = []
		if (typeof response.data.ocs.data.types['core:text2text:translate'].inputShapeEnumValues === 'object') {
			inputLanguages = response.data.ocs.data.types['core:text2text:translate'].inputShapeEnumValues.origin_language
			outputLanguages = response.data.ocs.data.types['core:text2text:translate'].inputShapeEnumValues.target_language
		} else {
			inputLanguages = response.data.ocs.data.types['core:text2text:translate'].inputShapeEnumValues[0]
			outputLanguages = response.data.ocs.data.types['core:text2text:translate'].inputShapeEnumValues[1]
		}
		mainStore.translationInputLanguages = inputLanguages
		mainStore.translationOutputLanguages = outputLanguages
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
