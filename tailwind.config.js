/** @type {import('tailwindcss').Config} */
module.exports = {
	content: [
		'./src/**/*.php',
		'./templates/**/*.php',
		'./assets/js/**/*.js',
	],
	prefix: 'wpr-', // This adds your required 'wpr-' prefix to all Tailwind classes
	corePlugins: {
		preflight: false, // Disable global CSS reset to avoid overriding theme/plugin styles
	},
	theme: {
		extend: {},
	},
	plugins: [],
}
