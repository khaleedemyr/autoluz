import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Autoluz';

async function flashSuccess(message) {
    if (!message) return;
    const { swalToast } = await import('./utils/swal');
    swalToast(message);
}

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        const initialFlash = props.initialPage?.props?.flash?.success;
        if (initialFlash) {
            queueMicrotask(() => flashSuccess(initialFlash));
        }

        router.on('success', (event) => {
            flashSuccess(event.detail.page.props?.flash?.success);
        });

        return vueApp.mount(el);
    },
    progress: {
        color: '#FF1E2D',
    },
});
