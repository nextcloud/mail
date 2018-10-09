const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
	entry: path.join(__dirname, 'main.js'),
	output: {
		path: path.resolve(__dirname, 'build'),
		publicPath: '/build/',
		filename: 'mail.js'
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
	plugins: [new VueLoaderPlugin()],
	resolve: {
		alias: {
			vue$: 'vue/dist/vue.esm.js',
			'@core': path.resolve('components/core')
		},
		extensions: ['*', '.js', '.vue', '.json']
	}
};