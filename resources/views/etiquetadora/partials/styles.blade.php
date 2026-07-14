@once
<style>
    :root {
        --etq-ink: rgb(31, 35, 72);
        --etq-ink-soft: rgba(31, 35, 72, 0.08);
        --etq-ink-border: rgba(31, 35, 72, 0.18);
    }

    .etq-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    }

    .etq-container {
        width: 100%;
        max-width: 96rem;
        margin-inline: auto;
        padding: 1.5rem 1rem;
    }

    .etq-header {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .etq-header-main {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }

    .etq-machine-icon {
        display: flex;
        width: 5.5rem;
        height: 5.5rem;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
    }

    .etq-machine-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .etq-accent-bar {
        width: 0.5rem;
        height: 2.75rem;
        border-radius: 999px;
        background: linear-gradient(180deg, var(--etq-ink), rgb(75, 85, 99));
        flex: 0 0 auto;
    }

    .etq-title {
        margin: 0;
        font-size: clamp(1.85rem, 4vw, 2.5rem);
        font-weight: 900;
        line-height: 1.05;
        color: transparent;
        background: linear-gradient(90deg, var(--etq-ink), rgb(75, 85, 99));
        -webkit-background-clip: text;
        background-clip: text;
    }

    .etq-subtitle {
        margin-top: 0.35rem;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .etq-status-pill,
    .etq-back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        border: 1px solid #e5e7eb;
        background: rgba(255, 255, 255, 0.82);
        color: #4b5563;
        border-radius: 0.85rem;
        padding: 0.65rem 0.9rem;
        font-size: 0.875rem;
        font-weight: 700;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        transition: all 0.2s ease;
    }

    .etq-back-link:hover {
        color: var(--etq-ink);
        background: #fff;
        transform: translateY(-1px);
    }

    .etq-menu-card {
        position: relative;
        display: block;
        overflow: hidden;
        border-radius: 1.5rem;
        border: 1px solid #f3f4f6;
        background: #fff;
        padding: 1.75rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .etq-menu-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 0.5rem;
        background: var(--etq-ink);
    }

    .etq-menu-card::after {
        content: "";
        position: absolute;
        inset: 0;
        transform: translateX(-105%);
        background: linear-gradient(90deg, transparent, rgba(31, 35, 72, 0.10), transparent);
        transition: transform 0.8s ease;
        pointer-events: none;
    }

    .etq-menu-card:hover {
        transform: translateY(-0.35rem);
        box-shadow: 0 24px 42px rgba(15, 23, 42, 0.14);
    }

    .etq-menu-card:hover::after {
        transform: translateX(105%);
    }

    .etq-menu-icon {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 4rem;
        height: 4rem;
        margin-bottom: 1.25rem;
        border-radius: 1rem;
        color: #fff;
        background: linear-gradient(135deg, var(--etq-ink), rgb(51, 55, 92));
        box-shadow: 0 12px 24px rgba(31, 35, 72, 0.24);
        transition: transform 0.3s ease;
    }

    .etq-menu-card:hover .etq-menu-icon {
        transform: scale(1.06) rotate(2deg);
    }

    .etq-panel {
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
    }

    .etq-panel-header {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(90deg, #f9fafb, #fff);
        padding: 1rem 1.25rem;
    }

    .etq-panel-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #111827;
        font-size: 1.1rem;
        font-weight: 900;
    }

    .etq-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        border: 1px solid var(--etq-ink-border);
        background: var(--etq-ink-soft);
        color: var(--etq-ink);
        padding: 0.35rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .etq-stat-card {
        border-radius: 1rem;
        border: 1px solid var(--etq-ink-border);
        background: linear-gradient(135deg, var(--etq-ink-soft), #fff);
        padding: 1.25rem;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }

    .etq-stat-label {
        color: var(--etq-ink);
        font-size: 0.72rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .etq-stat-value {
        margin-top: 0.35rem;
        color: #111827;
        font-size: 1.9rem;
        font-weight: 900;
        line-height: 1;
    }

    .etq-form-surface {
        border: 1px solid #e5e7eb;
        border-radius: 1.25rem;
        background: #fff;
        padding: 1.5rem;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.10);
    }

    .etq-context-strip {
        border: 1px solid #e5e7eb;
        border-radius: 0.9rem;
        background: linear-gradient(90deg, #f9fafb, #f3f4f6);
        padding: 1rem;
    }

    .etq-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.875rem;
    }

    .etq-table thead {
        background: #eff6ff;
    }

    .etq-table th {
        color: var(--etq-ink);
        font-size: 0.75rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .etq-table th,
    .etq-table td {
        border-bottom: 1px solid #f3f4f6;
        padding: 0.85rem 1rem;
    }

    .etq-table tbody tr {
        transition: background 0.15s ease;
    }

    .etq-table tbody tr:hover {
        background: #f9fafb;
    }

    .etq-line-card {
        overflow: hidden;
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .etq-line-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(90deg, var(--etq-ink), rgb(47, 53, 102));
        color: #fff;
        padding: 1rem 1.25rem;
    }

    .etq-machine-card {
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 0.9rem;
        background: #fff;
    }

    .etq-machine-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        border-bottom: 1px solid #dbeafe;
        background: #eff6ff;
        padding: 0.85rem 1rem;
    }

    .etq-group-title {
        position: sticky;
        top: 0;
        z-index: 10;
        border-block: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #4b5563;
        padding: 0.55rem 1rem;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .etq-empty {
        border: 1px dashed #d1d5db;
        border-radius: 1rem;
        background: #fff;
        padding: 3rem 1.5rem;
        text-align: center;
        color: #6b7280;
    }

    .etq-table-wrapper {
        position: relative;
        overflow: auto;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }

    .etq-compact-table {
        width: 100%;
        min-width: 1120px;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 0.78rem;
    }

    .etq-compact-table th,
    .etq-compact-table td {
        border: 1px solid #e2e8f0;
        padding: 0.7rem;
        vertical-align: top;
    }

    .etq-compact-table th {
        background: #eff6ff;
        color: var(--etq-ink);
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .etq-sticky-top {
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .etq-sticky-left {
        position: sticky;
        left: 0;
        z-index: 18;
        box-shadow: 8px 0 14px -14px rgba(15, 23, 42, 0.55);
    }

    .etq-sticky-corner {
        position: sticky;
        top: 0;
        left: 0;
        z-index: 30;
        box-shadow: 8px 0 14px -14px rgba(15, 23, 42, 0.55);
    }

    .etq-component-cell {
        background: #fff;
        min-width: 18rem;
        width: 18rem;
    }

    .etq-component-title {
        color: #1e293b;
        font-size: 0.9rem;
        font-weight: 900;
        line-height: 1.25;
    }

    .etq-component-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.45rem;
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .etq-analysis-cell {
        min-height: 8.25rem;
        background: #f8fafc;
        transition: background 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
    }

    .etq-analysis-cell.has-data {
        cursor: pointer;
    }

    .etq-analysis-cell.has-data:hover {
        transform: translateY(-1px);
        box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.22);
    }

    .etq-cell-ok {
        background: #ecfdf5;
        border-left: 4px solid #10b981 !important;
    }

    .etq-cell-review {
        background: #fffbeb;
        border-left: 4px solid #f59e0b !important;
    }

    .etq-cell-warning {
        background: #fff7ed;
        border-left: 4px solid #f97316 !important;
    }

    .etq-cell-danger {
        background: #fef2f2;
        border-left: 4px solid #ef4444 !important;
    }

    .etq-cell-changed {
        background: #eff6ff;
        border-left: 4px solid #3b82f6 !important;
    }

    .etq-cell-empty {
        background: #f8fafc;
        color: #94a3b8;
    }

    .etq-cell-action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.65rem;
    }

    .etq-mini-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-height: 2rem;
        border-radius: 0.5rem;
        padding: 0.35rem 0.6rem;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1;
        transition: all 0.18s ease;
    }

    .etq-mini-action.primary {
        background: #2563eb;
        color: #fff;
    }

    .etq-mini-action.secondary {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
    }

    .etq-mini-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 14px rgba(15, 23, 42, 0.10);
    }

    .etq-table-section-header {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: #fff;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .etq-table-section-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1rem;
        font-weight: 900;
    }

    .etq-section-card {
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 1.25rem;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .etq-section-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 20px 34px rgba(15, 23, 42, 0.10);
    }

    .etq-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: #fff;
        padding: 1.15rem 1.35rem;
    }

    .etq-section-title {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        font-size: 1.15rem;
        font-weight: 900;
    }

    .etq-line-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .etq-line-filter-link {
        display: inline-flex;
        min-height: 2.75rem;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        border: 2px solid #e2e8f0;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        padding: 0.55rem 1rem;
        font-size: 0.875rem;
        font-weight: 800;
        line-height: 1.2;
        text-align: center;
        transition: all 0.2s ease;
    }

    .etq-line-filter-link:hover {
        border-color: #94a3b8;
        background: #f1f5f9;
        transform: translateY(-1px);
        color: #1e293b;
    }

    .etq-line-filter-link.active {
        border-color: #2563eb;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22);
    }

    .etq-plan-list {
        display: grid;
        gap: 1rem;
        padding: 1.25rem;
    }

    .etq-plan-table-wrap {
        overflow-x: auto;
        background: #fff;
    }

    .etq-plan-table {
        width: 100%;
        min-width: 920px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .etq-plan-table th {
        border-bottom: 2px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        padding: 1rem 1.15rem;
        text-align: left;
        font-size: 0.78rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .etq-plan-table td {
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 1.15rem;
        vertical-align: top;
    }

    .etq-plan-table tbody tr {
        transition: background 0.15s ease;
    }

    .etq-plan-table tbody tr:hover {
        background: #f8fafc;
    }

    .etq-plan-item {
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: linear-gradient(135deg, #fff, #f8fafc);
        padding: 1rem;
        transition: all 0.2s ease;
    }

    .etq-plan-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        transform: translateY(-1px);
    }

    .etq-plan-title {
        color: #1e293b;
        font-size: 0.95rem;
        font-weight: 900;
        line-height: 1.45;
    }

    .etq-trace-grid {
        display: grid;
        gap: 0.35rem;
        color: #64748b;
        font-size: 0.75rem;
        line-height: 1.4;
    }

    .etq-date-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .etq-date-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 0.55rem;
        border: 1px solid #e5e7eb;
        padding: 0.4rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .etq-date-pill.fecha-vencida {
        border-color: #fecaca;
        background: #fee2e2;
        color: #991b1b;
    }

    .etq-date-pill.fecha-proxima {
        border-color: #bbf7d0;
        background: #dcfce7;
        color: #166534;
    }

    .etq-date-pill.fecha-cercana {
        border-color: #fef08a;
        background: #fef9c3;
        color: #854d0e;
    }

    .etq-date-pill.fecha-futura {
        border-color: #e5e7eb;
        background: #f3f4f6;
        color: #374151;
    }

    .etq-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        padding: 0.4rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 900;
        line-height: 1;
        white-space: nowrap;
    }

    .etq-status-badge.is-complete {
        border-color: #bbf7d0;
        background: #dcfce7;
        color: #166534;
    }

    .etq-status-badge.is-pending {
        border-color: #fde68a;
        background: #fef3c7;
        color: #92400e;
    }

    .etq-status-badge.is-danger {
        border-color: #fecaca;
        background: #fee2e2;
        color: #991b1b;
    }

    .etq-status-badge.is-process {
        border-color: #bfdbfe;
        background: #dbeafe;
        color: #1e40af;
    }

    .etq-progress-track {
        position: relative;
        overflow: hidden;
        height: 1.45rem;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .etq-progress-bar {
        display: flex;
        height: 100%;
        align-items: center;
        justify-content: flex-end;
        min-width: 2.25rem;
        border-radius: inherit;
        padding-right: 0.55rem;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 900;
        transition: width 0.25s ease;
    }

    .etq-progress-bar.success { background: linear-gradient(90deg, #10b981, #059669); }
    .etq-progress-bar.info { background: linear-gradient(90deg, #3b82f6, #2563eb); }
    .etq-progress-bar.warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .etq-progress-bar.danger { background: linear-gradient(90deg, #ef4444, #dc2626); }

    .etq-icon-action {
        display: inline-flex;
        width: 2.5rem;
        height: 2.5rem;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 0.7rem;
        color: #fff;
        transition: all 0.2s ease;
    }

    .etq-icon-action:hover {
        transform: translateY(-1px);
    }

    .etq-icon-action.edit {
        background: #3b82f6;
    }

    .etq-icon-action.delete {
        background: #ef4444;
    }

    .etq-preview-thumb {
        position: relative;
        overflow: hidden;
        width: 6rem;
        height: 6rem;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
    }

    .etq-preview-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .etq-preview-remove {
        position: absolute;
        top: 0.25rem;
        right: 0.25rem;
        display: flex;
        width: 1.55rem;
        height: 1.55rem;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 0.85rem;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .etq-preview-thumb:hover .etq-preview-remove {
        opacity: 1;
    }

    .etq-presentations {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        min-width: 0;
    }

    .etq-presentations-icons {
        display: inline-flex;
        align-items: flex-end;
        gap: 0.18rem;
        flex-wrap: nowrap;
        min-width: 0;
    }

    .etq-presentation-image {
        display: inline-block;
        width: auto;
        height: 2.35rem;
        max-width: 4.25rem;
        flex: 0 0 auto;
        object-fit: contain;
        filter: drop-shadow(0 4px 5px rgba(15, 23, 42, 0.14));
    }

    .etq-presentations-names {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .etq-product-icon {
        --bottle: #b45309;
        --bottle-dark: #7c2d12;
        --label-bg: #fef3c7;
        --label-text: #78350f;
        --cap: #111827;
        position: relative;
        display: inline-flex;
        width: 1.55rem;
        height: 2.25rem;
        flex: 0 0 auto;
        align-items: flex-end;
        justify-content: center;
        filter: drop-shadow(0 4px 5px rgba(15, 23, 42, 0.16));
    }

    .etq-product-icon::before {
        content: "";
        position: absolute;
        top: 0;
        left: 50%;
        width: 38%;
        height: 34%;
        transform: translateX(-50%);
        border: 1px solid rgba(15, 23, 42, 0.18);
        border-bottom: 0;
        border-radius: 0.28rem 0.28rem 0.12rem 0.12rem;
        background: linear-gradient(120deg, rgba(255,255,255,0.38), var(--bottle) 44%, var(--bottle-dark));
    }

    .etq-product-icon::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 10%;
        width: 80%;
        height: 72%;
        border: 1px solid rgba(15, 23, 42, 0.20);
        border-radius: 0.48rem 0.48rem 0.58rem 0.58rem;
        background:
            radial-gradient(circle at 30% 16%, rgba(255,255,255,0.55), transparent 28%),
            linear-gradient(130deg, rgba(255,255,255,0.22), var(--bottle) 44%, var(--bottle-dark));
    }

    .etq-product-label {
        position: absolute;
        z-index: 2;
        bottom: 18%;
        left: 18%;
        display: inline-flex;
        width: 64%;
        min-height: 24%;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 0.16rem;
        background: var(--label-bg);
        color: var(--label-text);
        font-size: 0.45rem;
        font-weight: 900;
        line-height: 1;
        letter-spacing: 0;
    }

    .etq-product-icon::selection,
    .etq-product-label::selection {
        background: transparent;
    }

    .etq-product-icon--mega {
        width: 1.78rem;
        height: 2.45rem;
    }

    .etq-product-icon--stubby {
        width: 1.5rem;
        height: 1.95rem;
    }

    .etq-product-icon--stubby::before {
        width: 46%;
        height: 24%;
    }

    .etq-product-icon--stubby::after {
        height: 78%;
        border-radius: 0.7rem 0.7rem 0.58rem 0.58rem;
    }

    .etq-product-icon--amber {
        --bottle: #d97706;
        --bottle-dark: #7c2d12;
        --label-bg: #fef3c7;
        --label-text: #7c2d12;
        --cap: #facc15;
    }

    .etq-product-icon--victoria {
        --bottle: #92400e;
        --bottle-dark: #451a03;
        --label-bg: #fde68a;
        --label-text: #7c2d12;
        --cap: #facc15;
    }

    .etq-product-icon--gold {
        --bottle: #f59e0b;
        --bottle-dark: #78350f;
        --label-bg: #fff7ed;
        --label-text: #92400e;
        --cap: #f8fafc;
    }

    .etq-product-icon--green {
        --bottle: #b45309;
        --bottle-dark: #5f2d0c;
        --label-bg: #d1fae5;
        --label-text: #047857;
        --cap: #047857;
    }

    .etq-product-icon--black {
        --bottle: #1f2937;
        --bottle-dark: #020617;
        --label-bg: #facc15;
        --label-text: #111827;
        --cap: #facc15;
    }

    .etq-product-icon--blue {
        --bottle: #c2410c;
        --bottle-dark: #7c2d12;
        --label-bg: #2563eb;
        --label-text: #ffffff;
        --cap: #1d4ed8;
    }

    .etq-product-icon--barrilito {
        --bottle: #d97706;
        --bottle-dark: #92400e;
        --label-bg: #1e3a8a;
        --label-text: #ffffff;
        --cap: #334155;
    }

    .etq-product-icon--paper {
        --bottle: #9ca3af;
        --bottle-dark: #6b7280;
        --label-bg: #f8fafc;
        --label-text: #991b1b;
        --cap: #ef4444;
    }

    .etq-product-icon--pacifico {
        --bottle: #d97706;
        --bottle-dark: #7c2d12;
        --label-bg: #facc15;
        --label-text: #1d4ed8;
        --cap: #1d4ed8;
    }

    .etq-presentations--xs .etq-product-icon {
        width: 1.05rem;
        height: 1.55rem;
        filter: drop-shadow(0 2px 3px rgba(15, 23, 42, 0.14));
    }

    .etq-presentations--xs .etq-presentation-image {
        height: 1.45rem;
        max-width: 2.5rem;
        filter: drop-shadow(0 2px 3px rgba(15, 23, 42, 0.14));
    }

    .etq-presentations--xs .etq-product-icon--mega {
        width: 1.18rem;
        height: 1.65rem;
    }

    .etq-presentations--xs .etq-product-icon--stubby {
        width: 1rem;
        height: 1.35rem;
    }

    .etq-presentations--xs .etq-product-label {
        font-size: 0.32rem;
    }

    .etq-presentations--lg .etq-presentations-icons {
        gap: 0.4rem;
    }

    .etq-presentations--lg .etq-presentation-image {
        height: 5.75rem;
        max-width: 7.5rem;
        filter: drop-shadow(0 10px 14px rgba(15, 23, 42, 0.16));
    }

    .etq-presentations--lg .etq-product-icon {
        width: 2.35rem;
        height: 3.4rem;
    }

    .etq-presentations--lg .etq-product-icon--mega {
        width: 2.65rem;
        height: 3.7rem;
    }

    .etq-presentations--lg .etq-product-icon--stubby {
        width: 2.25rem;
        height: 2.95rem;
    }

    .etq-presentations--lg .etq-product-label {
        font-size: 0.62rem;
    }

    @media (min-width: 640px) {
        .etq-container {
            padding-inline: 1.5rem;
            padding-block: 2rem;
        }

        .etq-header {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }

        .etq-machine-icon {
            width: 7rem;
            height: 7rem;
        }

        .etq-panel-header {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    @media (max-width: 640px) {
        .etq-section-header {
            align-items: stretch;
        }

        .etq-line-filter-link,
        .etq-icon-action {
            width: 100%;
        }
    }
</style>
@endonce
