module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import'
	],
	presets: [
		[
			'@babel/preset-env',
			{
				modules: process.env.NODE_ENV === 'testing' ? 'commonjs' : undefined,
				useBuiltIns: process.env.NODE_ENV === 'testing' ? 'usage' : 'entry',
				corejs: 3
			}
		]
	]
};
