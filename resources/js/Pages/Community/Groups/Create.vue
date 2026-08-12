<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { swalToast } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
const page = usePage();
const isAuth = computed(() => !!page.props.auth?.user);
const preview = ref(null);

const form = useForm({
    name: '',
    description: '',
    cover: null,
});

function onCover(e) {
    const file = e.target.files?.[0] || null;
    form.cover = file;
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
        preview.value = null;
    }
    if (file) {
        preview.value = URL.createObjectURL(file);
    }
}

function submit() {
    if (!isAuth.value) {
        window.location.href = route('login');
        return;
    }

    form.post(route('community.groups.store'), {
        forceFormData: true,
        onError: () => {
            swalToast(form.errors.name || form.errors.cover || t('community_post_failed'), { icon: 'error' });
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('community_group_create')" />

        <div class="mx-auto max-w-xl px-4 py-8 sm:px-6">
            <Link
                :href="route('community.groups.index')"
                class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50 hover:text-brand"
            >
                ← {{ t('community_groups') }}
            </Link>

            <h1 class="font-display mt-4 text-3xl tracking-[-0.03em] text-charcoal">
                {{ t('community_group_create') }}
            </h1>
            <p class="mt-2 text-sm text-charcoal/60">{{ t('community_group_create_desc') }}</p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50">
                        {{ t('community_group_name') }}
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        maxlength="80"
                        required
                        class="w-full rounded-2xl border border-[var(--line)] bg-white px-4 py-3 text-sm focus:border-brand focus:ring-0"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50">
                        {{ t('community_group_desc') }}
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        maxlength="300"
                        class="w-full resize-none rounded-2xl border border-[var(--line)] bg-white px-4 py-3 text-sm focus:border-brand focus:ring-0"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50">
                        {{ t('community_group_cover') }}
                    </label>
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm text-charcoal/60"
                        @change="onCover"
                    />
                    <img
                        v-if="preview"
                        :src="preview"
                        alt=""
                        class="mt-3 max-h-40 w-full rounded-2xl object-cover"
                    />
                </div>

                <button
                    type="submit"
                    class="rounded-full bg-brand px-6 py-2.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-40"
                    :disabled="form.processing || !form.name.trim()"
                >
                    {{ t('community_group_create') }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>
