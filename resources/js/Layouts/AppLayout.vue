<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SiteHeader from '@/Components/Site/SiteHeader.vue';
import SiteFooter from '@/Components/Site/SiteFooter.vue';
import CompareFloatingBar from '@/Components/Site/CompareFloatingBar.vue';
import CommunityPresenceHeartbeat from '@/Components/Community/CommunityPresenceHeartbeat.vue';
import CommunityLiveChatBubble from '@/Components/Community/CommunityLiveChatBubble.vue';

const page = usePage();
const footerCategories = computed(() => {
    const nav = page.props.nav || {};
    return [...(nav.primary || []), ...(nav.more || [])].slice(0, 8);
});
const isAuth = computed(() => !!page.props.auth?.user);
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <SiteHeader />
        <!-- Spacer matches fixed header height -->
        <div class="h-[4.25rem] shrink-0 lg:h-[4.75rem]" aria-hidden="true" />
        <main class="min-w-0 flex-1 overflow-x-clip">
            <Transition
                enter-active-class="transition duration-400 ease-editorial"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-editorial"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                mode="out-in"
            >
                <div :key="page.url" class="min-w-0">
                    <slot />
                </div>
            </Transition>
        </main>
        <SiteFooter :categories="footerCategories" />
        <CompareFloatingBar />
        <template v-if="isAuth">
            <CommunityPresenceHeartbeat />
            <CommunityLiveChatBubble />
        </template>
    </div>
</template>
