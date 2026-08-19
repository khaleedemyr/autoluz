<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Admin' },
});

const page = usePage();
const mobileOpen = ref(false);
const menuQuery = ref('');
const openGroups = ref({});
const user = computed(() => page.props.auth?.user);

const navGroups = [
    {
        key: 'utama',
        label: 'Utama',
        items: [
            { label: 'Dashboard', route: 'admin.dashboard', match: 'admin.dashboard', permission: 'dashboard' },
        ],
    },
    {
        key: 'konten',
        label: 'Konten',
        items: [
            { label: 'Artikel', route: 'admin.articles.index', match: 'admin.articles.*', permission: 'articles' },
            { label: 'Event', route: 'admin.events.index', match: 'admin.events.*', permission: 'events' },
            { label: 'Galeri', route: 'admin.galleries.index', match: 'admin.galleries.*', permission: 'galleries' },
            { label: 'Video', route: 'admin.videos.index', match: 'admin.videos.*', permission: 'videos' },
            { label: 'Kategori', route: 'admin.categories.index', match: 'admin.categories.*', permission: 'categories' },
            { label: 'Komentar', route: 'admin.comments.index', match: 'admin.comments.*', permission: 'comments' },
        ],
    },
    {
        key: 'katalog',
        label: 'Katalog',
        items: [
            { label: 'Merek', route: 'admin.brands.index', match: 'admin.brands.*', permission: 'brands' },
            { label: 'Kendaraan', route: 'admin.vehicles.index', match: 'admin.vehicles.*', permission: 'vehicles' },
        ],
    },
    {
        key: 'toko',
        label: 'Toko',
        items: [
            { label: 'Produk', route: 'admin.products.index', match: 'admin.products.*', permission: 'products' },
            { label: 'Toko Partner', route: 'admin.stores.index', match: 'admin.stores.*', permission: 'stores' },
            { label: 'Kategori Toko', route: 'admin.shop-categories.index', match: 'admin.shop-categories.*', permission: 'products' },
            { label: 'Pesanan', route: 'admin.orders.index', match: 'admin.orders.*', permission: 'orders' },
            { label: 'Pengaturan Toko', route: 'admin.shop-settings.edit', match: 'admin.shop-settings.*', permission: 'products' },
        ],
    },
    {
        key: 'audiens',
        label: 'Audiens',
        items: [
            { label: 'Newsletter', route: 'admin.newsletter.index', match: 'admin.newsletter.*', permission: 'newsletter' },
        ],
    },
    {
        key: 'sistem',
        label: 'Sistem',
        items: [
            { label: 'Users', route: 'admin.users.index', match: 'admin.users.*', permission: 'users' },
            { label: 'Role', route: 'admin.roles.index', match: 'admin.roles.*', permission: 'roles' },
        ],
    },
];

const permissions = computed(() => page.props.auth?.user?.permissions || []);
const canSee = (key) => permissions.value.includes('*') || permissions.value.includes(key);

function isActive(pattern) {
    return route().current(pattern);
}

const visibleGroups = computed(() => {
    const q = menuQuery.value.trim().toLowerCase();

    return navGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => {
                if (!canSee(item.permission)) return false;
                if (!q) return true;
                return item.label.toLowerCase().includes(q) || group.label.toLowerCase().includes(q);
            }),
        }))
        .filter((group) => group.items.length);
});

function groupHasActive(group) {
    return group.items.some((item) => isActive(item.match));
}

function isGroupOpen(key) {
    if (menuQuery.value.trim()) return true;
    if (Object.prototype.hasOwnProperty.call(openGroups.value, key)) {
        return openGroups.value[key];
    }
    return true;
}

function toggleGroup(key) {
    openGroups.value = {
        ...openGroups.value,
        [key]: !isGroupOpen(key),
    };
}

function closeMobile() {
    mobileOpen.value = false;
}

function logout() {
    router.post(route('logout'));
}

watch(
    () => page.url,
    () => {
        closeMobile();
    },
);
</script>

<template>
    <div class="min-h-screen bg-[#f3f4f6] text-charcoal">
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            @click="closeMobile"
        />

        <div class="flex min-h-screen">
            <aside
                class="fixed inset-y-0 left-0 z-50 flex w-72 shrink-0 flex-col bg-[#111318] text-white shadow-2xl transition-transform duration-200 lg:static lg:translate-x-0"
                :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <div class="border-b border-white/10 px-5 py-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="font-display text-2xl tracking-[-0.04em]">AUTOLUZ</div>
                            <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.28em] text-brand">Admin Portal</div>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-white/50 hover:bg-white/10 hover:text-white lg:hidden"
                            aria-label="Tutup menu"
                            @click="closeMobile"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </div>
                    <label class="mt-4 flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/70 focus-within:border-brand/60 focus-within:bg-white/10">
                        <svg class="h-4 w-4 shrink-0 text-white/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="7" />
                            <path d="M20 20l-3-3" />
                        </svg>
                        <input
                            v-model="menuQuery"
                            type="search"
                            placeholder="Cari menu..."
                            class="w-full border-0 bg-transparent p-0 text-sm text-white placeholder:text-white/35 focus:ring-0"
                        />
                    </label>
                </div>

                <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
                    <p v-if="!visibleGroups.length" class="px-2 py-6 text-center text-xs text-white/40">
                        Menu tidak ditemukan
                    </p>
                    <section v-for="group in visibleGroups" :key="group.key">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] transition"
                            :class="groupHasActive(group) ? 'text-white/70' : 'text-white/40 hover:text-white/70'"
                            @click="toggleGroup(group.key)"
                        >
                            <span>{{ group.label }}</span>
                            <svg
                                class="h-3.5 w-3.5 transition-transform"
                                :class="isGroupOpen(group.key) ? 'rotate-180' : ''"
                                viewBox="0 0 12 12"
                                fill="none"
                            >
                                <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                        </button>
                        <div v-show="isGroupOpen(group.key)" class="mt-1 space-y-0.5">
                            <Link
                                v-for="item in group.items"
                                :key="item.route"
                                :href="route(item.route)"
                                class="flex items-center rounded-xl px-3 py-2 text-sm font-medium transition"
                                :class="isActive(item.match) ? 'bg-brand text-white shadow-sm' : 'text-white/70 hover:bg-white/5 hover:text-white'"
                                @click="closeMobile"
                            >
                                {{ item.label }}
                            </Link>
                        </div>
                    </section>
                </nav>

                <div class="border-t border-white/10 p-4">
                    <div class="mb-3 truncate text-xs text-white/45">{{ user?.name }}</div>
                    <Link :href="route('home')" class="text-xs text-white/50 transition hover:text-white">
                        ← Lihat website
                    </Link>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col lg:ml-0">
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

                <main class="flex-1 px-4 py-6 lg:px-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
