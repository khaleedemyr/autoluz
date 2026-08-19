<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { swalConfirm, swalWarning } from '@/utils/swal';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissionCatalog: { type: Array, default: () => [] },
});

const page = usePage();
const flashError = computed(() => page.props.errors?.role || '');
const editingId = ref(null);
const showCreate = ref(false);

const createForm = useForm({
    name: '',
    type: 'visitor',
    is_default: true,
    permissions: [],
});

const editForm = reactive({
    name: '',
    type: 'admin',
    is_default: false,
    permissions: [],
    processing: false,
    errors: {},
});

function permissionLabels(role) {
    if (role.is_super) return 'Semua menu admin';
    if (role.type === 'visitor') {
        return role.is_default
            ? 'Pengunjung website — role default saat daftar'
            : 'Pengunjung website — tidak bisa masuk admin';
    }
    return (role.permissions || [])
        .map((key) => props.permissionCatalog.find((item) => item.key === key)?.label || key)
        .join(', ');
}

function togglePermission(list, key) {
    if (key === 'dashboard') return list;
    const next = [...list];
    const index = next.indexOf(key);
    if (index >= 0) {
        next.splice(index, 1);
    } else {
        next.push(key);
    }
    if (!next.includes('dashboard')) {
        next.unshift('dashboard');
    }
    return next;
}

function openCreate() {
    showCreate.value = true;
    createForm.reset();
    createForm.type = 'visitor';
    createForm.is_default = false;
    createForm.permissions = ['dashboard'];
    createForm.clearErrors();
}

function startEdit(role) {
    editingId.value = role.id;
    editForm.name = role.name;
    editForm.type = role.type || 'admin';
    editForm.is_default = !!role.is_default;
    editForm.permissions = [...(role.permissions || [])];
    editForm.errors = {};
}

function cancelEdit() {
    editingId.value = null;
    editForm.errors = {};
}

