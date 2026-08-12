import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const brand = '#FF1E2D';

const base = Swal.mixin({
    confirmButtonColor: brand,
    cancelButtonColor: '#6b7280',
    buttonsStyling: true,
});

export async function swalAlert(message, options = {}) {
    return base.fire({
        icon: options.icon || 'info',
        title: options.title || undefined,
        text: message,
        confirmButtonText: options.confirmButtonText || 'OK',
        ...options,
    });
}

export async function swalSuccess(message, options = {}) {
    return swalAlert(message, {
        icon: 'success',
        title: options.title || 'Berhasil',
        ...options,
    });
}

export async function swalError(message, options = {}) {
    return swalAlert(message, {
        icon: 'error',
        title: options.title || 'Gagal',
        ...options,
    });
}

export async function swalWarning(message, options = {}) {
    return swalAlert(message, {
        icon: 'warning',
        title: options.title || 'Perhatian',
        ...options,
    });
}

export async function swalConfirm(message, options = {}) {
    const result = await base.fire({
        icon: options.icon || 'warning',
        title: options.title || 'Konfirmasi',
        text: message,
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText || 'Ya',
        cancelButtonText: options.cancelButtonText || 'Batal',
        reverseButtons: true,
        ...options,
    });

    return result.isConfirmed;
}

export async function swalPrompt(message, options = {}) {
    const result = await base.fire({
        title: options.title || message,
        input: options.input || 'text',
        inputLabel: options.inputLabel || (options.title ? message : undefined),
        inputValue: options.inputValue ?? '',
        inputPlaceholder: options.inputPlaceholder || '',
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText || 'OK',
        cancelButtonText: options.cancelButtonText || 'Batal',
        reverseButtons: true,
        inputValidator: options.inputValidator,
        ...options,
    });

    if (!result.isConfirmed) {
        return null;
    }

    return result.value ?? '';
}

export function swalToast(message, options = {}) {
    return Swal.fire({
        toast: true,
        position: options.position || 'top-end',
        icon: options.icon || 'success',
        title: message,
        showConfirmButton: false,
        timer: options.timer ?? 2800,
        timerProgressBar: true,
    });
}

export { Swal };
