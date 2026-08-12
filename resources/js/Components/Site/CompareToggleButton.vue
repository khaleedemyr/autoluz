<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    COMPARE_EVENT,
    COMPARE_MAX,
    isInCompare,
    toggleCompare,
} from '@/utils/compareVehicles';
import { useI18n } from '@/composables/useI18n';
import { swalToast } from '@/utils/swal';

const props = defineProps({
    vehicle: { type: Object, required: true },
    compact: { type: Boolean, default: false },
});

const { t } = useI18n();
const active = ref(false);

function refresh() {
    active.value = isInCompare(props.vehicle.id);
}

function onToggle(event) {
    event?.preventDefault?.();
    event?.stopPropagation?.();
    const result = toggleCompare(props.vehicle);
    if (result.full) {
        swalToast(t('compare_full', { max: COMPARE_MAX }));
        return;
    }
    active.value = result.list.some((item) => item.id === Number(props.vehicle.id));
    swalToast(active.value ? t('compare_added') : t('compare_removed'));
}

onMounted(() => {
    refresh();
    window.addEventListener(COMPARE_EVENT, refresh);
    window.addEventListener('storage', refresh);
});

onUnmounted(() => {
    window.removeEventListener(COMPARE_EVENT, refresh);
    window.removeEventListener('storage', refresh);
});

const label = computed(() => (active.value ? t('compare_remove') : t('compare_add')));
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center justify-center gap-1.5 font-semibold uppercase tracking-[0.12em] transition"
        :class="compact
            ? 'rounded-full bg-white/95 px-2.5 py-1 text-[10px] text-charcoal shadow-soft hover:bg-white'
            : 'rounded-full border px-4 py-2 text-[11px] ' + (active ? 'border-brand bg-brand text-white' : 'border-[var(--line)] bg-white text-neutral-600 hover:border-brand hover:text-brand')"
        :aria-pressed="active"
        @click="onToggle"
    >
        <span aria-hidden="true">{{ active ? '✓' : '+' }}</span>
        {{ label }}
    </button>
</template>
