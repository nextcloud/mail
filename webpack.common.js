const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
	entry: path.join(__dirname, 'src/main.js'),
	output: {
		path: path.resolve(__dirname, 'js'),
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
				include: '/node_modules/quill/',
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
				loader: 'url-loader',
				exclude: path.join(__dirname, 'node_modules/quill'),
			},
			{
				test: /\.(svg)$/i,
				loader: 'html-loader',
				include: path.join(__dirname, 'node_modules/quill'),
			},
		]
	},
	plugins: [new VueLoaderPlugin()],
	resolve: {
		extensions: ['*', '.js', '.vue', '.json'],
		symlinks: false
	}
};