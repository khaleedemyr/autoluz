<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Dashboard Toko' },
});

const page = usePage();
const mobileOpen = ref(false);
const user = computed(() => page.props.auth?.user);
const storeName = computed(() => user.value?.store?.name || 'Toko');

const items = [
    { label: 'Dashboard', route: 'seller.dashboard', match: 'seller.dashboard' },
    { label: 'Produk', route: 'seller.products.index', match: 'seller.products.*' },
    { label: 'Pesanan', route: 'seller.orders.index', match: 'seller.orders.*' },
    { label: 'Pengaturan', route: 'seller.settings.edit', match: 'seller.settings.*' },
];

function isActive(pattern) {
    return route().current(pattern);
}

function closeMobile() {
    mobileOpen.value = false;
}

function logout() {
    router.post(route('logout'));
}

watch(
    () => page.url,
    () => closeMobile(),
);
</script>

<template>
    <div class="min-h-screen bg-[#f3f4f6] text-charcoal">
        <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="closeMobile" />

        <div class="flex min-h-screen">
            <aside
                class="fixed inset-y-0 left-0 z-50 flex w-72 shrink-0 flex-col bg-[#111318] text-white shadow-2xl transition-transform duration-200 lg:static lg:translate-x-0"
                :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <div class="border-b border-white/10 px-5 py-5">
                    <div class="font-display text-2xl tracking-[-0.04em]">AUTOLUZ</div>
                    <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.28em] text-brand">Seller Dashboard</div>
                    <p class="mt-3 truncate text-sm text-white/70">{{ storeName }}</p>
                </div>
                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    <Link
                        v-for="item in items"
                        :key="item.route"
                        :href="route(item.route)"
                        class="flex items-center rounded-xl px-3 py-2 text-sm font-medium transition"
                        :class="isActive(item.match) ? 'bg-brand text-white shadow-sm' : 'text-white/70 hover:bg-white/5 hover:text-white'"
                        @click="closeMobile"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
                <div class="border-t border-white/10 p-4">
                    <Link :href="route('shop.index')" class="text-xs text-white/50 transition hover:text-white">← Lihat etalase</Link>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-black/5 bg-white/90 px-4 py-3 backdrop-blur lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" class="rounded-lg border border-black/10 px-3 py-2 text-sm lg:hidden" @click="mobileOpen = !mobileOpen">Menu</button>
                        <div>
                            <h1 class="text-lg font-semibold tracking-[-0.02em]">{{ title }}</h1>
                            <p class="text-xs text-neutral-500">Kelola toko partner Autoluz</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="hidden text-neutral-500 sm:inline">{{ user?.name }}</span>
                        <button type="button" class="rounded-full bg-charcoal px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-white" @click="logout">Logout</button>
                    </div>
                </header>
                <main class="flex-1 px-4 py-6 lg:px-8">
                    <p v-if="page.props.flash?.success" class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ page.props.flash.success }}</p>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
