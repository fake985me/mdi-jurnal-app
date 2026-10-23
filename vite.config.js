import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    server: {
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
        origin: "http://192.168.15.27:5173",
        hmr: {
            host: "192.168.15.27",
        },
        cors: {
            origin: [
                "http://192.168.15.27:8000",
                "http://127.0.0.1:8000",
                "http://localhost:8000",
            ],
        },
    },
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
    optimizeDeps: {
        include: ["apexcharts", "vue3-apexcharts"],
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
