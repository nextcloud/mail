/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import AdminAiPrommptsSettings from './components/settings/AdminAiPrommptsSettings.vue'

Vue.prototype.$t = translate

const View = Vue.extend(AdminAiPrommptsSettings);

(new View({})).$mount('#ai-prompts-settings')
