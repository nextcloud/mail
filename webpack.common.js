/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const path = require('path')
const webpack = require('webpack')
const CKEditorWebpackPlugin = require('@ckeditor/ckeditor5-dev-webpack-plugin')
const { VueLoaderPlugin } = require('vue-loader')
const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')
const NodePolyfillPlugin = require('node-polyfill-webpack-plugin')
const { IgnorePlugin } = require('webpack')

const appName = 'mail'
const appVersion = require('./package.json').version

async function getPostCssConfig(ckEditorOpts) {
	// CKEditor is not compatbile with postcss@8 and postcss-loader@4 despite stating so.
	// Adapted from https://github.com/ckeditor/ckeditor5/issues/8112#issuecomment-960579351
	const { styles } = await import('@ckeditor/ckeditor5-dev-utils')
	const { plugins, ...rest } = styles.getPostCssConfig(ckEditorOpts);
	return { postcssOptions: { plugins }, ...rest };
};

const plugins = [
	// CKEditor needs its own plugin to be built using webpack.
	new CKEditorWebpackPlugin({
		// See https://ckeditor.com/docs/ckeditor5/latest/features/ui-language.html
		language: 'en',
	}),
	new VueLoaderPlugin(),

	// Make sure we auto-inject node polyfills on demand
	// https://webpack.js.org/blog/2020-10-10-webpack-5-release/#automatic-nodejs-polyfills-removed
	new NodePolyfillPlugin({
		// Console is available in the web-browser
		excludeAliases: ['console'],
	}),

	// Fix warning when bundling moment locales
	new IgnorePlugin({
		resourceRegExp: /^\.\/locale$/,
		contextRegExp: /moment\/min$/,
	}),
	new webpack.DefinePlugin({
		appName: JSON.stringify(appName),
		appVersion: JSON.stringify(appVersion),
	}),
]

module.exports = async () => ({
	entry: {
		autoredirect: path.join(__dirname, 'src/autoredirect.js'),
		mail: path.join(__dirname, 'src/main.js'),
		oauthpopup: path.join(__dirname, 'src/main-oauth-popup.js'),
		settings: path.join(__dirname, 'src/main-settings'),
		htmlresponse: path.join(__dirname, 'src/html-response.js'),
	},
	output: {
		path: path.resolve(__dirname, 'js'),
		chunkFilename: 'mail.[name].[contenthash].js',
		publicPath: '/js/',
	},
	module: {
		rules: [
			{
				test: /\.css$/,
				use: ['style-loader', 'css-loader'],
			},
			{
				test: /\.scss$/,
				use: ['style-loader', 'css-loader', 'sass-loader'],
			},
			{
				test: /\.vue$/,
				loader: 'vue-loader',
			},
			{
				test: /\.tsx?$/,
				use: 'ts-loader',
				exclude: /node_modules/,
			},
			{
				test: /\.js$/,
				loader: 'babel-loader',
				exclude: BabelLoaderExcludeNodeModulesExcept([
					'@ckeditor',
					'js-base64',
				]),
			},
			// Fix html-to-text and its dependencies
			// https://github.com/html-to-text/node-html-to-text/issues/229#issuecomment-945215065
			{
				type: 'javascript/auto',
				test: /\.[cm]?js$/,
				loader: 'babel-loader',
				include: /node_modules[/\\](@?selderee|parseley)/,
			},
			{
				test: /\.(png|jpg|gif)$/,
				loader: 'file-loader',
				options: {
					name: '[name].[ext]?[hash]',
				},
			},
			{
				test: /\.(svg)$/i,
				use: [
					{
						loader: 'svg-inline-loader',
					},
				],
				exclude: path.join(__dirname, 'node_modules', '@ckeditor'),
			},
			{
				test: /ckeditor5-[^/\\]+[/\\]theme[/\\]icons[/\\][^/\\]+\.svg$/,
				loader: 'raw-loader',
			},
			{
				test: /ckeditor5-[^/\\]+[/\\].+\.css$/,
				loader: 'postcss-loader',
				options: await getPostCssConfig({
					themeImporter: {
						themePath: require.resolve('@ckeditor/ckeditor5-theme-lark'),
					},
					minify: true,
				}),
			},
		],
	},
	plugins,
	resolve: {
		extensions: ['*', '.tsx', '.ts', '.js', '.vue', '.json'],
		symlinks: false,
	},
})
