<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CommunityComposer from '@/Components/Community/CommunityComposer.vue';
import CommunityPostCard from '@/Components/Community/CommunityPostCard.vue';
import CommunityGroupsSidebar from '@/Components/Community/CommunityGroupsSidebar.vue';
import CommunityInfiniteFeed from '@/Components/Community/CommunityInfiniteFeed.vue';
import { useI18n } from '@/composables/useI18n';

defineProps({
    posts: { type: Object, required: true },
    my_groups: { type: Array, default: () => [] },
    discover_groups: { type: Array, default: () => [] },
});

const { t } = useI18n();
const page = usePage();
const isAuth = computed(() => !!page.props.auth?.user);
</script>

<template>
    <AppLayout>
        <Head :title="t('community_title')" />

        <div class="border-b border-[var(--line)] bg-gradient-to-b from-mist/80 to-transparent">
            <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-brand">Autoluz</p>
                <h1 class="font-display mt-2 text-4xl tracking-[-0.03em] text-charcoal sm:text-5xl">
                    {{ t('community_title') }}
                </h1>
                <p class="mt-3 max-w-md text-sm leading-relaxed text-charcoal/60">
                    {{ t('community_desc') }}
                </p>
            </div>
        </div>

        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
            <!-- Mobile groups strip -->
            <div class="mb-5 lg:hidden">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50">
                        {{ t('community_groups') }}
                    </p>
                    <Link
                        :href="route('community.groups.index')"
                        class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand"
                    >
                        {{ t('community_groups_all') }}
                    </Link>
                </div>
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <Link
                        v-if="isAuth"
                        :href="route('community.groups.create')"
                        class="shrink-0 rounded-full border border-dashed border-charcoal/20 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-charcoal/55"
                    >
                        + {{ t('community_group_create') }}
                    </Link>
                    <Link
                        v-for="group in [...my_groups, ...discover_groups].slice(0, 10)"
                        :key="`m-${group.id}`"
                        :href="group.url"
                        class="inline-flex shrink-0 items-center gap-2 rounded-full border border-[var(--line)] bg-white px-3 py-2 text-xs font-semibold text-charcoal"
                    >
                        <span
                            class="flex h-6 w-6 items-center justify-center overflow-hidden rounded-full bg-mist text-[10px]"
                        >
                            <img
                                v-if="group.cover_url"
                                :src="group.cover_url"
                                :alt="group.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ (group.name || '?').slice(0, 1).toUpperCase() }}</span>
                        </span>
                        {{ group.name }}
                    </Link>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)_15rem]">
                <div class="hidden lg:block">
                    <div class="sticky top-24 space-y-4">
                        <CommunityGroupsSidebar
                            :title="t('community_my_groups')"
                            :groups="my_groups"
                            :empty-text="t('community_my_groups_empty')"
                            show-create
                        />
                    </div>
                </div>

                <div class="min-w-0">
                    <CommunityComposer :action="route('community.store')" />

                    <CommunityInfiniteFeed
                        :posts="posts"
                        class="mt-2"
                    >
                        <template #default="{ items }">
                            <CommunityPostCard
                                v-for="post in items"
                                :key="post.id"
                                :post="post"
                            />
                            <p
                                v-if="!items.length"
                                class="py-16 text-center text-sm text-charcoal/50"
                            >
                                {{ t('community_empty') }}
                            </p>
                        </template>
                    </CommunityInfiniteFeed>
                </div>

                <div class="hidden lg:block">
                    <div class="sticky top-24 space-y-4">
                        <CommunityGroupsSidebar
                            :title="t('community_discover_groups')"
                            :groups="discover_groups"
                            :empty-text="t('community_discover_groups_empty')"
                            :show-create="false"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
