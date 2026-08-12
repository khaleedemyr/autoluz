<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
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
    allowTagging: { type: Boolean, default: true },
});

const emit = defineEmits(['success', 'cancel']);

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const preview = ref(null);
const fileInput = ref(null);
const textarea = ref(null);

const tagPanel = ref(null); // 'article' | 'event' | 'vehicle' | null
const tagQuery = ref('');
const tagResults = ref([]);
const tagLoading = ref(false);
const selectedArticle = ref(null);
const selectedEvent = ref(null);
const selectedVehicle = ref(null);
let tagDebounce = null;

const canTag = computed(() => props.allowTagging);

const tagSearchRoute = {
    article: 'community.search-articles',
    event: 'community.search-events',
    vehicle: 'community.search-vehicles',
};

const tagSearchPlaceholder = computed(() => {
    if (tagPanel.value === 'article') return t('community_tag_search_article');
    if (tagPanel.value === 'event') return t('community_tag_search_event');
    return t('community_tag_search_vehicle');
});

const form = useForm({
    body: '',
    image: null,
    group_id: props.groupId,
    article_id: null,
    event_id: null,
    vehicle_id: null,
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

function openTagPanel(type) {
    tagPanel.value = tagPanel.value === type ? null : type;
    tagQuery.value = '';
    tagResults.value = [];
    if (tagPanel.value) {
        nextTick(() => searchTags());
    }
}

function closeTagPanel() {
    tagPanel.value = null;
    tagQuery.value = '';
    tagResults.value = [];
}

async function searchTags() {
    if (!tagPanel.value) return;
    tagLoading.value = true;
    try {
        const { data } = await axios.get(route(tagSearchRoute[tagPanel.value]), {
            params: { q: tagQuery.value },
        });
        tagResults.value = data?.data || [];
    } catch {
        tagResults.value = [];
    } finally {
        tagLoading.value = false;
    }
}

function onTagQueryInput() {
    clearTimeout(tagDebounce);
    tagDebounce = setTimeout(searchTags, 280);
}

function pickTag(item) {
    if (tagPanel.value === 'article') {
        selectedArticle.value = item;
        form.article_id = item.id;
    } else if (tagPanel.value === 'event') {
        selectedEvent.value = item;
        form.event_id = item.id;
    } else if (tagPanel.value === 'vehicle') {
        selectedVehicle.value = item;
        form.vehicle_id = item.id;
    }
    closeTagPanel();
}

function clearArticle() {
    selectedArticle.value = null;
    form.article_id = null;
}

function clearEvent() {
    selectedEvent.value = null;
    form.event_id = null;
}

function clearVehicle() {
    selectedVehicle.value = null;
    form.vehicle_id = null;
}

watch(
    () => props.groupId,
    (id) => {
        form.group_id = id;
    },
);

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
            form.reset('body', 'image', 'article_id', 'event_id', 'vehicle_id');
            form.group_id = props.groupId;
            clearImage();
            clearArticle();
            clearEvent();
            clearVehicle();
            closeTagPanel();
            emit('success');
        },
        onError: () => {
            const msg =
                form.errors.body ||
                form.errors.image ||
                form.errors.article_id ||
                form.errors.event_id ||
                form.errors.vehicle_id ||
                t('community_post_failed');
            swalToast(msg, { icon: 'error' });
        },
    });
}

if (props.autofocus) {
    nextTick(() => textarea.value?.focus());
}

