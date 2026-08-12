<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { swalConfirm, swalToast } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

defineProps({
    groups: { type: Object, required: true },
});

const { t } = useI18n();
const page = usePage();
const isAuth = computed(() => !!page.props.auth?.user);

function toggleJoin(group) {
    if (!isAuth.value) {
        window.location.href = `${route('login')}?intended=${encodeURIComponent(route('community.groups.index'))}`;
        return;
    }

    const leaving = !!group.is_member;

    (async () => {
        if (leaving) {
            const ok = await swalConfirm(t('community_group_leave_confirm'), {
                title: t('community_group_leave'),
                confirmButtonText: t('community_group_leave'),
                icon: 'warning',
            });
            if (!ok) return;
        }

        router.post(route('community.groups.join', group.slug), {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (leaving) swalToast(t('community_group_left'));
                else swalToast(t('community_group_joined'));
            },
        });
    })();
}
</script>

<template>
    <AppLayout>
        <Head :title="t('community_groups')" />

        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <Link
                        :href="route('community.index')"
                        class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50 hover:text-brand"
                    >
                        ← {{ t('community_title') }}
                    </Link>
                    <h1 class="font-display mt-2 text-3xl tracking-[-0.03em] text-charcoal">
                        {{ t('community_groups') }}
                    </h1>
                    <p class="mt-2 text-sm text-charcoal/60">{{ t('community_groups_desc') }}</p>
                </div>
                <Link
                    v-if="isAuth"
                    :href="route('community.groups.create')"
                    class="rounded-full bg-brand px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-white"
                >
                    + {{ t('community_group_create') }}
                </Link>
            </div>

            <div v-if="groups.data?.length" class="grid gap-4 sm:grid-cols-2">
                <div
                    v-for="group in groups.data"
                    :key="group.id"
                    class="overflow-hidden rounded-2xl border border-[var(--line)] bg-white"
                >
                    <div class="h-28 bg-mist">
                        <img
                            v-if="group.cover_url"
                            :src="group.cover_url"
                            :alt="group.name"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div class="p-4">
                        <Link :href="group.url" class="text-lg font-semibold text-charcoal hover:text-brand">
                            {{ group.name }}
                        </Link>
                        <p v-if="group.description" class="mt-1 line-clamp-2 text-sm text-charcoal/60">
                            {{ group.description }}
                        </p>
                        <p class="mt-2 text-xs text-charcoal/45">
                            {{ t('community_group_members', { count: group.members_count || 0 }) }}
                            · {{ t('community_group_posts', { count: group.posts_count || 0 }) }}
                        </p>
                        <div class="mt-3 flex gap-2">
                            <Link
                                :href="group.url"
                                class="rounded-full border border-charcoal/15 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] hover:border-brand hover:text-brand"
                            >
                                {{ t('community_open') }}
                            </Link>
                            <button
                                type="button"
                                class="rounded-full px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.12em]"
                                :class="group.is_member
                                    ? 'border border-charcoal/15 text-charcoal/60'
                                    : 'bg-brand text-white'"
                                @click="toggleJoin(group)"
                            >
                                {{ group.is_member ? t('community_group_leave') : t('community_group_join') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <p v-else class="py-16 text-center text-sm text-charcoal/50">
                {{ t('community_groups_empty') }}
            </p>

            <div v-if="groups.links?.length > 3" class="mt-8 flex flex-wrap justify-center gap-2">
                <template v-for="(link, i) in groups.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold"
                        :class="link.active ? 'bg-brand text-white' : 'text-charcoal/60 hover:bg-mist'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-full px-3 py-1.5 text-xs text-charcoal/30"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
