import {defineConfig} from "vite"
import {dirname, resolve} from "node:path"
import {fileURLToPath} from "node:url"
import autoOrigin from "vite-plugin-auto-origin"
import liveReload from "vite-plugin-live-reload"
import { viteStaticCopy } from "vite-plugin-static-copy";
import tailwindcss from '@tailwindcss/vite'

// TYPO3 root path (relative to this config file)
const VITE_TYPO3_ROOT = "./"

// Vite input files (relative to TYPO3 root path)
const VITE_ENTRYPOINTS = ["packages/sitepackage/Resources/Private/Frontend/app.js"]

// Output path for generated assets
const VITE_OUTPUT_PATH = "packages/sitepackage/Resources/Public/Frontend/"

const currentDir = dirname(fileURLToPath(import.meta.url))
const rootPath = resolve(currentDir, VITE_TYPO3_ROOT)
export default defineConfig({
    resolve: {
        alias: {
            "@frontend": fileURLToPath(new URL("packages/sitepackage/Resources/Private/Frontend", import.meta.url)),
            "@img": fileURLToPath(new URL("packages/sitepackage/Resources/Public/Images", import.meta.url)),
            "@node": fileURLToPath(new URL("node_modules", import.meta.url))
        },
    },
    assetsInclude: ['**/*.woff2', '**/*.woff', '**/*.ttf'],
    base: "",
    build: {
        manifest: true,
        assetsInlineLimit: 0,
        rollupOptions: {
            input: VITE_ENTRYPOINTS.map((entry) => resolve(rootPath, entry)),
        },
        outDir: resolve(rootPath, VITE_OUTPUT_PATH),
    },
    css: {
        devSourcemap: true,
    },
    plugins: [
        autoOrigin(),
        tailwindcss(),
        liveReload(["packages/**/*.php", "packages/**/*.html", "packages/**/*.typoscript"], { alwaysReload: true })
    ],
    publicDir: false,
    server: {
        origin: "https://cmt-blog.ddev.site:5173",
        port: 5173,
        strictPort: true,
        cors: {
            origin: true,
        },
    },
})