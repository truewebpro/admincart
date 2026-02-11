
import './bootstrap';
import { createApp } from 'vue';
import App from "./App.vue";
import vuetify from "./vuetify.js";
import router from "./router/index.js";
import store from "./store/index.js";

const app = createApp({App});

import ExampleComponent from './components/ExampleComponent.vue';
app.component('example-component', ExampleComponent);

app.use(vuetify)
app.use(router);
app.use(store);
app.mount('#app');
