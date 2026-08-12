<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import CommunityEmojiPicker from '@/Components/Community/CommunityEmojiPicker.vue';
import { applyEmoticons } from '@/utils/communityEmoji';
import { swalToast } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    conversation: { type: Object, required: true },
    messages: { type: Object, required: true },
});

const { t } = useI18n();
const listEl = ref(null);
const textarea = ref(null);
const liveMessages = ref([...(props.messages.data || [])]);
const otherUser = ref(props.conversation.other_user ? { ...props.conversation.other_user } : null);
let pollTimer = null;

const form = useForm({
    body: '',
});

const lastId = computed(() => {
    if (!liveMessages.value.length) return 0;
    return Math.max(...liveMessages.value.map((m) => Number(m.id)));
});

function insertEmoji(emoji) {
    const el = textarea.value;
    const current = form.body || '';
    if (!el) {
        form.body = `${current}${emoji}`;
        return;
    }
    const start = el.selectionStart ?? current.length;
    const end = el.selectionEnd ?? current.length;
    form.body = `${current.slice(0, start)}${emoji}${current.slice(end)}`;
    nextTick(() => {
        const pos = start + emoji.length;
        el.focus();
        el.setSelectionRange(pos, pos);
    });
}

function scrollBottom() {
    nextTick(() => {
        if (listEl.value) {
            listEl.value.scrollTop = listEl.value.scrollHeight;
        }
    });
}

async function poll() {
    try {
        const { data } = await axios.get(route('community.messages.poll', props.conversation.id), {
            params: { after_id: lastId.value },
        });
        if (data.other_user) otherUser.value = data.other_user;
        if (data.messages?.length) {
            const known = new Set(liveMessages.value.map((m) => m.id));
            const fresh = data.messages.filter((m) => !known.has(m.id));
            if (fresh.length) {
                liveMessages.value = [...liveMessages.value, ...fresh];
                scrollBottom();
            }
        }
    } catch {
        // ignore
    }
}

function submit() {
    form.body = applyEmoticons(form.body);
    if (!form.body.trim()) {
        swalToast(t('community_body_required'), { icon: 'warning' });
        return;
    }

    form.post(route('community.messages.store', props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body');
            poll();
            scrollBottom();
        },
        onError: () => swalToast(t('community_post_failed'), { icon: 'error' }),
    });
}

watch(
    () => props.messages.data,
    (v) => {
        liveMessages.value = [...(v || [])];
        scrollBottom();
    },
);

onMounted(() => {
    scrollBottom();
    pollTimer = setInterval(poll, 2500);
});

onUnmounted(() => clearInterval(pollTimer));
</script>

<template>
    <AppLayout>
        <Head :title="otherUser?.name || t('community_messages')" />

        <div class="mx-auto flex max-w-xl flex-col px-4 py-6 sm:px-6" style="min-height: calc(100vh - 8rem)">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-[var(--line)] pb-4">
                <Link
                    :href="route('community.messages.index')"
                    class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50 hover:text-brand"
                >
                    ← {{ t('community_messages') }}
                </Link>
                <Link
                    v-if="conversation.is_mutual_friend && otherUser?.username"
                    :href="route('community.live-chat.open', otherUser.username)"
                    class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-600 hover:text-emerald-700"
                >
                    {{ t('community_live_chat') }}
                </Link>
            </div>

            <div class="mb-4 flex items-center gap-3">
                <component
                    :is="otherUser?.url ? Link : 'div'"
                    v-bind="otherUser?.url ? { href: otherUser.url } : {}"
                    class="relative flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-mist text-sm font-semibold"
                >
                    <img
                        v-if="otherUser?.avatar_url"
                        :src="otherUser.avatar_url"
                        :alt="otherUser.name"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ (otherUser?.name || '?').slice(0, 1).toUpperCase() }}</span>
                </component>
                <div>
                    <p class="text-sm font-semibold text-charcoal">{{ otherUser?.name }}</p>
                    <p
                        class="text-xs"
                        :class="otherUser?.is_online ? 'text-emerald-600' : 'text-charcoal/45'"
                    >
                        <template v-if="otherUser?.username">@{{ otherUser.username }} · </template>
                        {{ otherUser?.is_online ? t('community_online_now') : t('community_offline') }}
                    </p>
                </div>
            </div>

            <div ref="listEl" class="min-h-0 flex-1 space-y-3 overflow-y-auto py-2">
                <div
                    v-for="msg in liveMessages"
                    :key="msg.id"
                    class="flex"
                    :class="msg.is_mine ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[80%] rounded-2xl px-4 py-2.5 text-[15px] leading-relaxed"
                        :class="msg.is_mine
                            ? 'rounded-br-md bg-brand text-white'
                            : 'rounded-bl-md border border-[var(--line)] bg-white text-charcoal'"
                    >
                        <p class="whitespace-pre-wrap break-words">{{ msg.body }}</p>
                        <p
                            class="mt-1 text-[10px]"
                            :class="msg.is_mine ? 'text-white/70' : 'text-charcoal/40'"
                        >
                            {{ msg.created_at_label }}
                        </p>
                    </div>
                </div>
                <p v-if="!liveMessages.length" class="py-10 text-center text-sm text-charcoal/45">
                    {{ t('community_messages_empty_thread') }}
                </p>
            </div>

            <form class="mt-4 border-t border-[var(--line)] pt-4" @submit.prevent="submit">
                <textarea
                    ref="textarea"
                    v-model="form.body"
                    rows="2"
                    maxlength="2000"
                    :placeholder="t('community_message_ph')"
                    class="w-full resize-none rounded-2xl border border-[var(--line)] bg-white px-4 py-3 text-sm focus:border-brand focus:ring-0"
                />
                <div class="mt-2 flex items-center justify-between gap-3">
                    <CommunityEmojiPicker @pick="insertEmoji" />
                    <button
                        type="submit"
                        class="rounded-full bg-brand px-5 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-40"
                        :disabled="form.processing || !form.body.trim()"
                    >
                        {{ t('community_send') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
