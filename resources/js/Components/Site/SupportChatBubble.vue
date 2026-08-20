<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from '@/composables/useI18n';

const page = usePage();
const { t } = useI18n();

const open = ref(false);
const loading = ref(false);
const sending = ref(false);
const agentsOnline = ref(false);
const conversation = ref(null);
const messages = ref([]);
const name = ref('');
const email = ref('');
const body = ref('');
const error = ref('');
const listEl = ref(null);
let pollTimer = null;

const authUser = computed(() => page.props.auth?.user || null);
const loggedIn = computed(() => !!authUser.value);
const lastId = computed(() => (messages.value.length ? Math.max(...messages.value.map((m) => Number(m.id))) : 0));

function scrollBottom() {
    nextTick(() => {
        if (listEl.value) listEl.value.scrollTop = listEl.value.scrollHeight;
    });
}

async function loadCurrent() {
    loading.value = true;
    try {
        const { data } = await axios.get(route('support.current'));
        conversation.value = data.conversation;
        messages.value = data.messages || [];
        agentsOnline.value = !!data.agents_online;
        name.value = data.visitor?.name || name.value;
        email.value = data.visitor?.email || email.value;
        scrollBottom();
    } catch {
        // ignore
    } finally {
        loading.value = false;
    }
}

async function poll() {
    if (!open.value) return;
    try {
        const { data } = await axios.get(route('support.poll'), {
            params: { after_id: lastId.value },
        });
        agentsOnline.value = !!data.agents_online;
        const incoming = data.messages || [];
        if (incoming.length) {
            const seen = new Set(messages.value.map((m) => m.id));
            incoming.forEach((row) => {
                if (!seen.has(row.id)) messages.value.push(row);
            });
            scrollBottom();
        }
        if (data.status && conversation.value) {
            conversation.value.status = data.status;
        }
    } catch {
        // ignore
    }
}

async function send() {
    const text = body.value.trim();
    if (!text || sending.value) return;

    if (!loggedIn.value && !conversation.value && (!name.value.trim() || !email.value.trim())) {
        error.value = t('support_need_identity');
        return;
    }

    sending.value = true;
    error.value = '';
    try {
        const { data } = await axios.post(route('support.store'), {
            body: text,
            name: name.value.trim() || undefined,
            email: email.value.trim() || undefined,
        });
        conversation.value = data.conversation;
        if (data.message) messages.value.push(data.message);
        body.value = '';
        scrollBottom();
    } catch (e) {
        const errors = e.response?.data?.errors || {};
        error.value = errors.body?.[0] || errors.name?.[0] || errors.email?.[0] || t('support_send_failed');
    } finally {
        sending.value = false;
    }
}

function toggle() {
    open.value = !open.value;
}

function onOpenEvent() {
    open.value = true;
}

watch(open, (value) => {
    if (value) {
        loadCurrent();
        pollTimer = setInterval(poll, 4000);
    } else if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
});

onMounted(() => {
    window.addEventListener('autoluz-support-open', onOpenEvent);
});

onUnmounted(() => {
    window.removeEventListener('autoluz-support-open', onOpenEvent);
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <div class="fixed bottom-[calc(4.75rem+env(safe-area-inset-bottom))] right-5 z-40 lg:bottom-5">
        <div
            v-if="open"
            class="mb-3 flex h-[min(32rem,70dvh)] w-[min(22rem,calc(100vw-2.5rem))] flex-col overflow-hidden rounded-2xl border border-[var(--line)] bg-white text-charcoal shadow-lift"
        >
            <div class="flex items-start justify-between gap-3 bg-charcoal px-4 py-3 text-white">
                <div>
                    <p class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em]">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 13a8 8 0 0116 0" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 13v4a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13v4a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3" />
                        </svg>
                        {{ t('support_title') }}
                    </p>
                    <p class="mt-1 flex items-center gap-1.5 text-xs text-white/65">
                        <span
                            class="h-2 w-2 rounded-full"
                            :class="agentsOnline ? 'bg-emerald-400' : 'bg-white/35'"
                        />
                        {{ agentsOnline ? t('support_online') : t('support_offline') }}
                    </p>
                </div>
                <button type="button" class="rounded-full p-1 text-white/60 hover:text-white" :aria-label="t('support_close')" @click="open = false">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <div ref="listEl" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-mist/40 px-4 py-4">
                <div class="rounded-2xl bg-white px-3 py-3 text-sm leading-relaxed text-neutral-600 shadow-soft">
                    {{ t('support_intro') }}
                    <Link :href="route('legal.faq')" class="mt-2 block font-medium text-brand hover:underline" @click="open = false">
                        {{ t('support_see_faq') }}
                    </Link>
                </div>

                <p v-if="loading" class="text-center text-xs text-neutral-400">{{ t('support_loading') }}</p>

                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="flex"
                    :class="message.is_mine ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[85%] rounded-2xl px-3 py-2 text-sm leading-relaxed"
                        :class="message.is_mine ? 'bg-brand text-white' : 'bg-white text-charcoal shadow-soft'"
                    >
                        <p class="whitespace-pre-wrap break-words">{{ message.body }}</p>
                        <p class="mt-1 text-[10px]" :class="message.is_mine ? 'text-white/70' : 'text-neutral-400'">
                            {{ message.sender_name }} · {{ message.created_at_label }}
                        </p>
                    </div>
                </div>
            </div>

            <form class="border-t border-[var(--line)] bg-white p-3" @submit.prevent="send">
                <div v-if="!loggedIn && !conversation" class="mb-2 grid grid-cols-2 gap-2">
                    <input
                        v-model="name"
                        type="text"
                        :placeholder="t('support_name')"
                        class="rounded-xl border-black/10 text-sm"
                    />
                    <input
                        v-model="email"
                        type="email"
                        :placeholder="t('support_email')"
                        class="rounded-xl border-black/10 text-sm"
                    />
                </div>
                <p v-if="error" class="mb-2 text-xs text-brand">{{ error }}</p>
                <div class="flex items-end gap-2">
                    <textarea
                        v-model="body"
                        rows="2"
                        :placeholder="t('support_placeholder')"
                        class="min-h-[2.75rem] flex-1 resize-none rounded-xl border-black/10 text-sm"
                        @keydown.enter.exact.prevent="send"
                    />
                    <button
                        type="submit"
                        class="inline-flex h-10 shrink-0 items-center rounded-full bg-brand px-4 text-[11px] font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-50"
                        :disabled="sending"
                    >
                        {{ sending ? t('support_sending') : t('support_send') }}
                    </button>
                </div>
            </form>
        </div>

        <button
            type="button"
            class="relative inline-flex h-14 w-14 items-center justify-center rounded-full bg-charcoal text-white shadow-lift transition hover:bg-black"
            :aria-label="t('support_title')"
            :aria-expanded="open"
            @click="toggle"
        >
            <svg v-if="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 13a8 8 0 0116 0" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 13v4a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H4" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13v4a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 21h3a3 3 0 003-3" />
            </svg>
            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
            <span
                v-if="agentsOnline && !open"
                class="absolute right-1 top-1 h-2.5 w-2.5 rounded-full border-2 border-charcoal bg-emerald-400"
            />
        </button>
    </div>
</template>
