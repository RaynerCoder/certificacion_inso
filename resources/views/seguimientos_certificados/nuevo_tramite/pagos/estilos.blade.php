<style>
.pago-tramite-page{width:100%;max-width:none;min-width:0;margin:12px 0 32px;color:#0f172a;container-type:inline-size}
.pago-tramite-page *{box-sizing:border-box}
.pago-tramite-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:20px}
.pago-tramite-head>div{flex:1 1 260px;min-width:0}
.pago-tramite-eyebrow{color:#0f766e;font-size:12px;font-weight:600}
.pago-tramite-head h1{font-size:26px;line-height:1.25;font-weight:700;margin:6px 0 8px}
.pago-tramite-head p{font-size:14px;line-height:1.6;color:#64748b;margin:0}
.pago-tramite-back,.pago-tramite-secondary,.pago-tramite-primary{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;cursor:pointer;line-height:1.4}
.pago-tramite-back,.pago-tramite-secondary{border:1px solid #cbd5e1;background:#fff;color:#334155}
.pago-tramite-back:hover,.pago-tramite-secondary:hover{background:#f1f5f9}
.pago-tramite-back{flex-shrink:0}
.pago-tramite-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);gap:20px;align-items:stretch}
.pago-tramite-card{display:flex;flex-direction:column;min-width:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden}
.pago-tramite-card-title{display:flex;align-items:center;gap:12px;padding:20px 24px;border-bottom:1px solid #e2e8f0}
.pago-tramite-card-title h2{margin:0;font-size:16px;font-weight:700;line-height:1.4}
.pago-tramite-card-title p{margin:4px 0 0;font-size:12px;color:#64748b;line-height:1.5}
.pago-tramite-icon{display:flex;align-items:center;justify-content:center;width:40px;height:40px;flex-shrink:0;border-radius:10px;background:#ecfdf5;color:#0f766e;font-size:18px}
.pago-tramite-data{padding:4px 24px;margin:0}
.pago-tramite-data>div{display:grid;grid-template-columns:125px minmax(0,1fr);gap:16px;padding:16px 0;border-bottom:1px solid #f1f5f9}
.pago-tramite-data>div:last-child{border-bottom:0}
.pago-tramite-data dt{font-size:12px;color:#64748b;line-height:1.6}
.pago-tramite-data dd{margin:0;font-size:13px;font-weight:600;line-height:1.6;overflow-wrap:anywhere}
.pago-tramite-status{display:inline-block;padding:4px 9px;border-radius:6px;background:#fffbeb;color:#92400e;font-size:11px;font-weight:600}
.pago-tramite-status.is-confirmed{background:#ecfdf5;color:#047857}
.pago-tramite-amount{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px 20px;margin:8px 24px 24px;padding:18px 0;border-top:2px solid #e2e8f0}
.pago-tramite-amount>span{font-size:14px;font-weight:600;color:#334155}
.pago-tramite-amount strong{font-size:24px;font-weight:700;line-height:1.3;color:#0f172a;font-variant-numeric:tabular-nums;white-space:nowrap}
.pago-tramite-amount small{font-size:16px;font-weight:600}
.pago-tramite-help{margin-top:auto;padding:20px 24px;border-top:1px solid #e2e8f0;background:#f8fafc}
.pago-tramite-help h3{margin:0 0 12px;font-size:13px;font-weight:700}
.pago-tramite-help ol{padding-left:20px;margin:0;list-style:decimal;font-size:12px;line-height:1.7;color:#475569}
.pago-tramite-help li+li{margin-top:8px}
.pago-tramite-qr-body{display:flex;flex-direction:column;align-items:center;padding:24px;min-width:0}
.pago-tramite-qr{display:flex;align-items:center;justify-content:center;width:min(100%,380px);min-width:0;aspect-ratio:1;padding:8px;background:white}
.pago-tramite-qr img{display:block;width:100%;height:100%;object-fit:contain}
.pago-tramite-qr-empty{display:flex;flex-direction:column;align-items:center;gap:12px;padding:16px;text-align:center}
.pago-tramite-qr-empty>i{font-size:64px;color:#cbd5e1}
.pago-tramite-qr-empty strong{font-size:15px;font-weight:600;color:#475569}
.pago-tramite-qr-empty p{font-size:12px;line-height:1.6;color:#64748b;margin:0}
.pago-tramite-qr-caption{font-size:12px;color:#475569;text-align:center;line-height:1.6;margin:14px 0 0;font-variant-numeric:tabular-nums}
.pago-tramite-qr-actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:18px;width:100%}
.pago-tramite-primary{margin-top:20px;background:#0f766e;color:white;border:1px solid #0f766e;width:100%;max-width:420px}
.pago-tramite-primary:hover{background:#115e59}
.pago-tramite-primary:disabled{opacity:.6;cursor:wait}
.pago-tramite-note{display:flex;align-items:flex-start;gap:9px;margin-top:22px;padding:14px 0 0;border-top:1px solid #e2e8f0;color:#64748b;width:100%;font-size:12px;line-height:1.6}
.pago-tramite-note i{margin-top:3px;flex-shrink:0;color:#0f766e}
.pago-tramite-note p{margin:0}
.pago-tramite-refresh{display:inline-flex;align-items:center;gap:8px;color:#0f766e;font-size:12px;font-weight:600;margin-top:16px;text-decoration:underline;text-underline-offset:3px}
.pago-tramite-confirmed{display:flex;flex-direction:column;align-items:center;gap:16px;padding:36px 8px;text-align:center}
.pago-tramite-confirmed i{font-size:56px;color:#059669}
.pago-tramite-confirmed strong{font-size:19px}
.pago-tramite-confirmed p{font-size:13px;color:#64748b;line-height:1.6;margin:0}
@container(max-width:800px){.pago-tramite-grid{grid-template-columns:minmax(0,1fr);gap:18px}}
@container(max-width:480px){.pago-tramite-head{flex-direction:column}.pago-tramite-head>div{flex:auto}.pago-tramite-data>div{grid-template-columns:minmax(0,1fr);gap:4px}.pago-tramite-qr-actions{display:grid;grid-template-columns:1fr}.pago-tramite-secondary{width:100%}}
@supports not (container-type:inline-size){@media(max-width:1100px){.pago-tramite-grid{grid-template-columns:minmax(0,1fr);gap:18px}}}
@media(max-width:540px){.pago-tramite-page{margin-top:4px}.pago-tramite-head{gap:12px;margin-bottom:18px}.pago-tramite-head h1{font-size:21px}.pago-tramite-head p{font-size:12px}.pago-tramite-back{padding:8px 10px;min-height:38px}.pago-tramite-card-title{padding:16px}.pago-tramite-data{padding:0 16px}.pago-tramite-data>div{grid-template-columns:minmax(0,1fr);gap:4px;padding:12px 0}.pago-tramite-amount{margin:12px 16px 16px;padding:16px 0}.pago-tramite-help{padding:16px}.pago-tramite-qr-body{padding:16px}.pago-tramite-qr-actions{display:grid;grid-template-columns:1fr}.pago-tramite-secondary{width:100%}.pago-tramite-qr{padding:10px}.pago-tramite-amount strong{font-size:22px}}
</style>
