/**
 * Compress property gallery images in the browser before Filament FilePond uploads them.
 */
(function () {
    'use strict';

    var hookedInputs = new WeakSet();

    function lhGalleryChainBeforeAddFile(pond, compressHandler) {
        var existing = pond.beforeAddFile;
        pond.beforeAddFile = function (fileItem) {
            return compressHandler(fileItem).then(function (ok) {
                if (!ok) {
                    return false;
                }
                if (typeof existing === 'function') {
                    return existing(fileItem);
                }
                return true;
            });
        };
    }

    function lhGalleryCompressFileItem(fileItem) {
        if (!fileItem || !(fileItem.file instanceof File)) {
            return Promise.resolve(true);
        }
        if (typeof window.lhGalleryIsAllowedImageFile === 'function' && !window.lhGalleryIsAllowedImageFile(fileItem.file)) {
            return Promise.resolve(true);
        }
        if (typeof window.lhPrepareGalleryFileForUpload !== 'function') {
            return Promise.resolve(true);
        }
        return window.lhPrepareGalleryFileForUpload(fileItem.file).then(function (prepared) {
            if (prepared && prepared instanceof File) {
                fileItem.file = prepared;
                fileItem.fileSize = prepared.size;
                if (typeof fileItem.fileExtension === 'string') {
                    fileItem.fileExtension = 'webp';
                }
            }
            return true;
        });
    }

    function lhHookGalleryFileponds() {
        var hooked = 0;
        if (!window.FilePond || typeof window.FilePond.find !== 'function') {
            return hooked;
        }

        document.querySelectorAll('.lh-property-gallery-upload input[type="file"]').forEach(function (input) {
            if (hookedInputs.has(input)) {
                return;
            }
            var pond = window.FilePond.find(input);
            if (!pond) {
                return;
            }
            hookedInputs.add(input);
            lhGalleryChainBeforeAddFile(pond, lhGalleryCompressFileItem);
            hooked++;
        });

        return hooked;
    }

    var bootAttempts = 0;
    var bootTimer = null;

    function lhGalleryFilamentBoot() {
        var pending = document.querySelectorAll('.lh-property-gallery-upload input[type="file"]').length;
        var hooked = lhHookGalleryFileponds();

        if (pending > 0 && hooked < pending && bootAttempts < 60) {
            bootAttempts++;
            bootTimer = window.setTimeout(lhGalleryFilamentBoot, 500);
        }
    }

    function lhGalleryFilamentRestartBoot() {
        if (bootTimer) {
            window.clearTimeout(bootTimer);
            bootTimer = null;
        }
        bootAttempts = 0;
        lhGalleryFilamentBoot();
    }

    document.addEventListener('livewire:initialized', lhGalleryFilamentRestartBoot);
    document.addEventListener('livewire:navigated', lhGalleryFilamentRestartBoot);
    if (window.Livewire && window.Livewire.hook) {
        window.Livewire.hook('morph.updated', function () {
            lhHookGalleryFileponds();
            lhGalleryFilamentBoot();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', lhGalleryFilamentRestartBoot);
    } else {
        lhGalleryFilamentRestartBoot();
    }
})();
