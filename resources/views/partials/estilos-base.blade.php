{{--
    Hojas comunes de todas las pantallas.
    - Tailwind compilado va aparte (partials.tailwind) al final del <head>, como lo inyectaba el CDN.
    - Fuente e iconos sin bloquear el primer pintado (media="print" → "all" al cargar).
--}}
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" media="print" onload="this.media='all'">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</noscript>
