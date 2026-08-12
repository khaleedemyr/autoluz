<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    notifications: { type: Object, required: true },
});

const { t } = useI18n();
const page = usePage();

function openNotification(item) {
    router.post(route('community.notifications.read', item.id), {}, {
        preserveScroll: true,
    });
}

function markAll() {
    router.post(route('community.notifications.read-all'), {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('community_notifications')" />

        <div class="mx-auto max-w-xl px-4 py-8 sm:px-6">
            <div class="mb-6 flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-brand">Autoluz</p>
                    <h1 class="font-display mt-1 text-3xl tracking-[-0.03em] text-charcoal">
                        {{ t('community_notifications') }}
                    </h1>
                </div>
                <button
                    v-if="page.props.auth?.user?.unread_notifications > 0"
                    type="button"
                    class="rounded-full border border-charcoal/15 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                    @click="markAll"
                >
                    {{ t('community_mark_all_read') }}
                </button>
            </div>

            <div v-if="notifications.data?.length" class="divide-y divide-[var(--line)] border-y border-[var(--line)]">
                <button
                    v-for="item in notifications.data"
                    :key="item.id"
                    type="button"
                    class="flex w-full gap-3 px-1 py-4 text-left transition hover:bg-mist/60"
                    :class="{ 'opacity-60': item.is_read }"
                    @click="openNotification(item)"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mist text-sm font-semibold">
                        <img
                            v-if="item.actor?.avatar_url"
                            :src="item.actor.avatar_url"
                            :alt="item.actor.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ (item.actor?.name || '?').slice(0, 1).toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-relaxed text-charcoal">
                            <span v-if="!item.is_read" class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-brand align-middle" />
                            {{ item.message }}
                        </p>
                        <p class="mt-1 text-xs text-charcoal/45">{{ item.created_at_label }}</p>
                    </div>
                </button>
            </div>
            <p v-else class="py-16 text-center text-sm text-charcoal/50">
                {{ t('community_notifications_empty') }}
            </p>

            <div v-if="notifications.links?.length > 3" class="mt-8 flex flex-wrap justify-center gap-2">
                <template v-for="(link, i) in notifications.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold"
                        :class="link.active ? 'bg-brand text-white' : 'text-charcoal/60 hover:bg-mist'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-full px-3 py-1.5 text-xs text-charcoal/30"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
