<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    posts: { type: Object, required: true },
    only: { type: Array, default: () => ['posts'] },
});

const emit = defineEmits(['update:items']);

const { t } = useI18n();
const items = ref([...(props.posts.data || [])]);
const nextUrl = ref(props.posts.next_page_url || null);
const loading = ref(false);
const sentinel = ref(null);
let observer = null;

function syncFromProps() {
    items.value = [...(props.posts.data || [])];
    nextUrl.value = props.posts.next_page_url || null;
    emit('update:items', items.value);
}

function loadMore() {
    if (!nextUrl.value || loading.value) return;
    loading.value = true;

    router.get(nextUrl.value, {}, {
        preserveState: true,
        preserveScroll: true,
        only: props.only,
        onSuccess: (page) => {
            const incoming = page.props.posts;
            const fresh = incoming?.data || [];
            const known = new Set(items.value.map((p) => p.id));
            const appended = fresh.filter((p) => !known.has(p.id));
            items.value = [...items.value, ...appended];
            nextUrl.value = incoming?.next_page_url || null;
            emit('update:items', items.value);
        },
        onFinish: () => {
            loading.value = false;
        },
    });
}

watch(
    () => props.posts?.current_page,
    (pageNum) => {
        if ((pageNum || 1) === 1) {
            syncFromProps();
        }
    },
);

onMounted(() => {
    emit('update:items', items.value);
    observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((e) => e.isIntersecting)) {
                loadMore();
            }
        },
        { rootMargin: '240px' },
    );
    if (sentinel.value) observer.observe(sentinel.value);
});

onUnmounted(() => observer?.disconnect());

defineExpose({ items, loadMore });
</script>

<template>
    <div>
        <slot :items="items" />

        <div ref="sentinel" class="py-6 text-center text-sm text-charcoal/45">
            <span v-if="loading">{{ t('community_loading_more') }}</span>
            <span v-else-if="!nextUrl && items.length">{{ t('community_end_of_feed') }}</span>
        </div>
    </div>
</template>
