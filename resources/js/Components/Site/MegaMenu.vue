<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    item: { type: Object, required: true },
});

const emit = defineEmits(['navigate']);
const { t } = useI18n();

const activeSub = ref('all');
const pageIndex = ref(0);
const perPage = 4;

watch(
    () => props.item?.key,
    () => {
        activeSub.value = 'all';
        pageIndex.value = 0;
    },
);

const articles = computed(() => {
    const panels = props.item?.panels || {};
    return panels[activeSub.value] || panels.all || [];
});

const pageCount = computed(() => Math.max(1, Math.ceil(articles.value.length / perPage)));

const visible = computed(() => {
    const start = pageIndex.value * perPage;
    return articles.value.slice(start, start + perPage);
});

function selectSub(key) {
    activeSub.value = key;
    pageIndex.value = 0;
}

function prev() {
    pageIndex.value = (pageIndex.value - 1 + pageCount.value) % pageCount.value;
}

function next() {
    pageIndex.value = (pageIndex.value + 1) % pageCount.value;
}
</script>

<template>
    <div class="border-b border-[var(--line)] bg-white text-charcoal shadow-lift">
        <div class="container-editorial grid lg:grid-cols-[12rem_minmax(0,1fr)]">
            <aside class="border-b border-[var(--line)] bg-mist lg:border-b-0 lg:border-r lg:border-[var(--line)]">
                <nav class="flex gap-1 overflow-x-auto px-3 py-4 lg:flex-col lg:overflow-visible lg:py-6 lg:pl-1 lg:pr-4">
                    <button
                        v-for="sub in item.subs"
                        :key="sub.key"
                        type="button"
                        class="shrink-0 rounded-full px-3.5 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.14em] transition lg:w-full lg:rounded-xl lg:text-right"
                        :class="
                            activeSub === sub.key
                                ? 'bg-brand text-white shadow-glow lg:bg-brand'
                                : 'text-charcoal/60 hover:bg-white hover:text-charcoal'
                        "
                        @click="selectSub(sub.key)"
                    >
                        {{ sub.label }}
                    </button>
                    <Link
                        :href="route('categories.show', item.slug)"
                        class="mt-3 hidden px-3 py-2 text-right text-[11px] font-semibold uppercase tracking-[0.16em] text-brand lg:block"
                        @click="emit('navigate')"
                    >
                        Lihat semua →
                    </Link>
                </nav>
            </aside>

            <div class="min-w-0 p-4 sm:p-6">
                <div v-if="visible.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="article in visible"
                        :key="article.id"
                        :href="route('articles.show', article.slug)"
                        class="group block"
                        @click="emit('navigate')"
                    >
                        <div class="media-frame aspect-[16/10] bg-charcoal-mute shadow-soft">
                            <img
                                v-if="article.featured_image_url"
                                :src="article.featured_image_url"
                                :alt="article.title"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                        </div>
                        <h3 class="mt-3 line-clamp-3 text-[13px] font-semibold leading-snug tracking-[-0.01em] text-charcoal transition group-hover:text-brand">
                            {{ article.title }}
                        </h3>
                    </Link>
                </div>
                <p v-else class="py-12 text-center text-sm text-neutral-500">
                    {{ t('mega_empty') }}
                </p>

                <div v-if="pageCount > 1" class="mt-5 flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-charcoal/10 text-charcoal/70 transition hover:border-brand hover:text-brand"
                        aria-label="Sebelumnya"
                        @click="prev"
                    >
                        ‹
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-charcoal/10 text-charcoal/70 transition hover:border-brand hover:text-brand"
                        aria-label="Berikutnya"
                        @click="next"
                    >
                        ›
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
