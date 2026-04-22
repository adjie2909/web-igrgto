import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = env.APP_URL || 'http://localhost';

    let hmrHost = 'localhost';
    try {
        hmrHost = new URL(appUrl).hostname || 'localhost';
    } catch {
        hmrHost = 'localhost';
    }

    return {
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            hmr: {
                host: env.VITE_HMR_HOST || hmrHost,
                protocol: 'ws',
            },
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
    };
});
