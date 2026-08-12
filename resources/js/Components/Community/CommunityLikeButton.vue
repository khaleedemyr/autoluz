<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { swalWarning } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    post: { type: Object, required: true },
});

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const liked = computed(() => !!props.post.liked_by_me);
const count = computed(() => Number(props.post.likes_count || 0));

async function toggle() {
    if (!user.value) {
        await swalWarning(t('community_login_gate'), {
            title: t('community_login'),
            confirmButtonText: t('community_login'),
        });
        const intended = window.location.pathname + window.location.search;
        window.location.href = `${route('login')}?intended=${encodeURIComponent(intended)}`;
        return;
    }

    router.post(route('community.like', props.post.id), {}, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] transition"
        :class="liked ? 'text-brand' : 'text-charcoal/55 hover:text-brand'"
        :aria-label="liked ? t('community_liked') : t('community_like')"
        @click="toggle"
    >
        <svg class="h-4 w-4" :fill="liked ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
        <span>{{ count }}</span>
    </button>
</template>
