<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    COMPARE_EVENT,
    COMPARE_MAX,
    clearCompare,
    compareUrl,
    getCompareList,
    setCompareList,
} from '@/utils/compareVehicles';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
const page = usePage();
const list = ref([]);

function refresh() {
    list.value = getCompareList();
}

function remove(id) {
    setCompareList(list.value.filter((item) => item.id !== Number(id)));
}

function goCompare() {
    router.get(compareUrl(list.value.map((item) => item.id)));
}

function clearAll() {
    clearCompare();
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

const onComparePage = computed(() => String(page.url || '').startsWith('/bandingkan'));
const visible = computed(() => list.value.length > 0 && !onComparePage.value);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="visible"
            class="fixed inset-x-0 bottom-[calc(3.5rem+env(safe-area-inset-bottom))] z-40 border-t border-[var(--line)] bg-white/95 p-3 shadow-[0_-12px_40px_rgba(0,0,0,0.12)] backdrop-blur lg:bottom-0"
        >
            <div class="container-editorial flex flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 flex-1 items-center gap-3 overflow-x-auto">
                    <div class="shrink-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                            {{ t('compare_tray_title') }}
                        </p>
                        <p class="mt-0.5 text-sm text-neutral-600">
                            {{ list.length }} / {{ COMPARE_MAX }} {{ t('compare_selected') }}
                        </p>
                    </div>
                    <div
                        v-for="item in list"
                        :key="item.id"
                        class="flex shrink-0 items-center gap-2 rounded-full border border-[var(--line)] bg-white py-1 pl-1 pr-2"
                    >
                        <div class="h-8 w-10 overflow-hidden rounded-full bg-mist">
                            <img v-if="item.cover_image_url" :src="item.cover_image_url" :alt="item.name" class="h-full w-full object-cover" />
                        </div>
                        <div class="min-w-0">
                            <p class="max-w-[7rem] truncate text-xs font-semibold">{{ item.name }}</p>
                            <p class="max-w-[7rem] truncate text-[10px] text-neutral-400">{{ item.brand }}</p>
                        </div>
                        <button type="button" class="px-1 text-neutral-400 hover:text-brand" @click="remove(item.id)">×</button>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-full border border-[var(--line)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-neutral-500"
                        @click="clearAll"
                    >
                        {{ t('compare_clear') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-full bg-brand px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-white disabled:opacity-50"
                        :disabled="list.length < 2"
                        @click="goCompare"
                    >
                        {{ t('compare_go') }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
    <div v-if="visible" class="h-[8.5rem] shrink-0 lg:h-20" aria-hidden="true" />
</template>
