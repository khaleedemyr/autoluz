<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CommunityPostCard from '@/Components/Community/CommunityPostCard.vue';
import { swalWarning } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    profile: { type: Object, required: true },
    posts: { type: Object, required: true },
});

const { t } = useI18n();
const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);

async function requireLogin() {
    await swalWarning(t('community_login_gate'), {
        title: t('community_login'),
        confirmButtonText: t('community_login'),
    });
    const intended = window.location.pathname + window.location.search;
    window.location.href = `${route('login')}?intended=${encodeURIComponent(intended)}`;
}

function toggleFollow() {
    if (!authUser.value) {
        requireLogin();
        return;
    }
    if (!props.profile.username) return;

    router.post(route('community.follow', props.profile.username), {}, {
        preserveScroll: true,
    });
}

function startMessage() {
    if (!authUser.value) {
        requireLogin();
        return;
    }
    if (!props.profile.username) return;
    router.visit(route('community.messages.start', props.profile.username));
}

function startLiveChat() {
    if (!authUser.value) {
        requireLogin();
        return;
    }
    if (!props.profile.username) return;
    router.visit(route('community.live-chat.open', props.profile.username));
}
</script>

<template>
    <AppLayout>
        <Head :title="profile.name || profile.username" />

        <div class="mx-auto max-w-xl px-4 py-8 sm:px-6">
            <div class="flex items-start gap-4 border-b border-[var(--line)] pb-8">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mist text-2xl font-semibold text-charcoal">
                    <img
                        v-if="profile.avatar_url"
                        :src="profile.avatar_url"
                        :alt="profile.name"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ (profile.name || '?').slice(0, 1).toUpperCase() }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="font-display text-3xl tracking-[-0.03em] text-charcoal">{{ profile.name }}</h1>
                    <p v-if="profile.username" class="mt-1 text-sm text-charcoal/50">@{{ profile.username }}</p>
                    <p v-if="profile.bio" class="mt-3 text-sm leading-relaxed text-charcoal/70">{{ profile.bio }}</p>

                    <div class="mt-3 flex flex-wrap gap-4 text-xs font-semibold uppercase tracking-[0.14em] text-charcoal/45">
                        <span>{{ t('community_posts_count', { count: profile.posts_count || 0 }) }}</span>
                        <span>{{ t('community_followers_count', { count: profile.followers_count || 0 }) }}</span>
                        <span>{{ t('community_following_count', { count: profile.following_count || 0 }) }}</span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <Link
                            v-if="profile.is_self"
                            :href="route('community.settings')"
                            class="inline-flex rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                        >
                            {{ t('community_settings') }}
                        </Link>

                        <template v-else>
                            <button
                                type="button"
                                class="inline-flex rounded-full px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition"
                                :class="profile.is_following
                                    ? 'border border-charcoal/15 text-charcoal hover:border-brand hover:text-brand'
                                    : 'bg-brand text-white hover:opacity-90'"
                                @click="toggleFollow"
                            >
                                {{ profile.is_following ? t('community_unfollow') : t('community_follow') }}
                            </button>
                            <button
                                v-if="profile.can_message || profile.username"
                                type="button"
                                class="inline-flex rounded-full border border-charcoal/15 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] transition hover:border-brand hover:text-brand"
                                @click="startMessage"
                            >
                                {{ t('community_message') }}
                            </button>
                            <button
                                v-if="profile.can_live_chat"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 transition hover:bg-emerald-100"
                                @click="startLiveChat"
                            >
                                <span
                                    class="h-2 w-2 rounded-full"
                                    :class="profile.is_online ? 'bg-emerald-500' : 'bg-charcoal/30'"
                                />
                                {{ t('community_live_chat') }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div v-if="posts.data?.length" class="mt-2">
                <CommunityPostCard
                    v-for="post in posts.data"
                    :key="post.id"
                    :post="post"
                />
            </div>
            <p v-else class="py-16 text-center text-sm text-charcoal/50">
                {{ t('community_empty') }}
            </p>

            <div v-if="posts.links?.length > 3" class="mt-8 flex flex-wrap justify-center gap-2">
                <template v-for="(link, i) in posts.links" :key="i">
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
