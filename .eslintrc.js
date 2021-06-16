module.exports = {
	extends: [
		'@nextcloud',
	],
	globals: {
		__webpack_nonce__: true,
		__webpack_public_path__: true,
		appName: true,
		appVersion: true,
		expect: true,
		OC: true,
		OCA: true,
		OCP: true,
		t: true,
	},
}
