<script setup>
import { reactive, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    categories: { type: Object, required: true },
    filters: { type: Object, default: () => ({ q: '' }) },
});

const q = ref(props.filters.q || '');
const editingId = ref(null);

const createForm = useForm({
    name: '',
    slug: '',
    description: '',
    sort_order: 0,
    is_active: true,
});

const editForm = reactive({
    name: '',
    slug: '',
    description: '',
    sort_order: 0,
    is_active: true,
});

watch(q, () => {
    router.get(route('admin.shop-categories.index'), { q: q.value || undefined }, { preserveState: true, replace: true });
});

watch(() => createForm.name, (name) => {
    createForm.slug = slugify(name, 180);
});

function startEdit(cat) {
    editingId.value = cat.id;
    editForm.name = cat.name;
    editForm.slug = cat.slug;
    editForm.description = cat.description || '';
    editForm.sort_order = cat.sort_order || 0;
    editForm.is_active = !!cat.is_active;
}

function submitCreate() {
    createForm.post(route('admin.shop-categories.store'), {
        onSuccess: () => createForm.reset('name', 'slug', 'description'),
    });
}

function submitEdit(id) {
    router.put(route('admin.shop-categories.update', id), { ...editForm }, {
        onSuccess: () => { editingId.value = null; },
    });
}

async function destroy(id) {
    const ok = await swalConfirm('Hapus kategori toko ini?', { title: 'Hapus Kategori', confirmButtonText: 'Hapus' });
    if (!ok) return;
    router.delete(route('admin.shop-categories.destroy', id));
}
</script>

<template>
    <AdminLayout title="Kategori Toko">
        <Head title="Kategori Toko" />

        <form class="mb-6 grid gap-3 rounded-2xl border border-black/5 bg-white p-5 shadow-sm md:grid-cols-6" @submit.prevent="submitCreate">
            <input v-model="createForm.name" type="text" placeholder="Nama" class="rounded-xl border-black/10 md:col-span-2" required />
            <input :value="createForm.slug" type="text" placeholder="Slug" class="rounded-xl border-black/10 bg-neutral-50" readonly />
            <input v-model="createForm.description" type="text" placeholder="Deskripsi" class="rounded-xl border-black/10 md:col-span-2" />
            <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white">Tambah</button>
        </form>

        <div class="mb-4">
            <input v-model="q" type="search" placeholder="Cari kategori..." class="rounded-xl border-black/10 text-sm" />
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist/60 text-xs uppercase tracking-[0.12em] text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Aktif</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="cat in categories.data" :key="cat.id" class="border-t border-black/5 align-top">
                        <template v-if="editingId === cat.id">
                            <td class="px-4 py-3" colspan="5">
                                <div class="grid gap-2 md:grid-cols-4">
                                    <input v-model="editForm.name" class="rounded-xl border-black/10" />
                                    <input v-model="editForm.slug" class="rounded-xl border-black/10" />
                                    <input v-model="editForm.description" class="rounded-xl border-black/10" />
                                    <label class="flex items-center gap-2 text-sm">
                                        <input v-model="editForm.is_active" type="checkbox" class="rounded text-brand" /> Aktif
                                    </label>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button type="button" class="rounded-full bg-brand px-3 py-1.5 text-xs font-semibold text-white" @click="submitEdit(cat.id)">Simpan</button>
                                    <button type="button" class="rounded-full border px-3 py-1.5 text-xs" @click="editingId = null">Batal</button>
                                </div>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-4 py-3 font-semibold">{{ cat.name }}</td>
                            <td class="px-4 py-3 text-neutral-500">{{ cat.slug }}</td>
                            <td class="px-4 py-3">{{ cat.products_count }}</td>
                            <td class="px-4 py-3">{{ cat.is_active ? 'Ya' : 'Tidak' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button" class="mr-3 text-brand hover:underline" @click="startEdit(cat)">Edit</button>
                                <button type="button" class="text-red-600 hover:underline" @click="destroy(cat.id)">Hapus</button>
                            </td>
                        </template>
                    </tr>
                    <tr v-if="!categories.data?.length">
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-neutral-500">Belum ada kategori.</td>
                    </tr>
                </tbody>
            </table>
            <PaginationBar :links="categories.links" :from="categories.from" :to="categories.to" :total="categories.total" />
        </div>
    </AdminLayout>
</template>
