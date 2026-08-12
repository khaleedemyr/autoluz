<script setup>
import { computed, nextTick, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import CommunityEmojiPicker from '@/Components/Community/CommunityEmojiPicker.vue';
import { applyEmoticons } from '@/utils/communityEmoji';
import { swalToast, swalWarning } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    placeholder: { type: String, default: '' },
    submitLabel: { type: String, default: '' },
    action: { type: String, required: true },
    replyToName: { type: String, default: '' },
    autofocus: { type: Boolean, default: false },
    groupId: { type: [Number, String], default: null },
});

const emit = defineEmits(['success', 'cancel']);

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const preview = ref(null);
const fileInput = ref(null);
const textarea = ref(null);

const form = useForm({
    body: '',
    image: null,
    group_id: props.groupId,
});

const resolvedPlaceholder = computed(() => {
    if (props.replyToName) {
        return t('community_reply_to_ph', { name: props.replyToName });
    }
    return props.placeholder || t('community_compose_ph');
});
const resolvedLabel = computed(() => props.submitLabel || t('community_post'));

function onFileChange(e) {
    const file = e.target.files?.[0] || null;
    form.image = file;
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
        preview.value = null;
    }
    if (file) {
        preview.value = URL.createObjectURL(file);
    }
}

function clearImage() {
    form.image = null;
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
        preview.value = null;
    }
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

async function goLogin() {
    await swalWarning(t('community_login_gate'), {
        title: t('community_login'),
        confirmButtonText: t('community_login'),
    });
    const intended = window.location.pathname + window.location.search;
    window.location.href = `${route('login')}?intended=${encodeURIComponent(intended)}`;
}

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

function submit() {
    if (!user.value) {
        goLogin();
        return;
    }

    form.body = applyEmoticons(form.body);

    if (!form.body.trim()) {
        swalToast(t('community_body_required'), { icon: 'warning' });
        return;
    }

    form.post(props.action, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body', 'image');
            form.group_id = props.groupId;
            clearImage();
            emit('success');
        },
        onError: () => {
            const msg = form.errors.body || form.errors.image || t('community_post_failed');
            swalToast(msg, { icon: 'error' });
        },
    });
}

if (props.autofocus) {
    nextTick(() => textarea.value?.focus());
}
</script>

<template>
    <div class="border-b border-[var(--line)] pb-5">
        <div v-if="!user" class="rounded-2xl border border-[var(--line)] bg-white/70 px-4 py-5 text-center">
            <p class="text-sm text-charcoal/70">{{ t('community_login_gate') }}</p>
            <div class="mt-3 flex justify-center gap-3">
                <button
                    type="button"
                    class="rounded-full bg-brand px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-white"
                    @click="goLogin"
                >
                    {{ t('community_login') }}
                </button>
                <a
                    :href="route('register')"
                    class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em]"
                >
                    {{ t('community_register') }}
                </a>
            </div>
        </div>

        <form v-else class="flex gap-3" @submit.prevent="submit">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mist text-sm font-semibold text-charcoal"
            >
                <img
                    v-if="user.avatar_url"
                    :src="user.avatar_url"
                    :alt="user.name"
                    class="h-full w-full object-cover"
                />
                <span v-else>{{ (user.name || '?').slice(0, 1).toUpperCase() }}</span>
            </div>

            <div class="min-w-0 flex-1">
                <p v-if="replyToName" class="mb-2 text-xs text-charcoal/50">
                    {{ t('community_replying_to', { name: replyToName }) }}
                    <button type="button" class="ml-2 font-semibold text-brand" @click="emit('cancel')">
                        {{ t('community_cancel') }}
                    </button>
                </p>

                <textarea
                    ref="textarea"
                    v-model="form.body"
                    rows="3"
                    maxlength="500"
                    :placeholder="resolvedPlaceholder"
                    class="w-full resize-none rounded-2xl border border-[var(--line)] bg-white/80 px-4 py-3 text-[15px] leading-relaxed text-charcoal placeholder:text-charcoal/35 focus:border-brand focus:ring-0"
                />
                <p v-if="form.errors.body" class="mt-1 text-xs text-red-600">{{ form.errors.body }}</p>

                <div v-if="preview" class="relative mt-3 overflow-hidden rounded-2xl border border-[var(--line)]">
                    <img :src="preview" alt="" class="max-h-72 w-full object-cover" />
                    <button
                        type="button"
                        class="absolute right-3 top-3 rounded-full bg-charcoal/80 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-white"
                        @click="clearImage"
                    >
                        {{ t('community_remove_photo') }}
                    </button>
                </div>
                <p v-if="form.errors.image" class="mt-1 text-xs text-red-600">{{ form.errors.image }}</p>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <CommunityEmojiPicker @pick="insertEmoji" />

                        <label class="inline-flex cursor-pointer items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/55 transition hover:text-brand">
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                @change="onFileChange"
                            />
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ t('community_add_photo') }}
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="rounded-full bg-brand px-5 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-white transition hover:opacity-90 disabled:opacity-40"
                        :disabled="form.processing || !form.body.trim()"
                    >
                        {{ resolvedLabel }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>
