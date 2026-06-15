/**
 * Client-side gallery image prep: downscale to max width + WebP encode before upload.
 * Matches server LH_PROPERTY_IMAGE_MAX_WIDTH / LH_PROPERTY_WEBP_QUALITY.
 */
(function () {
    'use strict';

    function isAllowedImageFile(file) {
        if (!file) return false;
        var t = file.type || '';
        if (t.indexOf('image/') === 0) return true;
        return /\.(jpe?g|png|webp)$/i.test(file.name || '');
    }

    function lhGalleryGetPrepareOpts() {
        var d = typeof window !== 'undefined' ? window.LH_GALLERY_PREPARE_UPLOAD : null;
        var maxW =
            d && typeof d.maxWidth === 'number' && d.maxWidth >= 640 && d.maxWidth <= 8192 ? d.maxWidth : 1920;
        var q =
            d && typeof d.webpQuality01 === 'number' && d.webpQuality01 > 0 && d.webpQuality01 <= 1
                ? d.webpQuality01
                : 0.88;
        return { maxWidth: maxW, webpQuality01: q };
    }

    function lhGalleryFallbackJpeg(canvas, originalFile, quality01) {
        return new Promise(function (resolve) {
            try {
                canvas.toBlob(
                    function (blob) {
                        if (!blob || !originalFile) {
                            resolve(originalFile);
                            return;
                        }
                        var base = (originalFile.name || 'photo').replace(/\.[^/.]+$/, '') || 'photo';
                        resolve(
                            new File([blob], base + '_lh.jpg', {
                                type: 'image/jpeg',
                                lastModified: Date.now(),
                            })
                        );
                    },
                    'image/jpeg',
                    Math.min(quality01, 0.92)
                );
            } catch (e) {
                resolve(originalFile);
            }
        });
    }

    function lhPrepareGalleryFileForUpload(file) {
        return new Promise(function (resolve) {
            if (!file || !isAllowedImageFile(file)) {
                resolve(file);
                return;
            }
            var opts = lhGalleryGetPrepareOpts();
            var img = new Image();
            img.onload = function () {
                try {
                    var w = img.naturalWidth || 0;
                    var h = img.naturalHeight || 0;
                    if (!w || !h) {
                        resolve(file);
                        return;
                    }
                    var tw = w;
                    var th = h;
                    if (w > opts.maxWidth) {
                        tw = opts.maxWidth;
                        th = Math.max(1, Math.round((h * opts.maxWidth) / w));
                    }
                    var canvas = document.createElement('canvas');
                    canvas.width = tw;
                    canvas.height = th;
                    var ctx = canvas.getContext('2d');
                    if (!ctx) {
                        resolve(file);
                        return;
                    }
                    ctx.imageSmoothingEnabled = true;
                    ctx.drawImage(img, 0, 0, tw, th);
                    var q = opts.webpQuality01;
                    canvas.toBlob(
                        function (blob) {
                            if (!blob) {
                                lhGalleryFallbackJpeg(canvas, file, q).then(resolve);
                                return;
                            }
                            var base = (file.name || 'photo').replace(/\.[^/.]+$/, '') || 'photo';
                            resolve(
                                new File([blob], base + '_lh.webp', {
                                    type: 'image/webp',
                                    lastModified: Date.now(),
                                })
                            );
                        },
                        'image/webp',
                        q
                    );
                } catch (e2) {
                    resolve(file);
                }
            };
            img.onerror = function () {
                resolve(file);
            };
            var reader = new FileReader();
            reader.onload = function (ev) {
                var dataUrl = ev && ev.target && ev.target.result ? String(ev.target.result) : '';
                if (!dataUrl) {
                    resolve(file);
                    return;
                }
                img.src = dataUrl;
            };
            reader.onerror = function () {
                resolve(file);
            };
            reader.readAsDataURL(file);
        });
    }

    window.lhPrepareGalleryFileForUpload = lhPrepareGalleryFileForUpload;
    window.lhGalleryIsAllowedImageFile = isAllowedImageFile;
})();
