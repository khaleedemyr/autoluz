<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { enablePushForNewsletter } from '@/utils/pushActions';
import { swalToast } from '@/utils/swal';

const { t } = useI18n();
const page = usePage();

const form = useForm({
    email: '',
    name: '',
});

function submit() {
    const email = form.email;
    form.post(route('newsletter.store'), {
        preserveScroll: true,
        onSuccess: async () => {
            form.reset();
            try {
                const result = await enablePushForNewsletter(page, email);
                if (result.ok) {
                    swalToast(t('push_newsletter_enabled'));
                } else if (result.reason === 'denied') {
                    swalToast(t('push_denied'));
                }
            } catch {
                // Email subscription already saved.
            }
        },
    });
}
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand">{{ t('newsletter_label') }}</p>
        <h3 class="font-display text-2xl tracking-[-0.03em] text-white">{{ t('newsletter_title') }}</h3>
        <p class="text-sm text-white/55">{{ t('newsletter_desc') }}</p>
        <p class="text-xs text-white/40">{{ t('push_newsletter_hint') }}</p>
        <input
            v-model="form.name"
            type="text"
            :placeholder="t('newsletter_name')"
            class="w-full rounded-xl border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white placeholder:text-white/35 focus:border-brand focus:ring-0"
        />
        <input
            v-model="form.email"
            type="email"
            required
            :placeholder="t('newsletter_email')"
            class="w-full rounded-xl border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white placeholder:text-white/35 focus:border-brand focus:ring-0"
        />
        <button
            type="submit"
            class="rounded-full bg-brand px-5 py-2.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white disabled:opacity-60"
            :disabled="form.processing"
        >
            {{ form.processing ? t('newsletter_sending') : t('newsletter_submit') }}
        </button>
        <p v-if="page.props.flash?.success" class="text-xs text-emerald-300">{{ page.props.flash.success }}</p>
        <p v-if="form.errors.email" class="text-xs text-red-300">{{ form.errors.email }}</p>
    </form>
</template>
