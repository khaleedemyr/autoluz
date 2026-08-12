<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PaginationBar from '@/Components/Admin/PaginationBar.vue';
import { swalConfirm } from '@/utils/swal';
import { slugify } from '@/utils/slugify';

const props = defineProps({
    brands: { type: Object, required: true },
    filters: { type: Object, default: () => ({ q: '' }) },
});

const q = ref(props.filters.q || '');
const editing = ref(null);

const form = useForm({
    name: '',
    slug: '',
    type: 'car',
    description: '',
    is_active: true,
    sort_order: 0,
    logo: null,
    remove_logo: false,
});

watch(q, () => {
    router.get(route('admin.brands.index'), { q: q.value || undefined }, { preserveState: true, replace: true });
});

function resetForm() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.type = 'car';
    form.is_active = true;
    form.sort_order = 0;
    form.remove_logo = false;
}

function edit(brand) {
    editing.value = brand;
    form.name = brand.name;
    form.slug = brand.slug;
    form.type = brand.type || 'car';
    form.description = brand.description || '';
    form.is_active = brand.is_active;
    form.sort_order = brand.sort_order || 0;
    form.logo = null;
    form.remove_logo = false;
}

function submit() {
    if (!form.slug) form.slug = slugify(form.name);
    if (editing.value) {
        form.transform((data) => ({ ...data, _method: 'put' }))
            .post(route('admin.brands.update', editing.value.id), {
                forceFormData: true,
                onSuccess: () => resetForm(),
            });
        return;
    }
    form.post(route('admin.brands.store'), {
        forceFormData: true,
        onSuccess: () => resetForm(),
    });
}

async function destroy(id) {
    const ok = await swalConfirm('Hapus merek ini?', { title: 'Hapus Merek', confirmButtonText: 'Hapus', icon: 'warning' });
    if (!ok) return;
    router.delete(route('admin.brands.destroy', id));
}
</script>

<template>
    <AdminLayout title="Merek">
        <Head title="Admin Merek" />

        <div class="mb-4">
            <input v-model="q" type="search" placeholder="Cari merek..." class="rounded-xl border-black/10 text-sm" />
        </div>

        <div class="grid gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
            <form class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="font-semibold">{{ editing ? 'Edit Merek' : 'Tambah Merek' }}</h2>
                <div class="mt-4 space-y-3">
                    <input v-model="form.name" type="text" required placeholder="Nama merek" class="w-full rounded-xl border-black/10 text-sm" />
                    <input v-model="form.slug" type="text" placeholder="Slug" class="w-full rounded-xl border-black/10 text-sm" />
                    <select v-model="form.type" class="w-full rounded-xl border-black/10 text-sm" required>
                        <option value="car">Mobil</option>
                        <option value="moto">Motor</option>
                        <option value="both">Mobil & Motor</option>
                    </select>
                    <textarea v-model="form.description" rows="3" placeholder="Deskripsi singkat" class="w-full rounded-xl border-black/10 text-sm" />
                    <input v-model="form.sort_order" type="number" min="0" class="w-full rounded-xl border-black/10 text-sm" />
                    <div v-if="editing?.logo_url" class="flex h-14 w-14 items-center justify-center rounded-xl border border-black/10 bg-white p-2">
                        <img :src="editing.logo_url" :alt="editing.name" class="h-full w-full object-contain" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Logo</label>
                        <input type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="w-full text-sm" @change="form.logo = $event.target.files?.[0] || null" />
                        <p class="mt-1.5 text-[11px] leading-relaxed text-neutral-500">
                            Disarankan <span class="font-semibold text-neutral-700">512×512 px</span> (persegi), PNG transparan atau SVG.
                            Minimal <span class="font-semibold text-neutral-700">256×256 px</span>. Maks. 2 MB.
                        </p>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-black/20 text-brand" />
                        Aktif
                    </label>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white" :disabled="form.processing">
                        Simpan
                    </button>
                    <button v-if="editing" type="button" class="rounded-full border border-black/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em]" @click="resetForm">
                        Batal
                    </button>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist/60 text-[11px] uppercase tracking-[0.12em] text-neutral-500">
                        <tr>
                            <th class="px-4 py-3">Merek</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Artikel</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="brand in brands.data" :key="brand.id" class="border-t border-black/5">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-black/10 bg-white p-1.5">
                                        <img v-if="brand.logo_url" :src="brand.logo_url" :alt="brand.name" class="h-full w-full object-contain" />
                                        <span v-else class="text-xs font-bold">{{ brand.name.slice(0, 1) }}</span>
                                    </div>
                                    <span class="font-semibold">{{ brand.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-neutral-500">{{ brand.type_label || brand.type }}</td>
                            <td class="px-4 py-3 text-neutral-500">{{ brand.articles_count || 0 }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase" :class="brand.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                    {{ brand.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button" class="mr-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand" @click="edit(brand)">Edit</button>
                                <button type="button" class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600" @click="destroy(brand.id)">Hapus</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <PaginationBar :links="brands.links" :from="brands.from" :to="brands.to" :total="brands.total" />
            </div>
        </div>
    </AdminLayout>
</template>
