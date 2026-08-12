<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getFollowedBrandIds, toggleFollowBrand } from '@/utils/followedBrands';
import { enablePushForBrands, syncFollowedBrandsToPush } from '@/utils/pushActions';
import { useI18n } from '@/composables/useI18n';
import { swalToast } from '@/utils/swal';

const props = defineProps({
    brandId: { type: [Number, String], required: true },
});

const { t } = useI18n();
const page = usePage();
const followed = ref([]);
const busy = ref(false);

const isFollowing = computed(() => followed.value.includes(Number(props.brandId)));

function refresh() {
    followed.value = getFollowedBrandIds();
}

async function toggle() {
    if (busy.value) return;
    busy.value = true;

    const next = toggleFollowBrand(props.brandId);
    followed.value = next;
    const nowFollowing = next.includes(Number(props.brandId));

    try {
        if (nowFollowing) {
            const result = await enablePushForBrands(page, next);
            if (result.ok) {
                swalToast(t('push_enabled'));
            } else if (result.reason === 'denied') {
                swalToast(t('push_denied'));
            } else if (result.reason === 'missing_vapid' || result.reason === 'unsupported') {
                // Follow still works offline via localStorage.
            }
        } else {
            await syncFollowedBrandsToPush(page);
        }
    } catch {
        // Keep local follow state even if push sync fails.
    } finally {
        busy.value = false;
    }
}

onMounted(() => {
    refresh();
    window.addEventListener('autoluz:brands-changed', refresh);
    window.addEventListener('storage', refresh);
});

onUnmounted(() => {
    window.removeEventListener('autoluz:brands-changed', refresh);
    window.removeEventListener('storage', refresh);
});
</script>

<template>
    <button
        type="button"
        class="rounded-full px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition disabled:opacity-60"
        :class="isFollowing
            ? 'border border-brand bg-brand text-white'
            : 'border border-charcoal/20 bg-white text-charcoal hover:border-brand hover:text-brand'"
        :disabled="busy"
        @click.stop.prevent="toggle"
    >
        {{ isFollowing ? t('brands_following') : t('brands_follow') }}
    </button>
</template>
