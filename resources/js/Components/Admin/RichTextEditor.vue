<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import { swalError, swalPrompt } from '@/utils/swal';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Tulis isi artikel di sini…' },
    uploadUrl: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const uploading = ref(false);
const fileInput = ref(null);

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Underline,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                rel: 'noopener noreferrer',
                target: '_blank',
            },
        }),
        Image.configure({
            allowBase64: false,
            HTMLAttributes: {
                class: 'rounded-xl max-w-full h-auto',
            },
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'rich-editor-content min-h-[22rem] px-4 py-3 focus:outline-none',
        },
    },
    onUpdate: ({ editor: current }) => {
        emit('update:modelValue', current.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;
        const current = editor.value.getHTML();
        if (value !== current) {
            editor.value.commands.setContent(value || '', { emitUpdate: false });
        }
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const canUpload = computed(() => !!props.uploadUrl);

function run(command) {
    if (!editor.value) return;
    command(editor.value.chain().focus()).run();
}

function isActive(name, attrs = {}) {
    return editor.value?.isActive(name, attrs) ?? false;
}

async function setLink() {
    if (!editor.value) return;
    const previous = editor.value.getAttributes('link').href || '';
    const url = await swalPrompt('Masukkan URL tautan', {
        title: 'Tautan',
        inputValue: previous,
        inputPlaceholder: 'https://...',
    });

    if (url === null) return;

    if (String(url).trim() === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: String(url).trim() }).run();
}

async function promptImageUrl() {
    const url = await swalPrompt('Masukkan URL gambar', {
        title: 'Gambar',
        inputPlaceholder: 'https://...',
    });
    if (!url?.trim() || !editor.value) return;
    editor.value.chain().focus().setImage({ src: String(url).trim() }).run();
}

function openFilePicker() {
    if (!canUpload.value) {
        promptImageUrl();
        return;
    }
    fileInput.value?.click();
}

async function onFileSelected(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file || !props.uploadUrl || !editor.value) return;

    uploading.value = true;
    try {
        const body = new FormData();
        body.append('image', file);

        const { data } = await window.axios.post(props.uploadUrl, body, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (data?.url) {
            editor.value.chain().focus().setImage({ src: data.url }).run();
        }
    } catch (error) {
        await swalError(error?.response?.data?.message || 'Gagal upload gambar.');
    } finally {
        uploading.value = false;
    }
}

const toolbar = [
    { label: 'B', title: 'Bold', action: (c) => c.toggleBold(), active: () => isActive('bold') },
    { label: 'I', title: 'Italic', action: (c) => c.toggleItalic(), active: () => isActive('italic'), class: 'italic' },
    { label: 'U', title: 'Underline', action: (c) => c.toggleUnderline(), active: () => isActive('underline'), class: 'underline' },
    { label: 'H2', title: 'Heading 2', action: (c) => c.toggleHeading({ level: 2 }), active: () => isActive('heading', { level: 2 }) },
    { label: 'H3', title: 'Heading 3', action: (c) => c.toggleHeading({ level: 3 }), active: () => isActive('heading', { level: 3 }) },
    { label: '• List', title: 'Bullet list', action: (c) => c.toggleBulletList(), active: () => isActive('bulletList') },
    { label: '1. List', title: 'Numbered list', action: (c) => c.toggleOrderedList(), active: () => isActive('orderedList') },
    { label: 'Quote', title: 'Quote', action: (c) => c.toggleBlockquote(), active: () => isActive('blockquote') },
];
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-black/10 bg-white">
        <div class="flex flex-wrap items-center gap-1 border-b border-black/10 bg-neutral-50 p-2">
            <button
                v-for="item in toolbar"
                :key="item.title"
                type="button"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold transition"
                :class="[
                    item.class || '',
                    item.active() ? 'bg-brand text-white' : 'text-neutral-700 hover:bg-black/5',
                ]"
                :title="item.title"
                @click="run(item.action)"
            >
                {{ item.label }}
            </button>

            <button
                type="button"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold transition"
                :class="isActive('link') ? 'bg-brand text-white' : 'text-neutral-700 hover:bg-black/5'"
                title="Tautan"
                @click="setLink"
            >
                Link
            </button>

            <button
                type="button"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-black/5 disabled:opacity-60"
                title="Sisipkan gambar"
                :disabled="uploading"
                @click="openFilePicker"
            >
                {{ uploading ? 'Upload...' : 'Gambar' }}
            </button>

            <button
                type="button"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-black/5"
                title="Rata kiri"
                @click="run((c) => c.setTextAlign('left'))"
            >
                ⟸
            </button>
            <button
                type="button"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-black/5"
                title="Rata tengah"
                @click="run((c) => c.setTextAlign('center'))"
            >
                ☰
            </button>
            <button
                type="button"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-black/5"
                title="Rata kanan"
                @click="run((c) => c.setTextAlign('right'))"
            >
                ⟹
            </button>

            <input
                ref="fileInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="onFileSelected"
            />
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>
