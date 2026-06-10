@php
    \App\Legacy\LegacyBridge::boot();
    $propertyId = (int) ($propertyId ?? 0);
    $images = is_array($images ?? null) ? $images : [];
@endphp

@include('filament.pages.partials.property-gallery-styles')

<div class="property-gallery-dnd space-y-3">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Trage imaginile pentru a schimba ordinea. Prima poză devine coperta listării.
    </p>

    @if ($images === [])
        <p class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-center text-sm text-gray-500 dark:border-gray-600">
            Nicio imagine încă. Adaugă poze mai jos.
        </p>
    @endif

    <div
        id="lhPropertyGalleryGrid"
        class="lh-gallery-grid"
        wire:key="gallery-grid-{{ md5(json_encode($images)) }}"
    >
        @foreach ($images as $row)
            @php
                $basename = is_array($row) ? trim((string) ($row['basename'] ?? '')) : trim((string) $row);
            @endphp
            @if ($basename === '')
                @continue
            @endif
            <div class="lh-gallery-item lh-gallery-existing group relative" data-basename="{{ $basename }}">
                <button
                    type="button"
                    class="lh-gallery-drag absolute z-20 flex cursor-grab items-center justify-center bg-gray-900/75 text-white shadow active:cursor-grabbing"
                    title="Trage pentru a reordona"
                    aria-label="Reordonează"
                >⋮⋮</button>
                <div class="lh-gallery-thumb">
                    <img
                        src="{{ lh_property_image_url($propertyId, $basename, 'thumb') }}"
                        alt=""
                        class="h-full w-full object-cover"
                        loading="lazy"
                    >
                </div>
                <button
                    type="button"
                    class="lh-gallery-remove-existing absolute rounded-md bg-gray-900/80 font-bold text-white transition hover:bg-red-600"
                    title="Elimină din galerie"
                >✕</button>
            </div>
        @endforeach
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
            (function () {
                function lhInitPropertyGalleryDnd() {
                    var grid = document.getElementById('lhPropertyGalleryGrid');
                    if (!grid || typeof Sortable === 'undefined' || !window.Livewire) {
                        return;
                    }

                    if (grid._lhSortable) {
                        grid._lhSortable.destroy();
                    }

                    var root = grid.closest('[wire\\:id]');
                    var wireId = root ? root.getAttribute('wire:id') : null;
                    var component = wireId ? window.Livewire.find(wireId) : null;
                    if (!component) {
                        return;
                    }

                    grid._lhSortable = new Sortable(grid, {
                        animation: 160,
                        handle: '.lh-gallery-drag',
                        draggable: '.lh-gallery-item',
                        ghostClass: 'opacity-40',
                        onEnd: function () {
                            var order = Array.from(grid.querySelectorAll('.lh-gallery-item'))
                                .map(function (el) { return el.dataset.basename || ''; })
                                .filter(Boolean);
                            component.call('updateExistingImagesOrder', order);
                        }
                    });

                    grid.querySelectorAll('.lh-gallery-remove-existing').forEach(function (btn) {
                        btn.onclick = function (event) {
                            event.preventDefault();
                            var item = btn.closest('.lh-gallery-item');
                            if (!item) {
                                return;
                            }
                            component.call('removeExistingImage', item.dataset.basename || '');
                        };
                    });
                }

                document.addEventListener('livewire:navigated', lhInitPropertyGalleryDnd);
                document.addEventListener('livewire:initialized', function () {
                    lhInitPropertyGalleryDnd();
                    if (window.Livewire && Livewire.hook) {
                        Livewire.hook('morph.updated', function () {
                            lhInitPropertyGalleryDnd();
                        });
                    }
                });
                if (document.readyState !== 'loading') {
                    lhInitPropertyGalleryDnd();
                } else {
                    document.addEventListener('DOMContentLoaded', lhInitPropertyGalleryDnd);
                }
            })();
</script>
