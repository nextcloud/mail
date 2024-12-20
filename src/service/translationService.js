/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

const getTranslationLanguages = async function(options) {
	return axios.get(generateOcsUrl('/translation/languages', undefined, options), options)
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

export { getTranslationLanguages, translateText }
