import { defineConfig, loadEnv } from "vite";
import path from "path";
import tailwindcss from "@tailwindcss/vite";
import fullReload from "vite-plugin-full-reload";

const resolvePath = (relativePath) => path.resolve(__dirname, relativePath);

const cors = ({ appUrl }) => ({
  origin: [
    appUrl.replace(/\/+$/, ""),
    /^https?:\/\/([\w-]+\.)*test(:\d+)?$/, // any Herd *.test site
    /^https?:\/\/(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/,
  ],
});

const viteAppUrl = (mode) => {
  const env = loadEnv(mode, __dirname); // default prefix = VITE_ only
  return env.VITE_APP_URL || "http://localhost:8080";
};

export default defineConfig(({ mode }) => ({
  plugins: [tailwindcss(), fullReload(["./resources/views/**/*.blade.php"])],
  root: resolvePath("./resources/app"),
  server: {
    strictPort: true,
    port: 5173,
    origin: "http://localhost:5173",
    cors: cors({ appUrl: viteAppUrl(mode) }),
  },
  build: {
    outDir: resolvePath("./public/build"),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: [
        resolvePath("./resources/app/app.js"),
        resolvePath("./resources/app/app.css"),
      ],
    },
  },
  resolve: {
    alias: {
      "@": resolvePath("./resources/app"),
    },
  },
  base: mode === "production" ? "/build/" : "/",
  envDir: __dirname, // frontend import.meta.env reads .env from project root
}));
