<script setup>
import { reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    categories: { type: Object, required: true },
    filters: { type: Object, default: () => ({ q: '', per_page: 15 }) },
});

const q = ref(props.filters.q || '');
const perPage = ref(Number(props.filters.per_page || 15));
const editingId = ref(null);
const editSlugLocked = ref(true);

const createForm = useForm({
    name: '',
    slug: '',
    description: '',
    parent_id: '',
    sort_order: 0,
    is_active: true,
});

const editForm = reactive({
    name: '',
    slug: '',
    description: '',
    parent_id: '',
    sort_order: 0,
    is_active: true,
});

watch([q, perPage], () => {
    router.get(
        route('admin.categories.index'),
        {
            q: q.value || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, replace: true },
    );
});

watch(
    () => createForm.name,
    (name) => {
        createForm.slug = slugify(name, 180);
    },
);

watch(
    () => editForm.name,
    (name) => {
        if (!editSlugLocked.value) {
            editForm.slug = slugify(name, 180);
        }
    },
);

function startEdit(cat) {
    editingId.value = cat.id;
    editSlugLocked.value = true;
    editForm.name = cat.name;
    editForm.slug = cat.slug;
    editForm.description = cat.description || '';
    editForm.parent_id = cat.parent_id || '';
    editForm.sort_order = cat.sort_order || 0;
    editForm.is_active = !!cat.is_active;
}

function unlockEditSlug() {
    editSlugLocked.value = false;
    editForm.slug = slugify(editForm.name, 180);
}

function cancelEdit() {
    editingId.value = null;
}

function submitCreate() {
    createForm.slug = slugify(createForm.name, 180);
    createForm
        .transform((data) => ({
            ...data,
            parent_id: data.parent_id || null,
            slug: slugify(data.name, 180),
        }))
        .post(route('admin.categories.store'), {
            onSuccess: () => createForm.reset('name', 'slug', 'description'),
        });
}

function submitEdit(id) {
    router.put(
        route('admin.categories.update', id),
        {
            ...editForm,
            parent_id: editForm.parent_id || null,
            slug: editSlugLocked.value ? editForm.slug : slugify(editForm.name, 180),
        },
        {
            onSuccess: () => {
                editingId.value = null;
            },
        },
    );
}
async function destroy(id) {
    const ok = await swalConfirm('Hapus kategori ini?', {
        title: 'Hapus Kategori',
        confirmButtonText: 'Hapus',
    });
    if (!ok) return;
    router.delete(route('admin.categories.destroy', id));
}
</script>

<template>
    <AdminLayout title="Kategori">
        <Head title="Admin Kategori" />

        <form class="mb-6 grid gap-3 rounded-2xl border border-black/5 bg-white p-5 shadow-sm md:grid-cols-6" @submit.prevent="submitCreate">
            <input v-model="createForm.name" type="text" placeholder="Nama" class="rounded-xl border-black/10 md:col-span-2" required />
            <input
                :value="createForm.slug"
                type="text"
                placeholder="Slug otomatis"
                class="rounded-xl border-black/10 bg-neutral-50"
                readonly
            />
            <input v-model="createForm.description" type="text" placeholder="Deskripsi" class="rounded-xl border-black/10 md:col-span-2" />
            <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white">
                Tambah
            </button>
        </form>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <input
                v-model="q"
                type="search"
                placeholder="Cari kategori..."
                class="rounded-xl border-black/10 text-sm"
            />
            <label class="flex items-center gap-2 text-sm text-neutral-600">
                Per page
                <select v-model.number="perPage" class="rounded-xl border-black/10 text-sm">
                    <option :value="10">10</option>
                    <option :value="15">15</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                </select>
            </label>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist/60 text-xs uppercase tracking-[0.12em] text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Artikel</th>
                        <th class="px-4 py-3">Aktif</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="cat in categories.data" :key="cat.id" class="border-t border-black/5 align-top">
                        <template v-if="editingId === cat.id">
                            <td class="px-4 py-3" colspan="5">
                                <div class="grid gap-2 md:grid-cols-5">
                                    <input v-model="editForm.name" class="rounded-xl border-black/10" placeholder="Nama" />
                                    <div class="flex gap-2">
                                        <input
                                            v-model="editForm.slug"
                                            class="w-full rounded-xl border-black/10 bg-neutral-50"
                                            readonly
                                            placeholder="Slug"
                                        />
                                        <button
                                            v-if="editSlugLocked"
                                            type="button"
                                            class="shrink-0 rounded-xl border border-brand/30 px-2 text-[10px] font-semibold uppercase tracking-[0.1em] text-brand"
                                            @click="unlockEditSlug"
                                        >
                                            Sync
                                        </button>
                                    </div>
                                    <input v-model="editForm.description" class="rounded-xl border-black/10 md:col-span-2" placeholder="Deskripsi" />
                                    <label class="flex items-center gap-2 text-sm">
                                        <input v-model="editForm.is_active" type="checkbox" class="rounded text-brand" />
                                        Aktif
                                    </label>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button type="button" class="rounded-full bg-brand px-3 py-1.5 text-xs font-semibold text-white" @click="submitEdit(cat.id)">
                                        Simpan
                                    </button>
                                    <button type="button" class="rounded-full border px-3 py-1.5 text-xs" @click="cancelEdit">Batal</button>
                                </div>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-4 py-3 font-semibold">
                                <Link
                                    :href="route('admin.articles.index', { category_id: cat.id })"
                                    class="hover:text-brand hover:underline"
                                >
                                    {{ cat.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-neutral-500">{{ cat.slug }}</td>
                            <td class="px-4 py-3">
                                <Link
                                    :href="route('admin.articles.index', { category_id: cat.id })"
                                    class="font-semibold text-brand hover:underline"
                                >
                                    {{ cat.articles_count }} artikel
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ cat.is_active ? 'Ya' : 'Tidak' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button" class="mr-3 text-brand hover:underline" @click="startEdit(cat)">Edit</button>
                                <button type="button" class="text-red-600 hover:underline" @click="destroy(cat.id)">Hapus</button>
                            </td>
                        </template>
                    </tr>
                    <tr v-if="!categories.data?.length">
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-neutral-500">
                            Tidak ada kategori.
                        </td>
                    </tr>
                </tbody>
            </table>

            <PaginationBar
                :links="categories.links"
                :from="categories.from"
                :to="categories.to"
                :total="categories.total"
            />
        </div>
    </AdminLayout>
</template>
