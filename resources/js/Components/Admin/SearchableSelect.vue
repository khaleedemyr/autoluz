<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Cari…' },
    emptyLabel: { type: String, default: '— Pilih —' },
    required: { type: Boolean, default: false },
    labelKey: { type: String, default: 'name' },
    valueKey: { type: String, default: 'id' },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const query = ref('');
const root = ref(null);

const selected = computed(() =>
    props.options.find((opt) => String(opt[props.valueKey]) === String(props.modelValue ?? '')),
);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter((opt) => String(opt[props.labelKey] || '').toLowerCase().includes(q));
});

watch(open, async (value) => {
    if (value) {
        query.value = '';
        await nextTick();
        root.value?.querySelector('input')?.focus();
    }
});

function select(opt) {
    emit('update:modelValue', opt[props.valueKey]);
    open.value = false;
}

function clear() {
    emit('update:modelValue', '');
    open.value = false;
}

function onDocClick(event) {
    if (!root.value?.contains(event.target)) open.value = false;
}

onMounted(() => document.addEventListener('click', onDocClick));
onBeforeUnmount(() => document.removeEventListener('click', onDocClick));
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-2 rounded-xl border border-black/10 bg-white px-3 py-2 text-left text-sm"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span :class="selected ? 'text-charcoal' : 'text-neutral-400'">
                {{ selected ? selected[labelKey] : emptyLabel }}
            </span>
            <span class="text-neutral-400">▾</span>
        </button>

        <div
            v-if="open"
            class="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-black/10 bg-white shadow-lg"
        >
            <div class="border-b border-black/5 p-2">
                <input
                    v-model="query"
                    type="search"
                    :placeholder="placeholder"
                    class="w-full rounded-lg border-black/10 text-sm"
                />
            </div>
            <div class="max-h-56 overflow-y-auto py-1">
                <button
                    v-if="!required"
                    type="button"
                    class="block w-full px-3 py-2 text-left text-sm text-neutral-400 hover:bg-mist"
                    @click="clear"
                >
                    {{ emptyLabel }}
                </button>
                <button
                    v-for="opt in filtered"
                    :key="opt[valueKey]"
                    type="button"
                    class="block w-full px-3 py-2 text-left text-sm hover:bg-mist"
                    :class="String(opt[valueKey]) === String(modelValue ?? '') ? 'bg-brand/10 font-semibold text-brand' : 'text-charcoal'"
                    @click="select(opt)"
                >
                    {{ opt[labelKey] }}
                </button>
                <p v-if="!filtered.length" class="px-3 py-2 text-xs text-neutral-400">Tidak ditemukan.</p>
            </div>
        </div>
    </div>
</template>
