<style>
    .bp-hero {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 55%, #f59e0b 140%);
        border-radius: 16px; padding: 1.75rem; position: relative; overflow: hidden;
        box-shadow: 0 10px 30px rgba(30, 60, 114, .25);
    }
    .bp-hero::after {
        content: ''; position: absolute; right: -60px; top: -60px; width: 220px; height: 220px;
        border-radius: 50%; background: rgba(255, 193, 7, .18);
    }
    .bp-stat-icon {
        width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: var(--c); background: color-mix(in srgb, var(--c) 12%, white);
    }
    .bp-stat { border-left: 4px solid var(--c) !important; }
    .bp-progress { height: 6px; border-radius: 6px; background: #e9ecef; }
    .bp-progress .progress-bar { background: linear-gradient(90deg, #0d6efd, #20c997); }
    .bg-orange { background-color: #fd7e14 !important; }
    .bp-section-title { font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; }
    .bp-check-item { border: 1px solid #e2e8f0; border-radius: 10px; padding: .55rem .75rem; transition: all .15s; }
    .bp-check-item:hover { background: #f8fafc; border-color: #93c5fd; }
    .bp-check-item.highlight { background: #fffbeb; border-color: #fcd34d; }
    .bp-termin { border-radius: 14px; padding: 1rem 1.25rem; border: 1px dashed #cbd5e1; }
    .bp-termin.done { border: 1px solid #86efac; background: #f0fdf4; }
    .bp-termin.active { border: 1px solid #93c5fd; background: #eff6ff; }
    .bp-timeline { border-left: 2px solid #e2e8f0; padding-left: 1rem; }
    .bp-timeline .t-item { position: relative; padding-bottom: .9rem; }
    .bp-timeline .t-item::before { content: ''; position: absolute; left: -1.38rem; top: .3rem; width: 10px; height: 10px; border-radius: 50%; background: #0d6efd; box-shadow: 0 0 0 3px #dbeafe; }
</style>
