<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import CommunityLikeButton from '@/Components/Community/CommunityLikeButton.vue';
import CommunityComposer from '@/Components/Community/CommunityComposer.vue';
import { swalConfirm, swalToast, swalWarning } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    post: { type: Object, required: true },
    compact: { type: Boolean, default: false },
    allowInlineReply: { type: Boolean, default: false },
    replyingToId: { type: [Number, String], default: null },
    depth: { type: Number, default: 0 },
});

const emit = defineEmits(['reply', 'cancel-reply', 'replied']);

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const isReplying = computed(() => props.allowInlineReply && Number(props.replyingToId) === Number(props.post.id));
const maxDepth = 3;

async function goReply() {
    if (!user.value) {
        await swalWarning(t('community_login_gate'), {
            title: t('community_login'),
            confirmButtonText: t('community_login'),
        });
        const intended = window.location.pathname + window.location.search;
        window.location.href = `${route('login')}?intended=${encodeURIComponent(intended)}`;
        return;
    }

    if (props.allowInlineReply) {
        emit('reply', props.post);
        return;
    }

    router.visit(route('community.show', props.post.root_id || props.post.id));
}

async function destroyPost() {
    const ok = await swalConfirm(t('community_delete_confirm'), {
        title: t('community_delete'),
        confirmButtonText: t('community_delete'),
        icon: 'warning',
    });
    if (!ok) return;

    router.delete(route('community.destroy', props.post.id), {
        preserveScroll: true,
        onSuccess: () => swalToast(t('community_deleted')),
    });
}

function onChildReply(post) {
    emit('reply', post);
}

function onChildCancel() {
    emit('cancel-reply');
}

function onChildReplied() {
    emit('replied');
}
</script>

