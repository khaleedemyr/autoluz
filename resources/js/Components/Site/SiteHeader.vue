<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import MegaMenu from '@/Components/Site/MegaMenu.vue';
import CommunityNotificationBell from '@/Components/Community/CommunityNotificationBell.vue';
import CartIcon from '@/Components/Site/CartIcon.vue';
import WishlistIcon from '@/Components/Site/WishlistIcon.vue';
import AccountMenuLink from '@/Components/Site/AccountMenuLink.vue';
import AutoluzMark from '@/Components/Site/AutoluzMark.vue';
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
const newsMegaOpen = computed(() => items.value.some((row) => row.key === activeKey.value));
const moreMegaOpen = computed(() => activeKey.value === 'more');
const communityBadge = computed(() => Number(authUser.value?.unread_messages || 0));
const tabCls = 'relative flex h-full flex-col items-center justify-center gap-0.5 text-[9px] font-semibold uppercase tracking-[0.12em] transition';
const tabIdle = 'text-white/45';
const tabOn = 'text-brand';

function isPath(prefix) {
    const url = page.url || '';
    return prefix === '/' ? url === '/' : url.startsWith(prefix);
}

function toggleMore() {
    mobileOpen.value = !mobileOpen.value;
    searchOpen.value = false;
}

const navLink = 'relative shrink-0 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55 transition hover:text-white';
const navLinkActive = 'text-white after:absolute after:inset-x-3 after:bottom-1 after:h-px after:bg-brand';
const iconBtn = 'relative inline-flex h-9 w-9 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white';
const divider = 'mx-1 hidden h-4 w-px bg-white/15 lg:block';

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

