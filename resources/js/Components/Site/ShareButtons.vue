<script setup>
import { computed, onMounted, ref } from 'vue';
import { swalToast } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    slug: { type: String, required: true },
    url: { type: String, required: true },
    title: { type: String, required: true },
    viewsCount: { type: Number, default: null },
    sharesCount: { type: Number, default: 0 },
    shareRoute: { type: String, default: 'articles.share' },
    description: { type: String, default: '' },
    copiedMessage: { type: String, default: '' },
    compact: { type: Boolean, default: false },
    dark: { type: Boolean, default: false },
    showStats: { type: Boolean, default: true },
});

const { t, formatNumber } = useI18n();
const copied = ref(false);
const canNativeShare = ref(false);
const localShares = ref(props.sharesCount);

onMounted(() => {
    canNativeShare.value = typeof navigator !== 'undefined' && typeof navigator.share === 'function';
});

const encodedUrl = computed(() => encodeURIComponent(props.url));
const encodedTitle = computed(() => encodeURIComponent(props.title));
const descriptionText = computed(() => props.description || t('share_text'));
const copiedText = computed(() => props.copiedMessage || t('link_copied'));
const showViews = computed(() => props.showStats && props.viewsCount !== null);
const showShares = computed(() => props.showStats);

const formattedViews = computed(() => formatNumber(props.viewsCount || 0));
const formattedShares = computed(() => formatNumber(localShares.value));

const btnClass = computed(() =>
    props.dark
        ? 'inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white transition hover:border-brand hover:bg-brand'
        : 'inline-flex h-11 w-11 items-center justify-center rounded-full border border-black/10 bg-white text-charcoal transition hover:border-brand hover:bg-brand hover:text-white',
);

const shares = computed(() => [
    {
        key: 'whatsapp',
        label: 'WhatsApp',
        href: `https://wa.me/?text=${encodedTitle.value}%20${encodedUrl.value}`,
    },
    {
        key: 'facebook',
        label: 'Facebook',
        href: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl.value}`,
    },
    {
        key: 'x',
        label: 'X',
        href: `https://twitter.com/intent/tweet?url=${encodedUrl.value}&text=${encodedTitle.value}`,
    },
    {
        key: 'telegram',
        label: 'Telegram',
        href: `https://t.me/share/url?url=${encodedUrl.value}&text=${encodedTitle.value}`,
    },
]);

async function trackShare(channel) {
    if (!props.shareRoute || !props.slug) return;

    try {
        const { data } = await window.axios.post(route(props.shareRoute, props.slug), { channel });
        if (typeof data?.shares_count === 'number') {
            localShares.value = data.shares_count;
        } else {
            localShares.value += 1;
        }
    } catch {
        // Keep UI usable even if tracking fails.
    }
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(props.url);
        copied.value = true;
        await trackShare('copy');
        swalToast(copiedText.value);
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        swalToast(t('link_copy_failed'), { icon: 'error' });
    }
}

async function nativeShare() {
    try {
        await navigator.share({
            title: props.title,
            text: props.title,
            url: props.url,
        });
        await trackShare('native');
    } catch (error) {
        if (error?.name !== 'AbortError') {
            swalToast(t('link_copy_failed'), { icon: 'error' });
        }
    }
}

function onShareClick(item) {
    trackShare(item.key);
}
</script>

