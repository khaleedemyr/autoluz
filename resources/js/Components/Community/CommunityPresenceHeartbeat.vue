<script setup>
import { onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
let timer = null;

async function beat() {
    if (!page.props.auth?.user) return;
    try {
        await axios.post(route('community.live-chat.heartbeat'));
    } catch {
        // ignore
    }
}

onMounted(() => {
    if (!page.props.auth?.user) return;
    beat();
    timer = setInterval(beat, 25000);
});

onUnmounted(() => {
    clearInterval(timer);
});
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
