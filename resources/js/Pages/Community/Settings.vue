<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: null },
});

const { t } = useI18n();
const user = usePage().props.auth.user;
const preview = ref(user.avatar_url || null);

const form = useForm({
    name: user.name || '',
    username: user.username || '',
    bio: user.bio || '',
    email: user.email || '',
    avatar: null,
    from_community: true,
    _method: 'patch',
});

function onAvatarChange(e) {
    const file = e.target.files?.[0] || null;
    form.avatar = file;
    if (file) {
        preview.value = URL.createObjectURL(file);
    }
}

function submit() {
    form.post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('community_settings')" />

        <div class="mx-auto max-w-xl px-4 py-8 sm:px-6">
            <div class="mb-6">
                <Link
                    v-if="user.username"
                    :href="route('community.profile', user.username)"
                    class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50 hover:text-brand"
                >
                    ← {{ t('community_profile') }}
                </Link>
                <h1 class="font-display mt-3 text-3xl tracking-[-0.03em] text-charcoal">
                    {{ t('community_settings') }}
                </h1>
                <p class="mt-2 text-sm text-charcoal/60">{{ t('community_settings_desc') }}</p>
            </div>

            <div
                v-if="$page.props.flash?.success"
                class="mb-4 rounded-2xl border border-brand/20 bg-brand/5 px-4 py-3 text-sm text-brand"
            >
                {{ $page.props.flash.success }}
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="flex items-center gap-4">
                    <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-mist text-2xl font-semibold">
                        <img v-if="preview" :src="preview" alt="" class="h-full w-full object-cover" />
                        <span v-else>{{ (form.name || '?').slice(0, 1).toUpperCase() }}</span>
                    </div>
                    <label class="cursor-pointer text-[11px] font-semibold uppercase tracking-[0.14em] text-brand">
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onAvatarChange" />
                        {{ t('community_change_avatar') }}
                    </label>
                </div>
                <p v-if="form.errors.avatar" class="text-xs text-red-600">{{ form.errors.avatar }}</p>

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50" for="name">Name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="mt-2 w-full rounded-2xl border border-[var(--line)] bg-white px-4 py-3 text-sm focus:border-brand focus:ring-0"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50" for="username">{{ t('community_username') }}</label>
                    <input
                        id="username"
                        v-model="form.username"
                        type="text"
                        required
                        pattern="[a-z0-9_]{3,30}"
                        class="mt-2 w-full rounded-2xl border border-[var(--line)] bg-white px-4 py-3 text-sm lowercase focus:border-brand focus:ring-0"
                    />
                    <p v-if="form.errors.username" class="mt-1 text-xs text-red-600">{{ form.errors.username }}</p>
                </div>

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50" for="bio">{{ t('community_bio') }}</label>
                    <textarea
                        id="bio"
                        v-model="form.bio"
                        rows="3"
                        maxlength="280"
                        class="mt-2 w-full rounded-2xl border border-[var(--line)] bg-white px-4 py-3 text-sm focus:border-brand focus:ring-0"
                    />
                    <p v-if="form.errors.bio" class="mt-1 text-xs text-red-600">{{ form.errors.bio }}</p>
                </div>

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50" for="email">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="mt-2 w-full rounded-2xl border border-[var(--line)] bg-white px-4 py-3 text-sm focus:border-brand focus:ring-0"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>

                <button
                    type="submit"
                    class="rounded-full bg-brand px-6 py-2.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-40"
                    :disabled="form.processing"
                >
                    {{ t('community_save_profile') }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>
