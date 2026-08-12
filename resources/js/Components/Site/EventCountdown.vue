<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    target: { type: String, required: true },
    compact: { type: Boolean, default: false },
});

const { t } = useI18n();
const now = ref(Date.now());
let timer = null;

const parts = computed(() => {
    const end = new Date(props.target).getTime();
    if (Number.isNaN(end)) {
        return { days: 0, hours: 0, minutes: 0, seconds: 0, ended: true };
    }

    let diff = Math.max(0, end - now.value);
    const days = Math.floor(diff / 86_400_000);
    diff %= 86_400_000;
    const hours = Math.floor(diff / 3_600_000);
    diff %= 3_600_000;
    const minutes = Math.floor(diff / 60_000);
    const seconds = Math.floor((diff % 60_000) / 1000);

    return {
        days,
        hours,
        minutes,
        seconds,
        ended: end <= now.value,
    };
});

const units = computed(() => [
    { key: 'days', value: parts.value.days, label: t('events_days') },
    { key: 'hours', value: parts.value.hours, label: t('events_hours') },
    { key: 'minutes', value: parts.value.minutes, label: t('events_mins') },
    { key: 'seconds', value: parts.value.seconds, label: t('events_secs') },
]);

function pad(value) {
    return String(value).padStart(2, '0');
}

onMounted(() => {
    timer = window.setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (timer) window.clearInterval(timer);
});
</script>

<template>
    <div v-if="parts.ended" class="inline-flex items-center gap-2 rounded-full bg-brand px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white">
        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white" />
        {{ t('events_live_now') }}
    </div>
    <div v-else>
        <p
            class="mb-2 text-[10px] font-semibold uppercase tracking-[0.18em]"
            :class="compact ? 'text-white/55' : 'text-white/50'"
        >
            {{ t('events_countdown') }}
        </p>
        <div class="flex flex-wrap gap-2">
            <div
                v-for="unit in units"
                :key="unit.key"
                class="min-w-[3.25rem] rounded-xl border border-white/15 bg-black/35 px-2.5 py-2 text-center backdrop-blur-sm"
                :class="compact ? 'min-w-[2.85rem] px-2 py-1.5' : ''"
            >
                <div
                    class="font-display tabular-nums leading-none tracking-[-0.04em]"
                    :class="compact ? 'text-xl' : 'text-2xl sm:text-3xl'"
                >
                    {{ pad(unit.value) }}
                </div>
                <div class="mt-1 text-[9px] font-semibold uppercase tracking-[0.14em] text-white/45">
                    {{ unit.label }}
                </div>
            </div>
        </div>
    </div>
</template>
