<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { EMOJI_CATEGORIES } from '@/utils/communityEmoji';
import { useI18n } from '@/composables/useI18n';

const emit = defineEmits(['pick']);
const { t } = useI18n();

const open = ref(false);
const root = ref(null);
const listEl = ref(null);
const activeId = ref(EMOJI_CATEGORIES[0].id);
const query = ref('');

const filteredCategories = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return EMOJI_CATEGORIES;

    return EMOJI_CATEGORIES
        .map((cat) => ({
            ...cat,
            emojis: cat.emojis.filter((e) => e.includes(q) || cat.label.toLowerCase().includes(q)),
        }))
        .filter((cat) => cat.emojis.length > 0);
});

function toggle() {
    open.value = !open.value;
    if (open.value) {
        query.value = '';
        activeId.value = EMOJI_CATEGORIES[0].id;
    }
}

function close() {
    open.value = false;
}

function pick(emoji) {
    emit('pick', emoji);
}

function onDocClick(e) {
    if (!root.value?.contains(e.target)) {
        close();
    }
}

function scrollToCategory(id) {
    activeId.value = id;
    const el = listEl.value?.querySelector(`[data-cat="${id}"]`);
    el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function onListScroll() {
    if (!listEl.value || query.value.trim()) return;
    const top = listEl.value.scrollTop + 24;
    const sections = [...listEl.value.querySelectorAll('[data-cat]')];
    for (let i = sections.length - 1; i >= 0; i -= 1) {
        if (sections[i].offsetTop <= top) {
            activeId.value = sections[i].dataset.cat;
            break;
        }
    }
}

watch(open, async (v) => {
    if (v) {
        await nextTick();
        listEl.value?.scrollTo({ top: 0 });
    }
});

onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/55 transition hover:text-brand"
            :aria-label="t('community_emoji')"
            @click.stop="toggle"
        >
            <span class="text-base leading-none">😊</span>
            <span class="hidden sm:inline">{{ t('community_emoji') }}</span>
        </button>

        <div
            v-if="open"
            class="absolute bottom-full left-0 z-40 mb-2 flex h-[22rem] w-[20rem] flex-col overflow-hidden rounded-2xl border border-[var(--line)] bg-white shadow-lift sm:w-[22rem]"
            @click.stop
        >
            <div class="border-b border-[var(--line)] p-2">
                <input
                    v-model="query"
                    type="search"
                    :placeholder="t('community_emoji_search')"
                    class="w-full rounded-xl border border-[var(--line)] bg-mist/50 px-3 py-2 text-sm text-charcoal placeholder:text-charcoal/40 focus:border-brand focus:ring-0"
                />
            </div>

            <div
                v-if="!query.trim()"
                class="flex shrink-0 gap-0.5 overflow-x-auto border-b border-[var(--line)] px-1.5 py-1.5"
            >
                <button
                    v-for="cat in EMOJI_CATEGORIES"
                    :key="cat.id"
                    type="button"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-base transition"
                    :class="activeId === cat.id ? 'bg-brand/10 ring-1 ring-brand/30' : 'hover:bg-mist'"
                    :title="cat.label"
                    @click="scrollToCategory(cat.id)"
                >
                    {{ cat.icon }}
                </button>
            </div>

            <div
                ref="listEl"
                class="min-h-0 flex-1 overflow-y-auto px-2 py-2"
                @scroll="onListScroll"
            >
                <div
                    v-for="cat in filteredCategories"
                    :key="cat.id"
                    :data-cat="cat.id"
                    class="mb-3"
                >
                    <p class="sticky top-0 z-10 bg-white/95 px-1 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-charcoal/45 backdrop-blur">
                        {{ cat.label }}
                    </p>
                    <div class="grid grid-cols-8 gap-0.5">
                        <button
                            v-for="(emoji, idx) in cat.emojis"
                            :key="`${cat.id}-${idx}-${emoji}`"
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-lg transition hover:bg-mist active:scale-95"
                            @click="pick(emoji)"
                        >
                            {{ emoji }}
                        </button>
                    </div>
                </div>
                <p
                    v-if="!filteredCategories.length"
                    class="py-8 text-center text-sm text-charcoal/45"
                >
                    {{ t('community_emoji_empty') }}
                </p>
            </div>
        </div>
    </div>
</template>
