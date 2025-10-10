/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('node:path')

webpackConfig.entry.autoredirect = path.resolve(__dirname, 'src/autoredirect.js')
webpackConfig.entry.oauthpopup = path.resolve(__dirname, 'src/main-oauth-popup.js')
webpackConfig.entry.settings = path.resolve(__dirname, 'src/main-settings')
webpackConfig.entry.htmlresponse = path.resolve(__dirname, 'src/html-response.js')

module.exports = webpackConfig
