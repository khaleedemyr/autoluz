<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Number, default: 0 },
    max: { type: Number, default: 5 },
    size: { type: String, default: 'md' },
    interactive: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
const uid = Math.random().toString(36).slice(2, 8);

const dim = computed(() => (props.size === 'sm' ? 'h-3.5 w-3.5' : props.size === 'lg' ? 'h-7 w-7' : 'h-5 w-5'));

function fill(index) {
    const value = Number(props.modelValue || 0);
    if (value >= index) return 'full';
    if (value >= index - 0.5) return 'half';
    return 'empty';
}

function pick(index) {
    if (!props.interactive) return;
    emit('update:modelValue', index);
}
</script>

<template>
    <div class="inline-flex items-center gap-0.5" :class="interactive ? 'cursor-pointer' : ''" role="img">
        <button
            v-for="index in max"
            :key="index"
            type="button"
            class="p-0.5 text-brand disabled:cursor-default"
            :disabled="!interactive"
            :aria-label="`${index}`"
            @click="pick(index)"
        >
            <svg :class="dim" viewBox="0 0 24 24">
                <defs v-if="fill(index) === 'half'">
                    <linearGradient :id="`star-half-${uid}-${index}`" x1="0" x2="1" y1="0" y2="0">
                        <stop offset="50%" stop-color="currentColor" />
                        <stop offset="50%" stop-color="currentColor" stop-opacity="0.2" />
                    </linearGradient>
                </defs>
                <path
                    d="M12 3.2l2.35 4.76 5.25.76-3.8 3.7.9 5.22L12 15.9 6.3 17.64l.9-5.22-3.8-3.7 5.25-.76L12 3.2z"
                    :fill="fill(index) === 'full' ? 'currentColor' : fill(index) === 'half' ? `url(#star-half-${uid}-${index})` : 'none'"
                    stroke="currentColor"
                    stroke-width="1.4"
                />
            </svg>
        </button>
    </div>
</template>
