<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';

const props = defineProps({
    comments: { type: Object, required: true },
    filters: {
        type: Object,
        default: () => ({ q: '', visibility: '', per_page: 15 }),
    },
    stats: {
        type: Object,
        default: () => ({ all: 0, visible: 0, hidden: 0 }),
    },
});

const q = ref(props.filters.q || '');
const visibility = ref(props.filters.visibility || '');
const perPage = ref(Number(props.filters.per_page || 15));

watch([q, visibility, perPage], () => {
    router.get(
        route('admin.comments.index'),
        {
            q: q.value || undefined,
            visibility: visibility.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, replace: true },
    );
});

function toggle(id) {
    router.patch(route('admin.comments.toggle', id), {}, { preserveScroll: true });
}

async function destroy(id) {
    const ok = await swalConfirm('Hapus komentar ini permanen?', {
        title: 'Hapus Komentar',
        confirmButtonText: 'Hapus',
    });
    if (!ok) return;
    router.delete(route('admin.comments.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <AdminLayout title="Komentar">
        <Head title="Admin Komentar" />

        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-black/5 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">Semua</p>
                <p class="mt-1 font-display text-3xl tracking-[-0.04em]">{{ stats.all }}</p>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">Visible</p>
                <p class="mt-1 font-display text-3xl tracking-[-0.04em] text-emerald-600">{{ stats.visible }}</p>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-400">Hidden</p>
                <p class="mt-1 font-display text-3xl tracking-[-0.04em] text-amber-600">{{ stats.hidden }}</p>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <input
                v-model="q"
                type="search"
                placeholder="Cari nama/isi/artikel..."
                class="rounded-xl border-black/10 text-sm"
            />
            <select v-model="visibility" class="rounded-xl border-black/10 text-sm">
                <option value="">Semua status</option>
                <option value="visible">Visible</option>
                <option value="hidden">Hidden</option>
            </select>
            <select v-model.number="perPage" class="rounded-xl border-black/10 text-sm">
                <option :value="10">10 / page</option>
                <option :value="15">15 / page</option>
                <option :value="25">25 / page</option>
                <option :value="50">50 / page</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <div class="space-y-0 divide-y divide-black/5">
                <div
                    v-for="comment in comments.data"
                    :key="comment.id"
                    class="px-4 py-4"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold">{{ comment.name }}</span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em]"
                                    :class="comment.is_visible ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                >
                                    {{ comment.is_visible ? 'Visible' : 'Hidden' }}
                                </span>
                            </div>
                            <p v-if="comment.email" class="mt-0.5 text-xs text-neutral-500">{{ comment.email }}</p>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-neutral-700">{{ comment.body }}</p>
                            <p class="mt-2 text-xs text-neutral-400">
                                <Link
                                    v-if="comment.article"
                                    :href="route('articles.show', comment.article.slug)"
                                    target="_blank"
                                    class="hover:text-brand"
                                >
                                    {{ comment.article.title }}
                                </Link>
                                <span class="mx-1">·</span>
                                {{ comment.created_at }}
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em]"
                                :class="comment.is_visible ? 'border-amber-200 text-amber-700' : 'border-emerald-200 text-emerald-700'"
                                @click="toggle(comment.id)"
                            >
                                {{ comment.is_visible ? 'Hide' : 'Show' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-red-600"
                                @click="destroy(comment.id)"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="!comments.data?.length" class="px-4 py-10 text-center text-sm text-neutral-500">
                    Belum ada komentar.
                </div>
            </div>

            <PaginationBar
                :links="comments.links"
                :from="comments.from"
                :to="comments.to"
                :total="comments.total"
            />
        </div>
    </AdminLayout>
</template>
