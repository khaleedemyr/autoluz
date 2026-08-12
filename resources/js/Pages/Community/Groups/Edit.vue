<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { swalToast } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    group: { type: Object, required: true },
});

const { t } = useI18n();
const preview = ref(null);
const removedCover = ref(false);

const form = useForm({
    name: props.group.name || '',
    description: props.group.description || '',
    cover: null,
    remove_cover: false,
});

const displayCover = computed(() => {
    if (preview.value) return preview.value;
    if (removedCover.value) return null;
    return props.group.cover_url || null;
});

function onCover(e) {
    const file = e.target.files?.[0] || null;
    form.cover = file;
    form.remove_cover = false;
    removedCover.value = false;
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
        preview.value = null;
    }
    if (file) {
        preview.value = URL.createObjectURL(file);
    }
}

function clearCover() {
    form.cover = null;
    form.remove_cover = true;
    removedCover.value = true;
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
        preview.value = null;
    }
}

function submit() {
    form.post(route('community.groups.update', props.group.slug), {
        forceFormData: true,
        onSuccess: () => swalToast(t('community_group_settings_saved')),
        onError: () => {
            swalToast(form.errors.name || form.errors.cover || t('community_post_failed'), { icon: 'error' });
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('community_group_settings')" />

        <div class="mx-auto max-w-xl px-4 py-8 sm:px-6">
            <Link
                :href="route('community.groups.show', group.slug)"
                class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50 hover:text-brand"
            >
                ← {{ group.name }}
            </Link>

            <h1 class="font-display mt-4 text-3xl tracking-[-0.03em] text-charcoal">
                {{ t('community_group_settings') }}
            </h1>
            <p class="mt-2 text-sm text-charcoal/60">{{ t('community_group_settings_desc') }}</p>

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
                        {{ t('community_group_banner') }}
                    </label>
                    <div
                        class="overflow-hidden rounded-2xl border border-[var(--line)] bg-mist"
                        style="aspect-ratio: 16 / 6"
                    >
                        <img
                            v-if="displayCover"
                            :src="displayCover"
                            :alt="form.name || group.name"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full items-center justify-center text-sm text-charcoal/40"
                        >
                            {{ t('community_group_no_banner') }}
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand">
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                @change="onCover"
                            />
                            {{ t('community_group_change_banner') }}
                        </label>
                        <button
                            v-if="displayCover"
                            type="button"
                            class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50 hover:text-brand"
                            @click="clearCover"
                        >
                            {{ t('community_group_remove_banner') }}
                        </button>
                    </div>
                    <p v-if="form.errors.cover" class="mt-1 text-xs text-red-600">{{ form.errors.cover }}</p>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button
                        type="submit"
                        class="rounded-full bg-brand px-6 py-2.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-40"
                        :disabled="form.processing || !form.name.trim()"
                    >
                        {{ t('community_group_save') }}
                    </button>
                    <Link
                        :href="route('community.groups.show', group.slug)"
                        class="rounded-full border border-charcoal/15 px-6 py-2.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/60"
                    >
                        {{ t('community_cancel') }}
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
