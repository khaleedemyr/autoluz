<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CommunityComposer from '@/Components/Community/CommunityComposer.vue';
import CommunityPostCard from '@/Components/Community/CommunityPostCard.vue';
import CommunityInfiniteFeed from '@/Components/Community/CommunityInfiniteFeed.vue';
import CommunityGroupMembers from '@/Components/Community/CommunityGroupMembers.vue';
import { swalConfirm, swalToast } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    group: { type: Object, required: true },
    members: { type: Array, default: () => [] },
    posts: { type: Object, required: true },
});

const { t } = useI18n();
const page = usePage();
const isAuth = computed(() => !!page.props.auth?.user);
const membersCount = ref(Number(props.group.members_count || 0));

function onMemberAdded(payload) {
    if (payload?.members_count != null) {
        membersCount.value = Number(payload.members_count);
    } else {
        membersCount.value += 1;
    }
}

function toggleJoin() {
    if (!isAuth.value) {
        window.location.href = `${route('login')}?intended=${encodeURIComponent(props.group.url)}`;
        return;
    }

    const leaving = !!props.group.is_member;
    const soleOwner = leaving && props.group.is_owner && membersCount.value <= 1;

    (async () => {
        if (soleOwner) {
            const ok = await swalConfirm(t('community_group_leave_owner_last'), {
                title: t('community_group_leave'),
                confirmButtonText: t('community_group_leave'),
                icon: 'warning',
            });
            if (!ok) return;
        } else if (leaving && props.group.is_owner) {
            const ok = await swalConfirm(t('community_group_leave_owner'), {
                title: t('community_group_leave'),
                confirmButtonText: t('community_group_leave'),
                icon: 'warning',
            });
            if (!ok) return;
        }

        router.post(route('community.groups.join', props.group.slug), {}, {
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
        <Head :title="group.name" />

        <div class="border-b border-[var(--line)]">
            <div class="h-36 bg-mist sm:h-48">
                <img
                    v-if="group.cover_url"
                    :src="group.cover_url"
                    :alt="group.name"
                    class="h-full w-full object-cover"
                />
            </div>
            <div class="mx-auto max-w-xl px-4 py-6 sm:px-6">
                <Link
                    :href="route('community.index')"
                    class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50 hover:text-brand"
                >
                    ← {{ t('community_title') }}
                </Link>
                <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand">
                            {{ t('community_groups') }}
                        </p>
                        <h1 class="font-display mt-1 text-3xl tracking-[-0.03em] text-charcoal">
                            {{ group.name }}
                        </h1>
                        <p v-if="group.description" class="mt-2 text-sm leading-relaxed text-charcoal/65">
                            {{ group.description }}
                        </p>
                        <p class="mt-3 text-xs text-charcoal/45">
                            {{ t('community_group_members', { count: membersCount }) }}
                            · {{ t('community_group_posts', { count: group.posts_count || 0 }) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Link
                            v-if="group.can_edit"
                            :href="route('community.groups.edit', group.slug)"
                            class="rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                        >
                            {{ t('community_group_settings') }}
                        </Link>
                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition"
                            :class="group.is_member
                                ? 'border border-charcoal/15 text-charcoal hover:border-brand hover:text-brand'
                                : 'bg-brand text-white hover:opacity-90'"
                            @click="toggleJoin"
                        >
                            {{ group.is_member ? t('community_group_leave') : t('community_group_join') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-xl px-4 py-6 sm:px-6">
            <CommunityGroupMembers
                :group="group"
                :members="members"
                @added="onMemberAdded"
            />

            <CommunityComposer
                v-if="group.can_post"
                :action="route('community.store')"
                :group-id="group.id"
                :placeholder="t('community_group_compose_ph')"
            />
            <p
                v-else
                class="mb-4 rounded-2xl border border-[var(--line)] bg-white/70 px-4 py-4 text-center text-sm text-charcoal/55"
            >
                {{ t('community_group_join_to_post') }}
            </p>

            <CommunityInfiniteFeed :posts="posts">
                <template #default="{ items }">
                    <CommunityPostCard
                        v-for="post in items"
                        :key="post.id"
                        :post="post"
                    />
                    <p v-if="!items.length" class="py-16 text-center text-sm text-charcoal/50">
                        {{ t('community_group_empty') }}
                    </p>
                </template>
            </CommunityInfiniteFeed>
        </div>
    </AppLayout>
</template>
