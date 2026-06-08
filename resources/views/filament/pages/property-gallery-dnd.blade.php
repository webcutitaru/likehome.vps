@php
    \App\Legacy\LegacyBridge::boot();
    $propertyId = (int) ($propertyId ?? 0);
    $images = is_array($images ?? null) ? $images : [];
@endphp

<div class="property-gallery-dnd space-y-3">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Trage imaginile pentru a schimba ordinea. Prima poză devine coperta listării.
    </p>

    @if ($images === [])
        <p class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-600">
            Nicio imagine încă. Adaugă poze mai jos.
        </p>
    @endif

    <div
        id="lhPropertyGalleryGrid"
        class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
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
                    class="lh-gallery-drag absolute left-2 top-2 z-20 flex h-8 w-8 cursor-grab items-center justify-center rounded-lg bg-gray-900/75 text-xs text-white shadow active:cursor-grabbing"
                    title="Trage pentru a reordona"
                    aria-label="Reordonează"
                >⋮⋮</button>
                <div class="aspect-square overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700">
                    <img
                        src="{{ lh_property_image_url($propertyId, $basename, 'thumb') }}"
                        alt=""
                        class="h-full w-full object-cover"
                        loading="lazy"
                    >
                </div>
                <button
                    type="button"
                    class="lh-gallery-remove-existing absolute right-2 top-2 rounded-lg bg-gray-900/80 px-2 py-1 text-xs font-bold text-white opacity-0 transition group-hover:opacity-100 hover:bg-red-600"
                    title="Elimină din galerie"
                >✕</button>
                <div class="mt-1 truncate text-center text-[10px] font-semibold text-gray-500" title="{{ $basename }}">
                    {{ $basename }}
                </div>
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
