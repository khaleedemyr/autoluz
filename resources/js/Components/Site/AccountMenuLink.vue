<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
    icon: { type: String, required: true },
    accent: { type: Boolean, default: false },
    muted: { type: Boolean, default: false },
    method: { type: String, default: 'get' },
    as: { type: String, default: 'a' },
});

const icons = {
    user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    heart: 'M12 21s-6.5-4.35-9.33-8.5C.8 9.7 1.6 5.9 5 4.6c1.9-.72 3.9-.1 5 1.4 1.1-1.5 3.1-2.12 5-1.4 3.4 1.3 4.2 5.1 2.33 7.9C18.5 16.65 12 21 12 21z',
    bag: 'M6 7h12l1 13H5L6 7zm3 0V6a3 3 0 016 0v1',
    store: 'M3 9l1.5-5h15L21 9M4 9h16v11H4V9zm4 4h2v6H8v-6zm6 0h2v6h-2v-6z',
    shield: 'M12 3l8 3v6c0 5-3.4 8.7-8 10-4.6-1.3-8-5-8-10V6l8-3z',
    cog: 'M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM19.4 15a7.9 7.9 0 00.1-1.5 7.9 7.9 0 00-.1-1.5l2-1.5-2-3.5-2.4 1a7.6 7.6 0 00-2.6-1.5L14 3h-4l-.4 2.5A7.6 7.6 0 007 7L4.6 6 2.6 9.5 4.6 11a7.9 7.9 0 00-.1 1.5 7.9 7.9 0 00.1 1.5l-2 1.5 2 3.5 2.4-1a7.6 7.6 0 002.6 1.5L10 21h4l.4-2.5a7.6 7.6 0 002.6-1.5l2.4 1 2-3.5-2-1.5z',
    bell: 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    users: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm14 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
    chat: 'M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.26-.95L3 20l1.4-3.72C3.51 15.04 3 13.57 3 12c0-4.42 4.03-8 9-8s9 3.58 9 8z',
    radio: 'M5 15a7 7 0 0114 0M8 15a4 4 0 018 0M12 15.5a1 1 0 100 2 1 1 0 000-2zM4.5 9.5A11 11 0 0112 7a11 11 0 017.5 2.5',
    logout: 'M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9',
};
</script>

<template>
    <Link
        :href="href"
        :method="method"
        :as="as"
        class="flex w-full items-center gap-3 px-3.5 py-2.5 text-[13px] font-medium transition hover:bg-mist"
        :class="accent ? 'text-brand' : muted ? 'text-neutral-400 hover:text-charcoal' : 'text-charcoal/80'"
    >
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path :d="icons[icon]" />
        </svg>
        <span class="truncate"><slot /></span>
    </Link>
</template>
