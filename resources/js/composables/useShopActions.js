import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { swalError, swalToast } from '@/utils/swal';

export function useShopActions() {
    const page = usePage();
    const { t } = useI18n();
    const busy = ref({});

    const wishedIds = computed(() => new Set(
        (page.props.wishlist?.product_ids || []).map((id) => Number(id)),
    ));

    function isWished(productId) {
        return wishedIds.value.has(Number(productId));
    }

    function withBusy(key, fn) {
        if (busy.value[key]) return;
        busy.value = { ...busy.value, [key]: true };
        fn(() => {
            busy.value = { ...busy.value, [key]: false };
        });
    }

    function toggleWishlist(product) {
        if (!product?.id) return;
        withBusy(`wish-${product.id}`, (done) => {
            const wasOn = isWished(product.id);
            router.post(route('shop.wishlist.toggle'), { product_id: product.id }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => swalToast(wasOn ? t('shop_wishlist_removed') : t('shop_wishlist_added')),
                onError: (errors) => swalError(errors.product_id || t('shop_wishlist_failed')),
                onFinish: done,
            });
        });
    }

    function addToCart(product, variantId = null, qty = 1) {
        const id = variantId || product?.default_variant_id;
        if (!id || !product?.in_stock) {
            swalError(t('shop_out'));
            return;
        }
        withBusy(`cart-${product.id}`, (done) => {
            router.post(route('shop.cart.store'), { variant_id: id, qty }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => swalToast(t('shop_added')),
                onError: (errors) => swalError(errors.qty || errors.variant_id || t('shop_add_failed')),
                onFinish: done,
            });
        });
    }

    return {
        busy,
        isWished,
        toggleWishlist,
        addToCart,
    };
}
