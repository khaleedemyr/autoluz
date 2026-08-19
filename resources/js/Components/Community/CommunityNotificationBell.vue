<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const PREVIEW_LIMIT = 5;

const page = usePage();
const { t } = useI18n();
const open = ref(false);
const root = ref(null);

const unread = computed(() => Number(page.props.auth?.user?.unread_notifications || 0));
const preview = computed(() => page.props.auth?.user?.notifications_preview || []);
const total = computed(() => Number(page.props.auth?.user?.notifications_total || 0));
const moreCount = computed(() => Math.max(0, total.value - PREVIEW_LIMIT));
const badgeLabel = computed(() => {
    if (unread.value <= 0) return '';
    return unread.value > PREVIEW_LIMIT ? `+${unread.value}` : String(unread.value);
});

function toggle() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function openItem(item) {
    close();
    router.post(route('community.notifications.read', item.id), {}, {
        preserveScroll: true,
    });
}

function onDocClick(e) {
    if (!root.value?.contains(e.target)) {
        close();
    }
}

onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="relative inline-flex h-9 w-9 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white"
            :aria-label="t('community_notifications')"
            @click.stop="toggle"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span
                v-if="unread > 0"
                class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[9px] font-bold leading-none text-white"
            >
                {{ badgeLabel }}
            </span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 top-full z-[60] mt-2 w-[min(20rem,calc(100vw-1.5rem))] overflow-hidden rounded-2xl border border-[var(--line)] bg-white text-charcoal shadow-lift sm:w-96"
        >
            <div class="flex items-center justify-between border-b border-[var(--line)] px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em]">{{ t('community_notifications') }}</p>
                <span v-if="unread" class="text-[10px] font-semibold text-brand">
                    {{ unread > PREVIEW_LIMIT ? `+${unread}` : unread }}
                </span>
            </div>

            <div v-if="preview.length" class="max-h-80 overflow-y-auto">
                <button
                    v-for="item in preview"
                    :key="item.id"
                    type="button"
                    class="flex w-full gap-3 border-b border-[var(--line)] px-4 py-3 text-left transition last:border-b-0 hover:bg-mist/70"
                    :class="{ 'bg-brand/[0.04]': !item.is_read }"
                    @click="openItem(item)"
                >
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mist text-xs font-semibold">
                        <img
                            v-if="item.actor?.avatar_url"
                            :src="item.actor.avatar_url"
                            :alt="item.actor.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ (item.actor?.name || '?').slice(0, 1).toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] leading-snug text-charcoal">
                            <span
                                v-if="!item.is_read"
                                class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-brand align-middle"
                            />
                            {{ item.message }}
                        </p>
                        <p class="mt-1 text-[11px] text-charcoal/45">{{ item.created_at_label }}</p>
                    </div>
                </button>

                <div
                    v-if="moreCount > 0"
                    class="border-t border-[var(--line)] bg-mist/40 px-4 py-2 text-center text-[11px] font-semibold text-charcoal/55"
                >
                    {{ t('community_notifications_more', { count: moreCount }) }}
                </div>
            </div>
            <div v-else class="px-4 py-8 text-center text-sm text-charcoal/55">
                {{ t('community_notifications_empty') }}
            </div>

            <Link
                :href="route('community.notifications')"
                class="block border-t border-[var(--line)] px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.14em] text-brand hover:bg-mist"
                @click="close"
            >
                {{ t('community_notifications_view') }}
            </Link>
        </div>
    </div>
</template>
