<style>
    .central-placeholder {
        width: 100%;
        max-width: min(960px, 100%);
        margin: 0 auto;
        overflow-x: clip;
    }

    .central-placeholder * {
        box-sizing: border-box;
        min-width: 0;
    }

    .central-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        overflow: hidden;
    }

    .central-card-header {
        align-items: center;
        background: linear-gradient(to right, #f9fafb, #ffffff);
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        gap: 16px;
        padding: 24px;
    }

    .central-icon {
        align-items: center;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        color: #2563eb;
        display: flex;
        flex: 0 0 auto;
        font-size: 24px;
        height: 58px;
        justify-content: center;
        width: 58px;
    }

    .central-card-header h1,
    .central-card-header p,
    .central-card p {
        overflow-wrap: anywhere;
    }

    @media (max-width: 640px) {
        .central-card-header {
            align-items: flex-start;
            flex-direction: column;
            padding: 18px;
        }

        .central-placeholder {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
    }
</style>

<div class="central-placeholder px-4 py-8">
    <div class="central-card">
        <div class="central-card-header">
            <div class="central-icon">
                <i class="fas fa-oil-can"></i>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Pasteurizadora</p>
                <h1 class="text-2xl font-bold text-gray-900">Central Hidraulica</h1>
                <p class="mt-1 text-sm text-gray-500">Modulo en preparacion</p>
            </div>
        </div>

        <div class="p-6 text-gray-600">
            <p>Esta seccion estara disponible cuando se habiliten los flujos de analisis de central hidraulica.</p>
        </div>
    </div>
</div>
