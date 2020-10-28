const path = require('path');
const BundleAnalyzerPlugin = require('@bundle-analyzer/webpack-plugin')
const CKEditorWebpackPlugin = require('@ckeditor/ckeditor5-dev-webpack-plugin');
const {styles} = require('@ckeditor/ckeditor5-dev-utils');
const { VueLoaderPlugin } = require('vue-loader');

const plugins = [
	// CKEditor needs its own plugin to be built using webpack.
	new CKEditorWebpackPlugin({
		// See https://ckeditor.com/docs/ckeditor5/latest/features/ui-language.html
		language: 'en'
	}),
	new VueLoaderPlugin()
]

if (process.env.BUNDLE_ANALYZER_TOKEN) {
	plugins.push(new BundleAnalyzerPlugin({ token: process.env.BUNDLE_ANALYZER_TOKEN }))
}

module.exports = {
	entry: {
		autoredirect: path.join(__dirname, 'src/autoredirect.js'),
		dashboard: path.join(__dirname, 'src/main-dashboard.js'),
		mail: path.join(__dirname, 'src/main.js'),
		settings: path.join(__dirname, 'src/main-settings'),
		htmlresponse: path.join(__dirname, 'src/html-response.js'),
	},
	output: {
		path: path.resolve(__dirname, 'js'),
		chunkFilename: 'mail.[name].[contenthash].js',
		publicPath: '/js/',
	},
	node: {
		fs: 'empty'
	},
	module: {
		rules: [
			{
				test: /\.css$/,
				use: ['style-loader', 'css-loader']
			},
			{
				test: /\.scss$/,
				use: ['style-loader', 'css-loader', 'sass-loader']
			},
			{
				test: /\.vue$/,
				loader: 'vue-loader'
			},
			{
				test: /\.js$/,
				loader: 'babel-loader',
				exclude: /node_modules(?!(\/|\\)(@ckeditor|js-base64)(\/|\\))/
			},
			{
				test: /\.(png|jpg|gif)$/,
				loader: 'file-loader',
				options: {
					name: '[name].[ext]?[hash]'
				}
			},
			{
				test: /\.(svg)$/i,
				use: [
					{
						loader: 'svg-inline-loader'
					}
				],
				exclude: path.join(__dirname, 'node_modules', '@ckeditor')
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
					minify: true
				})
			}
		]
	},
	plugins: plugins,
	resolve: {
		extensions: ['*', '.js', '.vue', '.json'],
		symlinks: false
	}
};
