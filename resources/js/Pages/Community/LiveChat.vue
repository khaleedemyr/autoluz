<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import CommunityEmojiPicker from '@/Components/Community/CommunityEmojiPicker.vue';
import { applyEmoticons } from '@/utils/communityEmoji';
import { swalToast } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    friends: { type: Array, default: () => [] },
    online_count: { type: Number, default: 0 },
    active_conversation_id: { type: Number, default: null },
    active_friend: { type: Object, default: null },
    messages: { type: Array, default: () => [] },
});

const { t } = useI18n();
const friendList = ref([...props.friends]);
const onlineCount = ref(props.online_count);
const chatMessages = ref([...props.messages]);
const activeFriend = ref(props.active_friend ? { ...props.active_friend } : null);
const body = ref('');
const sending = ref(false);
const listEl = ref(null);
const textarea = ref(null);
let friendsTimer = null;
let pollTimer = null;

const lastId = computed(() => {
    if (!chatMessages.value.length) return 0;
    return Math.max(...chatMessages.value.map((m) => Number(m.id)));
});

const onlineFriends = computed(() => friendList.value.filter((f) => f.is_online));
const offlineFriends = computed(() => friendList.value.filter((f) => !f.is_online));

function scrollBottom() {
    nextTick(() => {
        if (listEl.value) listEl.value.scrollTop = listEl.value.scrollHeight;
    });
}

function insertEmoji(emoji) {
    const el = textarea.value;
    const current = body.value || '';
    if (!el) {
        body.value = `${current}${emoji}`;
        return;
    }
    const start = el.selectionStart ?? current.length;
    const end = el.selectionEnd ?? current.length;
    body.value = `${current.slice(0, start)}${emoji}${current.slice(end)}`;
    nextTick(() => {
        const pos = start + emoji.length;
        el.focus();
        el.setSelectionRange(pos, pos);
    });
}

async function refreshFriends() {
    try {
        const { data } = await axios.get(route('community.live-chat.friends'));
        friendList.value = data.friends || [];
        onlineCount.value = data.online_count || 0;
        if (activeFriend.value) {
            const updated = friendList.value.find((f) => f.id === activeFriend.value.id);
            if (updated) activeFriend.value = updated;
        }
    } catch {
        // ignore transient errors
    }
}

async function pollMessages() {
    if (!props.active_conversation_id) return;
    try {
        const { data } = await axios.get(route('community.live-chat.poll', props.active_conversation_id), {
            params: { after_id: lastId.value },
        });
        if (data.friend) activeFriend.value = data.friend;
        if (data.messages?.length) {
            const known = new Set(chatMessages.value.map((m) => m.id));
            const fresh = data.messages.filter((m) => !known.has(m.id));
            if (fresh.length) {
                chatMessages.value = [...chatMessages.value, ...fresh];
                scrollBottom();
            }
        }
    } catch {
        // ignore
    }
}

async function send() {
    if (!props.active_conversation_id || sending.value) return;
    const text = applyEmoticons(body.value).trim();
    if (!text) {
        swalToast(t('community_body_required'), { icon: 'warning' });
        return;
    }

    sending.value = true;
    try {
        const { data } = await axios.post(route('community.live-chat.send', props.active_conversation_id), {
            body: text,
        });
        if (data.message) {
            chatMessages.value = [...chatMessages.value, data.message];
            body.value = '';
            scrollBottom();
        }
    } catch {
        swalToast(t('community_post_failed'), { icon: 'error' });
    } finally {
        sending.value = false;
    }
}

function openFriend(friend) {
    if (!friend?.username) return;
    router.visit(route('community.live-chat.open', friend.username));
}

watch(
    () => props.messages,
    (v) => {
        chatMessages.value = [...(v || [])];
        scrollBottom();
    },
);

watch(
    () => props.friends,
    (v) => {
        friendList.value = [...(v || [])];
    },
);

onMounted(() => {
    scrollBottom();
    friendsTimer = setInterval(refreshFriends, 15000);
    if (props.active_conversation_id) {
        pollTimer = setInterval(pollMessages, 2500);
    }
});

onUnmounted(() => {
    clearInterval(friendsTimer);
    clearInterval(pollTimer);
});
</script>

