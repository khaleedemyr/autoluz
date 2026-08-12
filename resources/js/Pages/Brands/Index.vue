<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BrandLogo from '@/Components/Site/BrandLogo.vue';
import FollowBrandButton from '@/Components/Site/FollowBrandButton.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    cars: { type: Array, default: () => [] },
    motos: { type: Array, default: () => [] },
});

const { t } = useI18n();
const query = ref('');

function matches(brand) {
    const q = query.value.trim().toLowerCase();
    if (!q) return true;
    return String(brand.name || '').toLowerCase().includes(q);
}

const filteredCars = computed(() => props.cars.filter(matches));
const filteredMotos = computed(() => props.motos.filter(matches));
const hasQuery = computed(() => query.value.trim() !== '');
const totalMatches = computed(() => filteredCars.value.length + filteredMotos.value.length);
</script>

<template>
    <AppLayout>
        <Head :title="t('brands_title')" />
        <section class="border-b border-[var(--line)] bg-white">
            <div class="container-editorial py-10 lg:py-14">
                <p class="section-label">{{ t('brands_label') }}</p>
                <h1 class="font-display mt-3 text-5xl tracking-[-0.04em]">{{ t('brands_title') }}</h1>
                <p class="mt-3 max-w-2xl text-neutral-600">{{ t('brands_page_desc') }}</p>

                <div class="mt-6 max-w-xl">
                    <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                        {{ t('brands_search') }}
                    </label>
                    <input
                        v-model="query"
                        type="search"
                        :placeholder="t('brands_search_ph')"
                        class="w-full rounded-xl border-[var(--line)] bg-white text-sm shadow-soft"
                    />
                    <p v-if="hasQuery" class="mt-2 text-xs text-neutral-500">
                        {{ totalMatches }} {{ t('brands_search_result') }}
                    </p>
                </div>
            </div>
        </section>

        <section class="container-editorial py-10">
            <div class="mb-6">
                <h2 class="font-display text-3xl tracking-[-0.04em]">{{ t('brands_cars') }}</h2>
                <p class="mt-1 text-sm text-neutral-500">{{ t('brands_cars_desc') }}</p>
            </div>
            <div v-if="filteredCars.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div
                    v-for="brand in filteredCars"
                    :key="`car-${brand.id}`"
                    class="flex items-center gap-4 rounded-2xl border border-[var(--line)] bg-white/80 p-4 shadow-soft"
                >
                    <Link :href="brand.url" class="shrink-0">
                        <BrandLogo :brand="brand" size="md" />
                    </Link>
                    <div class="min-w-0 flex-1">
                        <Link :href="brand.url" class="font-semibold tracking-[-0.01em] hover:text-brand">
                            {{ brand.name }}
                        </Link>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-400">
                            {{ brand.vehicles_count || 0 }} {{ t('vehicles_short') }}
                            ·
                            {{ brand.articles_count || 0 }} {{ t('brands_articles') }}
                        </p>
                    </div>
                    <FollowBrandButton :brand-id="brand.id" />
                </div>
            </div>
            <p v-else class="text-sm text-neutral-500">
                {{ hasQuery ? t('brands_search_empty') : t('brands_empty') }}
            </p>
        </section>

        <section class="container-editorial pb-16">
            <div class="mb-6">
                <h2 class="font-display text-3xl tracking-[-0.04em]">{{ t('brands_motos') }}</h2>
                <p class="mt-1 text-sm text-neutral-500">{{ t('brands_motos_desc') }}</p>
            </div>
            <div v-if="filteredMotos.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div
                    v-for="brand in filteredMotos"
                    :key="`moto-${brand.id}`"
                    class="flex items-center gap-4 rounded-2xl border border-[var(--line)] bg-white/80 p-4 shadow-soft"
                >
                    <Link :href="brand.url" class="shrink-0">
                        <BrandLogo :brand="brand" size="md" />
                    </Link>
                    <div class="min-w-0 flex-1">
                        <Link :href="brand.url" class="font-semibold tracking-[-0.01em] hover:text-brand">
                            {{ brand.name }}
                        </Link>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-400">
                            {{ brand.vehicles_count || 0 }} {{ t('vehicles_short') }}
                            ·
                            {{ brand.articles_count || 0 }} {{ t('brands_articles') }}
                        </p>
                    </div>
                    <FollowBrandButton :brand-id="brand.id" />
                </div>
            </div>
            <p v-else class="text-sm text-neutral-500">
                {{ hasQuery ? t('brands_search_empty') : t('brands_empty') }}
            </p>
        </section>
    </AppLayout>
</template>
