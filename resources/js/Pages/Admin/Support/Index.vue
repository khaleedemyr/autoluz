<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    conversations: { type: Array, default: () => [] },
    active: { type: Object, default: null },
    messages: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ status: 'open', q: '' }) },
    unread: { type: Number, default: 0 },
});

const q = ref(props.filters.q || '');
const status = ref(props.filters.status || 'open');
const chatMessages = ref([...props.messages]);
const body = ref('');
const sending = ref(false);
const listEl = ref(null);
let pollTimer = null;
let searchTimer = null;

const lastId = computed(() => (chatMessages.value.length ? Math.max(...chatMessages.value.map((m) => Number(m.id))) : 0));

function scrollBottom() {
    nextTick(() => {
        if (listEl.value) listEl.value.scrollTop = listEl.value.scrollHeight;
    });
}

function applyFilters() {
    router.get(
        route('admin.support.index'),
        {
            q: q.value || undefined,
            status: status.value,
            conversation: props.active?.id,
        },
        { preserveState: true, replace: true },
    );
}

function openConversation(id) {
    router.get(
        route('admin.support.index'),
        {
            q: q.value || undefined,
            status: status.value,
            conversation: id,
        },
        { preserveState: true, replace: true },
    );
}

async function send() {
    if (!props.active || !body.value.trim() || sending.value) return;
    sending.value = true;
    try {
        const { data } = await axios.post(route('admin.support.store', props.active.id), {
            body: body.value.trim(),
        });
        if (data.message) chatMessages.value.push(data.message);
        body.value = '';
        scrollBottom();
    } finally {
        sending.value = false;
    }
}

function setStatus(next) {
    if (!props.active) return;
    router.put(route('admin.support.update', props.active.id), { status: next }, { preserveScroll: true });
}

async function poll() {
    if (!props.active?.id) return;
    try {
        const { data } = await axios.get(route('admin.support.poll', props.active.id), {
            params: { after_id: lastId.value },
        });
        const incoming = data.messages || [];
        if (incoming.length) {
            const seen = new Set(chatMessages.value.map((m) => m.id));
            incoming.forEach((row) => {
                if (!seen.has(row.id)) chatMessages.value.push(row);
            });
            scrollBottom();
        }
    } catch {
        // ignore
    }
}

watch([q, status], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 250);
});

watch(
    () => props.messages,
    (value) => {
        chatMessages.value = [...value];
        scrollBottom();
    },
);

onMounted(() => {
    scrollBottom();
    pollTimer = setInterval(poll, 4000);
});

onUnmounted(() => {
    clearInterval(pollTimer);
    clearTimeout(searchTimer);
});
</script>

<template>
    <AdminLayout title="Live Support">
        <Head title="Admin Live Support" />

        <div class="mb-4 flex flex-wrap items-center gap-2">
            <input
                v-model="q"
                type="search"
                placeholder="Cari nama / email..."
                class="rounded-xl border-black/10 text-sm"
            />
            <select v-model="status" class="rounded-xl border-black/10 text-sm">
                <option value="open">Terbuka</option>
                <option value="closed">Selesai</option>
                <option value="all">Semua</option>
            </select>
            <p class="text-sm text-neutral-500">{{ unread }} pesan belum dibaca</p>
        </div>

        <div class="grid min-h-[32rem] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="border-b border-black/5 lg:border-b-0 lg:border-r">
                <div class="max-h-[28rem] overflow-y-auto lg:max-h-[40rem]">
                    <button
                        v-for="row in conversations"
                        :key="row.id"
                        type="button"
                        class="flex w-full flex-col gap-1 border-b border-black/5 px-4 py-3 text-left hover:bg-mist/70"
                        :class="active?.id === row.id ? 'bg-mist' : ''"
                        @click="openConversation(row.id)"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-semibold">{{ row.visitor_name }}</p>
                            <span
                                v-if="row.unread"
                                class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-bold text-white"
                            >
                                {{ row.unread }}
                            </span>
                        </div>
                        <p class="truncate text-xs text-neutral-500">{{ row.last_message || '—' }}</p>
                        <p class="text-[10px] uppercase tracking-[0.12em] text-neutral-400">
                            {{ row.last_message_label }} · {{ row.status }}
                        </p>
                    </button>
                    <p v-if="!conversations.length" class="px-4 py-10 text-center text-sm text-neutral-400">
                        Belum ada percakapan.
                    </p>
                </div>
            </aside>

            <section class="flex min-h-[24rem] flex-col">
                <template v-if="active">
                    <div class="flex items-center justify-between gap-3 border-b border-black/5 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold">{{ active.visitor_name }}</p>
                            <p class="text-xs text-neutral-500">{{ active.visitor_email }}</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-full border border-black/10 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.12em]"
                            @click="setStatus(active.status === 'open' ? 'closed' : 'open')"
                        >
                            {{ active.status === 'open' ? 'Tutup' : 'Buka lagi' }}
                        </button>
                    </div>
                    <div ref="listEl" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-[#f7f7f8] px-4 py-4">
                        <div
                            v-for="message in chatMessages"
                            :key="message.id"
                            class="flex"
                            :class="message.is_mine ? 'justify-end' : 'justify-start'"
                        >
                            <div
                                class="max-w-[80%] rounded-2xl px-3 py-2 text-sm"
                                :class="message.is_mine ? 'bg-charcoal text-white' : 'bg-white shadow-sm'"
                            >
                                <p class="whitespace-pre-wrap break-words">{{ message.body }}</p>
                                <p class="mt-1 text-[10px] opacity-60">{{ message.sender_name }} · {{ message.created_at_label }}</p>
                            </div>
                        </div>
                    </div>
                    <form class="flex items-end gap-2 border-t border-black/5 p-3" @submit.prevent="send">
                        <textarea
                            v-model="body"
                            rows="2"
                            class="flex-1 resize-none rounded-xl border-black/10 text-sm"
                            placeholder="Balas pengunjung..."
                            @keydown.enter.exact.prevent="send"
                        />
                        <button
                            type="submit"
                            class="rounded-full bg-brand px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-50"
                            :disabled="sending || active.status === 'closed'"
                        >
                            Kirim
                        </button>
                    </form>
                </template>
                <p v-else class="m-auto px-6 py-16 text-center text-sm text-neutral-400">
                    Pilih percakapan di kiri untuk membalas.
                </p>
            </section>
        </div>
    </AdminLayout>
</template>
