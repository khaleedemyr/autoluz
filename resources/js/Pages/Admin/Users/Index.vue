<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { swalConfirm, swalWarning } from '@/utils/swal';

const props = defineProps({
    users: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);
const editingId = ref(null);
const showCreate = ref(false);
const adminRoles = computed(() => props.roles.filter((role) => role.type !== 'visitor'));
const visitorRoles = computed(() => props.roles.filter((role) => role.type === 'visitor'));

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: '',
});

const editForm = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: '',
    processing: false,
    errors: {},
});

function openCreate() {
    showCreate.value = true;
    createForm.reset();
    createForm.role_id = props.roles.find((role) => role.is_default)?.id
        || props.roles.find((role) => role.type === 'visitor')?.id
        || '';
    createForm.clearErrors();
}

function startEdit(user) {
    editingId.value = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.role_id = user.role_id || '';
    editForm.errors = {};
}

function cancelEdit() {
    editingId.value = null;
    editForm.errors = {};
}

function submitCreate() {
    createForm
        .transform((data) => ({ ...data, role_id: data.role_id || null }))
        .post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showCreate.value = false;
                createForm.reset();
            },
        });
}

function submitEdit(user) {
    editForm.processing = true;
    editForm.errors = {};

    router.put(
        route('admin.users.update', user.id),
        {
            name: editForm.name,
            email: editForm.email,
            password: editForm.password || null,
            password_confirmation: editForm.password_confirmation || null,
            role_id: editForm.role_id || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null;
            },
            onError: (errors) => {
                editForm.errors = errors;
            },
            onFinish: () => {
                editForm.processing = false;
            },
        },
    );
}

async function destroyUser(user) {
    if (user.id === currentUserId.value) {
        await swalWarning('Tidak bisa menghapus akun sendiri.');
        return;
    }
    const ok = await swalConfirm(`Hapus user "${user.name}"?`, {
        title: 'Hapus User',
        confirmButtonText: 'Hapus',
    });
    if (!ok) return;
    router.delete(route('admin.users.destroy', user.id), { preserveScroll: true });
}
</script>

<template>
    <AdminLayout title="User Management">
        <Head title="User Management" />

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-neutral-500">Kelola akun dan tetapkan role untuk akses menu admin.</p>
            <button
                type="button"
                class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white"
                @click="openCreate"
            >
                + User Baru
            </button>
        </div>

        <div
            v-if="showCreate"
            class="mb-5 rounded-2xl border border-black/5 bg-white p-5 shadow-sm"
        >
            <h2 class="mb-4 font-display text-xl tracking-[-0.03em]">Tambah User</h2>
            <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitCreate">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Nama</label>
                    <input v-model="createForm.name" type="text" class="w-full rounded-xl border-black/10" required />
                    <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-600">{{ createForm.errors.name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Email</label>
                    <input v-model="createForm.email" type="email" class="w-full rounded-xl border-black/10" required />
                    <p v-if="createForm.errors.email" class="mt-1 text-xs text-red-600">{{ createForm.errors.email }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Password</label>
                    <input v-model="createForm.password" type="password" class="w-full rounded-xl border-black/10" required />
                    <p v-if="createForm.errors.password" class="mt-1 text-xs text-red-600">{{ createForm.errors.password }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Konfirmasi</label>
                    <input v-model="createForm.password_confirmation" type="password" class="w-full rounded-xl border-black/10" required />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Role</label>
                    <select v-model="createForm.role_id" class="w-full rounded-xl border-black/10">
                        <optgroup v-if="adminRoles.length" label="Admin">
                            <option v-for="role in adminRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </optgroup>
                        <optgroup v-if="visitorRoles.length" label="Pengunjung">
                            <option v-for="role in visitorRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </optgroup>
                    </select>
                    <p v-if="createForm.errors.role_id" class="mt-1 text-xs text-red-600">{{ createForm.errors.role_id }}</p>
                </div>
                <div class="flex gap-2 sm:col-span-2">
                    <button
                        type="submit"
                        class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-60"
                        :disabled="createForm.processing"
                    >
                        Simpan
                    </button>
                    <button
                        type="button"
                        class="rounded-full border border-black/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em]"
                        @click="showCreate = false"
                    >
                        Batal
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-black/5 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-black/5 bg-neutral-50 text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-500">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Dibuat</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id" class="border-b border-black/5 align-top">
                        <td class="px-4 py-3" colspan="5" v-if="editingId === user.id">
                            <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitEdit(user)">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Nama</label>
                                    <input v-model="editForm.name" type="text" class="w-full rounded-xl border-black/10" required />
                                    <p v-if="editForm.errors.name" class="mt-1 text-xs text-red-600">{{ editForm.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Email</label>
                                    <input v-model="editForm.email" type="email" class="w-full rounded-xl border-black/10" required />
                                    <p v-if="editForm.errors.email" class="mt-1 text-xs text-red-600">{{ editForm.errors.email }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Password baru</label>
                                    <input v-model="editForm.password" type="password" class="w-full rounded-xl border-black/10" placeholder="Kosongkan jika tidak diganti" />
                                    <p v-if="editForm.errors.password" class="mt-1 text-xs text-red-600">{{ editForm.errors.password }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Konfirmasi</label>
                                    <input v-model="editForm.password_confirmation" type="password" class="w-full rounded-xl border-black/10" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Role</label>
                                    <select v-model="editForm.role_id" class="w-full rounded-xl border-black/10">
                                        <optgroup v-if="adminRoles.length" label="Admin">
                                            <option v-for="role in adminRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                        </optgroup>
                                        <optgroup v-if="visitorRoles.length" label="Pengunjung">
                                            <option v-for="role in visitorRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                        </optgroup>
                                    </select>
                                    <p v-if="editForm.errors.role_id" class="text-xs text-red-600">{{ editForm.errors.role_id }}</p>
                                </div>
                                <div class="flex gap-2 sm:col-span-2">
                                    <button
                                        type="submit"
                                        class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white disabled:opacity-60"
                                        :disabled="editForm.processing"
                                    >
                                        Simpan
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full border border-black/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em]"
                                        @click="cancelEdit"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </td>
                        <template v-else>
                            <td class="px-4 py-3 font-medium">
                                {{ user.name }}
                                <span v-if="user.id === currentUserId" class="ml-2 text-[10px] uppercase tracking-wider text-brand">Anda</span>
                            </td>
                            <td class="px-4 py-3 text-neutral-600">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em]"
                                    :class="user.is_admin ? 'bg-brand/10 text-brand' : 'bg-neutral-100 text-neutral-500'"
                                >
                                    {{ user.role_name || 'Pengunjung' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-neutral-500">{{ user.created_at }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="mr-2 text-xs font-semibold uppercase tracking-[0.12em] text-neutral-600 hover:text-brand"
                                    @click="startEdit(user)"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="user.id !== currentUserId"
                                    type="button"
                                    class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600 hover:text-red-700"
                                    @click="destroyUser(user)"
                                >
                                    Hapus
                                </button>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
