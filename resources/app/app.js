/**
 * aap.js
 *
 * This file is the entry point of the Vite application. It contains the
 * necessary code to initialize the application and mount the root
 * component to the DOM.
 *
 * When the application is built, Vite uses this file as the input and
 * generates a bundle that can be loaded by the browser.
 *
 * @module aap
 */

import "./app.css";

import Alpine from "alpinejs";

import axios from "axios";

window.Alpine = Alpine;

window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

Alpine.start();
