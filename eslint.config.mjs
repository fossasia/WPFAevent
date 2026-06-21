import wordpress from '@wordpress/eslint-plugin';

export default [
	...wordpress.configs.recommended,
	{
		languageOptions: {
			globals: {
				wp: 'readonly',
			},
		},
	},
	{
		ignores: [
			'node_modules/**',
			'vendor/**',
			'build/**',
			'assets/dist/**',
			'**/*.min.js',
		],
	},
];
