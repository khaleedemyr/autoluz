<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    title: { type: String, required: true },
    groups: { type: Array, default: () => [] },
    emptyText: { type: String, default: '' },
    showCreate: { type: Boolean, default: false },
    showBrowse: { type: Boolean, default: true },
});

const { t } = useI18n();
const page = usePage();
const isAuth = computed(() => !!page.props.auth?.user);

function toggleJoin(group) {
    if (!isAuth.value) {
        window.location.href = `${route('login')}?intended=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    router.post(route('community.groups.join', group.slug), {}, { preserveScroll: true });
}
</script>

<template>
    <aside class="rounded-2xl border border-[var(--line)] bg-white/80 p-4">
        <div class="mb-3 flex items-center justify-between gap-2">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50">
                {{ title }}
            </p>
            <Link
                v-if="showBrowse"
                :href="route('community.groups.index')"
                class="text-[10px] font-semibold uppercase tracking-[0.12em] text-brand hover:opacity-80"
            >
                {{ t('community_groups_all') }}
            </Link>
        </div>

        <div v-if="groups.length" class="space-y-1">
            <div
                v-for="group in groups"
                :key="group.id"
                class="flex items-start gap-3 rounded-xl px-2 py-2 transition hover:bg-mist/70"
            >
                <Link :href="group.url" class="flex min-w-0 flex-1 items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-mist text-xs font-semibold text-charcoal">
                        <img
                            v-if="group.cover_url"
                            :src="group.cover_url"
                            :alt="group.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ (group.name || '?').slice(0, 1).toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-charcoal">{{ group.name }}</p>
                        <p class="text-[11px] text-charcoal/45">
                            {{ t('community_group_members', { count: group.members_count || 0 }) }}
                        </p>
                    </div>
                </Link>
                <button
                    v-if="!group.is_member"
                    type="button"
                    class="shrink-0 rounded-full border border-charcoal/15 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.1em] text-charcoal/60 hover:border-brand hover:text-brand"
                    @click="toggleJoin(group)"
                >
                    {{ t('community_group_join') }}
                </button>
            </div>
        </div>
        <p v-else class="py-4 text-center text-sm text-charcoal/45">
            {{ emptyText || t('community_groups_empty') }}
        </p>

        <Link
            v-if="showCreate && isAuth"
            :href="route('community.groups.create')"
            class="mt-3 flex w-full items-center justify-center rounded-full border border-dashed border-charcoal/20 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/55 transition hover:border-brand hover:text-brand"
        >
            + {{ t('community_group_create') }}
        </Link>
    </aside>
</template>
