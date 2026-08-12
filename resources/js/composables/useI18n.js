import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export function useI18n() {
    const page = usePage();

    const locale = computed(() => page.props.locale || 'id');
    const dateLocale = computed(() => (locale.value === 'en' ? 'en-US' : 'id-ID'));

    function t(key, replace = {}) {
        const dict = page.props.translations?.site || {};
        let text = dict[key] ?? key;

        Object.entries(replace).forEach(([name, value]) => {
            text = String(text).replaceAll(`:${name}`, String(value));
        });

        return text;
    }

    function formatDate(value, options = {}) {
        if (!value) return '';
        return new Intl.DateTimeFormat(dateLocale.value, {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            ...options,
        }).format(new Date(value));
    }

    function formatNumber(value) {
        return new Intl.NumberFormat(dateLocale.value).format(Number(value || 0));
    }

    function setLocale(next) {
        if (!next || next === locale.value) return;
        router.get(route('locale.update', next), {}, { preserveScroll: true });
    }

    function paginationLabel(raw) {
        const label = String(raw || '')
            .replace(/&laquo;|&raquo;|&nbsp;/gi, ' ')
            .replace(/<[^>]+>/g, '')
            .trim();

        const lower = label.toLowerCase();
        const prev = page.props.translations?.pagination?.previous || 'Previous';
        const next = page.props.translations?.pagination?.next || 'Next';

        if (
            lower.includes('previous')
            || lower.includes('sebelumnya')
            || lower === 'pagination.previous'
            || lower.includes('«')
        ) {
            return stripEntities(prev);
        }

        if (
            lower.includes('next')
            || lower.includes('berikutnya')
            || lower === 'pagination.next'
            || lower.includes('»')
        ) {
            return stripEntities(next);
        }

        return label;
    }

    return {
        locale,
        dateLocale,
        t,
        formatDate,
        formatNumber,
        setLocale,
        paginationLabel,
    };
}

function stripEntities(value) {
    return String(value || '')
        .replace(/&laquo;|&raquo;|&nbsp;/gi, ' ')
        .replace(/<[^>]+>/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}
