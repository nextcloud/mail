var webpack = require('webpack');
const merge = require('webpack-merge');
const baseConfig = require('./webpack.base.config.js');

module.exports = merge(baseConfig, {
	mode: 'production',
	plugins: [
		new webpack.DefinePlugin({
			'process.env': {
				'NODE_ENV': JSON.stringify('production')
			}
		}),
		new webpack.optimize.AggressiveMergingPlugin()// Merge chunks
	]

});