function submitCreate() {
    createForm.post(route('admin.roles.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });
}

function submitEdit(role) {
    editForm.processing = true;
    editForm.errors = {};

    router.put(
        route('admin.roles.update', role.id),
        {
            name: editForm.name,
            type: role.is_super ? 'admin' : editForm.type,
            is_default: editForm.is_default,
            permissions: role.is_super || editForm.type === 'visitor' ? undefined : editForm.permissions,
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

async function destroyRole(role) {
    if (role.is_super) {
        await swalWarning('Role Super Admin tidak bisa dihapus.');
        return;
    }
    if (role.is_default) {
        await swalWarning('Role default pendaftaran tidak bisa dihapus.');
        return;
    }
    const ok = await swalConfirm(`Hapus role "${role.name}"?`, {
        title: 'Hapus Role',
        confirmButtonText: 'Hapus',
    });
    if (!ok) return;
    router.delete(route('admin.roles.destroy', role.id), { preserveScroll: true });
}
</script>

<template>
    <AdminLayout title="Role">
        <Head title="Role" />

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-neutral-500">Buat role admin (akses menu) atau role pengunjung (user website).</p>
            <button
                type="button"
                class="rounded-full bg-brand px-4 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-white"
                @click="openCreate"
            >
                + Role Baru
            </button>
        </div>

        <p v-if="flashError" class="mb-4 text-sm text-red-600">{{ flashError }}</p>

        <div v-if="showCreate" class="mb-5 rounded-2xl border border-black/5 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-display text-xl tracking-[-0.03em]">Tambah Role</h2>
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Nama role</label>
                    <input v-model="createForm.name" type="text" class="w-full max-w-md rounded-xl border-black/10" required placeholder="Contoh: Pengunjung atau Editor Event" />
                    <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-600">{{ createForm.errors.name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Tipe</label>
                    <select v-model="createForm.type" class="w-full max-w-md rounded-xl border-black/10">
                        <option value="visitor">Pengunjung (website)</option>
                        <option value="admin">Admin (portal)</option>
                    </select>
                </div>
                <p v-if="createForm.type === 'visitor'" class="text-sm text-neutral-500">
                    Role ini untuk user website. Tidak bisa masuk portal admin.
                </p>
                <label v-if="createForm.type === 'visitor'" class="flex items-center gap-2 text-sm">
                    <input v-model="createForm.is_default" type="checkbox" class="rounded border-black/20 text-brand" />
                    Role default saat user daftar
                </label>
                <div v-if="createForm.type === 'admin'">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Akses menu</p>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="item in permissionCatalog"
                            :key="item.key"
                            class="flex items-center gap-2 rounded-xl border border-black/5 px-3 py-2 text-sm"
                            :class="item.key === 'dashboard' ? 'bg-mist/70 text-neutral-500' : ''"
                        >
                            <input
                                type="checkbox"
                                class="rounded border-black/20 text-brand"
                                :checked="createForm.permissions.includes(item.key)"
                                :disabled="item.key === 'dashboard'"
                                @change="createForm.permissions = togglePermission(createForm.permissions, item.key)"
                            />
                            {{ item.label }}
                        </label>
                    </div>
                    <p v-if="createForm.errors.permissions" class="mt-1 text-xs text-red-600">{{ createForm.errors.permissions }}</p>
                </div>
                <div class="flex gap-2">
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

        <div class="space-y-3">
            <article
                v-for="role in roles"
                :key="role.id"
                class="rounded-2xl border border-black/5 bg-white p-5 shadow-sm"
            >
                <form v-if="editingId === role.id" class="space-y-4" @submit.prevent="submitEdit(role)">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Nama role</label>
                        <input v-model="editForm.name" type="text" class="w-full max-w-md rounded-xl border-black/10" required />
                        <p v-if="editForm.errors.name" class="mt-1 text-xs text-red-600">{{ editForm.errors.name }}</p>
                    </div>
                    <div v-if="!role.is_super">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Tipe</label>
                        <select v-model="editForm.type" class="w-full max-w-md rounded-xl border-black/10">
                            <option value="visitor">Pengunjung (website)</option>
                            <option value="admin">Admin (portal)</option>
                        </select>
                    </div>
                    <label v-if="!role.is_super && editForm.type === 'visitor'" class="flex items-center gap-2 text-sm">
                        <input v-model="editForm.is_default" type="checkbox" class="rounded border-black/20 text-brand" />
                        Role default saat user daftar
                    </label>
                    <div v-if="!role.is_super && editForm.type === 'admin'">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-neutral-500">Akses menu</p>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <label
                                v-for="item in permissionCatalog"
                                :key="item.key"
                                class="flex items-center gap-2 rounded-xl border border-black/5 px-3 py-2 text-sm"
                                :class="item.key === 'dashboard' ? 'bg-mist/70 text-neutral-500' : ''"
                            >
                                <input
                                    type="checkbox"
                                    class="rounded border-black/20 text-brand"
                                    :checked="editForm.permissions.includes(item.key)"
                                    :disabled="item.key === 'dashboard'"
                                    @change="editForm.permissions = togglePermission(editForm.permissions, item.key)"
                                />
                                {{ item.label }}
                            </label>
                        </div>
                    </div>
                    <p v-if="role.is_super" class="text-sm text-neutral-500">Super Admin punya akses ke semua menu.</p>
                    <p v-else-if="editForm.type === 'visitor'" class="text-sm text-neutral-500">
                        Role pengunjung untuk user website, tanpa akses portal admin.
                    </p>
                    <div class="flex gap-2">
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

                <div v-else class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-display text-xl tracking-[-0.03em]">{{ role.name }}</h2>
                            <span
                                v-if="role.is_super"
                                class="rounded-full bg-brand/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-brand"
                            >
                                Super
                            </span>
                            <span
                                v-else
                                class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em]"
                                :class="role.type === 'visitor' ? 'bg-neutral-100 text-neutral-600' : 'bg-brand/10 text-brand'"
                            >
                                {{ role.type === 'visitor' ? 'Pengunjung' : 'Admin' }}
                            </span>
                            <span
                                v-if="role.is_default"
                                class="rounded-full bg-mist px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-neutral-500"
                            >
                                Default daftar
                            </span>
                            <span class="text-xs text-neutral-400">{{ role.users_count }} user</span>
                        </div>
                        <p class="mt-2 text-sm text-neutral-600">{{ permissionLabels(role) }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="text-xs font-semibold uppercase tracking-[0.12em] text-neutral-600 hover:text-brand"
                            @click="startEdit(role)"
                        >
                            Edit
                        </button>
                        <button
                            v-if="!role.is_super && !role.is_default"
                            type="button"
                            class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600 hover:text-red-700"
                            @click="destroyRole(role)"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </AdminLayout>
</template>
