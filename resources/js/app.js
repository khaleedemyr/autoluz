import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { swalToast } from './utils/swal';

const appName = import.meta.env.VITE_APP_NAME || 'Autoluz';

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

        // Show flash success via SweetAlert toast on every Inertia visit.
        const initialFlash = props.initialPage?.props?.flash?.success;
        if (initialFlash) {
            queueMicrotask(() => swalToast(initialFlash));
        }

        router.on('success', (event) => {
            const message = event.detail.page.props?.flash?.success;
            if (message) {
                swalToast(message);
            }
        });

        return vueApp.mount(el);
    },
    progress: {
        color: '#FF1E2D',
    },
});
