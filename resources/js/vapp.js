import "vuetify/dist/vuetify.min.css";
import { createApp} from "vue";
import App from "./App.vue";
import vuetify from "./vuetify.js";
import router from "./router/index.js";
import store from "./store/index.js";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.prod.css"
import VueApexCharts from "vue3-apexcharts";
import Toast, {useToast} from "vue-toastification";
import "vue-toastification/dist/index.css";
const options = {
    position: 'bottom-right',
    timeout: 1000,
    closeOnClick: true,
    pauseOnHover: true,
};

import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: "pusher",
    key: "f77aa6a9dbb4581a646d",
    cluster: "eu",
    forceTLS: true
})

import {Quill} from "@vueup/vue-quill";
const Link = Quill.import('formats/link')

class CustomLink extends Link {
    static create(value) {
        const node = super.create(value);

        node.setAttribute('href', this.sanitize(value));
        if (window.isAdminUser) { // Pass this flag globally before initializing
            node.setAttribute('rel', 'dofollow');
        } else {
            node.setAttribute('rel', 'noreferrer noopener');
        }
        // Remove or change target attribute as needed
        node.removeAttribute('target');

        return node;
    }
}
Quill.register('formats/link',CustomLink,true);

const app = createApp(App);
app.use(Toast, options);
app.config.globalProperties.$toast = useToast();
window.Toast = useToast();
app.component('QuillEditor', QuillEditor);
app.use(VueApexCharts);
app.use(vuetify);
app.use(router);
app.use(store);
app.mount('#app');
