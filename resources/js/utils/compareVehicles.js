const STORAGE_KEY = 'autoluz_compare_vehicles';
const MAX = 3;
const EVENT = 'autoluz:compare-changed';

function normalize(list) {
    if (!Array.isArray(list)) return [];
    const seen = new Set();
    const out = [];
    for (const item of list) {
        const id = Number(item?.id ?? item);
        if (!id || seen.has(id)) continue;
        seen.add(id);
        out.push({
            id,
            name: item?.name || `#${id}`,
            brand: item?.brand || '',
            cover_image_url: item?.cover_image_url || null,
        });
        if (out.length >= MAX) break;
    }
    return out;
}

export function getCompareList() {
    try {
        return normalize(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'));
    } catch {
        return [];
    }
}

export function getCompareIds() {
    return getCompareList().map((item) => item.id);
}

export function setCompareList(list) {
    const next = normalize(list);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    window.dispatchEvent(new CustomEvent(EVENT, { detail: next }));
    return next;
}

export function isInCompare(id) {
    return getCompareIds().includes(Number(id));
}

export function toggleCompare(vehicle) {
    const current = getCompareList();
    const id = Number(vehicle?.id ?? vehicle);
    if (current.some((item) => item.id === id)) {
        return { list: setCompareList(current.filter((item) => item.id !== id)), full: false };
    }
    if (current.length >= MAX) {
        return { list: current, full: true };
    }
    return {
        list: setCompareList([
            ...current,
            {
                id,
                name: vehicle?.name || `#${id}`,
                brand: vehicle?.brand?.name || vehicle?.brand || '',
                cover_image_url: vehicle?.cover_image_url || null,
            },
        ]),
        full: false,
    };
}

export function clearCompare() {
    return setCompareList([]);
}

export function compareUrl(ids = getCompareIds()) {
    if (!ids.length) return route('vehicles.compare');
    return `${route('vehicles.compare')}?ids=${ids.join(',')}`;
}

export const COMPARE_MAX = MAX;
export const COMPARE_EVENT = EVENT;
