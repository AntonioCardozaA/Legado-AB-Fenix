/** Match the authenticated layout's Tailwind CDN defaults. */
module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './public/js/**/*.js',
        './app/**/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    // Elongation state colors and report badges are assembled at runtime.
    safelist: [{
        pattern: /^(bg|text|border|ring|from|to)-(red|yellow|green|blue|orange|gray|slate|amber|indigo|purple)-(50|100|200|500|600|700|800|900)$/,
        variants: ['hover', 'focus'],
    }],
    theme: { extend: {} },
    plugins: [],
};