<template>
    <div
        :class="
            compact
                ? ''
                : dark
                  ? 'rounded-2xl border border-white/10 bg-white/5 p-5'
                  : 'rounded-2xl border border-[var(--line)] bg-white/80 p-5 shadow-soft'
        "
    >
        <div v-if="!compact" class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p
                    class="text-[11px] font-semibold uppercase tracking-[0.18em]"
                    :class="dark ? 'text-white/40' : 'text-neutral-400'"
                >
                    {{ t('share_title') }}
                </p>
                <p class="mt-1 text-sm" :class="dark ? 'text-white/70' : 'text-neutral-600'">
                    {{ descriptionText }}
                </p>
            </div>
            <div
                v-if="showViews || showShares"
                class="flex flex-wrap gap-4 text-sm"
                :class="dark ? 'text-white/70' : 'text-neutral-600'"
            >
                <div v-if="showViews" class="inline-flex items-center gap-2">
                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full"
                        :class="dark ? 'bg-white/10 text-white' : 'bg-mist text-charcoal'"
                        aria-hidden="true"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </span>
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-[0.16em]"
                            :class="dark ? 'text-white/40' : 'text-neutral-400'"
                        >
                            {{ t('views_label') }}
                        </p>
                        <p class="font-semibold tracking-[-0.02em]">{{ formattedViews }}x</p>
                    </div>
                </div>
                <div v-if="showShares" class="inline-flex items-center gap-2">
                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full"
                        :class="dark ? 'bg-white/10 text-white' : 'bg-mist text-charcoal'"
                        aria-hidden="true"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7" />
                            <path d="M16 6l-4-4-4 4" />
                            <path d="M12 2v13" />
                        </svg>
                    </span>
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-[0.16em]"
                            :class="dark ? 'text-white/40' : 'text-neutral-400'"
                        >
                            {{ t('shares_label') }}
                        </p>
                        <p class="font-semibold tracking-[-0.02em]">{{ formattedShares }}x</p>
                    </div>
                </div>
            </div>
        </div>
        <p
            v-else
            class="mb-3 text-[10px] font-semibold uppercase tracking-[0.16em]"
            :class="dark ? 'text-white/40' : 'text-neutral-400'"
        >
            {{ t('share_title') }}
        </p>

        <div :class="compact ? 'flex flex-wrap gap-2' : 'mt-5 flex flex-wrap gap-2.5'">
            <button
                v-if="canNativeShare"
                type="button"
                :class="btnClass"
                :title="t('share_native')"
                :aria-label="t('share_native')"
                @click="nativeShare"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7" />
                    <path d="M16 6l-4-4-4 4" />
                    <path d="M12 2v13" />
                </svg>
            </button>

            <button
                type="button"
                :class="btnClass"
                :title="copied ? copiedText : t('copy_link')"
                :aria-label="copied ? copiedText : t('copy_link')"
                @click="copyLink"
            >
                <svg v-if="!copied" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                    <path d="M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07L11 5" />
                    <path d="M14 11a5 5 0 0 0-7.07 0L4.81 13.12a5 5 0 0 0 7.07 7.07L13 19" />
                </svg>
                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M5 13l4 4L19 7" />
                </svg>
            </button>

            <a
                v-for="item in shares"
                :key="item.key"
                :href="item.href"
                target="_blank"
                rel="noopener noreferrer"
                :class="btnClass"
                :title="item.label"
                :aria-label="`Bagikan ke ${item.label}`"
                @click="onShareClick(item)"
            >
                <svg v-if="item.key === 'whatsapp'" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.15 1.6 5.96L0 24l6.3-1.65a11.9 11.9 0 0 0 5.75 1.47h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.44-8.44ZM12.06 21.15h-.01a9.24 9.24 0 0 1-4.71-1.29l-.34-.2-3.74.98 1-3.64-.22-.37a9.23 9.23 0 0 1-1.42-4.93c0-5.1 4.15-9.25 9.26-9.25 2.47 0 4.8.96 6.55 2.71a9.2 9.2 0 0 1 2.7 6.55c0 5.1-4.15 9.24-9.25 9.24Zm5.07-6.92c-.28-.14-1.64-.81-1.9-.9-.25-.1-.44-.14-.62.14-.18.27-.71.9-.87 1.08-.16.18-.32.2-.6.07-.28-.14-1.17-.43-2.23-1.37-.82-.73-1.38-1.64-1.54-1.92-.16-.27-.02-.42.12-.56.13-.12.28-.32.42-.48.14-.16.18-.27.28-.45.09-.18.05-.34-.02-.48-.07-.14-.62-1.5-.85-2.05-.22-.53-.45-.46-.62-.47h-.53c-.18 0-.48.07-.73.34-.25.27-.96.94-.96 2.3s.98 2.67 1.12 2.85c.14.18 1.93 2.95 4.67 4.13.65.28 1.16.45 1.56.57.65.2 1.25.18 1.72.11.52-.08 1.64-.67 1.87-1.32.23-.65.23-1.2.16-1.32-.07-.11-.25-.18-.53-.32Z" />
                </svg>
                <svg v-else-if="item.key === 'facebook'" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.4c0-3.02 1.8-4.7 4.55-4.7 1.32 0 2.7.24 2.7.24v2.97h-1.52c-1.5 0-1.97.93-1.97 1.89v2.26h3.35l-.54 3.49h-2.81V24C19.61 23.1 24 18.1 24 12.07z" />
                </svg>
                <svg v-else-if="item.key === 'x'" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M18.9 2H22l-6.8 7.78L23.2 22h-6.4l-5-6.55L6.2 22H3.1l7.27-8.31L.8 2h6.55l4.52 5.98L18.9 2Zm-1.12 18h1.78L6.35 3.9H4.44L17.78 20Z" />
                </svg>
                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M23.91 3.87a1.5 1.5 0 0 0-1.54-.22L1.7 12.2a1.5 1.5 0 0 0 .07 2.8l4.75 1.55 1.84 5.66a1.5 1.5 0 0 0 2.5.66l2.8-2.72 4.7 3.46a1.5 1.5 0 0 0 2.35-.9L24.03 5.2a1.5 1.5 0 0 0-.12-1.33ZM8.8 14.86l8.5-5.36-6.55 6.7-.2 2.4-1.75-3.74Zm8.7 4.75-3.95-2.9 5.67-5.8-1.72 8.7Z" />
                </svg>
            </a>
        </div>
    </div>
</template>
