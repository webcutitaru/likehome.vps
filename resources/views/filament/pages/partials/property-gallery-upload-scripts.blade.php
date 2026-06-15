@php
    $lhGalleryPrepareJsVer = @filemtime(public_path('assets/js/lh-gallery-prepare-upload.js')) ?: 1;
    $lhGalleryFilamentJsVer = @filemtime(public_path('assets/js/admin-property-gallery-filament.js')) ?: 1;
@endphp
<script>
    window.LH_GALLERY_PREPARE_UPLOAD = {
        maxWidth: 1920,
        webpQuality01: 0.88,
    };
</script>
<script src="{{ asset('assets/js/lh-gallery-prepare-upload.js') }}?v={{ $lhGalleryPrepareJsVer }}"></script>
<script src="{{ asset('assets/js/admin-property-gallery-filament.js') }}?v={{ $lhGalleryFilamentJsVer }}"></script>
