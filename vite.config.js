import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path'; // Add this import!
import fs from 'node:fs/promises';

const frontendDebugLogPlugin = () => ({
    name: 'frontend-debug-log',
    configureServer(server) {
        server.middlewares.use('/__fs_console_capture', (req, res) => {
            if (req.method !== 'POST') {
                res.statusCode = 405;
                res.end('Method not allowed');
                return;
            }

            const origin = req.headers.origin;
            if (origin) {
                try {
                    const hostname = new URL(origin).hostname.replace(/^\[|\]$/g, '');
                    if (!['localhost', '127.0.0.1', '::1'].includes(hostname)) {
                        res.statusCode = 403;
                        res.end('Forbidden');
                        return;
                    }
                } catch {
                    res.statusCode = 403;
                    res.end('Forbidden');
                    return;
                }
            }

            let body = '';
            req.setEncoding('utf8');
            req.on('data', chunk => {
                body += chunk;
                if (body.length > 32_768) req.destroy();
            });
            req.on('end', async () => {
                try {
                    const entry = JSON.parse(body);
                    const message = String(entry.message || '').slice(0, 24_000)
                        .replace(/Bearer\s+[^\s"']+/gi, 'Bearer [REDACTED]')
                        .replace(/(auth(?:entication)? token initialized:\s*)[^\s"']+/gi, '$1[REDACTED]')
                        .replace(/\b\d+\|[A-Za-z0-9_-]{20,}\b/g, '[REDACTED_TOKEN]');
                    const line = `${new Date().toISOString()} [${String(entry.level || 'log').toUpperCase()}] ${message}\n`;
                    const logPath = path.resolve(process.cwd(), 'storage/logs/frontend-debug.log');
                    await fs.mkdir(path.dirname(logPath), { recursive: true });
                    await fs.appendFile(logPath, line, 'utf8');
                    res.statusCode = 204;
                    res.end();
                } catch {
                    res.statusCode = 400;
                    res.end('Invalid log entry');
                }
            });
        });
    },
});

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        frontendDebugLogPlugin(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'), // Point to your actual JS source
            'vue-router': path.resolve(__dirname, 'resources/js/router-compat.ts'),
        }
    }
});
