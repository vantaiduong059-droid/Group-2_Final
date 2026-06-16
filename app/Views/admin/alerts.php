<?php ob_start(); ?>

<style>
    .status-badge {
        font-size: 0.78rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 20px;
    }
    .status-pending { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .status-resolved { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }

    .nav-tabs-modern {
        border-bottom: 2px solid var(--border-color-darker);
        gap: 8px;
    }
    .nav-tabs-modern .nav-link {
        border: none;
        color: var(--text-muted);
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px 8px 0 0;
        position: relative;
        transition: all 0.2s ease;
        background: transparent;
    }
    .nav-tabs-modern .nav-link:hover {
        color: var(--primary);
        background: rgba(37, 99, 235, 0.04);
    }
    .nav-tabs-modern .nav-link.active {
        color: var(--primary);
        background: transparent;
    }
    .nav-tabs-modern .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: var(--primary);
        border-radius: 2px;
    }
    .config-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-main);
        margin-bottom: 6px;
    }
</style>
