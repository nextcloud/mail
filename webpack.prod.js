/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const { merge } = require('webpack-merge')
const common = require('./webpack.common.js')

module.exports = async () => merge(await common(), {
  mode: 'production',
  devtool: 'source-map'
})
