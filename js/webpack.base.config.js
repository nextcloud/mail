const path = require('path');

module.exports = {
	entry: './js/init.js',
	output: {
		filename: 'build.js',
		path: path.resolve(__dirname, 'build')
	},
	module: {
		rules: [
			{test: /davclient/, use: 'exports-loader?dav'},
			{
				test: /\.html$/, loader: "handlebars-loader", query: {
				extensions: '.html',
				helperDirs: __dirname + '/templatehelpers'
			}
			}
		],
		loaders: [
			{test: /ical/, loader: 'exports-loader?ICAL'}
		]
	},
	resolve: {
		modules: [path.resolve(__dirname), 'node_modules'],
		alias: {
			'handlebars': 'handlebars/runtime.js'
		}
	}
};
