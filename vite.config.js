import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import { VitePWA } from "vite-plugin-pwa";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
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
        VitePWA({
            registerType: "autoUpdate",
            includeAssets: [
                "favicon.ico",
                "apple-touch-icon.png",
                "masked-icon.svg",
            ],
            manifest: {
                name: "Material Management System",
                short_name: "MaterialMS",
                description:
                    "Track and manage materials with real-time monitoring",
                theme_color: "#3b82f6",
                background_color: "#ffffff",
                display: "standalone",
                orientation: "portrait",
                scope: "/",
                start_url: "/",
                icons: [
                    {
                        src: "/pwa-192x192.png",
                        sizes: "192x192",
                        type: "image/png",
                    },
                    {
                        src: "/pwa-512x512.png",
                        sizes: "512x512",
                        type: "image/png",
                    },
                    {
                        src: "/pwa-512x512.png",
                        sizes: "512x512",
                        type: "image/png",
                        purpose: "any maskable",
                    },
                ],
                categories: ["business", "productivity"],
                shortcuts: [
                    {
                        name: "Daily Input",
                        short_name: "Input",
                        description: "Add daily stock input",
                        url: "/daily-input",
                        icons: [
                            { src: "/icons/daily-input.png", sizes: "96x96" },
                        ],
                    },
                    {
                        name: "Leaderboard",
                        short_name: "Stats",
                        description: "View material statistics",
                        url: "/leaderboard",
                        icons: [
                            { src: "/icons/leaderboard.png", sizes: "96x96" },
                        ],
                    },
                ],
            },
            workbox: {
                globPatterns: ["**/*.{js,css,html,ico,png,svg,woff,woff2}"],
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                        handler: "CacheFirst",
                        options: {
                            cacheName: "google-fonts-cache",
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365, // 1 year
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        urlPattern: /^https:\/\/cdnjs\.cloudflare\.com\/.*/i,
                        handler: "CacheFirst",
                        options: {
                            cacheName: "cdn-cache",
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
                            },
                        },
                    },
                    {
                        urlPattern: /\/api\/.*/i,
                        handler: "NetworkFirst",
                        options: {
                            cacheName: "api-cache",
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 5, // 5 minutes
                            },
                            networkTimeoutSeconds: 10,
                        },
                    },
                ],
            },
            devOptions: {
                enabled: true,
            },
        }),
    ],
    build: {
        manifest: true,
        outDir: "public/build",
        rollupOptions: {
            input: "resources/js/app.js",
        },
    },
    base: "",
    resolve: {
        alias: {
            "@": "/resources/js",
            "ziggy-js": "/vendor/tightenco/ziggy/dist/index.js",
            vue: "vue/dist/vue.esm-bundler.js",
        },
    },
});