function openSupport() {
    closeMega();
    mobileOpen.value = false;
    window.dispatchEvent(new CustomEvent('autoluz-support-open'));
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
            <div class="container-editorial flex h-[4.25rem] items-center gap-2 lg:h-[4.75rem] lg:gap-6">
                <Link
                    :href="route('home')"
                    class="group relative flex shrink-0 items-center gap-2.5"
                >
                    <AutoluzMark
                        class="h-9 w-9 rounded-[0.7rem] ring-1 ring-white/10 sm:h-10 sm:w-10 sm:rounded-[0.8rem]"
                    />
                    <span class="flex flex-col justify-center">
                        <span class="font-display text-[1.35rem] leading-none tracking-[-0.04em] sm:text-[1.65rem]">
                            AUTOLUZ
                        </span>
                        <span class="mt-1 text-[8px] font-semibold uppercase tracking-[0.3em] text-brand sm:text-[9px] sm:tracking-[0.34em]">
                            Car &amp; Moto
                        </span>
                    </span>
                    <span class="absolute -bottom-1 left-12 h-[2px] w-10 bg-brand transition-all duration-500 ease-editorial group-hover:w-16" />
                </Link>

                <nav
                    class="relative hidden min-w-0 flex-1 items-center justify-center lg:flex"
                    @mouseleave="scheduleClose"
                >
                    <Link
                        :href="route('home')"
                        :class="[navLink, page.url === '/' && !megaOpen ? navLinkActive : '']"
                        @mouseenter="closeMega"
                    >
                        {{ t('home') }}
                    </Link>
                    <Link
                        :href="route('events.index')"
                        :class="[navLink, page.url.startsWith('/event') && !megaOpen ? navLinkActive : '']"
                        @mouseenter="closeMega"
                    >
                        {{ t('events_nav') }}
                    </Link>
                    <Link
                        :href="route('community.index')"
                        :class="[navLink, page.url.startsWith('/komunitas') && !megaOpen ? navLinkActive : '']"
                        @mouseenter="closeMega"
                    >
                        {{ t('community_nav') }}
                    </Link>
                    <Link
                        :href="route('shop.index')"
                        :class="[navLink, page.url.startsWith('/toko') && !megaOpen ? navLinkActive : '']"
                        @mouseenter="closeMega"
                    >
                        {{ t('shop_nav') }}
                    </Link>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1"
                        :class="[navLink, newsMegaOpen ? navLinkActive : '']"
                        @mouseenter="openMega(items[0]?.key)"
                        @click="router.visit(route('articles.index'))"
                    >
                        {{ t('news_nav') }}
                        <svg class="h-2.5 w-2.5 opacity-60" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                            <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1"
                        :class="[navLink, moreMegaOpen ? navLinkActive : '']"
                        @mouseenter="openMega('more')"
                    >
                        {{ t('see_more') }}
                        <svg class="h-2.5 w-2.5 opacity-60" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                            <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </button>
                </nav>

                <div class="ml-auto flex shrink-0 items-center">
                    <button
                        type="button"
                        class="hidden h-9 min-w-9 items-center justify-center rounded-full text-[10px] font-semibold tracking-[0.14em] text-white/65 transition hover:bg-white/10 hover:text-white lg:inline-flex"
                        :aria-label="t('lang_id') + ' / ' + t('lang_en')"
                        @click="setLocale(locale === 'id' ? 'en' : 'id')"
                    >
                        {{ locale === 'id' ? t('lang_id') : t('lang_en') }}
                    </button>

                    <span :class="divider" />

                    <template v-if="authUser">
                        <CommunityNotificationBell />
                        <Link
                            :href="route('community.messages.index')"
                            :class="['hidden lg:inline-flex', iconBtn]"
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

                        <span :class="divider" />

                        <div ref="accountRoot" class="relative hidden lg:block">
                            <button
                                type="button"
                                :class="iconBtn"
                                :aria-label="authUser.name"
                                @click.stop="toggleAccount"
                            >
                                <span class="flex h-7 w-7 items-center justify-center overflow-hidden rounded-full bg-white/10 text-[11px] font-semibold uppercase">
                                    <img
                                        v-if="authUser.avatar_url"
                                        :src="authUser.avatar_url"
                                        :alt="authUser.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else>{{ (authUser.name || '?').slice(0, 1) }}</span>
                                </span>
                            </button>

                            <div
                                v-if="accountOpen"
                                class="absolute right-0 top-full z-[60] mt-2 w-60 overflow-hidden rounded-2xl border border-[var(--line)] bg-white text-charcoal shadow-lift"
                            >
                                <div class="flex items-center gap-3 border-b border-[var(--line)] px-3.5 py-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mist text-xs font-semibold uppercase">
                                        <img
                                            v-if="authUser.avatar_url"
                                            :src="authUser.avatar_url"
                                            :alt="authUser.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else>{{ (authUser.name || '?').slice(0, 1) }}</span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ authUser.name }}</p>
                                        <p v-if="authUser.username" class="truncate text-xs text-neutral-400">@{{ authUser.username }}</p>
                                    </div>
                                </div>

                                <div class="py-1.5">
                                    <AccountMenuLink
                                        v-if="authUser.username"
                                        :href="route('community.profile', authUser.username)"
                                        icon="user"
                                        @click="closeAccount"
                                    >
                                        {{ t('community_profile') }}
                                    </AccountMenuLink>
                                    <AccountMenuLink :href="route('shop.wishlist')" icon="heart" @click="closeAccount">
                                        {{ t('shop_wishlist') }}
                                    </AccountMenuLink>
                                    <AccountMenuLink :href="route('shop.orders.index')" icon="bag" @click="closeAccount">
                                        {{ t('shop_orders') }}
                                    </AccountMenuLink>
                                </div>

                                <div v-if="authUser.is_seller || authUser.is_admin" class="border-t border-[var(--line)] py-1.5">
                                    <AccountMenuLink
                                        v-if="authUser.is_seller"
                                        :href="route('seller.dashboard')"
                                        icon="store"
                                        accent
                                        @click="closeAccount"
                                    >
                                        {{ t('shop_seller_dash') }}
                                    </AccountMenuLink>
                                    <AccountMenuLink
                                        v-if="authUser.is_admin"
                                        :href="route('admin.dashboard')"
                                        icon="shield"
                                        accent
                                        @click="closeAccount"
                                    >
                                        {{ t('admin_panel') }}
                                    </AccountMenuLink>
                                </div>

                                <div class="border-t border-[var(--line)] py-1.5">
                                    <AccountMenuLink :href="route('community.settings')" icon="cog" @click="closeAccount">
                                        {{ t('community_settings') }}
                                    </AccountMenuLink>
                                    <AccountMenuLink :href="route('community.notifications')" icon="bell" @click="closeAccount">
                                        {{ t('community_notifications') }}
                                    </AccountMenuLink>
                                    <AccountMenuLink :href="route('community.groups.index')" icon="users" @click="closeAccount">
                                        {{ t('community_groups') }}
                                    </AccountMenuLink>
                                    <AccountMenuLink :href="route('community.messages.index')" icon="chat" @click="closeAccount">
                                        {{ t('community_messages') }}
                                    </AccountMenuLink>
                                    <AccountMenuLink :href="route('community.live-chat')" icon="radio" @click="closeAccount">
                                        {{ t('community_live_chat') }}
                                    </AccountMenuLink>
                                </div>

                                <div class="border-t border-[var(--line)] py-1.5">
                                    <AccountMenuLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        icon="logout"
                                        muted
                                        @click="closeAccount"
                                    >
                                        {{ t('community_logout') }}
                                    </AccountMenuLink>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="hidden rounded-full px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-white/70 transition hover:text-white lg:inline-block"
                        >
                            {{ t('community_login') }}
                        </Link>
                    </template>

                    <span :class="divider" />

                    <WishlistIcon />
                    <CartIcon />

                    <span :class="divider" />

                    <button
                        type="button"
                        :class="iconBtn"
                        :aria-label="t('search')"
                        @click="searchOpen = !searchOpen"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.6-5.4a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
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
                    <div class="border-b border-[var(--line)] bg-white text-charcoal shadow-lift">
                        <div v-if="newsMegaOpen" class="container-editorial flex gap-1 overflow-x-auto border-b border-[var(--line)]">
                            <button
                                v-for="item in items"
                                :key="item.key"
                                type="button"
                                class="shrink-0 border-b-2 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.16em] transition"
                                :class="activeKey === item.key ? 'border-brand text-brand' : 'border-transparent text-neutral-400 hover:text-charcoal'"
                                @mouseenter="openMega(item.key)"
                            >
                                {{ item.name }}
                            </button>
                        </div>
                        <MegaMenu
                            v-if="activeItem"
                            :item="activeItem"
                            bare
                            @navigate="closeMega"
                        />
                        <div v-else-if="moreMegaOpen" class="container-editorial py-6">
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
                                    :href="route('legal.faq')"
                                    class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                                    @click="closeMega"
                                >
                                    {{ t('footer_faq') }}
                                </Link>
                                <Link
                                    :href="route('legal.privacy')"
                                    class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                                    @click="closeMega"
                                >
                                    {{ t('footer_privacy') }}
                                </Link>
                                <button
                                    type="button"
                                    class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                                    @click="openSupport"
                                >
                                    {{ t('support_title') }}
                                </button>
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
            class="relative z-50 max-h-[min(70dvh,calc(100dvh-8rem))] overflow-y-auto border-b border-white/10 lg:hidden transition-colors duration-300"
            :class="scrolled ? 'bg-charcoal/60 backdrop-blur-xl' : 'bg-charcoal'"
        >
            <nav class="container-editorial flex flex-col py-3 pb-4">
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
                <template v-if="authUser">
                    <Link
                        v-if="authUser.username"
                        :href="route('community.profile', authUser.username)"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                        @click="mobileOpen = false"
                    >
                        {{ authUser.name }}
                    </Link>
                    <Link
                        :href="route('shop.orders.index')"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                        @click="mobileOpen = false"
                    >
                        {{ t('shop_orders') }}
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
                        v-if="authUser.is_seller"
                        :href="route('seller.dashboard')"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-brand"
                        @click="mobileOpen = false"
                    >
                        {{ t('shop_seller_dash') }}
                    </Link>
                    <Link
                        v-if="authUser.is_admin"
                        :href="route('admin.dashboard')"
                        class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-brand"
                        @click="mobileOpen = false"
                    >
                        {{ t('admin_panel') }}
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
                    :href="route('articles.index')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('news_nav') }}
                </Link>
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
                    :href="route('legal.faq')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('footer_faq') }}
                </Link>
                <Link
                    :href="route('legal.privacy')"
                    class="rounded-lg px-2 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="mobileOpen = false"
                >
                    {{ t('footer_privacy') }}
                </Link>
                <button
                    type="button"
                    class="rounded-lg px-2 py-3 text-left text-sm font-semibold uppercase tracking-[0.14em] text-white/85"
                    @click="openSupport"
                >
                    {{ t('support_title') }}
                </button>
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

        <Teleport to="body">
        <nav
            class="fixed inset-x-0 bottom-0 z-50 border-t border-white/10 bg-charcoal/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden"
            aria-label="Mobile"
        >
            <div class="grid h-14 grid-cols-5">
                <Link
                    :href="route('home')"
                    :class="[tabCls, isPath('/') && !mobileOpen ? tabOn : tabIdle]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z" />
                    </svg>
                    {{ t('home') }}
                </Link>
                <Link
                    :href="route('shop.index')"
                    :class="[tabCls, isPath('/toko') && !mobileOpen ? tabOn : tabIdle]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 9h16l-1 11H5L4 9zm4-4a4 4 0 018 0" />
                    </svg>
                    {{ t('shop_nav') }}
                </Link>
                <Link
                    :href="route('community.index')"
                    :class="[tabCls, isPath('/komunitas') && !mobileOpen ? tabOn : tabIdle]"
                >
                    <span class="relative">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20v-1a4 4 0 00-4-4H7a4 4 0 00-4 4v1m14-10a3 3 0 11-6 0 3 3 0 016 0zm6 10v-1a4 4 0 00-3-3.87M16 3.13a3 3 0 010 5.74" />
                        </svg>
                        <span
                            v-if="communityBadge"
                            class="absolute -right-2 -top-1 flex h-3.5 min-w-3.5 items-center justify-center rounded-full bg-brand px-0.5 text-[8px] font-bold leading-none text-white"
                        >
                            {{ communityBadge > 9 ? '9+' : communityBadge }}
                        </span>
                    </span>
                    {{ t('community_nav') }}
                </Link>
                <Link
                    :href="route('events.index')"
                    :class="[tabCls, isPath('/event') && !mobileOpen ? tabOn : tabIdle]"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                    {{ t('events_nav') }}
                </Link>
                <button
                    type="button"
                    :class="[tabCls, mobileOpen ? tabOn : tabIdle]"
                    :aria-label="t('see_more')"
                    :aria-expanded="mobileOpen"
                    @click="toggleMore"
                >
                    <svg v-if="!mobileOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                    {{ t('see_more') }}
                </button>
            </div>
        </nav>
        </Teleport>
    </header>
</template>
