{{--
    Tailwind compilado (`npm run build:css`). Va justo antes de </head>, después de Font Awesome
    y de los <style> de cada vista, porque ahí inyectaba sus estilos el CDN y algunas clases
    (text-lg en íconos, border-white/10 sobre .glass) dependen de ganar en ese orden.
--}}
<link rel="stylesheet" href="/css/tailwind.css?v={{ @filemtime(public_path('css/tailwind.css')) }}">
