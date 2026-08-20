<script setup>
import logoUrl from '@/brand/logo.png';

defineProps({
    alt: { type: String, default: 'Autoluz' },
});
</script>

<template>
    <img :src="logoUrl" :alt="alt" class="object-contain" />
</template>
