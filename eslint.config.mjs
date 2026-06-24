import wordpress from '@wordpress/eslint-plugin';

export default [
	...wordpress.configs.recommended,
	{
		languageOptions: {
			globals: {
				wp: 'readonly',
				ajaxurl: 'readonly',
				wpfaeventSpeakersConfig: 'readonly',
				WPFA_Speakers: 'readonly',
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
