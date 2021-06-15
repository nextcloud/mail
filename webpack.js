
const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const webpackRules = require('@nextcloud/webpack-vue-config/rules')

const { styles } = require('@ckeditor/ckeditor5-dev-utils')
const CKEditorWebpackPlugin = require('@ckeditor/ckeditor5-dev-webpack-plugin')
const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')

webpackConfig.entry = {
	autoredirect: path.join(__dirname, 'src/autoredirect.js'),
	dashboard: path.join(__dirname, 'src/main-dashboard.js'),
	main: path.join(__dirname, 'src/main.js'),
	settings: path.join(__dirname, 'src/main-settings'),
	htmlresponse: path.join(__dirname, 'src/html-response.js'),
}

webpackConfig.plugins.push(
	// CKEditor needs its own plugin to be built using webpack.
	new CKEditorWebpackPlugin({
		// See https://ckeditor.com/docs/ckeditor5/latest/features/ui-language.html
		language: 'en',
	})
)

webpackRules.RULE_JS.exclude = BabelLoaderExcludeNodeModulesExcept([
	'@ckeditor',
	'js-base64',
])

webpackConfig.module.rules = Object.values(webpackRules)
webpackConfig.module.rules.push({
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
	loader: 'raw-loader'
},
{
	test: /ckeditor5-[^/\\]+[/\\].+\.css$/,
	loader: 'postcss-loader',
	options: styles.getPostCssConfig({
		themeImporter: {
			themePath: require.resolve('@ckeditor/ckeditor5-theme-lark'),
		},
		minify: true,
	}),
})

module.exports = webpackConfig
