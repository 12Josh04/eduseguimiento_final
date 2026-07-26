import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            /*
             * Este proyecto usa CSS y JS propios en public/css y public/js,
             * servidos directamente con asset(). Vite solo se usa si decides
             * migrar a recursos compilados en el futuro.
             *
             * Por ahora los entry points apuntan a archivos vacíos para que
             * Vite no genere errores al ejecutar `npm run build`.
             */
            input: ['resources/js/app.js', 'resources/css/app.css'],
            refresh: true,
        }),
    ],
});
