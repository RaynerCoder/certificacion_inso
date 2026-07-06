{{-- Estilos locales: mejoran la lectura del menu sin cambiar el layout general. --}}
<style>
    .cert-sidebar {
        background: #1f2937;
        border-right: 1px solid #374151;
    }

    .cert-sidebar-scroll {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow-y: auto;
        padding: 12px;
        background: #1f2937;
    }

    .cert-sidebar-menu {
        flex: 1 1 auto;
    }

    .cert-sidebar-theme-panel {
        margin-top: 12px;
        border: 1px solid #374151;
        border-radius: 8px;
        background: #111827;
        padding: 8px;
    }

    .cert-sidebar-theme-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        color: #e2e8f0;
        font-size: 11px;
        font-weight: 900;
    }

    .cert-sidebar-theme-title span {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 600;
    }

    .cert-sidebar-theme-options {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
        margin-top: 7px;
    }

    .cert-theme-option {
        display: flex;
        min-height: 30px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid #4b5563;
        border-radius: 7px;
        background: transparent;
        color: #cbd5e1;
        font-size: 11px;
        font-weight: 800;
        transition: all 160ms ease;
    }

    .cert-theme-option:hover {
        border-color: #10b981;
        color: #ffffff;
    }

    .cert-theme-option.is-selected {
        border-color: #34d399;
        background: #065f46;
        color: #ffffff;
    }

    .cert-sidebar-section {
        margin-bottom: 8px;
    }

    .cert-menu-link,
    .cert-menu-button {
        position: relative;
        display: flex;
        width: 100%;
        align-items: center;
        gap: 10px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #cbd5e1;
        padding: 10px 11px;
        text-align: left;
        transition: background 160ms ease, color 160ms ease;
    }

    .cert-menu-link:hover,
    .cert-menu-button:hover {
        background: #374151;
        color: #ffffff;
    }

    .cert-menu-link.is-active,
    .cert-menu-button.is-active {
        background: #065f46;
        color: #ffffff;
        box-shadow: inset 3px 0 0 #34d399;
    }

    .cert-menu-link.is-disabled,
    .cert-submenu-link.is-disabled {
        cursor: not-allowed;
        opacity: 0.55;
    }

    .cert-menu-icon {
        display: inline-flex;
        width: 32px;
        height: 32px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(209, 250, 229, 0.10);
        color: #d1fae5;
        font-size: 14px;
    }

    .cert-menu-link.is-active .cert-menu-icon,
    .cert-menu-button.is-active .cert-menu-icon {
        background: rgba(52, 211, 153, 0.18);
        color: #a7f3d0;
    }

    .cert-menu-text {
        min-width: 0;
        flex: 1;
    }

    .cert-menu-title {
        display: block;
        font-size: 14px;
        font-weight: 800;
        line-height: 1.1;
    }

    .cert-menu-description {
        display: block;
        margin-top: 3px;
        color: #94a3b8;
        font-size: 11px;
        font-weight: 500;
        line-height: 1.2;
    }

    .cert-menu-chevron {
        color: #94a3b8;
        font-size: 11px;
    }

    .cert-submenu {
        margin: 6px 0 8px 16px;
        border-left: 1px solid #4b5563;
        padding-left: 10px;
    }

    .cert-submenu-link {
        display: flex;
        align-items: center;
        gap: 9px;
        border-radius: 7px;
        color: #cbd5e1;
        padding: 8px 9px;
        font-size: 13px;
        font-weight: 700;
        transition: background 160ms ease, color 160ms ease;
    }

    .cert-submenu-link:hover {
        background: #374151;
        color: #ffffff;
    }

    .cert-submenu-link.is-active {
        background: #d1fae5;
        color: #065f46;
    }

    .cert-submenu-icon {
        display: inline-flex;
        width: 20px;
        justify-content: center;
        color: currentColor;
        font-size: 12px;
    }

    .cert-module-pill {
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.14);
        color: #94a3b8;
        padding: 2px 7px;
        font-size: 10px;
        font-weight: 800;
    }

    .cert-sidebar[data-sidebar-theme="light"] {
        background: #ffffff;
        border-right-color: #e5e7eb;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-sidebar-scroll {
        background: #ffffff;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-sidebar-theme-panel {
        border-color: #e2e8f0;
        background: #f8fafc;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-sidebar-theme-title {
        color: #0f172a;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-theme-option {
        border-color: #e2e8f0;
        background: #ffffff;
        color: #475569;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-theme-option:hover {
        border-color: #059669;
        color: #047857;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-theme-option.is-selected {
        border-color: #059669;
        background: #d1fae5;
        color: #047857;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-link,
    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-button {
        color: #334155;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-link:hover,
    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-button:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-link.is-active,
    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-button.is-active {
        background: #d1fae5;
        color: #047857;
        box-shadow: inset 3px 0 0 #059669;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-icon {
        background: #f1f5f9;
        color: #64748b;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-link.is-active .cert-menu-icon,
    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-button.is-active .cert-menu-icon {
        background: #a7f3d0;
        color: #065f46;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-description,
    .cert-sidebar[data-sidebar-theme="light"] .cert-menu-chevron {
        color: #64748b;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-submenu {
        border-left-color: #cbd5e1;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-submenu-link {
        color: #475569;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-submenu-link:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-submenu-link.is-active {
        background: #059669;
        color: #ffffff;
    }

    .cert-sidebar[data-sidebar-theme="light"] .cert-module-pill {
        background: #e2e8f0;
        color: #475569;
    }
</style>
