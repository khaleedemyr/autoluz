<script setup>
import { Link } from '@inertiajs/vue3';
import SocialLinks from '@/Components/Site/SocialLinks.vue';
import NewsletterForm from '@/Components/Site/NewsletterForm.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    categories: { type: Array, default: () => [] },
});

const { t } = useI18n();

function openSupport() {
    window.dispatchEvent(new CustomEvent('autoluz-support-open'));
}
</script>

<template>
    <footer class="mt-8 border-t border-white/10 bg-charcoal text-white">
        <div class="container-editorial grid gap-10 py-16 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <div class="font-display text-4xl tracking-[-0.04em]">AUTOLUZ</div>
                <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.3em] text-brand">Car &amp; Moto</p>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/55">
                    {{ t('footer_tagline') }}
                </p>
                <div class="mt-6">
                    <SocialLinks />
                </div>
            </div>

            <div>
                <h3 class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand">{{ t('footer_categories') }}</h3>
                <ul class="mt-4 space-y-2.5">
                    <li v-for="cat in categories" :key="cat.id">
                        <Link
                            :href="route('categories.show', cat.slug)"
                            class="text-sm text-white/65 transition hover:text-white"
                        >
                            {{ cat.name }}
                        </Link>
                    </li>
                </ul>
            </div>

            <div>
                <h3 class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand">{{ t('footer_explore') }}</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/65">
                    <li><Link :href="route('home')" class="hover:text-white">{{ t('footer_home') }}</Link></li>
                    <li><Link :href="route('events.index')" class="hover:text-white">{{ t('events_nav') }}</Link></li>
                    <li><Link :href="route('community.index')" class="hover:text-white">{{ t('community_nav') }}</Link></li>
                    <li><Link :href="route('shop.index')" class="hover:text-white">{{ t('shop_nav') }}</Link></li>
                    <li><Link :href="route('brands.index')" class="hover:text-white">{{ t('brands_nav') }}</Link></li>
                    <li><Link :href="route('vehicles.compare')" class="hover:text-white">{{ t('compare_nav') }}</Link></li>
                    <li><Link :href="route('credit.simulate')" class="hover:text-white">{{ t('credit_nav') }}</Link></li>
                    <li><Link :href="route('galleries.index')" class="hover:text-white">{{ t('galleries_nav') }}</Link></li>
                    <li><Link :href="route('search')" class="hover:text-white">{{ t('footer_search') }}</Link></li>
                    <li><Link :href="route('legal.faq')" class="hover:text-white">{{ t('footer_faq') }}</Link></li>
                    <li><Link :href="route('legal.privacy')" class="hover:text-white">{{ t('footer_privacy') }}</Link></li>
                    <li>
                        <button type="button" class="hover:text-white" @click="openSupport">
                            {{ t('support_title') }}
                        </button>
                    </li>
                </ul>
            </div>

            <div>
                <NewsletterForm />
            </div>
        </div>
        <div class="border-t border-white/10 py-5">
            <div class="container-editorial flex flex-col items-center justify-between gap-3 text-xs text-white/55 md:flex-row">
                <p>&copy; {{ new Date().getFullYear() }} Autoluz Car &amp; Moto</p>
                <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                    <Link :href="route('legal.faq')" class="transition hover:text-white">{{ t('footer_faq') }}</Link>
                    <Link :href="route('legal.privacy')" class="transition hover:text-white">{{ t('footer_privacy') }}</Link>
                    <button type="button" class="transition hover:text-white" @click="openSupport">
                        {{ t('support_title') }}
                    </button>
                </div>
            </div>
        </div>
    </footer>
</template>
