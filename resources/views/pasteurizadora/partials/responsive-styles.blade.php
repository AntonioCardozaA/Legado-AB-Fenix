<style>
    /* Base responsive compartida para los flujos de Pasteurizadora y Lubricador. */
    .pasteur-index-shell,
    .pasteur-history,
    .pasteur-form-shell,
    .pasteur-edit-page,
    .excentricos-shell {
        width: 100%;
        min-width: 0;
        max-width: 100%;
    }

    .pasteur-index-shell *,
    .pasteur-history *,
    .pasteur-form-shell *,
    .pasteur-edit-page *,
    .excentricos-shell * {
        min-width: 0;
    }

    .pasteur-index-shell img,
    .pasteur-history img,
    .pasteur-form-shell img,
    .pasteur-edit-page img,
    .excentricos-shell img {
        max-width: 100%;
    }

    .pasteur-index-shell .table-wrapper,
    .pasteur-history .overflow-auto,
    .excentricos-shell .excentricos-diagram-body {
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #e2e8f0;
        overscroll-behavior-x: contain;
    }

    .pasteur-index-shell .table-wrapper::-webkit-scrollbar,
    .pasteur-history .overflow-auto::-webkit-scrollbar,
    .excentricos-shell .excentricos-diagram-body::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }

    .pasteur-index-shell .table-wrapper::-webkit-scrollbar-thumb,
    .pasteur-history .overflow-auto::-webkit-scrollbar-thumb,
    .excentricos-shell .excentricos-diagram-body::-webkit-scrollbar-thumb {
        border-radius: 999px;
        background: #94a3b8;
    }

    .pasteur-index-shell .table-wrapper::-webkit-scrollbar-track,
    .pasteur-history .overflow-auto::-webkit-scrollbar-track,
    .excentricos-shell .excentricos-diagram-body::-webkit-scrollbar-track {
        background: #e2e8f0;
    }

    .pasteur-form-shell input:not([type="hidden"]),
    .pasteur-form-shell select,
    .pasteur-form-shell textarea,
    .pasteur-edit-page input:not([type="hidden"]),
    .pasteur-edit-page select,
    .pasteur-edit-page textarea {
        max-width: 100%;
    }

    .pasteur-history .history-card,
    .pasteur-history .history-card > div,
    .pasteur-history .history-card h3,
    .pasteur-history .history-card p,
    .pasteur-history .history-card span {
        overflow-wrap: anywhere;
    }

    .pasteur-history .history-evidence-section,
    .pasteur-history .history-evidence-grid,
    .pasteur-history .history-evidence-grid > div {
        min-width: 0;
        max-width: 100%;
    }

    .pasteur-history .history-evidence-grid > div {
        overflow: hidden;
    }

    @media (hover: none) {
        .pasteur-index-shell .scroll-indicator {
            display: block;
            pointer-events: none;
        }

        .pasteur-history .image-hover-zoom:hover,
        .excentricos-shell .image-grid-enhanced .image-item:hover,
        .excentricos-shell .image-grid-enhanced .image-item:hover .grid-image {
            transform: none;
        }
    }

    @media (max-width: 767px) {
        .pasteur-index-shell,
        .pasteur-history,
        .excentricos-shell {
            padding-inline: 0.75rem !important;
        }

        .pasteur-index-shell .pasteurizadora-card-header {
            padding: 0.85rem 1rem;
        }

        .pasteur-index-shell .pasteurizadora-card-header h3 {
            font-size: 1rem;
            line-height: 1.25;
        }

        .pasteur-index-shell .scroll-indicator {
            bottom: 0.5rem;
            left: 50%;
            right: auto;
            max-width: calc(100% - 1rem);
            transform: translateX(-50%);
            white-space: normal;
            text-align: center;
        }

        .pasteur-history .history-card .status-badge {
            max-width: 100%;
            white-space: normal;
        }

        .pasteur-history .history-evidence-section {
            padding: 0.85rem;
        }

        .pasteur-history .history-evidence-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 0.65rem;
            margin-bottom: 0.85rem;
        }

        .pasteur-history .history-evidence-title {
            max-width: 100%;
        }

        .pasteur-history .history-evidence-header > span {
            align-self: flex-start;
            max-width: 100%;
            white-space: normal;
        }

        .pasteur-edit-page .pasteur-evidence-actions {
            align-items: stretch;
            flex-wrap: wrap;
        }

        .pasteur-edit-page .pasteur-evidence-actions button,
        .pasteur-edit-page .pasteur-evidence-actions label,
        .pasteur-edit-page .pasteur-evidence-actions span {
            flex: 1 1 100%;
            justify-content: center;
            max-width: 100%;
            white-space: normal;
        }

        .pasteur-form-shell .pasteur-context,
        .pasteur-edit-page .pasteur-context {
            padding: 0.85rem;
        }

        .pasteur-form-shell .pasteur-context > .flex,
        .pasteur-edit-page .pasteur-context > .flex {
            align-items: flex-start;
        }

        .pasteur-history .modal-fade-in,
        .pasteur-history #imageModalStage,
        .pasteur-history #galleryModal > div,
        #photoPreviewModal > div,
        #analysisDetailModal > div,
        #estadoModal > div,
        #allImagesModal > div,
        #singleImageModal > div {
            max-height: calc(100dvh - 1rem);
        }

        .pasteur-history #modalCaption,
        #modalCaption {
            max-width: calc(100vw - 2rem);
            white-space: normal;
            line-height: 1.25;
        }
    }

    @media (max-width: 360px) {
        .excentricos-shell .excentricos-selector-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .excentricos-shell .excentricos-selector-btn {
            min-height: 2.5rem;
            padding-inline: 0.35rem;
        }

        .excentricos-shell .excentricos-module-grid {
            grid-template-columns: 1fr;
        }

        .pasteur-history .history-evidence-grid {
            grid-template-columns: 1fr;
        }

        .pasteur-history .history-evidence-grid img {
            height: auto !important;
            aspect-ratio: 4 / 3;
        }

        .pasteur-index-shell .filters-section {
            padding-inline: 0.75rem;
        }
    }
</style>
