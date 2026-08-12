/**
 * Client-side community video compression via MediaRecorder.
 * Reduces bitrate (and optionally resolution) before upload.
 */

const MAX_OUTPUT_BYTES = 25 * 1024 * 1024;
const MAX_INPUT_BYTES = 150 * 1024 * 1024;
const MAX_DURATION_SEC = 60;
const SKIP_IF_UNDER_BYTES = 7 * 1024 * 1024;

function pickMimeType() {
    if (typeof MediaRecorder === 'undefined') {
        return null;
    }

    return [
        'video/webm;codecs=vp9',
        'video/webm;codecs=vp8',
        'video/webm',
        'video/mp4',
    ].find((type) => MediaRecorder.isTypeSupported(type)) || null;
}

function loadVideo(file) {
    const url = URL.createObjectURL(file);
    const video = document.createElement('video');
    video.muted = true;
    video.playsInline = true;
    video.preload = 'auto';
    video.src = url;

    return new Promise((resolve, reject) => {
        const cleanupFail = () => {
            URL.revokeObjectURL(url);
            reject(new Error('LOAD_FAILED'));
        };

        video.onloadedmetadata = () => {
            if (!Number.isFinite(video.duration) || video.duration <= 0) {
                cleanupFail();
                return;
            }
            resolve({ video, url });
        };
        video.onerror = cleanupFail;
    });
}

function waitEnded(video) {
    return new Promise((resolve) => {
        if (video.ended) {
            resolve();
            return;
        }
        video.onended = () => resolve();
    });
}

/**
 * @param {File} file
 * @param {{ onProgress?: (ratio: number) => void }} [options]
 * @returns {Promise<File>}
 */
export async function compressCommunityVideo(file, options = {}) {
    const onProgress = typeof options.onProgress === 'function' ? options.onProgress : () => {};

    if (!file || !String(file.type || '').startsWith('video/')) {
        throw new Error('NOT_VIDEO');
    }

    if (file.size > MAX_INPUT_BYTES) {
        throw new Error('TOO_LARGE');
    }

    const { video, url } = await loadVideo(file);

    if (video.duration > MAX_DURATION_SEC + 0.35) {
        URL.revokeObjectURL(url);
        throw new Error('TOO_LONG');
    }

    if (file.size <= SKIP_IF_UNDER_BYTES) {
        URL.revokeObjectURL(url);
        onProgress(1);
        return file;
    }

    const mimeType = pickMimeType();
    const canCapture = typeof video.captureStream === 'function' || typeof video.mozCaptureStream === 'function';

    if (!mimeType || !canCapture) {
        URL.revokeObjectURL(url);
        if (file.size <= MAX_OUTPUT_BYTES) {
            onProgress(1);
            return file;
        }
        throw new Error('UNSUPPORTED');
    }

    const maxWidth = 1280;
    const maxHeight = 720;
    const needsDownscale =
        video.videoWidth > maxWidth + 8 || video.videoHeight > maxHeight + 8;

    let stream;
    let drawLoop = null;

    try {
        if (needsDownscale) {
            const scale = Math.min(1, maxWidth / video.videoWidth, maxHeight / video.videoHeight);
            const width = Math.max(2, Math.round((video.videoWidth * scale) / 2) * 2);
            const height = Math.max(2, Math.round((video.videoHeight * scale) / 2) * 2);
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d', { alpha: false });
            stream = canvas.captureStream(30);

            try {
                const raw = (video.captureStream || video.mozCaptureStream).call(video);
                raw.getAudioTracks().forEach((track) => stream.addTrack(track));
            } catch {
                // video-only fallback
            }

            drawLoop = () => {
                if (video.paused || video.ended) return;
                ctx.drawImage(video, 0, 0, width, height);
                requestAnimationFrame(drawLoop);
            };
        } else {
            stream = (video.captureStream || video.mozCaptureStream).call(video);
        }

        const recorder = new MediaRecorder(stream, {
            mimeType,
            videoBitsPerSecond: 1_600_000,
        });

        const chunks = [];
        recorder.ondataavailable = (event) => {
            if (event.data && event.data.size > 0) {
                chunks.push(event.data);
            }
        };

        const stopped = new Promise((resolve, reject) => {
            recorder.onstop = () => resolve();
            recorder.onerror = () => reject(new Error('ENCODE_FAILED'));
        });

        video.ontimeupdate = () => {
            if (video.duration > 0) {
                onProgress(Math.min(0.98, video.currentTime / video.duration));
            }
        };

        await video.play();
        if (drawLoop) drawLoop();
        recorder.start(200);

        await waitEnded(video);

        if (recorder.state !== 'inactive') {
            recorder.stop();
        }
        await stopped;

        stream.getTracks().forEach((track) => track.stop());
        URL.revokeObjectURL(url);

        const baseType = mimeType.split(';')[0];
        const blob = new Blob(chunks, { type: baseType });
        if (!blob.size) {
            throw new Error('ENCODE_FAILED');
        }

        if (blob.size >= file.size && file.size <= MAX_OUTPUT_BYTES) {
            onProgress(1);
            return file;
        }

        if (blob.size > MAX_OUTPUT_BYTES) {
            if (file.size <= MAX_OUTPUT_BYTES) {
                onProgress(1);
                return file;
            }
            throw new Error('STILL_TOO_LARGE');
        }

        const ext = baseType.includes('mp4') ? 'mp4' : 'webm';
        onProgress(1);
        return new File([blob], `community-${Date.now()}.${ext}`, {
            type: baseType,
            lastModified: Date.now(),
        });
    } catch (error) {
        try {
            stream?.getTracks?.().forEach((track) => track.stop());
        } catch {
            // ignore
        }
        URL.revokeObjectURL(url);
        video.pause();

        if (error instanceof Error && [
            'TOO_LARGE',
            'TOO_LONG',
            'UNSUPPORTED',
            'STILL_TOO_LARGE',
            'ENCODE_FAILED',
            'LOAD_FAILED',
            'NOT_VIDEO',
        ].includes(error.message)) {
            throw error;
        }

        if (file.size <= MAX_OUTPUT_BYTES) {
            onProgress(1);
            return file;
        }

        throw new Error('ENCODE_FAILED');
    }
}

export const COMMUNITY_VIDEO_LIMITS = {
    maxOutputBytes: MAX_OUTPUT_BYTES,
    maxInputBytes: MAX_INPUT_BYTES,
    maxDurationSec: MAX_DURATION_SEC,
};
