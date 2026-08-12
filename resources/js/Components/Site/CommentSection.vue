<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    articleSlug: { type: String, required: true },
    comments: { type: Array, default: () => [] },
});

const { t, formatDate, formatNumber } = useI18n();

const form = useForm({
    name: '',
    email: '',
    body: '',
    website: '', // honeypot
});

function submit() {
    form.post(route('articles.comments.store', props.articleSlug), {
        preserveScroll: true,
        onSuccess: () => form.reset('body', 'website'),
    });
}
</script>

<template>
    <section class="rounded-2xl border border-[var(--line)] bg-white/80 p-5 shadow-soft sm:p-7">
        <div class="mb-6">
            <p class="section-label">{{ t('comments_label') }}</p>
            <h2 class="font-display mt-2 text-3xl tracking-[-0.04em]">{{ t('comments_title') }}</h2>
            <p class="mt-1 text-sm text-neutral-500">{{ t('comments_count', { count: formatNumber(comments.length) }) }}</p>
        </div>

        <form class="mb-8 grid gap-3" @submit.prevent="submit">
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ t('comment_name') }}</label>
                    <input v-model="form.name" type="text" class="w-full rounded-xl border-black/10" required maxlength="120" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ t('comment_email') }}</label>
                    <input v-model="form.email" type="email" class="w-full rounded-xl border-black/10" maxlength="160" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>
            </div>

            <!-- Honeypot -->
            <div class="hidden" aria-hidden="true">
                <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ t('comment_body') }}</label>
                <textarea
                    v-model="form.body"
                    rows="4"
                    class="w-full rounded-xl border-black/10"
                    required
                    maxlength="2000"
                    :placeholder="t('comment_placeholder')"
                />
                <p v-if="form.errors.body" class="mt-1 text-xs text-red-600">{{ form.errors.body }}</p>
            </div>

            <div>
                <button
                    type="submit"
                    class="rounded-full bg-brand px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? t('comment_sending') : t('comment_submit') }}
                </button>
            </div>
        </form>

        <div v-if="comments.length" class="space-y-4">
            <article
                v-for="comment in comments"
                :key="comment.id"
                class="rounded-2xl border border-black/5 bg-mist/40 px-4 py-4"
            >
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="font-semibold tracking-[-0.01em]">{{ comment.name }}</h3>
                    <time class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">
                        {{ formatDate(comment.created_at, { hour: '2-digit', minute: '2-digit' }) }}
                    </time>
                </div>
                <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-neutral-700">{{ comment.body }}</p>
            </article>
        </div>
        <p v-else class="text-sm text-neutral-500">{{ t('comments_empty') }}</p>
    </section>
</template>
