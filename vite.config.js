import { defineConfig, loadEnv } from "vite";
import path from "path";
import tailwindcss from "@tailwindcss/vite";
import fullReload from "vite-plugin-full-reload";

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, __dirname); // default prefix = VITE_ only
  const appUrl = (env.VITE_APP_URL || "http://localhost:8080").replace(
    /\/+$/,
    "",
  );

  return {
    plugins: [tailwindcss(), fullReload(["./resources/views/**/*.blade.php"])],
    base: mode === "production" ? "/build/" : "/",
    root: path.resolve(__dirname, "./resources/app"),
    envDir: __dirname, // frontend import.meta.env reads .env from project root
    server: {
      strictPort: true,
      port: 5173,
      origin: "http://localhost:5173",
      cors: {
        origin: [
          appUrl,
          /^https?:\/\/([\w-]+\.)*test(:\d+)?$/, // any Herd *.test site
          /^https?:\/\/(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/,
        ],
      },
    },
    build: {
      outDir: path.resolve(__dirname, "./public/build"),
      emptyOutDir: true,
      manifest: true,
      rollupOptions: {
        input: path.resolve(__dirname, "./resources/app/app.js"),
      },
    },
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "./resources/app"),
      },
    },
  };
});
