const path = require('path');
const BundleAnalyzerPlugin = require('@bundle-analyzer/webpack-plugin')
const { VueLoaderPlugin } = require('vue-loader');

const plugins = [
	new VueLoaderPlugin()
]

if (process.env.BUNDLE_ANALYZER_TOKEN) {
	plugins.push(new BundleAnalyzerPlugin({ token: process.env.BUNDLE_ANALYZER_TOKEN }))
}

module.exports = {
	entry: path.join(__dirname, 'src/main.js'),
	output: {
		path: path.resolve(__dirname, 'js'),
		chunkFilename: 'mail.[name].[contenthash].js',
		publicPath: '/js/',
		filename: 'mail.js'
	},
	node: {
		fs: 'empty'
	},
	module: {
		rules: [
			{
				test: /davclient/,
				use: 'exports-loader?dav'
			},
			{
				test: /ical/,
				use: 'exports-loader?ICAL'
			},
			{
				test: /\.css$/,
				use: ['vue-style-loader', 'css-loader']
			},
			{
				test: /\.scss$/,
				use: ['vue-style-loader', 'css-loader', 'sass-loader']
			},
			{
				test: /\.vue$/,
				loader: 'vue-loader'
			},
			{
				test: /\.js$/,
				loader: 'babel-loader',
				exclude: /node_modules/
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
						loader: 'url-loader'
					}
				]
			}
		]
	},
	plugins: plugins,
	resolve: {
		extensions: ['*', '.js', '.vue', '.json'],
		symlinks: false
	}
};
