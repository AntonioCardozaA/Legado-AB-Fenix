@once
<style>
    :root {
        --primary-blue: #3b82f6;
        --success-green: #10b981;
        --warning-yellow: #f59e0b;
        --danger-red: #ef4444;
        --changed-blue: #3b82f6;
        --light-gray: #f9fafb;
        --medium-gray: #e5e7eb;
        --dark-gray: #6b7280;
    }

    .sticky-top { position: sticky; top: 0; z-index: 30; }
    .sticky-left { position: sticky; left: 0; z-index: 20; }
    .sticky-top-left { position: sticky; top: 0; left: 0; z-index: 40; }
    .cell-ok { background-color: #f0f9ff; border-left: 4px solid var(--success-green); }
    .cell-review { background-color: #fefce8; border-left: 4px solid var(--warning-yellow); }
    .cell-warning { background-color: #fff7ed; border-left: 4px solid #f97316; }
    .cell-danger { background-color: #fef2f2; border-left: 4px solid var(--danger-red); }
    .cell-changed { background-color: #eff6ff; border-left: 4px solid var(--changed-blue); }
    .cell-empty { background-color: var(--light-gray); }
    .cell-header { background-color: #eff6ff; }

    .compact-table td,
    .compact-table th {
        padding: 8px !important;
        font-size: 0.75rem !important;
        min-width: 120px;
    }

    .filters-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
        width: 100%;
        max-width: 100%;
    }

    .lineas-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    .lineas-title i {
        color: #3b82f6;
        font-size: 16px;
    }

    .lineas-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .linea-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        max-width: 100%;
        padding: 8px 20px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        overflow-wrap: anywhere;
        text-align: center;
    }

    .linea-item i {
        font-size: 14px;
        color: #94a3b8;
    }

    .linea-item:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-1px);
    }

    .linea-item.active {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
    }

    .linea-item.active i,
    .linea-item.active .etq-presentations-names {
        color: white;
    }

    .filters-divider {
        margin: 24px 0 16px 0;
        border-top: 2px solid #f1f5f9;
    }

    .filters-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }

    .filter-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }

    .filter-link:hover,
    .filter-link.active {
        background: #f8fafc;
        color: #2563eb;
        font-weight: 600;
    }

    .btn-apply {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 28px;
        background: #2563eb;
        color: white;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 40px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-left: auto;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }

    .btn-apply:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 6px 10px -1px rgba(37, 99, 235, 0.3);
    }

    .btn-clear {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        background: white;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-clear:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #475569;
    }

    .advanced-filters-panel {
        margin-top: 20px;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
        display: none;
        border: 1px solid #e2e8f0;
    }

    .advanced-filters-panel.show {
        display: block;
    }

    .advanced-filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        min-width: 0;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-select,
    .filter-input {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        color: #1e293b;
        background: white;
        transition: all 0.2s ease;
    }

    .filter-select:focus,
    .filter-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(16, 83, 192, 0.1);
    }

    .table-header-container,
    .lavadora-card-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
    }

    .table-header-container {
        padding: 16px 20px;
        border-radius: 12px 12px 0 0;
    }

    .lavadoras-section {
        margin-top: 30px;
    }

    .lavadora-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        border: 1px solid #e2e8f0;
        width: 100%;
        max-width: 100%;
    }

    .lavadora-card-header {
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
    }

    .lavadora-card-header > * {
        min-width: 0;
    }

    .lavadora-card-header h3 {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
        overflow-wrap: anywhere;
    }

    .lavadora-card-header .badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
    }

    .etq-process-diagram {
        --etq-process-duration: 12s;
        background:
            linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(241, 245, 249, 0.96)),
            repeating-linear-gradient(90deg, rgba(15, 23, 42, 0.04) 0 1px, transparent 1px 18px);
        border-top: 1px solid rgba(226, 232, 240, 0.9);
        padding: 18px 20px 20px;
    }

    .etq-process-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }

    .etq-process-title-block {
        min-width: 0;
    }

    .etq-process-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .etq-process-kicker i {
        color: #f59e0b;
    }

    .etq-process-title-block h4 {
        margin: 5px 0 0;
        color: #0f172a;
        font-size: 18px;
        font-weight: 900;
        line-height: 1.15;
        overflow-wrap: anywhere;
    }

    .etq-process-tags,
    .etq-process-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        min-width: 0;
    }

    .etq-process-tags {
        justify-content: flex-end;
    }

    .etq-process-tags span,
    .etq-process-legend span {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        max-width: 100%;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.88);
        color: #334155;
        padding: 7px 10px;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.2;
        overflow-wrap: anywhere;
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06);
    }

    .etq-process-tags i,
    .etq-process-legend i {
        color: #2563eb;
        flex: 0 0 auto;
    }

    .etq-process-canvas {
        --bottle-width: clamp(34px, 5.4vw, 54px);
        --bottle-height: clamp(82px, 12.5vw, 124px);
        position: relative;
        isolation: isolate;
        min-height: clamp(218px, 24vw, 285px);
        overflow: hidden;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        background:
            radial-gradient(circle at 12% 20%, rgba(255, 255, 255, 0.95), transparent 22%),
            linear-gradient(180deg, #f8fafc 0%, #e2e8f0 56%, #cbd5e1 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .etq-process-canvas::before {
        content: "";
        position: absolute;
        inset: auto 0 0;
        height: 34%;
        background:
            linear-gradient(180deg, rgba(148, 163, 184, 0.14), rgba(71, 85, 105, 0.22)),
            repeating-linear-gradient(90deg, rgba(15, 23, 42, 0.08) 0 1px, transparent 1px 26px);
        z-index: 0;
    }

    .etq-process-belt {
        position: absolute;
        left: 4%;
        right: 4%;
        bottom: 48px;
        height: clamp(42px, 5.8vw, 64px);
        overflow: hidden;
        border: 1px solid #64748b;
        border-radius: 999px;
        background:
            linear-gradient(180deg, #111827 0%, #020617 46%, #334155 48%, #94a3b8 50%, #475569 52%, #1f2937 100%);
        box-shadow:
            inset 0 12px 18px rgba(255, 255, 255, 0.08),
            0 18px 24px rgba(15, 23, 42, 0.18);
        z-index: 1;
    }

    .etq-process-belt::before {
        content: "";
        position: absolute;
        inset: 5px 20px auto;
        height: 46%;
        border-radius: 999px;
        background:
            repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.08) 0 7px, transparent 7px 14px),
            repeating-linear-gradient(0deg, rgba(255, 255, 255, 0.06) 0 2px, transparent 2px 8px),
            #0f172a;
        background-size: 84px 100%, 100% 16px, auto;
        animation: etqBeltMove 2.5s linear infinite;
    }

    .etq-process-belt::after {
        content: "";
        position: absolute;
        left: 24px;
        right: 24px;
        bottom: 9px;
        height: 11px;
        border-radius: 999px;
        background:
            repeating-linear-gradient(90deg, #e2e8f0 0 22px, #94a3b8 22px 29px),
            linear-gradient(180deg, #f8fafc, #94a3b8);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
    }

    .etq-process-arrow {
        position: absolute;
        bottom: 128px;
        width: min(16vw, 148px);
        height: 2px;
        background: linear-gradient(90deg, transparent, #2563eb);
        z-index: 2;
    }

    .etq-process-arrow::after {
        content: "";
        position: absolute;
        top: 50%;
        right: -2px;
        width: 0;
        height: 0;
        transform: translateY(-50%);
        border-block: 6px solid transparent;
        border-left: 10px solid #2563eb;
    }

    .etq-process-arrow--in {
        left: 20%;
    }

    .etq-process-arrow--out {
        right: 20%;
    }

    .etq-process-station {
        position: absolute;
        bottom: 52px;
        display: flex;
        width: 35%;
        flex-direction: column;
        align-items: stretch;
        gap: 9px;
        z-index: 3;
    }

    .etq-process-station-track {
        display: flex;
        min-height: calc(var(--bottle-height) * 1.55);
        align-items: flex-end;
        justify-content: space-evenly;
        gap: clamp(4px, 1vw, 14px);
    }

    .etq-process-station > span {
        align-self: center;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.88);
        color: #334155;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 900;
        line-height: 1.1;
        text-align: center;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
    }

    .etq-process-station--entrada {
        left: 4%;
    }

    .etq-process-station--salida {
        right: 4%;
    }

    .etq-process-station--marker {
        bottom: 30px;
        width: auto;
        max-width: 28%;
        align-items: center;
        pointer-events: none;
        z-index: 8;
    }

    .etq-process-station--marker > span {
        border: 1px solid rgba(203, 213, 225, 0.9);
        background: rgba(255, 255, 255, 0.82);
        color: #475569;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }

    .etq-process-bottle {
        --etq-cap-top: #f8fafc;
        --etq-cap-bottom: #64748b;
        --etq-neck-highlight: rgba(255, 255, 255, 0.78);
        --etq-neck-mid: rgba(180, 83, 9, 0.72);
        --etq-neck-dark: rgba(92, 45, 12, 0.94);
        --etq-body-highlight: rgba(255, 255, 255, 0.58);
        --etq-body-shine: rgba(255, 255, 255, 0.24);
        --etq-body-mid: rgba(180, 83, 9, 0.82);
        --etq-body-dark: #7c2d12;
        --etq-body-border: rgba(15, 23, 42, 0.23);
        position: relative;
        display: flex;
        width: calc(var(--bottle-width) * var(--etq-bottle-scale, 1));
        height: calc(var(--bottle-height) * var(--etq-bottle-scale, 1));
        flex: 0 0 auto;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        filter: drop-shadow(0 10px 8px rgba(15, 23, 42, 0.24));
    }

    .etq-process-bottle--photo {
        width: calc(var(--bottle-width) * 1.6 * var(--etq-bottle-scale, 1));
        height: calc(var(--bottle-height) * 1.34 * var(--etq-bottle-scale, 1));
        justify-content: center;
        filter:
            drop-shadow(0 14px 10px rgba(15, 23, 42, 0.24))
            drop-shadow(0 2px 1px rgba(15, 23, 42, 0.22));
    }

    .etq-process-bottle-photo-shell {
        position: relative;
        display: block;
        width: 100%;
        height: 100%;
        transform: translateY(10px);
    }

    .etq-process-bottle-photo-base,
    .etq-process-bottle-photo-labeled {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        user-select: none;
    }

    .etq-process-bottle-photo-base {
        position: relative;
        z-index: 1;
    }

    .etq-process-bottle-photo-base--under-labeled:not(.etq-process-bottle-photo-base--animated) {
        opacity: 0;
    }

    .etq-process-bottle-photo-base--animated {
        animation: etqBottlePhotoBaseHide var(--etq-process-duration, 12s) ease-in-out infinite;
        animation-delay: var(--etq-delay, 0s);
        animation-fill-mode: both;
    }

    .etq-process-bottle-photo-labeled {
        position: absolute;
        inset: 0;
        z-index: 2;
        transform:
            translate(var(--etq-labeled-bottle-x, 0px), var(--etq-labeled-bottle-y, 0px))
            scale(var(--etq-labeled-bottle-scale, 1));
        transform-origin: center bottom;
        filter:
            saturate(1.05)
            drop-shadow(0 12px 10px rgba(15, 23, 42, 0.18));
    }

    .etq-process-bottle-photo-labeled--animated {
        opacity: 0;
        animation: etqBottlePhotoReveal var(--etq-process-duration, 12s) ease-in-out infinite;
        animation-delay: var(--etq-delay, 0s);
        animation-fill-mode: both;
    }

    .etq-process-photo-label {
        position: absolute;
        left: var(--etq-photo-label-left, 50%);
        bottom: var(--etq-photo-label-bottom, 20%);
        z-index: 2;
        display: flex;
        width: var(--etq-photo-label-width, 56%);
        height: var(--etq-photo-label-height, 34%);
        align-items: center;
        justify-content: center;
        overflow: hidden;
        transform:
            translateX(-50%)
            translateY(var(--etq-photo-label-translate-y, 0px))
            perspective(240px)
            rotateY(var(--etq-photo-label-rotate-y, 0deg))
            scaleX(var(--etq-photo-label-scale-x, 0.9));
        transform-origin: center;
        border-radius: 50% / 13%;
        background: rgba(255, 255, 255, 0.94);
        clip-path: polygon(
            var(--etq-photo-label-curve-inset, 8%) 0%,
            calc(100% - var(--etq-photo-label-curve-inset, 8%)) 0%,
            97% 9%,
            100% 50%,
            97% 91%,
            calc(100% - var(--etq-photo-label-curve-inset, 8%)) 100%,
            var(--etq-photo-label-curve-inset, 8%) 100%,
            3% 91%,
            0% 50%,
            3% 9%
        );
        box-shadow:
            inset 12px 0 15px rgba(255, 255, 255, 0.22),
            inset -13px 0 18px rgba(15, 23, 42, 0.28),
            0 2px 4px rgba(15, 23, 42, 0.22);
        filter:
            drop-shadow(0 2px 3px rgba(15, 23, 42, 0.28))
            saturate(1.04);
    }

    .etq-process-photo-label::before {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 2;
        border-radius: inherit;
        background:
            linear-gradient(90deg, rgba(24, 12, 5, 0.40), rgba(24, 12, 5, 0.10) 13%, transparent 28% 72%, rgba(24, 12, 5, 0.12) 87%, rgba(24, 12, 5, 0.42)),
            radial-gradient(ellipse at 50% 50%, transparent 46%, rgba(15, 23, 42, 0.18) 100%);
        pointer-events: none;
        mix-blend-mode: multiply;
        opacity: 0.68;
    }

    .etq-process-photo-label::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 3;
        border-radius: inherit;
        background:
            linear-gradient(90deg, rgba(255, 255, 255, 0.18), transparent 17% 43%, rgba(255, 255, 255, 0.24) 50%, transparent 61% 86%, rgba(255, 255, 255, 0.12)),
            linear-gradient(180deg, rgba(255, 255, 255, 0.18), transparent 26%, rgba(15, 23, 42, 0.10));
        pointer-events: none;
        mix-blend-mode: screen;
        opacity: 0.75;
    }

    .etq-process-photo-label img {
        position: relative;
        z-index: 1;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: var(--etq-photo-label-fit, fill);
    }

    .etq-process-photo-label span {
        position: relative;
        z-index: 1;
        display: flex;
        width: 100%;
        height: 100%;
        align-items: center;
        justify-content: center;
        border-radius: inherit;
        background: #fff;
        color: #0f172a;
        font-size: 9px;
        font-weight: 900;
    }

    .etq-process-bottle-cap {
        width: 36%;
        height: 8%;
        border: 1px solid rgba(15, 23, 42, 0.3);
        border-radius: 5px 5px 2px 2px;
        background: linear-gradient(180deg, var(--etq-cap-top), var(--etq-cap-bottom));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
    }

    .etq-process-bottle-neck {
        width: 31%;
        height: 23%;
        border: 1px solid var(--etq-body-border);
        border-bottom: 0;
        border-radius: 7px 7px 2px 2px;
        background:
            linear-gradient(115deg, var(--etq-neck-highlight), var(--etq-neck-mid) 42%, var(--etq-neck-dark)),
            var(--etq-body-mid);
    }

    .etq-process-bottle-body {
        position: relative;
        display: flex;
        width: 84%;
        height: 69%;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid var(--etq-body-border);
        border-radius: 11px 11px 15px 15px;
        background:
            radial-gradient(circle at 30% 16%, var(--etq-body-highlight), transparent 25%),
            linear-gradient(120deg, var(--etq-body-shine), var(--etq-body-mid) 42%, var(--etq-body-dark));
        box-shadow:
            inset -8px 0 12px rgba(15, 23, 42, 0.16),
            inset 4px 0 8px rgba(255, 255, 255, 0.16);
    }

    .etq-process-bottle-body::after {
        content: "";
        position: absolute;
        inset: 8% auto 10% 18%;
        width: 10%;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.38);
    }

    .etq-process-bottle--empty .etq-process-bottle-body {
        opacity: 0.86;
    }

    .etq-process-bottle--clear {
        --etq-neck-highlight: rgba(255, 255, 255, 0.92);
        --etq-neck-mid: rgba(224, 242, 254, 0.48);
        --etq-neck-dark: rgba(125, 211, 252, 0.36);
        --etq-body-highlight: rgba(255, 255, 255, 0.9);
        --etq-body-shine: rgba(255, 255, 255, 0.68);
        --etq-body-mid: rgba(224, 242, 254, 0.44);
        --etq-body-dark: rgba(56, 189, 248, 0.28);
        --etq-body-border: rgba(59, 130, 246, 0.22);
    }

    .etq-process-bottle--amber {
        --etq-neck-highlight: rgba(255, 255, 255, 0.56);
        --etq-neck-mid: rgba(217, 119, 6, 0.82);
        --etq-neck-dark: rgba(92, 45, 12, 0.96);
        --etq-body-highlight: rgba(255, 255, 255, 0.5);
        --etq-body-shine: rgba(255, 255, 255, 0.20);
        --etq-body-mid: rgba(180, 83, 9, 0.9);
        --etq-body-dark: #5f2d0c;
        --etq-body-border: rgba(92, 45, 12, 0.36);
    }

    .etq-process-bottle--dark {
        --etq-neck-highlight: rgba(255, 255, 255, 0.32);
        --etq-neck-mid: rgba(68, 36, 20, 0.92);
        --etq-neck-dark: #140b06;
        --etq-body-highlight: rgba(255, 255, 255, 0.28);
        --etq-body-shine: rgba(255, 255, 255, 0.12);
        --etq-body-mid: rgba(49, 27, 18, 0.96);
        --etq-body-dark: #090503;
        --etq-body-border: rgba(15, 23, 42, 0.42);
    }

    .etq-process-bottle--silver-clear {
        --etq-neck-highlight: rgba(255, 255, 255, 0.96);
        --etq-neck-mid: rgba(226, 232, 240, 0.58);
        --etq-neck-dark: rgba(148, 163, 184, 0.42);
        --etq-body-highlight: rgba(255, 255, 255, 0.94);
        --etq-body-shine: rgba(255, 255, 255, 0.72);
        --etq-body-mid: rgba(226, 232, 240, 0.54);
        --etq-body-dark: rgba(96, 165, 250, 0.18);
        --etq-body-border: rgba(100, 116, 139, 0.25);
    }

    .etq-process-bottle--cap-gold {
        --etq-cap-top: #fef3c7;
        --etq-cap-bottom: #d97706;
    }

    .etq-process-bottle--cap-silver {
        --etq-cap-top: #f8fafc;
        --etq-cap-bottom: #94a3b8;
    }

    .etq-process-bottle--cap-blue {
        --etq-cap-top: #bfdbfe;
        --etq-cap-bottom: #1d4ed8;
    }

    .etq-process-bottle--cap-black {
        --etq-cap-top: #64748b;
        --etq-cap-bottom: #020617;
    }

    .etq-process-bottle--mega .etq-process-bottle-body {
        width: 88%;
        height: 71%;
        border-radius: 13px 13px 19px 19px;
    }

    .etq-process-bottle--mega .etq-process-bottle-neck {
        width: 32%;
        height: 21%;
    }

    .etq-process-bottle--standard .etq-process-bottle-body,
    .etq-process-bottle--longneck .etq-process-bottle-body {
        border-radius: 11px 11px 15px 15px;
    }

    .etq-process-bottle--stubby .etq-process-bottle-cap,
    .etq-process-bottle--modelito .etq-process-bottle-cap {
        width: 41%;
    }

    .etq-process-bottle--stubby .etq-process-bottle-neck {
        width: 39%;
        height: 17%;
        border-radius: 8px 8px 3px 3px;
    }

    .etq-process-bottle--stubby .etq-process-bottle-body {
        width: 91%;
        height: 75%;
        border-radius: 18px 18px 17px 17px;
    }

    .etq-process-bottle--modelito .etq-process-bottle-neck {
        width: 38%;
        height: 19%;
    }

    .etq-process-bottle--modelito .etq-process-bottle-body {
        width: 88%;
        height: 73%;
        border-radius: 15px 15px 16px 16px;
    }

    .etq-process-bottle--slim .etq-process-bottle-cap {
        width: 34%;
    }

    .etq-process-bottle--slim .etq-process-bottle-neck {
        width: 27%;
        height: 25%;
    }

    .etq-process-bottle--slim .etq-process-bottle-body {
        width: 72%;
        height: 67%;
        border-radius: 10px 10px 15px 15px;
    }

    .etq-process-bottle-label {
        position: relative;
        z-index: 2;
        display: flex;
        width: 76%;
        min-height: 31%;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1px;
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, 0.13);
        border-radius: 5px;
        background: rgba(255, 255, 255, 0.94);
        color: #0f172a;
        font-size: 8px;
        font-weight: 900;
        line-height: 1;
        text-align: center;
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.86);
    }

    .etq-process-bottle-label img {
        width: 92%;
        max-height: 27px;
        object-fit: contain;
    }

    .etq-process-bottle-label span {
        display: block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: clip;
        white-space: nowrap;
    }

    .etq-process-bottle--moving {
        position: absolute;
        left: 10%;
        bottom: 80px;
        opacity: 0;
        transform: translateX(-50%);
        z-index: 4;
        animation: etqBottleMove var(--etq-process-duration, 12s) ease-in-out infinite;
        animation-delay: var(--etq-delay, 0s);
        animation-fill-mode: both;
    }

    .etq-process-bottle-label--animated {
        opacity: 0;
        animation: etqLabelReveal var(--etq-process-duration, 12s) ease-in-out infinite;
        animation-delay: var(--etq-delay, 0s);
        animation-fill-mode: both;
    }

    .etq-process-machine {
        position: absolute;
        left: 50%;
        bottom: 55px;
        width: clamp(146px, 28vw, 270px);
        height: clamp(142px, 16.5vw, 180px);
        transform: translateX(-50%);
        border: 1px solid #64748b;
        border-radius: 10px;
        background:
            linear-gradient(90deg, #64748b 0 12%, transparent 12% 88%, #475569 88% 100%),
            linear-gradient(180deg, #e5e7eb 0%, #cbd5e1 12%, #94a3b8 13%, #e2e8f0 100%);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.72),
            0 18px 26px rgba(15, 23, 42, 0.2);
        z-index: 7;
    }

    .etq-process-machine::before,
    .etq-process-machine::after {
        content: "";
        position: absolute;
        top: 23%;
        bottom: 19%;
        width: 10px;
        border-radius: 999px;
        background: #1f2937;
    }

    .etq-process-machine::before {
        left: 17%;
    }

    .etq-process-machine::after {
        right: 17%;
    }

    .etq-process-machine-top {
        position: absolute;
        left: 10%;
        right: 10%;
        top: 9px;
        display: flex;
        justify-content: space-between;
        gap: 7px;
    }

    .etq-process-machine-top span {
        height: 8px;
        flex: 1 1 0;
        border-radius: 999px;
        background: linear-gradient(180deg, #f8fafc, #94a3b8);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .etq-process-machine-window {
        position: absolute;
        left: 8%;
        right: 8%;
        top: 24px;
        bottom: 20px;
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, 0.18);
        border-radius: 8px;
        background:
            linear-gradient(120deg, rgba(255, 255, 255, 0.62), rgba(219, 234, 254, 0.72) 38%, rgba(148, 163, 184, 0.7)),
            #dbeafe;
    }

    .etq-process-machine-window::before {
        content: "";
        position: absolute;
        inset: auto 0 0;
        height: 43%;
        background: rgba(15, 23, 42, 0.72);
    }

    .etq-process-machine-roller {
        position: absolute;
        bottom: 17%;
        width: 22px;
        height: 22px;
        border: 4px solid #1f2937;
        border-radius: 999px;
        background: #e2e8f0;
        animation: etqRollerSpin 1.1s linear infinite;
    }

    .etq-process-machine-roller--left {
        left: 21%;
    }

    .etq-process-machine-roller--right {
        right: 21%;
    }

    .etq-process-label-head {
        --etq-label-slot-height: 62px;
        position: absolute;
        left: 50%;
        top: 8%;
        display: flex;
        width: min(calc(100% - 16px), clamp(154px, 16vw, 202px));
        height: 76px;
        align-items: center;
        justify-content: center;
        transform: translateX(-50%);
        border: 1px solid #bfdbfe;
        border-radius: 22px;
        background:
            radial-gradient(circle at 50% 50%, #ffffff 0 42%, rgba(239, 246, 255, 0.98) 43% 100%),
            #ffffff;
        color: #2563eb;
        box-shadow:
            0 0 0 9px rgba(59, 130, 246, 0.12),
            0 8px 18px rgba(15, 23, 42, 0.16);
        animation: etqLabelPulse 1.8s ease-in-out infinite;
        box-sizing: border-box;
        padding: 7px;
        overflow: hidden;
        z-index: 3;
    }

    .etq-process-label-head-stack {
        display: grid;
        width: 100%;
        height: 100%;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: minmax(0, 1fr);
        align-items: stretch;
        justify-items: stretch;
        gap: 4px;
    }

    .etq-process-label-head-item {
        display: flex;
        width: 100%;
        height: 100%;
        min-width: 0;
        min-height: 0;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 5px;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.94)),
            #fff;
        box-sizing: border-box;
        overflow: hidden;
        padding: 3px;
        box-shadow:
            0 4px 8px rgba(15, 23, 42, 0.20),
            inset 0 1px 0 rgba(255, 255, 255, 0.75);
    }

    .etq-process-label-head-image {
        display: block;
        width: 100%;
        height: 100%;
        min-width: 0;
        min-height: 0;
        object-fit: contain;
        object-position: center;
        filter: brightness(1.12) contrast(1.16) saturate(1.15);
    }

    .etq-process-label-head-item:nth-child(odd),
    .etq-process-label-head-item:nth-child(even) {
        transform: none;
    }

    .etq-process-label-head-item i {
        color: #1e293b;
        font-size: 9px;
        font-weight: 900;
    }

    .etq-process-label-head[data-etq-label-count="1"] {
        --etq-label-slot-height: 56px;
        width: min(calc(100% - 24px), clamp(112px, 11vw, 136px));
        height: 70px;
    }

    .etq-process-label-head[data-etq-label-count="1"] .etq-process-label-head-stack {
        grid-template-columns: minmax(0, 1fr);
        grid-template-rows: minmax(0, 1fr);
    }

    .etq-process-label-head[data-etq-label-count="1"] .etq-process-label-head-image,
    .etq-process-label-head[data-etq-label-count="1"] .etq-process-label-head-item {
        height: 100%;
    }

    .etq-process-label-head[data-etq-label-count="2"] {
        --etq-label-slot-height: 62px;
        width: min(calc(100% - 16px), clamp(154px, 16vw, 202px));
        height: 76px;
    }

    .etq-process-label-head[data-etq-label-count="2"] .etq-process-label-head-stack {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: minmax(0, 1fr);
    }

    .etq-process-label-head[data-etq-label-count="3"] {
        --etq-label-slot-height: 62px;
        width: min(calc(100% - 12px), clamp(184px, 18vw, 218px));
        height: 76px;
        border-radius: 26px;
    }

    .etq-process-label-head[data-etq-label-count="3"] .etq-process-label-head-stack {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        grid-template-rows: minmax(0, 1fr);
        gap: 5px;
    }

    .etq-process-label-head[data-etq-label-count="3"] .etq-process-label-head-image,
    .etq-process-label-head[data-etq-label-count="3"] .etq-process-label-head-item {
        height: 100%;
    }

    .etq-process-label-head[data-etq-label-count="4"] {
        --etq-label-slot-height: 38px;
        width: min(calc(100% - 12px), clamp(166px, 17vw, 208px));
        height: 88px;
        border-radius: 26px;
    }

    .etq-process-label-head[data-etq-label-count="4"] .etq-process-label-head-stack {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: repeat(2, minmax(0, 1fr));
        align-content: center;
        gap: 4px;
    }

    .etq-process-label-head[data-etq-label-count="4"] .etq-process-label-head-image,
    .etq-process-label-head[data-etq-label-count="4"] .etq-process-label-head-item {
        height: 100%;
    }

    .etq-process-label-head.etq-process-label-head--carousel {
        --etq-label-slot-height: 76px;
        width: min(calc(100% - 10px), clamp(172px, 18vw, 222px));
        height: 94px;
        border-radius: 24px;
        padding: 8px 10px;
    }

    .etq-process-label-head.etq-process-label-head--carousel .etq-process-label-head-stack {
        position: relative;
        display: block;
        width: 100%;
        height: 100%;
        grid-template-columns: none;
        grid-template-rows: none;
        gap: 0;
    }

    .etq-process-label-head.etq-process-label-head--carousel .etq-process-label-head-item {
        position: absolute;
        inset: 0;
        opacity: 0;
        height: 100%;
        padding: 5px 8px;
        transform: translateX(16%) scale(0.96);
        animation: etqLabelSlide2 var(--etq-carousel-duration, 4.8s) ease-in-out infinite;
        animation-delay: var(--etq-label-delay, 0s);
        will-change: opacity, transform;
    }

    .etq-process-label-head.etq-process-label-head--carousel[data-etq-label-count="3"] .etq-process-label-head-item {
        animation-name: etqLabelSlide3;
    }

    .etq-process-label-head.etq-process-label-head--carousel[data-etq-label-count="4"] .etq-process-label-head-item {
        animation-name: etqLabelSlide4;
    }

    .etq-process-label-head-stack i,
    .etq-process-photo-label i,
    .etq-process-bottle-label i {
        color: #2563eb;
        font-size: 13px;
        line-height: 1;
    }

    .etq-process-machine-base {
        position: absolute;
        left: 8%;
        right: 8%;
        bottom: 10px;
        height: 12px;
        border-radius: 999px;
        background: linear-gradient(90deg, #475569, #e2e8f0, #475569);
    }

    .etq-process-legend {
        margin-top: 12px;
    }

    .etq-process-legend span {
        border-color: #e2e8f0;
        background: #fff;
        box-shadow: none;
    }

    .etq-process-legend span:first-child i {
        color: #0f766e;
    }

    .etq-process-legend span:nth-child(3) i {
        color: #f59e0b;
    }

    .etq-process-legend span:last-child i {
        color: #10b981;
    }

    @keyframes etqBeltMove {
        from { background-position: 0 0, 0 0, 0 0; }
        to { background-position: 84px 0, 0 0, 0 0; }
    }

    @keyframes etqBottleMove {
        0% { left: 10%; opacity: 0; transform: translateX(-50%) scale(0.96); }
        7% { opacity: 1; transform: translateX(-50%) scale(1); }
        31% { left: 35%; opacity: 1; }
        42% { left: 45%; opacity: 0.18; }
        50% { left: 50%; opacity: 0; }
        57% { left: 55%; opacity: 0.18; }
        66% { left: 65%; opacity: 1; }
        92% { left: 90%; opacity: 1; transform: translateX(-50%) scale(1); }
        100% { left: 94%; opacity: 0; transform: translateX(-50%) scale(0.96); }
    }

    @keyframes etqLabelReveal {
        0%, 57% { opacity: 0; }
        66%, 100% { opacity: 1; }
    }

    @keyframes etqBottlePhotoReveal {
        0%, 57% { opacity: 0; }
        66%, 100% { opacity: 1; }
    }

    @keyframes etqBottlePhotoBaseHide {
        0%, 57% { opacity: 1; }
        66%, 100% { opacity: 0; }
    }

    @keyframes etqRollerSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @keyframes etqLabelPulse {
        0%, 100% { box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.10); }
        50% { box-shadow: 0 0 0 13px rgba(245, 158, 11, 0.18); }
    }

    @keyframes etqLabelSlide2 {
        0%, 38% {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
        48%, 100% {
            opacity: 0;
            transform: translateX(-18%) scale(0.96);
        }
    }

    @keyframes etqLabelSlide3 {
        0%, 26% {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
        34%, 100% {
            opacity: 0;
            transform: translateX(-18%) scale(0.96);
        }
    }

    @keyframes etqLabelSlide4 {
        0%, 20% {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
        27%, 100% {
            opacity: 0;
            transform: translateX(-18%) scale(0.96);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .etq-process-belt::before,
        .etq-process-bottle--moving,
        .etq-process-bottle-photo-base--animated,
        .etq-process-bottle-photo-labeled--animated,
        .etq-process-bottle-label--animated,
        .etq-process-label-head--carousel .etq-process-label-head-item,
        .etq-process-machine-roller,
        .etq-process-label-head {
            animation-duration: 0.01ms;
            animation-iteration-count: 1;
        }

        .etq-process-label-head--carousel .etq-process-label-head-item {
            opacity: 0;
            transform: none;
        }

        .etq-process-label-head--carousel .etq-process-label-head-item:first-child {
            opacity: 1;
        }

        .etq-process-bottle--moving {
            left: 66%;
            opacity: 1;
        }

        .etq-process-bottle-label--animated {
            opacity: 1;
        }

        .etq-process-bottle-photo-base--animated {
            opacity: 0;
        }

        .etq-process-bottle-photo-labeled--animated {
            opacity: 1;
        }
    }

    .table-wrapper {
        position: relative;
        overflow: auto;
        border: 1px solid var(--medium-gray);
        border-radius: 8px;
        max-height: 650px;
        width: 100%;
        max-width: 100%;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
    }

    .lavadora-card .table-wrapper {
        border-radius: 0;
        border: none;
        border-top: 1px solid #e2e8f0;
    }

    .table-wrapper > table,
    .compact-table {
        width: max-content;
        min-width: 100%;
    }

    .table-corner {
        background: #eff6ff;
        border-right: 1px solid #dbeafe;
        border-bottom: 1px solid #dbeafe;
    }

    .scroll-indicator {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        z-index: 50;
        display: none;
    }

    .table-wrapper:hover .scroll-indicator {
        display: block;
    }

    .component-header,
    .reductor-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 4px;
    }

    .component-name,
    .reductor-name {
        font-weight: 600;
        color: #1e40af;
        font-size: 11px;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .component-code,
    .reductor-label {
        font-size: 9px;
        color: var(--dark-gray);
        background: #f3f4f6;
        padding: 2px 4px;
        border-radius: 3px;
    }

    .component-industrial-icon {
        width: 4.5rem;
        height: 4.5rem;
        margin-top: 0.35rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, #eef2ff, #eff6ff);
        color: #2563eb;
        border: 1px solid #dbeafe;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        transition: transform 0.2s ease;
    }

    .component-industrial-icon:hover {
        transform: scale(1.06);
    }

    .analysis-cell {
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        min-height: 120px;
    }

    .analysis-cell:not(.no-data):hover {
        transform: translateY(-1px);
        box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.22);
    }

    .analysis-cell.no-data {
        cursor: default;
    }

    .empty-cell {
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--dark-gray);
        padding: 10px;
    }

    .empty-cell-icon {
        font-size: 24px;
        margin-bottom: 8px;
        color: #d1d5db;
    }

    .badge-new {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #ef4444;
        color: white;
        font-size: 8px;
        padding: 2px 6px;
        border-radius: 10px;
        z-index: 5;
    }

    .search-target-line {
        border-color: #2563eb;
        box-shadow: 0 18px 35px -22px rgba(37, 99, 235, 0.75);
    }

    .search-target-header,
    .search-target-cell {
        outline: 3px solid rgba(37, 99, 235, 0.85);
        outline-offset: -3px;
        box-shadow: inset 0 0 0 9999px rgba(219, 234, 254, 0.32);
        position: relative;
        z-index: 3;
    }

    .cell-highlight {
        animation: highlight-pulse 2s ease-out;
    }

    @keyframes highlight-pulse {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }

    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .animate-modalIn {
        animation: modalIn 0.3s ease-out;
    }

    .lado-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 12px;
        font-family: monospace;
    }

    .lado-badge.vapor {
        background-color: #f3f4f6;
        color: #1363d3;
        border: 1px solid #9ca3af;
    }

    .lado-badge.pasillo {
        background-color: #e5e7eb;
        color: #1363d3;
        border: 1px solid #6b7280;
    }

    .image-grid-enhanced {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    .image-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid var(--medium-gray);
        transition: all 0.3s ease;
        background: white;
    }

    .image-item:hover {
        border-color: var(--primary-blue);
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .grid-image {
        width: 100%;
        height: 150px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .grid-image:hover {
        transform: scale(1.05);
    }

    .image-number {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(31, 41, 55, 0.9);
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 4px;
        z-index: 10;
        border: 1px solid #6b7280;
        font-family: monospace;
    }

    .image-info {
        padding: 8px;
        background: white;
        border-top: 1px solid #e5e7eb;
    }

    .download-image-btn {
        width: 100%;
        padding: 6px;
        background: #374151;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-family: monospace;
        transition: background 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .download-image-btn:hover {
        background: #1f2937;
    }

    @media (max-width: 768px) {
        .filters-section {
            padding: 16px;
            border-radius: 12px;
        }

        .lineas-grid { gap: 8px; }
        .linea-item { padding: 6px 16px; font-size: 13px; }
        .filters-row { flex-direction: column; align-items: stretch; }
        .filter-link,
        .btn-apply,
        .btn-clear {
            justify-content: center;
            width: 100%;
            margin-left: 0;
        }

        .lavadora-card-header {
            align-items: stretch;
            padding: 14px;
        }

        .lavadora-card-header > div {
            width: 100%;
        }

        .etq-process-diagram {
            padding: 15px 14px 16px;
        }

        .etq-process-heading {
            flex-direction: column;
            gap: 12px;
        }

        .etq-process-tags {
            justify-content: flex-start;
            width: 100%;
        }

        .etq-process-tags span,
        .etq-process-legend span {
            flex: 1 1 145px;
            justify-content: center;
            text-align: center;
        }

        .etq-process-canvas {
            --bottle-width: clamp(22px, 7vw, 34px);
            --bottle-height: clamp(62px, 18vw, 90px);
            min-height: 214px;
            border-radius: 10px;
        }

        .etq-process-belt {
            left: 3%;
            right: 3%;
            bottom: 43px;
        }

        .etq-process-machine {
            width: clamp(112px, 31vw, 190px);
            height: clamp(136px, 37vw, 172px);
            bottom: 50px;
        }

        .etq-process-machine-window {
            top: 22px;
            bottom: 20px;
        }

        .etq-process-label-head {
            --etq-label-slot-height: 44px;
            width: min(calc(100% - 10px), clamp(112px, 34vw, 150px));
            height: 58px;
            padding: 6px 7px;
        }

        .etq-process-label-head-stack {
            gap: 4px;
        }

        .etq-process-label-head-image,
        .etq-process-label-head-item {
            height: 100%;
        }

        .etq-process-label-head[data-etq-label-count="1"] {
            --etq-label-slot-height: 48px;
            width: min(calc(100% - 20px), clamp(84px, 23vw, 104px));
            height: 60px;
        }

        .etq-process-label-head[data-etq-label-count="1"] .etq-process-label-head-image,
        .etq-process-label-head[data-etq-label-count="1"] .etq-process-label-head-item {
            height: 100%;
        }

        .etq-process-label-head[data-etq-label-count="2"] {
            --etq-label-slot-height: 48px;
            width: min(calc(100% - 10px), clamp(126px, 38vw, 154px));
            height: 60px;
        }

        .etq-process-label-head[data-etq-label-count="3"] {
            --etq-label-slot-height: 44px;
            width: min(calc(100% - 8px), clamp(136px, 42vw, 168px));
            height: 60px;
        }

        .etq-process-label-head[data-etq-label-count="3"] .etq-process-label-head-image,
        .etq-process-label-head[data-etq-label-count="3"] .etq-process-label-head-item {
            height: 100%;
        }

        .etq-process-label-head[data-etq-label-count="4"] {
            --etq-label-slot-height: 32px;
            width: min(calc(100% - 8px), clamp(126px, 38vw, 156px));
            height: 78px;
        }

        .etq-process-label-head[data-etq-label-count="4"] .etq-process-label-head-image,
        .etq-process-label-head[data-etq-label-count="4"] .etq-process-label-head-item {
            height: 100%;
        }

        .etq-process-label-head.etq-process-label-head--carousel {
            --etq-label-slot-height: 58px;
            width: min(calc(100% - 8px), clamp(134px, 42vw, 168px));
            height: 74px;
            padding: 7px 8px;
        }

        .etq-process-label-head.etq-process-label-head--carousel .etq-process-label-head-stack {
            position: relative;
            display: block;
            width: 100%;
            height: 100%;
            grid-template-columns: none;
            grid-template-rows: none;
            gap: 0;
        }

        .etq-process-label-head.etq-process-label-head--carousel .etq-process-label-head-item {
            position: absolute;
            inset: 0;
            height: 100%;
            padding: 4px 6px;
        }

        .etq-process-station {
            bottom: 50px;
            width: 34%;
        }

        .etq-process-station--entrada {
            left: 3%;
        }

        .etq-process-station--salida {
            right: 3%;
        }

        .etq-process-station--marker {
            bottom: 31px;
            max-width: 34%;
        }

        .etq-process-station--marker > span {
            padding: 4px 7px;
            font-size: 10px;
        }

        .etq-process-bottle--moving {
            bottom: 70px;
        }

        .etq-process-arrow {
            bottom: 121px;
            width: 13vw;
        }

        .etq-process-arrow--in {
            left: 23%;
        }

        .etq-process-arrow--out {
            right: 23%;
        }

        .compact-table td,
        .compact-table th {
            min-width: 100px;
            font-size: 0.7rem !important;
            padding: 6px !important;
        }

        .component-industrial-icon {
            width: 3.5rem;
            height: 3.5rem;
        }

        .image-grid-enhanced {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .grid-image { height: 120px; }
    }

    @media (max-width: 480px) {
        .image-grid-enhanced { grid-template-columns: 1fr; }
    }
</style>
@endonce
