{{-- Estilo compartido por los menús de lavadora y pasteurizadora. --}}
<style>
    .industrial-menu {
        position: relative;
        isolation: isolate;
        background:
            radial-gradient(circle at 12% 8%, rgba(42, 169, 255, .10), transparent 27rem),
            radial-gradient(circle at 88% 18%, rgba(154, 123, 255, .08), transparent 25rem),
            linear-gradient(135deg, #f7faff 0%, #eef3f8 52%, #f9fbfd 100%);
    }

    .industrial-menu::before {
        position: absolute;
        inset: 0;
        z-index: -1;
        pointer-events: none;
        content: "";
        opacity: .42;
        background-image: linear-gradient(rgba(31, 35, 72, .035) 1px, transparent 1px), linear-gradient(90deg, rgba(31, 35, 72, .035) 1px, transparent 1px);
        background-size: 34px 34px;
        mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .72), transparent 68%);
    }

    .industrial-menu > .max-w-7xl > .mb-10 {
        margin-bottom: 2rem;
        padding: clamp(1rem, 2vw, 1.4rem) clamp(1rem, 2.5vw, 1.7rem);
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        backdrop-filter: none;
    }

    .industrial-menu .menu-hero-brand {
        gap: 1rem;
    }

    .industrial-menu .menu-hero-icon {
        width: 6.5rem;
        height: 6.5rem;
        flex: 0 0 6.5rem;
        overflow: hidden;
        border-radius: 50%;
        filter: none;
    }

    .industrial-menu .menu-hero-icon img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
        image-rendering: auto;
    }

    .industrial-menu .menu-hero-copy {
        gap: .8rem;
    }

    .industrial-menu .menu-hero-accent {
        width: .3rem;
        flex-basis: .3rem;
        height: 3.5rem;
        background: linear-gradient(to bottom, #1f2348, #2aa9ff);
        box-shadow: 0 0 16px rgba(42, 169, 255, .28);
    }

    .industrial-menu .menu-hero-title {
        font-size: clamp(1.9rem, 4.2vw, 3.6rem);
        letter-spacing: .08em;
    }

    .industrial-menu-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: clamp(1rem, 2vw, 1.35rem);
        margin-top: .75rem;
    }

    .industrial-menu-card {
        position: relative;
        display: flex;
        min-width: 0;
        min-height: 27rem;
        flex-direction: column;
        overflow: hidden;
        color: #111827;
        text-decoration: none;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 24px;
        background: linear-gradient(145deg, rgba(255, 255, 255, .98), rgba(241, 245, 249, .94));
        box-shadow: 0 14px 30px rgba(31, 35, 72, .09), inset 0 1px 0 rgba(255, 255, 255, .88);
        transition: transform 300ms cubic-bezier(.2, .75, .25, 1), box-shadow 300ms ease, border-color 300ms ease;
    }

    .industrial-menu-card::after {
        position: absolute;
        right: -3.5rem;
        bottom: -4rem;
        width: 10rem;
        height: 10rem;
        pointer-events: none;
        content: "";
        opacity: .14;
        border-radius: 999px;
        background: var(--card-accent);
        filter: blur(34px);
        transition: opacity 300ms ease, transform 300ms ease;
    }

    .industrial-menu-card:hover {
        transform: translateY(-5px);
        border-color: color-mix(in srgb, var(--card-accent) 42%, white);
        box-shadow: 0 24px 42px rgba(31, 35, 72, .15), 0 0 0 1px rgba(255, 255, 255, .72) inset;
    }

    .industrial-menu-card:hover::after {
        opacity: .28;
        transform: scale(1.2);
    }

    .industrial-menu-card:active {
        transform: translateY(-1px) scale(.985);
        transition-duration: 120ms;
    }

    .industrial-menu-card:focus-visible {
        outline: 3px solid var(--card-accent);
        outline-offset: 4px;
    }

    .industrial-menu-card__shine {
        position: absolute;
        inset: 0;
        z-index: 3;
        pointer-events: none;
        transform: translateX(-120%);
        background: linear-gradient(105deg, transparent 32%, rgba(255, 255, 255, .32) 48%, transparent 64%);
        transition: transform 700ms ease;
    }

    .industrial-menu-card:hover .industrial-menu-card__shine {
        transform: translateX(120%);
    }

    .industrial-menu-card__topline {
        position: absolute;
        top: 0;
        left: 1.2rem;
        right: 1.2rem;
        z-index: 4;
        height: 3px;
        border-radius: 99px;
        background: var(--card-accent);
        box-shadow: 0 0 14px var(--card-glow);
        transition: box-shadow 300ms ease;
    }

    .industrial-menu-card:hover .industrial-menu-card__topline {
        box-shadow: 0 0 24px var(--card-glow), 0 0 6px var(--card-accent);
    }

    .industrial-menu-card__visual {
        position: relative;
        display: grid;
        place-items: center;
        height: 14.6rem;
        flex: 0 0 14.6rem;
        margin: .7rem .7rem 0;
        overflow: hidden;
        border-radius: 19px;
        background:
            radial-gradient(circle at 50% 48%, var(--card-glow), transparent 47%),
            linear-gradient(145deg, #172033 0%, #0b1220 100%);
    }

    .industrial-menu-card__visual::before {
        position: absolute;
        inset: .55rem;
        content: "";
        border: 1px solid rgba(255, 255, 255, .11);
        border-radius: 14px;
        background-image: linear-gradient(rgba(255, 255, 255, .06) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .06) 1px, transparent 1px);
        background-size: 24px 24px;
        mask-image: radial-gradient(circle at center, black, transparent 74%);
    }

    .industrial-menu-card__visual-glow {
        position: absolute;
        width: 58%;
        height: 58%;
        border-radius: 50%;
        background: var(--card-accent);
        opacity: .2;
        filter: blur(28px);
        transition: opacity 300ms ease, transform 300ms ease;
    }

    .industrial-menu-card__visual img {
        position: relative;
        z-index: 1;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
        transform: translateY(2px) scale(.94);
        filter: drop-shadow(0 18px 14px rgba(0, 0, 0, .28));
        transition: transform 320ms cubic-bezier(.2, .75, .25, 1), filter 320ms ease;
    }

    .industrial-menu-card:hover .industrial-menu-card__visual img {
        transform: translateY(-6px) scale(1.02);
        filter: drop-shadow(0 24px 18px rgba(0, 0, 0, .32));
    }

    .industrial-menu-card:hover .industrial-menu-card__visual-glow {
        opacity: .34;
        transform: scale(1.15);
    }

    .industrial-menu-card__visual-tag {
        position: absolute;
        right: .85rem;
        bottom: .8rem;
        z-index: 2;
        padding: .28rem .5rem;
        color: rgba(255, 255, 255, .82);
        border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 999px;
        background: rgba(7, 13, 26, .54);
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .12em;
        line-height: 1;
    }

    .industrial-menu-card__body {
        position: relative;
        z-index: 2;
        display: flex;
        min-height: 11.35rem;
        flex: 1;
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem 1.15rem 1.15rem;
    }

    .industrial-menu-card__eyebrow {
        margin-bottom: .45rem;
        color: var(--card-accent);
        font-size: .65rem;
        font-weight: 800;
        letter-spacing: .13em;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .industrial-menu-card__title {
        max-width: 100%;
        color: #182033;
        font-size: clamp(1.03rem, 1.5vw, 1.24rem);
        font-weight: 900;
        letter-spacing: -.025em;
        line-height: 1.15;
        text-wrap: balance;
    }

    .industrial-menu-card__description {
        max-width: 32rem;
        margin-top: .55rem;
        color: #64748b;
        font-size: .82rem;
        line-height: 1.45;
    }

    .industrial-menu-card__action {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-top: auto;
        padding-top: .8rem;
        color: #334155;
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .02em;
        transition: color 250ms ease, gap 250ms ease;
    }

    .industrial-menu-card__action i {
        color: var(--card-accent);
        font-size: .72rem;
    }

    .industrial-menu-card:hover .industrial-menu-card__action {
        gap: .65rem;
        color: #111827;
    }

    .legacy-menu-grid {
        display: none !important;
    }

    @media (max-width: 1100px) {
        .industrial-menu .menu-hero-icon {
            width: 6.5rem;
            height: 6.5rem;
            flex-basis: 6.5rem;
        }

        .industrial-menu-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .industrial-menu .menu-hero-icon {
            width: 4.5rem;
            height: 4.5rem;
            flex-basis: 4.5rem;
        }

        .industrial-menu {
            padding-top: 1rem;
        }

        .industrial-menu > .max-w-7xl {
            padding-right: .85rem;
            padding-left: .85rem;
        }

        .industrial-menu > .max-w-7xl > .mb-10 {
            padding: .9rem;
            border-radius: 20px;
        }

        .industrial-menu .menu-hero-title {
            font-size: 1.8rem;
        }

        .industrial-menu-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .industrial-menu-card {
            min-height: 25.5rem;
        }

        .industrial-menu-card__visual {
            height: 14rem;
            flex-basis: 14rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .industrial-menu-card,
        .industrial-menu-card::after,
        .industrial-menu-card__shine,
        .industrial-menu-card__visual img,
        .industrial-menu-card__visual-glow,
        .industrial-menu-card__action {
            transition: none;
        }

        .industrial-menu-card:hover,
        .industrial-menu-card:active,
        .industrial-menu-card:hover .industrial-menu-card__visual img {
            transform: none;
        }
    }
</style>
<style>
    /* Composición horizontal tipo ficha industrial */
    .industrial-menu-grid {
        gap: clamp(1rem, 1.8vw, 1.3rem);
    }

    .industrial-menu-card {
        display: block;
        min-height: 14.25rem;
        border-radius: 22px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 12px 25px rgba(31, 35, 72, .10), inset 0 1px 0 rgba(255, 255, 255, .92);
    }

    .industrial-menu-card::before {
        position: absolute;
        top: 1rem;
        left: 1.35rem;
        z-index: 4;
        color: color-mix(in srgb, var(--card-accent) 72%, #ffffff);
        content: attr(data-number);
        font-size: 2.15rem;
        font-weight: 900;
        letter-spacing: -.06em;
        line-height: 1;
    }

    .industrial-menu-card::after {
        right: -1px;
        bottom: -1px;
        width: 65%;
        height: 100%;
        z-index: 0;
        opacity: .16;
        border-radius: 0 22px 22px 0;
        background: linear-gradient(135deg, transparent 0 25%, var(--card-accent) 25%, color-mix(in srgb, var(--card-accent) 65%, white) 100%);
        filter: none;
        clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
        transition: opacity 300ms ease, transform 300ms ease;
    }

    .industrial-menu-card:hover::after {
        opacity: .24;
        transform: scale(1.03);
    }

    .industrial-menu-card__topline {
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        z-index: 5;
        border-radius: 22px 22px 0 0;
    }

    .industrial-menu-card__visual {
        position: absolute;
        inset: 0 0 0 47%;
        z-index: 1;
        display: grid;
        height: auto;
        flex: none;
        margin: 0;
        overflow: hidden;
        border-radius: 0 22px 22px 0;
        background: transparent;
    }

    .industrial-menu-card__visual::before {
        display: none;
    }

    .industrial-menu-card__visual-glow {
        width: 70%;
        height: 70%;
        opacity: .18;
        filter: blur(32px);
    }

    .industrial-menu-card__visual img {
        width: 100%;
        height: 100%;
        transform: translateY(1px) scale(.98);
        filter: drop-shadow(0 17px 13px rgba(31, 35, 72, .22));
    }

    .industrial-menu-card:hover .industrial-menu-card__visual img {
        transform: translateY(-4px) scale(1.06);
        filter: drop-shadow(0 22px 16px rgba(31, 35, 72, .26));
    }

    .industrial-menu-card__visual-tag {
        display: none;
    }

    .industrial-menu-card__body {
        position: relative;
        z-index: 3;
        display: flex;
        width: 47%;
        min-height: 14.25rem;
        padding: 3.65rem .75rem 1rem 1.35rem;
        align-items: flex-start;
        background: linear-gradient(90deg, rgba(255, 255, 255, .98) 0%, rgba(255, 255, 255, .94) 84%, rgba(255, 255, 255, 0) 100%);
    }

    .industrial-menu-card__eyebrow {
        display: none;
    }

    .industrial-menu-card__title {
        max-width: 15rem;
        color: #14213d;
        font-size: clamp(1rem, 1.55vw, 1.2rem);
        line-height: 1.08;
    }

    .industrial-menu-card__description {
        max-width: 14rem;
        margin-top: .65rem;
        color: #64748b;
        font-size: .78rem;
        line-height: 1.45;
    }

    .industrial-menu-card__action {
        gap: .55rem;
        margin-top: auto;
        padding: .28rem .68rem .28rem .28rem;
        color: #1e293b;
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: 999px;
        background: rgba(255, 255, 255, .76);
        box-shadow: 0 7px 15px rgba(31, 35, 72, .08);
        font-size: .74rem;
        white-space: nowrap;
    }

    .industrial-menu-card__action i {
        order: -1;
        display: grid;
        width: 1.85rem;
        height: 1.85rem;
        place-items: center;
        color: #fff;
        border-radius: 999px;
        background: var(--card-accent);
        box-shadow: 0 5px 10px var(--card-glow);
        font-size: .68rem;
    }

    @media (max-width: 640px) {
        .industrial-menu-card {
            display: flex;
            min-height: 0;
            flex-direction: column;
        }

        .industrial-menu-card::before {
            top: .85rem;
            left: 1rem;
            font-size: 1.9rem;
        }

        .industrial-menu-card__visual {
            position: relative;
            inset: auto;
            width: 100%;
            height: 11.25rem;
            flex: 0 0 11.25rem;
            border-radius: 0;
        }

        .industrial-menu-card__visual img {
            transform: translateY(1px) scale(.96);
        }

        .industrial-menu-card:hover .industrial-menu-card__visual img {
            transform: translateY(-4px) scale(1.03);
        }

        .industrial-menu-card__body {
            width: 100%;
            min-height: 0;
            padding: 1rem 1rem 1.1rem;
            background: rgba(255, 255, 255, .96);
        }

        .industrial-menu-card__title {
            font-size: .95rem;
        }

        .industrial-menu-card__description {
            max-width: 100%;
            font-size: .72rem;
        }

        .industrial-menu-card__action {
            font-size: .68rem;
        }
    }
</style>
<style>
    .industrial-menu-card--pasteur-analysis {
        container: pasteur-analysis-card / inline-size;
    }

    .industrial-menu-card--pasteur-analysis .industrial-menu-card__body {
        container-type: inline-size;
    }

    .industrial-menu-card--pasteur-analysis .industrial-menu-card__title {
        width: 100%;
        max-width: 100%;
        font-size: .9rem;
        font-size: clamp(.75rem, 11cqi, 1.2rem);
        line-height: 1.2;
        word-break: normal;
        overflow-wrap: normal;
        text-wrap: wrap;
    }

    @media (min-width: 641px) {
        @container pasteur-analysis-card (max-width: 300px) {
            .industrial-menu-card--pasteur-analysis .industrial-menu-card__body {
                width: 65%;
            }

            .industrial-menu-card--pasteur-analysis .industrial-menu-card__visual {
                left: 65%;
            }
        }
    }

    .industrial-menu-card[aria-disabled="true"] { cursor: default; }
    .industrial-menu-card__visual > i {
        position: relative;
        z-index: 1;
        color: var(--card-accent);
        font-size: clamp(4rem, 7vw, 6rem);
        filter: drop-shadow(0 17px 13px var(--card-glow));
    }
    .pasteur-menu .menu-hero-title {
        font-size: clamp(1.4rem, 3.5vw, 3.2rem);
        letter-spacing: .02em;
    }
</style>
