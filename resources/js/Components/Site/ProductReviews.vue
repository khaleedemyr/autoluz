<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StarRating from '@/Components/Site/StarRating.vue';
import { useI18n } from '@/composables/useI18n';
import { swalError, swalToast } from '@/utils/swal';

const props = defineProps({
    product: { type: Object, required: true },
    reviews: { type: Object, required: true },
});

const { t, formatDate } = useI18n();
const rating = ref(props.reviews.mine?.rating || 0);
const body = ref(props.reviews.mine?.body || '');
const saving = ref(false);

watch(() => props.reviews.mine, (mine) => {
    rating.value = mine?.rating || 0;
    body.value = mine?.body || '';
});

const bars = computed(() => [5, 4, 3, 2, 1].map((star) => {
    const total = Number(props.reviews.count || 0);
    const count = Number(props.reviews.distribution?.[star] || 0);
    return {
        star,
        count,
        pct: total ? Math.round((count / total) * 100) : 0,
    };
}));

const loginHref = computed(() => {
    const intended = `/toko/${props.product.slug}#ulasan`;
    return route('login', { intended });
});

function submit() {
    if (!rating.value) {
        swalError(t('shop_review_need_rating'));
        return;
    }
    saving.value = true;
    router.post(route('shop.reviews.store', props.product.slug), {
        rating: rating.value,
        body: body.value,
    }, {
        preserveScroll: true,
        onSuccess: () => swalToast(t('shop_review_saved')),
        onError: (errors) => swalError(errors.rating || errors.body || t('shop_review_failed')),
        onFinish: () => { saving.value = false; },
    });
}
</script>

<template>
    <section id="ulasan" class="mt-16 scroll-mt-28">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="section-label">{{ t('shop_reviews') }}</p>
                <h2 class="font-display mt-2 text-3xl tracking-[-0.04em]">{{ t('shop_reviews_title') }}</h2>
            </div>
            <div v-if="reviews.count" class="flex items-center gap-3">
                <p class="font-display text-4xl tracking-[-0.04em] text-charcoal">{{ reviews.avg_label }}</p>
                <div>
                    <StarRating :model-value="reviews.avg" />
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-neutral-400">
                        {{ t('shop_reviews_count', { count: reviews.count }) }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="reviews.count" class="mt-6 grid gap-2 sm:max-w-sm">
            <div v-for="bar in bars" :key="bar.star" class="flex items-center gap-2 text-xs">
                <span class="w-8 tabular-nums text-neutral-500">{{ bar.star }}★</span>
                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-mist">
                    <div class="h-full rounded-full bg-brand" :style="{ width: `${bar.pct}%` }" />
                </div>
                <span class="w-6 text-right text-neutral-400">{{ bar.count }}</span>
            </div>
        </div>

        <div class="mt-8 rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft">
            <h3 class="font-semibold">{{ reviews.mine ? t('shop_review_edit') : t('shop_review_write') }}</h3>
            <template v-if="reviews.can_review">
                <p class="mt-1 text-sm text-neutral-500">{{ t('shop_review_verified_hint') }}</p>
                <div class="mt-4">
                    <StarRating v-model="rating" interactive size="lg" />
                </div>
                <textarea
                    v-model="body"
                    rows="4"
                    :placeholder="t('shop_review_placeholder')"
                    class="mt-4 w-full rounded-2xl border-[var(--line)] text-sm"
                    maxlength="2000"
                />
                <button
                    type="button"
                    class="mt-4 rounded-full bg-brand px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.16em] text-white disabled:opacity-50"
                    :disabled="saving"
                    @click="submit"
                >
                    {{ t('shop_review_submit') }}
                </button>
            </template>
            <p v-else-if="reviews.login_required" class="mt-2 text-sm text-neutral-600">
                {{ t('shop_review_login') }}
                <Link :href="loginHref" class="font-semibold text-brand">{{ t('community_login') }}</Link>
            </p>
            <p v-else class="mt-2 text-sm text-neutral-600">{{ t('shop_review_buyers_only') }}</p>
        </div>

        <div v-if="reviews.items?.length" class="mt-8 space-y-4">
            <article
                v-for="item in reviews.items"
                :key="item.id"
                class="rounded-2xl border border-[var(--line)] bg-white p-5 shadow-soft"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mist text-sm font-semibold">
                        <img v-if="item.user?.avatar_url" :src="item.user.avatar_url" alt="" class="h-full w-full object-cover" />
                        <span v-else>{{ (item.user?.name || '?').slice(0, 1) }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold">{{ item.user?.name || t('shop_review_buyer') }}</p>
                            <span class="rounded-full bg-mist px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-500">
                                {{ t('shop_review_verified') }}
                            </span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <StarRating :model-value="item.rating" size="sm" />
                            <p class="text-xs text-neutral-400">{{ formatDate(item.created_at) }}</p>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-neutral-700">{{ item.body }}</p>
                    </div>
                </div>
            </article>
        </div>
        <p v-else class="mt-8 text-sm text-neutral-500">{{ t('shop_reviews_empty') }}</p>
    </section>
</template>
