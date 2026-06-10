<style>
    .property-gallery-dnd .lh-gallery-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.5rem;
    }

    @media (min-width: 640px) {
        .property-gallery-dnd .lh-gallery-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
    }

    @media (min-width: 768px) {
        .property-gallery-dnd .lh-gallery-grid {
            grid-template-columns: repeat(8, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .property-gallery-dnd .lh-gallery-grid {
            grid-template-columns: repeat(10, minmax(0, 1fr));
        }
    }

    .property-gallery-dnd .lh-gallery-item .lh-gallery-thumb {
        aspect-ratio: 1 / 1;
        overflow: hidden;
        border-radius: 0.5rem;
        border: 1px solid rgb(229 231 235);
        background: rgb(249 250 251);
    }

    .dark .property-gallery-dnd .lh-gallery-item .lh-gallery-thumb {
        border-color: rgb(55 65 81);
        background: rgb(17 24 39);
    }

    .property-gallery-dnd .lh-gallery-drag {
        height: 1.25rem;
        width: 1.25rem;
        font-size: 0.5rem;
        line-height: 1;
        left: 0.25rem;
        top: 0.25rem;
        border-radius: 0.375rem;
    }

    .property-gallery-dnd .lh-gallery-remove-existing {
        right: 0.25rem;
        top: 0.25rem;
        padding: 0.125rem 0.375rem;
        font-size: 0.625rem;
        opacity: 0.85;
    }

    @media (min-width: 768px) {
        .property-gallery-dnd .lh-gallery-remove-existing {
            opacity: 0;
        }

        .property-gallery-dnd .lh-gallery-item:hover .lh-gallery-remove-existing {
            opacity: 1;
        }
    }

    .lh-property-gallery-upload.fi-fo-file-upload .filepond--root[data-style-panel-layout='grid'] .filepond--item {
        width: calc(20% - 0.4rem);
    }

    @media (min-width: 640px) {
        .lh-property-gallery-upload.fi-fo-file-upload .filepond--root[data-style-panel-layout='grid'] .filepond--item {
            width: calc(16.666% - 0.4rem);
        }
    }

    @media (min-width: 768px) {
        .lh-property-gallery-upload.fi-fo-file-upload .filepond--root[data-style-panel-layout='grid'] .filepond--item {
            width: calc(12.5% - 0.4rem);
        }
    }

    @media (min-width: 1024px) {
        .lh-property-gallery-upload.fi-fo-file-upload .filepond--root[data-style-panel-layout='grid'] .filepond--item {
            width: calc(10% - 0.4rem);
        }
    }

    .lh-property-gallery-upload.fi-fo-file-upload .filepond--drop-label {
        padding: 0.5rem !important;
    }

    .lh-property-gallery-upload.fi-fo-file-upload .filepond--file-action-button {
        transform: scale(0.85);
    }
</style>
