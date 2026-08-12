<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { swalToast } from '@/utils/swal';
import { useI18n } from '@/composables/useI18n';

const props = defineProps({
    group: { type: Object, required: true },
    members: { type: Array, default: () => [] },
});

const emit = defineEmits(['added']);

const { t } = useI18n();
const query = ref('');
const results = ref([]);
const searching = ref(false);
const addingId = ref(null);
const open = ref(false);
const localMembers = ref([...(props.members || [])]);
let debounceTimer = null;

const canAdd = computed(() => !!props.group.can_add_member);

watch(
    () => props.members,
    (v) => {
        localMembers.value = [...(v || [])];
    },
);

watch(query, (value) => {
    clearTimeout(debounceTimer);
    const q = value.trim();
    if (q.length < 2) {
        results.value = [];
        searching.value = false;
        return;
    }

    searching.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get(route('community.groups.search-users', props.group.slug), {
                params: { q },
            });
            results.value = data.users || [];
        } catch {
            results.value = [];
            swalToast(t('community_group_search_failed'), { icon: 'error' });
        } finally {
            searching.value = false;
        }
    }, 300);
});

async function addUser(user) {
    if (!user?.id || addingId.value) return;
    addingId.value = user.id;

    try {
        const { data } = await axios.post(
            route('community.groups.add-member', props.group.slug),
            { user_id: user.id },
            { headers: { Accept: 'application/json' } },
        );

        if (data.added && data.user) {
            localMembers.value = [data.user, ...localMembers.value.filter((m) => m.id !== data.user.id)];
            results.value = results.value.filter((u) => u.id !== user.id);
            emit('added', data);
            swalToast(t('community_group_member_added'));
        } else {
            swalToast(t('community_group_member_exists'), { icon: 'info' });
        }
    } catch {
        swalToast(t('community_group_member_add_failed'), { icon: 'error' });
    } finally {
        addingId.value = null;
    }
}

onUnmounted(() => clearTimeout(debounceTimer));
</script>

<template>
    <div class="mb-5 rounded-2xl border border-[var(--line)] bg-white/80 p-4">
        <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-charcoal/50">
                {{ t('community_group_members_title') }}
            </p>
            <button
                v-if="canAdd"
                type="button"
                class="rounded-full border border-charcoal/15 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-charcoal/60 transition hover:border-brand hover:text-brand"
                @click="open = !open"
            >
                {{ open ? t('community_cancel') : `+ ${t('community_group_add_member')}` }}
            </button>
        </div>

        <div v-if="canAdd && open" class="mt-3">
            <input
                v-model="query"
                type="search"
                autocomplete="off"
                :placeholder="t('community_group_search_member_ph')"
                class="w-full rounded-2xl border border-[var(--line)] bg-white px-4 py-2.5 text-sm focus:border-brand focus:ring-0"
            />
            <p class="mt-1.5 text-[11px] text-charcoal/40">
                {{ t('community_group_search_member_hint') }}
            </p>

            <div v-if="searching" class="mt-3 text-sm text-charcoal/45">
                {{ t('community_group_searching') }}
            </div>
            <div v-else-if="query.trim().length >= 2" class="mt-2 max-h-56 overflow-y-auto rounded-xl border border-[var(--line)]">
                <button
                    v-for="user in results"
                    :key="user.id"
                    type="button"
                    class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition hover:bg-mist/70 disabled:opacity-50"
                    :disabled="addingId === user.id"
                    @click="addUser(user)"
                >
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-mist text-xs font-semibold">
                        <img
                            v-if="user.avatar_url"
                            :src="user.avatar_url"
                            :alt="user.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ (user.name || '?').slice(0, 1).toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-charcoal">{{ user.name }}</p>
                        <p class="truncate text-xs text-charcoal/45">
                            <span v-if="user.username">@{{ user.username }} · </span>{{ user.email }}
                        </p>
                    </div>
                    <span class="shrink-0 text-[10px] font-semibold uppercase tracking-[0.12em] text-brand">
                        {{ addingId === user.id ? '...' : t('community_group_add') }}
                    </span>
                </button>
                <p v-if="!results.length" class="px-3 py-6 text-center text-sm text-charcoal/45">
                    {{ t('community_group_search_empty') }}
                </p>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <component
                :is="member.url ? Link : 'div'"
                v-for="member in localMembers"
                :key="member.id"
                v-bind="member.url ? { href: member.url } : {}"
                class="inline-flex items-center gap-2 rounded-full border border-[var(--line)] bg-white px-2.5 py-1.5"
            >
                <span class="flex h-6 w-6 items-center justify-center overflow-hidden rounded-full bg-mist text-[10px] font-semibold">
                    <img
                        v-if="member.avatar_url"
                        :src="member.avatar_url"
                        :alt="member.name"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ (member.name || '?').slice(0, 1).toUpperCase() }}</span>
                </span>
                <span class="max-w-[7rem] truncate text-xs font-semibold text-charcoal">{{ member.name }}</span>
                <span
                    v-if="member.role === 'owner' || member.role === 'admin'"
                    class="text-[9px] font-semibold uppercase tracking-[0.1em] text-brand"
                >
                    {{ member.role }}
                </span>
            </component>
            <p v-if="!localMembers.length" class="w-full py-2 text-center text-sm text-charcoal/45">
                {{ t('community_group_no_members') }}
            </p>
        </div>
    </div>
</template>
