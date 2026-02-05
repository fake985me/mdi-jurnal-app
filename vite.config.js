import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            "@": "/resources/js/",
        },
    },
    build: {
        chunkSizeWarningLimit: 1500, // Naikkan batas dari default 500 KB → 1.5MB
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes("node_modules")) {
                        if (id.includes("vue")) return "vue";
                        if (id.includes("chart.js")) return "chart";
                        if (id.includes("axios")) return "axios";
                        return "vendor";
                    }
                },
            },
        },
    },
});
