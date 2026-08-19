<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from '@/composables/useI18n';

const page = usePage();
const { t } = useI18n();
const open = ref(false);
const friends = ref([]);
const onlineCount = ref(0);
let timer = null;

const authUser = computed(() => page.props.auth?.user || null);

async function loadFriends() {
    if (!authUser.value) return;
    try {
        const { data } = await axios.get(route('community.live-chat.friends'));
        friends.value = (data.friends || []).filter((f) => f.is_online);
        onlineCount.value = data.online_count || friends.value.length;
    } catch {
        // ignore
    }
}

function toggle() {
    open.value = !open.value;
    if (open.value) loadFriends();
}

function openChat(friend) {
    open.value = false;
    if (!friend?.username) return;
    router.visit(route('community.live-chat.open', friend.username));
}

onMounted(() => {
    if (!authUser.value) return;
    loadFriends();
    timer = setInterval(loadFriends, 20000);
});

onUnmounted(() => clearInterval(timer));
</script>

<template>
    <div
        v-if="authUser && !page.url.startsWith('/komunitas/live-chat')"
        class="fixed bottom-[calc(4.75rem+env(safe-area-inset-bottom))] right-5 z-40 lg:bottom-5"
    >
        <div
            v-if="open"
            class="mb-3 w-72 overflow-hidden rounded-2xl border border-[var(--line)] bg-white text-charcoal shadow-lift"
        >
            <div class="flex items-center justify-between border-b border-[var(--line)] px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em]">{{ t('community_live_chat') }}</p>
                <Link
                    :href="route('community.live-chat')"
                    class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand"
                    @click="open = false"
                >
                    {{ t('community_open') }}
                </Link>
            </div>
            <div class="max-h-64 overflow-y-auto">
                <button
                    v-for="friend in friends"
                    :key="friend.id"
                    type="button"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-mist/70"
                    @click="openChat(friend)"
                >
                    <div class="relative">
                        <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-mist text-xs font-semibold">
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
                        <p class="truncate text-sm font-semibold">{{ friend.name }}</p>
                        <p class="text-xs text-emerald-600">{{ t('community_online_now') }}</p>
                    </div>
                </button>
                <p v-if="!friends.length" class="px-4 py-8 text-center text-sm text-charcoal/45">
                    {{ t('community_no_friends_online') }}
                </p>
            </div>
        </div>

        <button
            type="button"
            class="relative inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand text-white shadow-lift transition hover:opacity-90"
            :aria-label="t('community_live_chat')"
            @click="toggle"
        >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span
                v-if="onlineCount > 0"
                class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-charcoal px-1 text-[10px] font-bold"
            >
                {{ onlineCount > 5 ? `+${onlineCount}` : onlineCount }}
            </span>
        </button>
    </div>
</template>
