<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-lg overflow-hidden bg-zinc-950">
        <img src="/logo-gym.png" class="size-7 object-contain" alt="Eagle Fitness Logo" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-bold leading-none text-gray-900 dark:text-zinc-100">GYM AGUILAS</span>
        <span class="text-[10px] text-gray-500 dark:text-zinc-500 font-bold uppercase tracking-wider">Membresías</span>
    </div>
</template>
