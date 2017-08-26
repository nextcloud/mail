const path = require('path');

module.exports = {
	entry: './js/init.js',
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
	devtool: '#source-map',
	module: {
		rules: [
			{test: /davclient/, use: 'exports-loader?dav'}
		],
		loaders: [
			{test: /ical/, loader: 'exports-loader?ICAL'},
			{test: /\.html$/, loader: 'handlebars-loader'}
		]
	}
};
