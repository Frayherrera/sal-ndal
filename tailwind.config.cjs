// Configuración de Tailwind 3.4 (CLI) para public/css/tailwind.css.
// Reemplaza el CDN de desarrollo (cdn.tailwindcss.com), que compilaba el CSS en el navegador.
module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],
    theme: { extend: {} },
    plugins: [],
};
