const path = require('path');
module.exports = {
	entry: './js/init.js',
	node: {
		fs: 'empty'
	},
	output: {
		filename: 'build.js',
		path: path.resolve(__dirname, 'build')
	},
	resolve: {
		modules: [path.resolve(__dirname), 'node_modules'],
		alias: {
			'handlebars': 'handlebars/runtime.js'
		}
	},
	module: {
		rules: [
			{test: /davclient/, use: 'exports-loader?dav'},
			{test: /ical/, use: 'exports-loader?ICAL'},
			{
				test: /\.html$/, loader: "handlebars-loader", query: {
					extensions: '.html',
					helperDirs: __dirname + '/templatehelpers'
				}
			}
		]
	}
};
