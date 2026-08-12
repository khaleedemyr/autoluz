const STORAGE_KEY = 'autoluz_followed_brands';

export function getFollowedBrandIds() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        const parsed = JSON.parse(raw || '[]');
        return Array.isArray(parsed) ? parsed.map(Number).filter((id) => id > 0) : [];
    } catch {
        return [];
    }
}

export function setFollowedBrandIds(ids) {
    const unique = [...new Set(ids.map(Number).filter((id) => id > 0))];
    localStorage.setItem(STORAGE_KEY, JSON.stringify(unique));
    window.dispatchEvent(new CustomEvent('autoluz:brands-changed', { detail: unique }));
    return unique;
}

export function isFollowingBrand(id) {
    return getFollowedBrandIds().includes(Number(id));
}

export function toggleFollowBrand(id) {
    const current = getFollowedBrandIds();
    const num = Number(id);
    if (current.includes(num)) {
        return setFollowedBrandIds(current.filter((item) => item !== num));
    }
    return setFollowedBrandIds([...current, num]);
}
