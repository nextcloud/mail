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
			'backbone': 'vendor/backbone/backbone',
			'backbone.radio': 'vendor/backbone.radio/build/backbone.radio',
			'davclient': 'vendor/davclient.js/lib/client',
			'domready': 'vendor/domReady/domReady',
			'es6-promise': 'vendor/es6-promise/es6-promise.min',
			'handlebars': 'vendor/handlebars/handlebars',
			'ical': 'vendor/ical.js/build/ical.min',
			'marionette': 'vendor/backbone.marionette/lib/backbone.marionette',
			'underscore': 'vendor/underscore/underscore'
		}
	},
	module: {
		rules: [
			{test: /davclient/, use: 'exports-loader?dav'}
		],
		loaders: [
			{test: /ical/, loader: 'exports-loader?ICAL'},
			{test: /\.html$/, loader: "handlebars-template-loader" }
		]
	}
};
