import wordpress from '@wordpress/eslint-plugin';

export default [
	...wordpress.configs.recommended,
	{
		languageOptions: {
			globals: {
				wp: 'readonly',
				ajaxurl: 'readonly',
				wpfaeventSpeakersConfig: 'readonly',
				wpfaeventEventsConfig: 'readonly',
				wpfaeventFooterConfig: 'readonly',
				jQuery: 'readonly',
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
			'tests/**',
			'languages/**',
		],
	},
];
