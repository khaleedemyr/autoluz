<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import MegaMenu from '@/Components/Site/MegaMenu.vue';
import CommunityNotificationBell from '@/Components/Community/CommunityNotificationBell.vue';
import { useI18n } from '@/composables/useI18n';

const page = usePage();
const { t, locale, setLocale } = useI18n();
const nav = computed(() => page.props.nav || { items: [], more: [], primary: [] });
const items = computed(() => nav.value.items || []);
const more = computed(() => nav.value.more || []);

const activeKey = ref(null);
const mobileOpen = ref(false);
const searchOpen = ref(false);
const accountOpen = ref(false);
const scrolled = ref(false);
const q = ref('');
const accountRoot = ref(null);
let closeTimer = null;

const activeItem = computed(() => items.value.find((i) => i.key === activeKey.value) || null);
const megaOpen = computed(() => !!activeItem.value || activeKey.value === 'more');
const authUser = computed(() => page.props.auth?.user || null);

function onScroll() {
    scrolled.value = window.scrollY > 8;
}

function closeAccount() {
    accountOpen.value = false;
}

function toggleAccount() {
    accountOpen.value = !accountOpen.value;
}

function onDocClick(e) {
    if (!accountRoot.value?.contains(e.target)) {
        closeAccount();
    }
}

function openMega(key) {
    clearTimeout(closeTimer);
    activeKey.value = key;
}

function scheduleClose() {
    clearTimeout(closeTimer);
    closeTimer = setTimeout(() => {
        activeKey.value = null;
    }, 180);
}

function keepOpen() {
    clearTimeout(closeTimer);
}

function closeMega() {
    clearTimeout(closeTimer);
    activeKey.value = null;
}

function submitSearch() {
    router.get(route('search'), { q: q.value }, { preserveState: true });
    searchOpen.value = false;
    mobileOpen.value = false;
}

watch(
    () => page.url,
    () => {
        closeMega();
        closeAccount();
        mobileOpen.value = false;
        searchOpen.value = false;
    },
);

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('click', onDocClick);
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    document.removeEventListener('click', onDocClick);
});
</script>

