<script setup>
import { onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CommunityComposer from '@/Components/Community/CommunityComposer.vue';
import CommunityPostCard from '@/Components/Community/CommunityPostCard.vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    post: { type: Object, required: true },
    replies: { type: Array, default: () => [] },
});

const { t } = useI18n();
const replyingToId = ref(null);
const replyingTo = ref(null);

function startReply(target) {
    replyingToId.value = target.id;
    replyingTo.value = target;
}

function cancelReply() {
    replyingToId.value = null;
    replyingTo.value = null;
}

onMounted(() => {
    if (window.location.hash) {
        const el = document.querySelector(window.location.hash);
        el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>

<template>
    <AppLayout>
        <Head :title="t('community_thread')" />

        <div class="mx-auto max-w-xl px-4 py-6 sm:px-6">
            <Link
                :href="route('community.index')"
                class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50 transition hover:text-brand"
            >
                ← {{ t('community_back_feed') }}
            </Link>

            <div class="mt-4">
                <CommunityPostCard
                    :post="post"
                    allow-inline-reply
                    :replying-to-id="replyingToId"
                    @reply="startReply"
                    @cancel-reply="cancelReply"
                    @replied="cancelReply"
                />
            </div>

            <div v-if="!replyingToId" class="mt-2 pl-2 sm:pl-4">
                <CommunityComposer
                    :action="route('community.reply', post.id)"
                    :placeholder="t('community_reply_ph')"
                    :submit-label="t('community_reply')"
                    :allow-tagging="false"
                />
            </div>

            <div v-if="replies.length" class="mt-2">
                <CommunityPostCard
                    v-for="reply in replies"
                    :key="reply.id"
                    :post="reply"
                    compact
                    allow-inline-reply
                    :replying-to-id="replyingToId"
                    :depth="1"
                    @reply="startReply"
                    @cancel-reply="cancelReply"
                    @replied="cancelReply"
                />
            </div>
            <p v-else class="py-10 text-center text-sm text-charcoal/45">
                {{ t('community_no_replies') }}
            </p>
        </div>
    </AppLayout>
</template>
