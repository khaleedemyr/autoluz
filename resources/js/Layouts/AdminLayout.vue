<script setup>
import { computed, ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    title: { type: String, default: 'Admin' },
});

const page = usePage();
const mobileOpen = ref(false);
const user = computed(() => page.props.auth?.user);

const nav = [
    { label: 'Dashboard', route: 'admin.dashboard', match: 'admin.dashboard' },
    { label: 'Artikel', route: 'admin.articles.index', match: 'admin.articles.*' },
    { label: 'Event', route: 'admin.events.index', match: 'admin.events.*' },
    { label: 'Galeri', route: 'admin.galleries.index', match: 'admin.galleries.*' },
    { label: 'Merek', route: 'admin.brands.index', match: 'admin.brands.*' },
    { label: 'Kendaraan', route: 'admin.vehicles.index', match: 'admin.vehicles.*' },
    { label: 'Newsletter', route: 'admin.newsletter.index', match: 'admin.newsletter.*' },
    { label: 'Kategori', route: 'admin.categories.index', match: 'admin.categories.*' },
    { label: 'Komentar', route: 'admin.comments.index', match: 'admin.comments.*' },
    { label: 'Video', route: 'admin.videos.index', match: 'admin.videos.*' },
    { label: 'Users', route: 'admin.users.index', match: 'admin.users.*' },
];

function isActive(pattern) {
    return route().current(pattern);
}

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen bg-[#f3f4f6] text-charcoal">
        <div class="flex min-h-screen">
            <aside class="hidden w-64 shrink-0 border-r border-black/5 bg-charcoal text-white lg:flex lg:flex-col">
                <div class="border-b border-white/10 px-5 py-5">
                    <div class="font-display text-2xl tracking-[-0.04em]">AUTOLUZ</div>
                    <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.28em] text-brand">Admin Portal</div>
                </div>
                <nav class="flex-1 space-y-1 p-3">
                    <Link
                        v-for="item in nav"
                        :key="item.route"
                        :href="route(item.route)"
                        class="block rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                        :class="isActive(item.match) ? 'bg-brand text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
                <div class="border-t border-white/10 p-4 text-xs text-white/50">
                    <Link :href="route('home')" class="hover:text-white">← Lihat website</Link>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-black/5 bg-white/90 px-4 py-3 backdrop-blur lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="rounded-lg border border-black/10 px-3 py-2 text-sm lg:hidden"
                            @click="mobileOpen = !mobileOpen"
                        >
                            Menu
                        </button>
                        <div>
                            <h1 class="text-lg font-semibold tracking-[-0.02em]">{{ title }}</h1>
                            <p class="text-xs text-neutral-500">Kelola konten Autoluz</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="hidden text-neutral-500 sm:inline">{{ user?.name }}</span>
                        <button
                            type="button"
                            class="rounded-full bg-charcoal px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-white"
                            @click="logout"
                        >
                            Logout
                        </button>
                    </div>
                </header>

                <div v-if="mobileOpen" class="border-b border-black/5 bg-white p-3 lg:hidden">
                    <Link
                        v-for="item in nav"
                        :key="item.route"
                        :href="route(item.route)"
                        class="block rounded-lg px-3 py-2 text-sm font-semibold"
                        :class="isActive(item.match) ? 'bg-brand text-white' : 'hover:bg-mist'"
                        @click="mobileOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                </div>

                <main class="flex-1 px-4 py-6 lg:px-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
