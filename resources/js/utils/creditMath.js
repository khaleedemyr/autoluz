/**
 * Automotive credit simulation helpers (IDR).
 * Flat = dealer-style flat annual interest.
 * Annuity = effective / declining-balance monthly payment.
 */

export function clamp(value, min, max) {
    const n = Number(value);
    if (!Number.isFinite(n)) return min;
    return Math.min(max, Math.max(min, n));
}

export function roundRupiah(value) {
    return Math.round(Number(value) || 0);
}

/**
 * @param {{
 *   price: number,
 *   downPayment: number,
 *   tenor: number,
 *   annualRate: number,
 *   method?: 'flat' | 'annuity',
 * }} input
 */
export function simulateCredit(input) {
    const price = Math.max(0, roundRupiah(input.price));
    const downPayment = clamp(roundRupiah(input.downPayment), 0, price);
    const tenor = Math.max(1, Math.floor(Number(input.tenor) || 1));
    const annualRate = Math.max(0, Number(input.annualRate) || 0);
    const method = input.method === 'annuity' ? 'annuity' : 'flat';

    const principal = Math.max(0, price - downPayment);

    if (principal <= 0) {
        return {
            ok: true,
            method,
            price,
            downPayment,
            principal: 0,
            tenor,
            annualRate,
            monthly: 0,
            totalInterest: 0,
            totalPayment: downPayment,
            totalWithDp: downPayment,
        };
    }

    if (method === 'annuity') {
        const r = annualRate / 100 / 12;
        let monthly;
        if (r === 0) {
            monthly = principal / tenor;
        } else {
            const factor = (1 + r) ** tenor;
            monthly = (principal * r * factor) / (factor - 1);
        }
        monthly = roundRupiah(monthly);
        const totalInstallments = monthly * tenor;
        const totalInterest = Math.max(0, totalInstallments - principal);

        return {
            ok: true,
            method,
            price,
            downPayment,
            principal,
            tenor,
            annualRate,
            monthly,
            totalInterest,
            totalPayment: totalInstallments,
            totalWithDp: totalInstallments + downPayment,
        };
    }

    const years = tenor / 12;
    const totalInterest = roundRupiah(principal * (annualRate / 100) * years);
    const totalPayment = principal + totalInterest;
    const monthly = roundRupiah(totalPayment / tenor);

    return {
        ok: true,
        method,
        price,
        downPayment,
        principal,
        tenor,
        annualRate,
        monthly,
        totalInterest,
        totalPayment,
        totalWithDp: totalPayment + downPayment,
    };
}

/**
 * Quick comparison across common tenors.
 * @param {Omit<Parameters<typeof simulateCredit>[0], 'tenor'> & { tenors?: number[] }} input
 */
export function simulateTenorTable(input) {
    const tenors = (input.tenors || [12, 24, 36, 48, 60]).filter((n) => n > 0);
    return tenors.map((tenor) => simulateCredit({ ...input, tenor }));
}

export function dpFromPercent(price, percent) {
    const p = clamp(percent, 0, 100);
    return roundRupiah((Math.max(0, roundRupiah(price)) * p) / 100);
}

export function percentFromDp(price, downPayment) {
    const p = Math.max(0, roundRupiah(price));
    if (p <= 0) return 0;
    return Math.round((clamp(roundRupiah(downPayment), 0, p) / p) * 1000) / 10;
}