<template>
    <AppLayout>
        <Head :title="t('community_live_chat')" />

        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            <div class="mb-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-brand">Autoluz</p>
                <h1 class="font-display mt-1 text-3xl tracking-[-0.03em] text-charcoal">
                    {{ t('community_live_chat') }}
                </h1>
                <p class="mt-2 text-sm text-charcoal/60">{{ t('community_live_chat_desc') }}</p>
            </div>

            <div class="grid min-h-[32rem] overflow-hidden rounded-2xl border border-[var(--line)] bg-white lg:grid-cols-[16rem_minmax(0,1fr)]">
                <aside class="border-b border-[var(--line)] lg:border-b-0 lg:border-r">
                    <div class="flex items-center justify-between border-b border-[var(--line)] px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/55">
                            {{ t('community_friends') }}
                        </p>
                        <span class="text-[10px] font-semibold text-brand">
                            {{ onlineCount }} {{ t('community_online') }}
                        </span>
                    </div>

                    <div class="max-h-56 overflow-y-auto lg:max-h-[30rem]">
                        <p
                            v-if="!friendList.length"
                            class="px-4 py-8 text-center text-sm text-charcoal/45"
                        >
                            {{ t('community_friends_empty') }}
                        </p>

                        <template v-else>
                            <p
                                v-if="onlineFriends.length"
                                class="px-4 pt-3 text-[10px] font-semibold uppercase tracking-[0.14em] text-charcoal/40"
                            >
                                {{ t('community_online') }}
                            </p>
                            <button
                                v-for="friend in onlineFriends"
                                :key="`on-${friend.id}`"
                                type="button"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-mist/70"
                                :class="{ 'bg-brand/5': activeFriend?.id === friend.id }"
                                @click="openFriend(friend)"
                            >
                                <div class="relative">
                                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-mist text-sm font-semibold">
                                        <img
                                            v-if="friend.avatar_url"
                                            :src="friend.avatar_url"
                                            :alt="friend.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else>{{ (friend.name || '?').slice(0, 1).toUpperCase() }}</span>
                                    </div>
                                    <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-500" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-charcoal">{{ friend.name }}</p>
                                    <p class="truncate text-xs text-charcoal/45">@{{ friend.username }}</p>
                                </div>
                            </button>

                            <p
                                v-if="offlineFriends.length"
                                class="px-4 pt-3 text-[10px] font-semibold uppercase tracking-[0.14em] text-charcoal/40"
                            >
                                {{ t('community_offline') }}
                            </p>
                            <button
                                v-for="friend in offlineFriends"
                                :key="`off-${friend.id}`"
                                type="button"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left opacity-70 transition hover:bg-mist/70 hover:opacity-100"
                                :class="{ 'bg-brand/5 opacity-100': activeFriend?.id === friend.id }"
                                @click="openFriend(friend)"
                            >
                                <div class="relative">
                                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-mist text-sm font-semibold">
                                        <img
                                            v-if="friend.avatar_url"
                                            :src="friend.avatar_url"
                                            :alt="friend.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else>{{ (friend.name || '?').slice(0, 1).toUpperCase() }}</span>
                                    </div>
                                    <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-charcoal/30" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-charcoal">{{ friend.name }}</p>
                                    <p class="truncate text-xs text-charcoal/45">@{{ friend.username }}</p>
                                </div>
                            </button>
                        </template>
                    </div>
                </aside>

                <section class="flex min-h-[24rem] flex-col">
                    <div
                        v-if="activeFriend"
                        class="flex items-center gap-3 border-b border-[var(--line)] px-4 py-3"
                    >
                        <div class="relative">
                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-mist text-sm font-semibold">
                                <img
                                    v-if="activeFriend.avatar_url"
                                    :src="activeFriend.avatar_url"
                                    :alt="activeFriend.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ (activeFriend.name || '?').slice(0, 1).toUpperCase() }}</span>
                            </div>
                            <span
                                class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white"
                                :class="activeFriend.is_online ? 'bg-emerald-500' : 'bg-charcoal/30'"
                            />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-charcoal">{{ activeFriend.name }}</p>
                            <p class="text-xs" :class="activeFriend.is_online ? 'text-emerald-600' : 'text-charcoal/45'">
                                {{ activeFriend.is_online ? t('community_online_now') : t('community_offline') }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-1 items-center justify-center px-6 text-center text-sm text-charcoal/45"
                    >
                        {{ t('community_live_chat_pick') }}
                    </div>

                    <template v-if="activeFriend && active_conversation_id">
                        <div ref="listEl" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4">
                            <div
                                v-for="msg in chatMessages"
                                :key="msg.id"
                                class="flex"
                                :class="msg.is_mine ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="max-w-[80%] rounded-2xl px-4 py-2.5 text-[15px] leading-relaxed"
                                    :class="msg.is_mine
                                        ? 'rounded-br-md bg-brand text-white'
                                        : 'rounded-bl-md border border-[var(--line)] bg-mist/40 text-charcoal'"
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
                            <p v-if="!chatMessages.length" class="py-10 text-center text-sm text-charcoal/45">
                                {{ t('community_messages_empty_thread') }}
                            </p>
                        </div>

                        <form class="border-t border-[var(--line)] p-4" @submit.prevent="send">
                            <textarea
                                ref="textarea"
                                v-model="body"
                                rows="2"
                                maxlength="2000"
                                :placeholder="t('community_message_ph')"
                                class="w-full resize-none rounded-2xl border border-[var(--line)] bg-white px-4 py-3 text-sm focus:border-brand focus:ring-0"
                                @keydown.enter.exact.prevent="send"
                            />
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <CommunityEmojiPicker @pick="insertEmoji" />
                                <button
                                    type="submit"
                                    class="rounded-full bg-brand px-5 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-40"
                                    :disabled="sending || !body.trim()"
                                >
                                    {{ t('community_send') }}
                                </button>
                            </div>
                        </form>
                    </template>
                </section>
            </div>

            <p class="mt-4 text-center text-xs text-charcoal/40">
                <Link :href="route('community.messages.index')" class="hover:text-brand">
                    {{ t('community_messages') }}
                </Link>
                · {{ t('community_live_chat_hint') }}
            </p>
        </div>
    </AppLayout>
</template>
