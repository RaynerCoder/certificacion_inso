<style>
    .producto-show { display: grid; gap: 16px; margin-top: 16px; }
    .producto-show-section { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    .producto-show-section-head { display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; padding: 13px 16px; }
    .producto-show-section-number { display: inline-flex; width: 28px; height: 28px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 999px; background: #059669; color: #fff; font-size: 12px; font-weight: 900; }
    .producto-show-section-head h1, .producto-show-section-head h2 { margin: 0; color: #0f172a; font-size: 14px; font-weight: 900; }
    .producto-show-section-head p { margin: 2px 0 0; color: #64748b; font-size: 11px; font-weight: 600; }
    .producto-show-general-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 0; padding: 4px 16px 12px; }
    .producto-show-general-grid>div { min-width: 0; border-bottom: 1px solid #f1f5f9; padding: 10px 12px; }
    .producto-show-general-grid .producto-show-field-wide { grid-column: span 2; }
    .producto-show-general-grid dt, .producto-show-block-label, .producto-show-presentation-data dt, .producto-show-register-data dt { color: #64748b; font-size: 9px; font-weight: 900; text-transform: uppercase; }
    .producto-show-general-grid dd, .producto-show-presentation-data dd, .producto-show-register-data dd { margin: 3px 0 0; color: #0f172a; font-size: 11px; font-weight: 800; overflow-wrap: anywhere; }
    .producto-show-field-detail { display: block; margin-top: 3px; color: #64748b; font-size: 10px; font-weight: 600; }
    .producto-show-status { display: inline-flex; border: 1px solid #bbf7d0; border-radius: 999px; background: #ecfdf5; color: #047857; padding: 3px 8px; font-size: 9px; font-weight: 900; }
    .producto-show-status.is-inactive { border-color: #cbd5e1; background: #f1f5f9; color: #64748b; }
    .producto-show-table-wrap { margin: 14px 16px 16px; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
    .producto-show-table { width: 100%; border-collapse: collapse; }
    .producto-show-table th { border-bottom: 1px solid #e2e8f0; background: #f8fafc; color: #475569; padding: 9px 12px; text-align: left; font-size: 9px; font-weight: 900; text-transform: uppercase; }
    .producto-show-table td { border-bottom: 1px solid #f1f5f9; color: #334155; padding: 9px 12px; font-size: 11px; }
    .producto-show-table tbody tr:last-child td { border-bottom: 0; }
    .producto-show-empty { padding: 22px !important; text-align: center; color: #64748b !important; }
    .producto-show-presentations { display: grid; gap: 14px; padding: 14px; }
    .producto-show-presentation { overflow: hidden; border: 1px solid #dbe3ec; border-radius: 9px; background: #fff; }
    .producto-show-presentation-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; padding: 10px 14px; }
    .producto-show-presentation-head>div>span { color: #0f766e; font-size: 9px; font-weight: 900; text-transform: uppercase; }
    .producto-show-presentation-head h3 { margin: 2px 0 0; color: #0f172a; font-size: 13px; font-weight: 900; }
    .producto-show-presentation-body { display: grid; grid-template-columns: 140px minmax(190px, .65fr) minmax(500px, 1.7fr); gap: 14px; padding: 12px; }
    .producto-show-label-preview { display: grid; min-width: 0; align-content: start; gap: 7px; }
    .producto-show-label-image, .producto-show-label-empty, .producto-show-file-link { display: flex; min-height: 118px; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #dbe3ec; border-radius: 8px; background: #f8fafc; position: relative; }
    .producto-show-label-image img { width: 100%; height: 105px; object-fit: contain; }
    .producto-show-label-image>span { display: inline-flex; width: 26px; height: 26px; align-items: center; justify-content: center; position: absolute; right: 8px; bottom: 8px; border-radius: 999px; background: rgba(15,23,42,.72); color: #fff; }
    .producto-show-label-empty { flex-direction: column; gap: 6px; color: #94a3b8; font-size: 20px; }
    .producto-show-label-empty span, .producto-show-file-link { color: #64748b; font-size: 10px; font-weight: 700; text-align: center; }
    .producto-show-label-action { min-height: 30px; border: 1px solid #6ee7b7; border-radius: 7px; background: #fff; color: #047857; font-size: 10px; font-weight: 900; }
    .producto-show-file-name { overflow: hidden; color: #64748b; font-size: 9px; text-align: center; text-overflow: ellipsis; white-space: nowrap; }
    .producto-show-presentation-data { min-width: 0; border-right: 1px solid #e2e8f0; padding-right: 14px; }
    .producto-show-presentation-data dl { display: grid; gap: 0; margin: 4px 0 0; }
    .producto-show-presentation-data dl>div { border-bottom: 1px solid #f1f5f9; padding: 6px 0 8px; }
    .producto-show-registers { min-width: 0; }
    .producto-show-registers-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 7px; }
    .producto-show-registers-head h4 { margin: 0; color: #0f172a; font-size: 11px; font-weight: 900; }
    .producto-show-registers-head>span { color: #64748b; font-size: 9px; font-weight: 800; }
    .producto-show-register-list, .producto-show-orphan-registers { display: grid; gap: 9px; }
    .producto-show-orphan-registers { padding: 12px; }
    .producto-show-register-item { overflow: hidden; border: 1px solid #dbe3ec; border-radius: 8px; background: #fff; }
    .producto-show-register-item-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; border-bottom: 1px solid #e2e8f0; padding: 8px 10px; }
    .producto-show-register-title { display: flex; min-width: 0; align-items: center; gap: 8px; }
    .producto-show-register-title>span { display: inline-flex; width: 28px; height: 28px; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 7px; background: #eff6ff; color: #2563eb; }
    .producto-show-register-title h5 { margin: 0; color: #0f172a; font-size: 10px; font-weight: 900; }
    .producto-show-register-title p { margin: 1px 0 0; color: #64748b; font-size: 8px; font-weight: 600; }
    .producto-show-register-data { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin: 0; }
    .producto-show-register-data>div { min-width: 0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 8px 10px; }
    .producto-show-register-data>div:nth-child(3n) { border-right: 0; }
    .producto-show-register-data>div:nth-child(n+4) { border-bottom: 0; }
    .producto-show-empty-box { border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; color: #64748b; padding: 12px; font-size: 10px; font-weight: 700; text-align: center; }
    .producto-show-modal { display: none; position: fixed; inset: 0; z-index: 60; align-items: center; justify-content: center; background: rgba(15,23,42,.72); padding: 16px; }
    .producto-show-modal.is-open { display: flex; }
    .producto-show-modal-dialog { width: 100%; max-width: 780px; border-radius: 12px; background: #fff; padding: 14px; box-shadow: 0 25px 60px rgba(15,23,42,.35); }
    .producto-show-modal-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
    .producto-show-modal-head strong { color: #0f172a; }
    .producto-show-modal-head button { border-radius: 7px; padding: 7px 10px; color: #475569; font-size: 11px; font-weight: 800; }
    .producto-show-modal-dialog img { width: 100%; max-height: 75vh; object-fit: contain; }

    @media (max-width: 1100px) {
        .producto-show-general-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .producto-show-presentation-body { grid-template-columns: 140px minmax(0, 1fr); }
        .producto-show-presentation-data { border-right: 0; padding-right: 0; }
        .producto-show-registers { grid-column: 1 / -1; }
    }
    @media (max-width: 640px) {
        .producto-show-general-grid { grid-template-columns: 1fr; padding-inline: 10px; }
        .producto-show-general-grid .producto-show-field-wide { grid-column: auto; }
        .producto-show-presentation-body, .producto-show-register-data { grid-template-columns: 1fr; }
        .producto-show-register-data>div, .producto-show-register-data>div:nth-child(3n), .producto-show-register-data>div:nth-child(n+4) { border-right: 0; border-bottom: 1px solid #e2e8f0; }
        .producto-show-register-data>div:last-child { border-bottom: 0; }
        .producto-show-label-image, .producto-show-label-empty, .producto-show-file-link { min-height: 200px; }
        .producto-show-label-image img { height: 185px; }
    }
</style>
