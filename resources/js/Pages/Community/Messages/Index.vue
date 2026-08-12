<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    conversations: { type: Object, required: true },
});

const { t } = useI18n();
</script>

<template>
    <AppLayout>
        <Head :title="t('community_messages')" />

        <div class="mx-auto max-w-xl px-4 py-8 sm:px-6">
            <div class="mb-6">
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-brand">Autoluz</p>
                <h1 class="font-display mt-1 text-3xl tracking-[-0.03em] text-charcoal">
                    {{ t('community_messages') }}
                </h1>
                <p class="mt-2 text-sm text-charcoal/60">{{ t('community_messages_desc') }}</p>
            </div>

            <div v-if="conversations.data?.length" class="divide-y divide-[var(--line)] border-y border-[var(--line)]">
                <Link
                    v-for="item in conversations.data"
                    :key="item.id"
                    :href="item.url"
                    class="flex gap-3 px-1 py-4 transition hover:bg-mist/60"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mist text-sm font-semibold">
                        <img
                            v-if="item.other_user?.avatar_url"
                            :src="item.other_user.avatar_url"
                            :alt="item.other_user.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ (item.other_user?.name || '?').slice(0, 1).toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="truncate text-sm font-semibold text-charcoal">
                                {{ item.other_user?.name || t('community_unknown_user') }}
                            </p>
                            <span class="shrink-0 text-[11px] text-charcoal/40">{{ item.last_message_at_label }}</span>
                        </div>
                        <p class="mt-1 truncate text-sm text-charcoal/55">
                            <span v-if="item.unread_count" class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-brand align-middle" />
                            {{ item.last_message?.body || t('community_messages_empty_thread') }}
                        </p>
                    </div>
                    <span
                        v-if="item.unread_count"
                        class="self-center rounded-full bg-brand px-2 py-0.5 text-[10px] font-bold text-white"
                    >
                        {{ item.unread_count > 5 ? `+${item.unread_count}` : item.unread_count }}
                    </span>
                </Link>
            </div>
            <p v-else class="py-16 text-center text-sm text-charcoal/50">
                {{ t('community_messages_empty') }}
            </p>

            <div v-if="conversations.links?.length > 3" class="mt-8 flex flex-wrap justify-center gap-2">
                <template v-for="(link, i) in conversations.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold"
                        :class="link.active ? 'bg-brand text-white' : 'text-charcoal/60 hover:bg-mist'"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