<template>
    <header class="fixed inset-x-0 top-0 z-50">
        <!-- Dim page behind menu (must stay under nav+mega stacking context) -->
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="megaOpen"
                class="fixed inset-0 z-30 bg-charcoal/45"
                @click="closeMega"
            />
        </Transition>

        <div
            class="relative z-50 border-b text-white backdrop-blur-xl transition-[background-color,box-shadow,border-color] duration-300"
            :class="scrolled
                ? 'border-white/10 bg-charcoal/55 shadow-[0_12px_40px_rgba(0,0,0,0.28)]'
                : 'border-white/10 bg-charcoal/95'"
        >
            <div class="container-editorial flex h-[4.25rem] items-center gap-2 lg:h-[4.75rem] lg:gap-3">
                <Link
                    :href="route('home')"
                    class="group relative flex shrink-0 flex-col justify-center pr-2 lg:pr-3"
                >
                    <span class="font-display text-[1.55rem] leading-none tracking-[-0.04em] sm:text-[1.85rem]">
                        AUTOLUZ
                    </span>
                    <span class="mt-1 text-[8px] font-semibold uppercase tracking-[0.3em] text-brand sm:text-[9px] sm:tracking-[0.34em]">
                        Car &amp; Moto
                    </span>
                    <span class="absolute -bottom-1 left-0 h-[2px] w-10 bg-brand transition-all duration-500 ease-editorial group-hover:w-16" />
                </Link>

                <nav
                    class="relative hidden min-w-0 flex-1 items-center justify-start gap-0.5 overflow-hidden lg:flex xl:gap-1"
                    @mouseleave="scheduleClose"
                >
                    <Link
                        :href="route('home')"
                        class="shrink-0 rounded-full px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.1em] text-white/70 transition hover:bg-white/5 hover:text-white xl:px-2.5 xl:text-[11px] xl:tracking-[0.12em]"
                        :class="{ 'bg-white/10 text-white': page.url === '/' && !megaOpen }"
                        @mouseenter="closeMega"
                    >
                        {{ t('home') }}
                    </Link>

                    <Link
                        :href="route('events.index')"
                        class="shrink-0 rounded-full px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.1em] text-white/70 transition hover:bg-white/5 hover:text-white xl:px-2.5 xl:text-[11px] xl:tracking-[0.12em]"
                        :class="{ 'bg-white/10 text-white': page.url.startsWith('/event') && !megaOpen }"
                        @mouseenter="closeMega"
                    >
                        {{ t('events_nav') }}
                    </Link>

                    <Link
                        :href="route('community.index')"
                        class="shrink-0 rounded-full px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.1em] text-white/70 transition hover:bg-white/5 hover:text-white xl:px-2.5 xl:text-[11px] xl:tracking-[0.12em]"
                        :class="{ 'bg-white/10 text-white': page.url.startsWith('/komunitas') && !megaOpen }"
                        @mouseenter="closeMega"
                    >
                        {{ t('community_nav') }}
                    </Link>

                    <button
                        v-for="item in items"
                        :key="item.key"
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.1em] transition xl:px-2.5 xl:text-[11px] xl:tracking-[0.12em]"
                        :class="activeKey === item.key ? 'bg-brand text-white shadow-glow' : 'text-white/70 hover:bg-white/5 hover:text-white'"
                        @mouseenter="openMega(item.key)"
                        @focus="openMega(item.key)"
                        @click="router.visit(route('categories.show', item.slug))"
                    >
                        {{ item.name }}
                        <svg class="h-2.5 w-2.5 opacity-70" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                            <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-2 text-[10px] font-semibold uppercase tracking-[0.1em] transition xl:px-2.5 xl:text-[11px] xl:tracking-[0.12em]"
                        :class="activeKey === 'more' ? 'bg-brand text-white' : 'text-white/70 hover:bg-white/5 hover:text-white'"
                        @mouseenter="openMega('more')"
                    >
                        {{ t('see_more') }}
                        <svg class="h-2.5 w-2.5 opacity-70" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                            <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </button>
                </nav>

                <div class="ml-auto flex shrink-0 items-center gap-0.5 sm:gap-1">
                    <div class="hidden items-center rounded-full border border-white/15 p-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] xl:inline-flex">
                        <button
                            type="button"
                            class="rounded-full px-2 py-1 transition"
                            :class="locale === 'id' ? 'bg-brand text-white' : 'text-white/60 hover:text-white'"
                            @click="setLocale('id')"
                        >
                            {{ t('lang_id') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-2 py-1 transition"
                            :class="locale === 'en' ? 'bg-brand text-white' : 'text-white/60 hover:text-white'"
                            @click="setLocale('en')"
                        >
                            {{ t('lang_en') }}
                        </button>
                    </div>

                    <template v-if="authUser">
                        <CommunityNotificationBell class="hidden lg:block" />

                        <Link
                            :href="route('community.messages.index')"
                            class="relative hidden h-10 w-10 items-center justify-center rounded-full text-white/75 transition hover:bg-white/10 hover:text-white lg:inline-flex"
                            :aria-label="t('community_messages')"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span
                                v-if="authUser.unread_messages"
                                class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[9px] font-bold leading-none text-white"
                            >
                                {{ authUser.unread_messages > 5 ? `+${authUser.unread_messages}` : authUser.unread_messages }}
                            </span>
                        </Link>

                        <div ref="accountRoot" class="relative hidden lg:block">
                            <button
                                type="button"
                                class="inline-flex max-w-[8.5rem] items-center gap-2 rounded-full border border-white/15 py-1 pl-1 pr-2.5 text-white/80 transition hover:bg-white/10 hover:text-white"
                                @click.stop="toggleAccount"
                            >
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/10 text-[11px] font-semibold uppercase">
                                    <img
                                        v-if="authUser.avatar_url"
                                        :src="authUser.avatar_url"
                                        :alt="authUser.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else>{{ (authUser.name || '?').slice(0, 1) }}</span>
                                </span>
                                <span class="truncate text-[10px] font-semibold uppercase tracking-[0.1em]">
                                    {{ authUser.name }}
                                </span>
                                <svg class="h-2.5 w-2.5 shrink-0 opacity-70" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                    <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </button>

                            <div
                                v-if="accountOpen"
                                class="absolute right-0 top-full z-[60] mt-2 w-52 overflow-hidden rounded-2xl border border-[var(--line)] bg-white text-charcoal shadow-lift"
                            >
                                <Link
                                    v-if="authUser.username"
                                    :href="route('community.profile', authUser.username)"
                                    class="block px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] hover:bg-mist"
                                    @click="closeAccount"
                                >
                                    {{ t('community_profile') }}
                                </Link>
                                <Link
                                    :href="route('community.settings')"
                                    class="block px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] hover:bg-mist"
                                    @click="closeAccount"
                                >
                                    {{ t('community_settings') }}
                                </Link>
                                <Link
                                    :href="route('community.notifications')"
                                    class="block px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] hover:bg-mist"
                                    @click="closeAccount"
                                >
                                    {{ t('community_notifications') }}
                                </Link>
                                <Link
                                    :href="route('community.groups.index')"
                                    class="block px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] hover:bg-mist"
                                    @click="closeAccount"
                                >
                                    {{ t('community_groups') }}
                                </Link>
                                <Link
                                    :href="route('community.messages.index')"
                                    class="block px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] hover:bg-mist"
                                    @click="closeAccount"
                                >
                                    {{ t('community_messages') }}
                                </Link>
                                <Link
                                    :href="route('community.live-chat')"
                                    class="block px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] hover:bg-mist"
                                    @click="closeAccount"
                                >
                                    {{ t('community_live_chat') }}
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="block w-full border-t border-[var(--line)] px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-charcoal/55 hover:bg-mist"
                                    @click="closeAccount"
                                >
                                    {{ t('community_logout') }}
                                </Link>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="hidden rounded-full px-2.5 py-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-white/75 transition hover:bg-white/10 hover:text-white lg:inline-block xl:px-3 xl:text-[11px]"
                        >
                            {{ t('community_login') }}
                        </Link>
                        <Link
                            :href="route('register')"
                            class="hidden rounded-full bg-brand px-2.5 py-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-white transition hover:opacity-90 lg:inline-block xl:px-3 xl:text-[11px]"
                        >
                            {{ t('community_register') }}
                        </Link>
                    </template>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full text-white/75 transition hover:bg-white/10 hover:text-white"
                        :aria-label="t('search')"
                        @click="searchOpen = !searchOpen"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.6-5.4a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full text-white/75 lg:hidden"
                        aria-label="Menu"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <span class="flex flex-col gap-1.5">
                            <span class="block h-0.5 w-5 bg-current" />
                            <span class="block h-0.5 w-5 bg-current" />
                            <span class="block h-0.5 w-5 bg-current" />
                        </span>
                    </button>
                </div>
            </div>

            <Transition
                enter-active-class="transition duration-200 ease-editorial"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="searchOpen"
                    class="border-t border-white/10 transition-colors duration-300"
                    :class="scrolled ? 'bg-charcoal/60 backdrop-blur-xl' : 'bg-charcoal-soft'"
                >
                    <form class="container-editorial flex items-center gap-3 py-4" @submit.prevent="submitSearch">
                        <input
                            v-model="q"
                            type="search"
                            autofocus
                            :placeholder="t('search_placeholder')"
                            class="flex-1 rounded-none border-0 border-b border-white/15 bg-transparent px-0 py-2 text-sm text-white placeholder:text-white/35 focus:border-brand focus:ring-0"
                        />
                        <button type="submit" class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand">
                            {{ t('search') }}
                        </button>
                    </form>
                </div>
            </Transition>

            <Transition
                enter-active-class="transition duration-200 ease-editorial"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-editorial"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="megaOpen"
                    class="absolute inset-x-0 top-full z-50"
                    @mouseenter="keepOpen"
                    @mouseleave="scheduleClose"
                >
                    <MegaMenu
                        v-if="activeItem"
                        :item="activeItem"
                        @navigate="closeMega"
                    />
                    <div
                        v-else-if="activeKey === 'more'"
                        class="border-b border-[var(--line)] bg-white text-charcoal shadow-lift"
                    >
                        <div class="container-editorial py-6">
                            <div class="mb-5 flex flex-wrap gap-2 border-b border-[var(--line)] pb-5">
                                <Link
                                    :href="route('brands.index')"
                                    class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                                    @click="closeMega"
                                >
                                    {{ t('brands_nav') }}
                                </Link>
                                <Link
                                    :href="route('vehicles.compare')"
                                    class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                                    @click="closeMega"
                                >
                                    {{ t('compare_nav') }}
                                </Link>
                                <Link
                                    :href="route('credit.simulate')"
                                    class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                                    @click="closeMega"
                                >
                                    {{ t('credit_nav') }}
                                </Link>
                                <Link
                                    :href="route('galleries.index')"
                                    class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                                    @click="closeMega"
                                >
                                    {{ t('galleries_nav') }}
                                </Link>
                                <Link
                                    :href="route('articles.index')"
                                    class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                                    @click="closeMega"
                                >
                                    {{ t('see_all_articles') }}
                                </Link>
                            </div>
                            <p class="mb-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-neutral-400">
                                {{ t('footer_categories') }}
                            </p>
                            <div class="grid gap-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                <Link
                                    v-for="cat in more"
                                    :key="cat.id"
                                    :href="route('categories.show', cat.slug)"
                                    class="rounded-xl px-4 py-3 text-[12px] font-semibold uppercase tracking-[0.14em] text-charcoal/75 transition hover:bg-mist hover:text-brand"
                                    @click="closeMega"
                                >
                                    {{ cat.name }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <div
            v-if="mobileOpen"
            class="relative z-50 border-b border-white/10 lg:hidden transition-colors duration-300"
            :class="scrolled ? 'bg-charcoal/60 backdrop-blur-xl' : 'bg-charcoal'"
        >
            <nav class="container-editorial flex flex-col py-3">
                <div class="mb-2 flex items-center gap-2 px-2">
                    <button
                        type="button"
                        class="rounded-full px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.14em]"
                        :class="locale === 'id' ? 'bg-brand text-white' : 'bg-white/10 text-white/70'"
                        @click="setLocale('id')"
                    >
                        {{ t('lang_id') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-full px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.14em]"
                        :class="locale === 'en' ? 'bg-brand text-white' : 'bg-white/10 text-white/70'"
                        @click="setLocale('en')"
                    >
                        {{ t('lang_en') }}
                    </button>
                </div>
                <Link
                    :href="route('home')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('home') }}
                </Link>
                <Link
                    :href="route('events.index')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('events_nav') }}
                </Link>
                <Link
                    :href="route('community.index')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('community_nav') }}
                </Link>
                <template v-if="authUser">
                    <Link
                        :href="route('community.notifications')"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                        @click="mobileOpen = false"
                    >
                        {{ t('community_notifications') }}
                        <span
                            v-if="authUser.unread_notifications"
                            class="ml-2 rounded-full bg-brand px-1.5 py-0.5 text-[10px] text-white"
                        >
                            {{ authUser.unread_notifications }}
                        </span>
                    </Link>
                    <Link
                        :href="route('community.messages.index')"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                        @click="mobileOpen = false"
                    >
                        {{ t('community_messages') }}
                        <span
                            v-if="authUser.unread_messages"
                            class="ml-2 rounded-full bg-brand px-1.5 py-0.5 text-[10px] text-white"
                        >
                            {{ authUser.unread_messages }}
                        </span>
                    </Link>
                    <Link
                        :href="route('community.settings')"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                        @click="mobileOpen = false"
                    >
                        {{ t('community_settings') }}
                    </Link>
                    <Link
                        v-if="authUser.username"
                        :href="route('community.profile', authUser.username)"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                        @click="mobileOpen = false"
                    >
                        {{ authUser.name }}
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="rounded-lg px-2 py-3 text-left text-sm font-semibold uppercase tracking-[0.14em] text-white/55"
                        @click="mobileOpen = false"
                    >
                        {{ t('community_logout') }}
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                        @click="mobileOpen = false"
                    >
                        {{ t('community_login') }}
                    </Link>
                    <Link
                        :href="route('register')"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-brand"
                        @click="mobileOpen = false"
                    >
                        {{ t('community_register') }}
                    </Link>
                </template>
                <Link
                    :href="route('brands.index')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('brands_nav') }}
                </Link>
                <Link
                    :href="route('vehicles.compare')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('compare_nav') }}
                </Link>
                <Link
                    :href="route('credit.simulate')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('credit_nav') }}
                </Link>
                <Link
                    :href="route('galleries.index')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('galleries_nav') }}
                </Link>
                <Link
                    v-for="item in items"
                    :key="item.key"
                    :href="route('categories.show', item.slug)"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ item.name }}
                </Link>
                <Link
                    v-for="cat in more"
                    :key="cat.id"
                    :href="route('categories.show', cat.slug)"
                    class="rounded-lg px-2 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white/50"
                    @click="mobileOpen = false"
                >
                    {{ cat.name }}
                </Link>
            </nav>
        </div>
    </header>
</template>
