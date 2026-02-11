
import { createApp} from "vue";
import App from "./App.vue";
import vuetify from "./vuetify.js";
import router from "./router/index.js";
import store from "./store/index.js";
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

const app = createApp(App);
app.use(Toast, options);
app.config.globalProperties.$toast = useToast();
window.Toast = useToast();
app.use(VueApexCharts);
app.use(vuetify);
app.use(router);
app.use(store);
app.mount('#app');