<template>
    <article :id="`post-${post.id}`" class="border-b border-[var(--line)] py-5">
        <div class="flex gap-3">
            <component
                :is="post.user?.url ? Link : 'div'"
                v-bind="post.user?.url ? { href: post.user.url } : {}"
                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mist text-sm font-semibold text-charcoal"
            >
                <img
                    v-if="post.user?.avatar_url"
                    :src="post.user.avatar_url"
                    :alt="post.user?.name || ''"
                    class="h-full w-full object-cover"
                />
                <span v-else>{{ (post.user?.name || '?').slice(0, 1).toUpperCase() }}</span>
            </component>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                    <component
                        :is="post.user?.url ? Link : 'span'"
                        v-bind="post.user?.url ? { href: post.user.url } : {}"
                        class="text-sm font-semibold text-charcoal"
                        :class="post.user?.url ? 'hover:text-brand' : ''"
                    >
                        {{ post.user?.name || t('community_unknown_user') }}
                    </component>
                    <span v-if="post.user?.username" class="text-xs text-charcoal/45">@{{ post.user.username }}</span>
                    <span class="text-xs text-charcoal/40">· {{ post.created_at_label }}</span>
                    <Link
                        v-if="post.group?.url"
                        :href="post.group.url"
                        class="text-xs font-semibold text-brand hover:opacity-80"
                    >
                        · {{ post.group.name }}
                    </Link>
                </div>

                <p
                    v-if="post.parent_user && depth > 0"
                    class="mt-1 text-xs text-charcoal/45"
                >
                    {{ t('community_replying_to', { name: post.parent_user.username ? `@${post.parent_user.username}` : post.parent_user.name }) }}
                </p>

                <div class="mt-2 whitespace-pre-wrap break-words text-[15px] leading-relaxed text-charcoal">
                    {{ post.body }}
                </div>

                <div v-if="post.article || post.event || post.vehicle" class="mt-3 space-y-2">
                    <a
                        v-if="post.article"
                        :href="post.article.url"
                        class="flex items-center gap-3 rounded-2xl border border-[var(--line)] bg-white/70 p-2.5 transition hover:border-brand/40 hover:bg-white"
                    >
                        <img
                            v-if="post.article.featured_image_url"
                            :src="post.article.featured_image_url"
                            alt=""
                            class="h-14 w-14 shrink-0 rounded-xl object-cover"
                            loading="lazy"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand">
                                {{ t('community_tagged_article') }}
                            </p>
                            <p class="line-clamp-2 text-sm font-medium text-charcoal">{{ post.article.title }}</p>
                        </div>
                    </a>
                    <a
                        v-if="post.event"
                        :href="post.event.url"
                        class="flex items-center gap-3 rounded-2xl border border-[var(--line)] bg-white/70 p-2.5 transition hover:border-brand/40 hover:bg-white"
                    >
                        <img
                            v-if="post.event.cover_image_url"
                            :src="post.event.cover_image_url"
                            alt=""
                            class="h-14 w-14 shrink-0 rounded-xl object-cover"
                            loading="lazy"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand">
                                {{ t('community_tagged_event') }}
                            </p>
                            <p class="line-clamp-2 text-sm font-medium text-charcoal">{{ post.event.title }}</p>
                            <p v-if="post.event.starts_at_label" class="mt-0.5 text-xs text-charcoal/45">
                                {{ post.event.starts_at_label }}
                            </p>
                        </div>
                    </a>
                    <a
                        v-if="post.vehicle?.url"
                        :href="post.vehicle.url"
                        class="flex items-center gap-3 rounded-2xl border border-[var(--line)] bg-white/70 p-2.5 transition hover:border-brand/40 hover:bg-white"
                    >
                        <img
                            v-if="post.vehicle.cover_image_url"
                            :src="post.vehicle.cover_image_url"
                            alt=""
                            class="h-14 w-14 shrink-0 rounded-xl object-cover"
                            loading="lazy"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand">
                                {{ t('community_tagged_vehicle') }}
                            </p>
                            <p class="line-clamp-2 text-sm font-medium text-charcoal">{{ post.vehicle.name }}</p>
                            <p
                                v-if="post.vehicle.brand_name || post.vehicle.price_label"
                                class="mt-0.5 truncate text-xs text-charcoal/45"
                            >
                                {{
                                    [post.vehicle.brand_name, post.vehicle.price_label]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </p>
                        </div>
                    </a>
                </div>

                <Link
                    v-if="post.image_url"
                    :href="post.url"
                    class="mt-3 block overflow-hidden rounded-2xl border border-[var(--line)]"
                >
                    <img :src="post.image_url" alt="" class="max-h-96 w-full object-cover" loading="lazy" />
                </Link>

                <div class="mt-3 flex items-center gap-5">
                    <CommunityLikeButton :post="post" />

                    <button
                        v-if="depth < maxDepth"
                        type="button"
                        class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-charcoal/55 transition hover:text-brand"
                        @click="goReply"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span>{{ t('community_reply') }}</span>
                        <span v-if="!compact && post.replies_count">· {{ post.replies_count }}</span>
                    </button>

                    <button
                        v-if="post.can_delete"
                        type="button"
                        class="ml-auto text-[11px] font-semibold uppercase tracking-[0.12em] text-charcoal/40 transition hover:text-red-600"
                        @click="destroyPost"
                    >
                        {{ t('community_delete') }}
                    </button>
                </div>

                <div v-if="isReplying" class="mt-4">
                    <CommunityComposer
                        :action="route('community.reply', post.id)"
                        :placeholder="t('community_reply_ph')"
                        :submit-label="t('community_reply')"
                        :reply-to-name="post.user?.username ? `@${post.user.username}` : (post.user?.name || '')"
                        autofocus
                        @success="emit('replied')"
                        @cancel="emit('cancel-reply')"
                    />
                </div>

                <div
                    v-if="post.replies?.length"
                    class="mt-2 border-l-2 border-[var(--line)] pl-4 sm:pl-5"
                >
                    <CommunityPostCard
                        v-for="child in post.replies"
                        :key="child.id"
                        :post="child"
                        compact
                        :allow-inline-reply="allowInlineReply"
                        :replying-to-id="replyingToId"
                        :depth="depth + 1"
                        @reply="onChildReply"
                        @cancel-reply="onChildCancel"
                        @replied="onChildReplied"
                    />
                </div>
            </div>
        </div>
    </article>
</template>
