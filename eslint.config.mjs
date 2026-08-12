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
				window: 'readonly',
				document: 'readonly',
				alert: 'readonly',
				confirm: 'readonly',
				IntersectionObserver: 'readonly',
				FormData: 'readonly',
			},
		},
		rules: {
			'no-alert': 'off',
			'no-console': 'off',
			camelcase: 'off',
			'no-unused-vars': [
				'error',
				{
					argsIgnorePattern:
						'^(error|e|eventId|eventName|speakerId|speakerName)$',
				},
			],
			'jsdoc/check-tag-names': 'off',
			'jsdoc/require-param-type': 'off',
			'jsdoc/require-returns-description': 'off',
			'import/no-unresolved': 'off',
			'import/no-extraneous-dependencies': 'off',
			'import/default': 'off',
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
