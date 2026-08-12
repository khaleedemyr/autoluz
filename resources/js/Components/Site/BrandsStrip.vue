<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import BrandLogo from '@/Components/Site/BrandLogo.vue';
import FollowBrandButton from '@/Components/Site/FollowBrandButton.vue';
import { getFollowedBrandIds } from '@/utils/followedBrands';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    brands: {
        type: Object,
        default: () => ({ cars: [], motos: [] }),
    },
});

const { t } = useI18n();
const followedIds = ref([]);
const tab = ref('cars');

const cars = computed(() => props.brands?.cars || []);
const motos = computed(() => props.brands?.motos || []);
const list = computed(() => (tab.value === 'motos' ? motos.value : cars.value));
const allBrands = computed(() => [...cars.value, ...motos.value]);
const followed = computed(() =>
    allBrands.value.filter((brand) => followedIds.value.includes(Number(brand.id))),
);

function refresh() {
    followedIds.value = getFollowedBrandIds();
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
    <section v-if="cars.length || motos.length" class="container-editorial py-10">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="section-label">{{ t('brands_label') }}</p>
                <h2 class="font-display mt-2 text-3xl tracking-[-0.04em]">{{ t('brands_title') }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-if="followed.length"
                    :href="route('brands.following', { ids: followedIds.join(',') })"
                    class="rounded-full border border-charcoal px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:bg-brand hover:text-white"
                >
                    {{ t('brands_my_feed') }}
                </Link>
                <Link
                    :href="route('brands.index')"
                    class="rounded-full border border-charcoal/20 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                >
                    {{ t('brands_see_all') }}
                </Link>
            </div>
        </div>

        <div class="mb-5 inline-flex rounded-full border border-[var(--line)] bg-white p-1">
            <button
                type="button"
                class="rounded-full px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition"
                :class="tab === 'cars' ? 'bg-brand text-white' : 'text-neutral-500 hover:text-charcoal'"
                @click="tab = 'cars'"
            >
                {{ t('brands_cars') }}
            </button>
            <button
                type="button"
                class="rounded-full px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition"
                :class="tab === 'motos' ? 'bg-brand text-white' : 'text-neutral-500 hover:text-charcoal'"
                @click="tab = 'motos'"
            >
                {{ t('brands_motos') }}
            </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="brand in list.slice(0, 8)"
                :key="brand.id"
                class="flex items-center gap-3 rounded-2xl border border-[var(--line)] bg-white/80 px-3 py-3 shadow-soft"
            >
                <Link :href="brand.url || route('brands.show', brand.slug)" class="shrink-0">
                    <BrandLogo :brand="brand" size="sm" />
                </Link>
                <Link :href="brand.url || route('brands.show', brand.slug)" class="min-w-0 flex-1">
                    <p class="truncate font-semibold tracking-[-0.01em]">{{ brand.name }}</p>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-400">
                        {{ brand.vehicles_count || 0 }} {{ t('vehicles_short') }}
                        ·
                        {{ brand.articles_count || 0 }} {{ t('brands_articles') }}
                    </p>
                </Link>
                <FollowBrandButton :brand-id="brand.id" />
            </div>
        </div>
    </section>
</template>
