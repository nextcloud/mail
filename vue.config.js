const path = require('path');

module.exports = {
	configureWebpack: {
		entry: {
			app: './js/main.js'
		},
		resolve: {
			alias: {
				'@':
					path.resolve('js')
			}
		}
	},
	filenameHashing: false,
	outputDir: 'js/build'
}