onUnmounted(() => clearTimeout(tagDebounce));
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

                <div
                    v-if="canTag && (selectedArticle || selectedEvent || selectedVehicle)"
                    class="mt-3 space-y-2"
                >
                    <div
                        v-if="selectedArticle"
                        class="flex items-center gap-3 rounded-2xl border border-[var(--line)] bg-white/80 p-2.5"
                    >
                        <img
                            v-if="selectedArticle.featured_image_url"
                            :src="selectedArticle.featured_image_url"
                            alt=""
                            class="h-12 w-12 shrink-0 rounded-xl object-cover"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand">
                                {{ t('community_tagged_article') }}
                            </p>
                            <p class="truncate text-sm font-medium text-charcoal">{{ selectedArticle.title }}</p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 text-[10px] font-semibold uppercase tracking-[0.12em] text-charcoal/45 hover:text-red-600"
                            @click="clearArticle"
                        >
                            {{ t('community_tag_clear') }}
                        </button>
                    </div>
                    <div
                        v-if="selectedEvent"
                        class="flex items-center gap-3 rounded-2xl border border-[var(--line)] bg-white/80 p-2.5"
                    >
                        <img
                            v-if="selectedEvent.cover_image_url"
                            :src="selectedEvent.cover_image_url"
                            alt=""
                            class="h-12 w-12 shrink-0 rounded-xl object-cover"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand">
                                {{ t('community_tagged_event') }}
                            </p>
                            <p class="truncate text-sm font-medium text-charcoal">{{ selectedEvent.title }}</p>
                            <p v-if="selectedEvent.starts_at_label" class="text-xs text-charcoal/45">
                                {{ selectedEvent.starts_at_label }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 text-[10px] font-semibold uppercase tracking-[0.12em] text-charcoal/45 hover:text-red-600"
                            @click="clearEvent"
                        >
                            {{ t('community_tag_clear') }}
                        </button>
                    </div>
                    <div
                        v-if="selectedVehicle"
                        class="flex items-center gap-3 rounded-2xl border border-[var(--line)] bg-white/80 p-2.5"
                    >
                        <img
                            v-if="selectedVehicle.cover_image_url"
                            :src="selectedVehicle.cover_image_url"
                            alt=""
                            class="h-12 w-12 shrink-0 rounded-xl object-cover"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand">
                                {{ t('community_tagged_vehicle') }}
                            </p>
                            <p class="truncate text-sm font-medium text-charcoal">
                                {{ selectedVehicle.name || selectedVehicle.title }}
                            </p>
                            <p
                                v-if="selectedVehicle.brand_name || selectedVehicle.price_label"
                                class="truncate text-xs text-charcoal/45"
                            >
                                {{
                                    [selectedVehicle.brand_name, selectedVehicle.price_label]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 text-[10px] font-semibold uppercase tracking-[0.12em] text-charcoal/45 hover:text-red-600"
                            @click="clearVehicle"
                        >
                            {{ t('community_tag_clear') }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="canTag && tagPanel"
                    class="mt-3 overflow-hidden rounded-2xl border border-[var(--line)] bg-white"
                >
                    <div class="border-b border-[var(--line)] px-3 py-2">
                        <input
                            v-model="tagQuery"
                            type="search"
                            :placeholder="tagSearchPlaceholder"
                            class="w-full border-0 bg-transparent px-1 py-1.5 text-sm text-charcoal placeholder:text-charcoal/35 focus:ring-0"
                            @input="onTagQueryInput"
                        />
                    </div>
                    <div class="max-h-56 overflow-y-auto">
                        <p v-if="tagLoading" class="px-4 py-3 text-xs text-charcoal/45">…</p>
                        <p
                            v-else-if="!tagResults.length"
                            class="px-4 py-3 text-xs text-charcoal/45"
                        >
                            {{ t('community_tag_empty') }}
                        </p>
                        <button
                            v-for="item in tagResults"
                            :key="`${tagPanel}-${item.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition hover:bg-mist/60"
                            @click="pickTag(item)"
                        >
                            <img
                                v-if="item.featured_image_url || item.cover_image_url"
                                :src="item.featured_image_url || item.cover_image_url"
                                alt=""
                                class="h-10 w-10 shrink-0 rounded-lg object-cover"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-charcoal">
                                    {{ item.title || item.name }}
                                </p>
                                <p
                                    v-if="item.starts_at_label || item.excerpt || item.price_label"
                                    class="truncate text-xs text-charcoal/45"
                                >
                                    {{ item.starts_at_label || item.excerpt || item.price_label }}
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-3">
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

                        <template v-if="canTag">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] transition"
                                :class="tagPanel === 'article' || selectedArticle ? 'text-brand' : 'text-charcoal/55 hover:text-brand'"
                                @click="openTagPanel('article')"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                                {{ t('community_tag_article') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] transition"
                                :class="tagPanel === 'event' || selectedEvent ? 'text-brand' : 'text-charcoal/55 hover:text-brand'"
                                @click="openTagPanel('event')"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ t('community_tag_event') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] transition"
                                :class="tagPanel === 'vehicle' || selectedVehicle ? 'text-brand' : 'text-charcoal/55 hover:text-brand'"
                                @click="openTagPanel('vehicle')"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 17h.01M16 17h.01M5 11l1.5-4.5A2 2 0 018.4 5h7.2a2 2 0 011.9 1.5L19 11m-14 0h14m-14 0v6a1 1 0 001 1h1m12-7v6a1 1 0 01-1 1h-1" />
                                </svg>
                                {{ t('community_tag_vehicle') }}
                            </button>
                        </template>
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
