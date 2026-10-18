import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import { useAuthStore } from './stores/auth';
import i18n from './i18n';
import { displayLabel } from './utils/displayLabel';
import { uiText, displayDate } from './utils/uiText';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(i18n);
app.config.globalProperties.$label = displayLabel;
app.config.globalProperties.$ui = uiText;
app.config.globalProperties.$date = displayDate;

// Initialize authentication before mounting
const authStore = useAuthStore();
authStore.initializeAuth().then(() => {
    app.mount('#app');
});
