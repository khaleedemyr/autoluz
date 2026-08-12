import { enableWebPush, syncPushPreferences } from '@/utils/webPush';
import { getFollowedBrandIds } from '@/utils/followedBrands';

export function getVapidPublicKey(page) {
    return page?.props?.webpush?.vapidPublicKey || null;
}

export async function enablePushForBrands(page, brandIds = null) {
    const vapidPublicKey = getVapidPublicKey(page);
    const ids = brandIds ?? getFollowedBrandIds();
    return enableWebPush({
        vapidPublicKey,
        brandIds: ids,
        wantsNewsletter: false,
    });
}

export async function enablePushForNewsletter(page, email) {
    const vapidPublicKey = getVapidPublicKey(page);
    return enableWebPush({
        vapidPublicKey,
        email,
        brandIds: getFollowedBrandIds(),
        wantsNewsletter: true,
    });
}

export async function syncFollowedBrandsToPush(page) {
    return syncPushPreferences({
        brandIds: getFollowedBrandIds(),
        vapidPublicKey: getVapidPublicKey(page),
    });
}
