<style>
    @page { margin: 40px 44px; }
    * { box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px;
        line-height: 1.5;
        color: #1a1a2e;
        margin: 0;
        padding: 0;
    }
    .receipt-page { page-break-after: always; }
    .receipt-page:last-child { page-break-after: auto; }
    .header-table { width: 100%; border-collapse: collapse; }
    .badge {
        display: inline-block;
        width: 34px;
        height: 34px;
        background: #0f766e;
        color: #ffffff;
        border-radius: 17px;
        text-align: center;
        font-size: 16px;
        font-weight: 700;
        line-height: 34px;
    }
    .logo {
        width: 34px;
        height: 34px;
        border-radius: 17px;
        vertical-align: middle;
    }
    .org-name {
        font-size: 18px;
        font-weight: 700;
        color: #1a1a2e;
        padding-left: 10px;
    }
    .doc-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f766e;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .doc-subtitle { font-size: 10px; color: #64748b; }
    .rule { border: 0; border-top: 2px solid #0f766e; margin: 12px 0 26px; }
    .thanks { font-size: 22px; font-weight: 700; color: #1a1a2e; margin: 0 0 4px; }
    .thanks-sub { font-size: 11px; color: #64748b; margin: 0 0 24px; }
    .section-label {
        font-size: 9px;
        font-weight: 700;
        color: #0f766e;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }
    .panel { background: #f1f5f9; padding: 14px 16px; }
    .kv { font-size: 11px; }
    .kv .k { color: #64748b; }
    .kv .v { font-weight: 700; }
    .status-value { font-size: 16px; font-weight: 700; color: #0f766e; }
    .col-block .name { font-size: 13px; font-weight: 700; color: #1a1a2e; }
    .col-block .meta { font-size: 11px; color: #64748b; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.items th {
        background: #0f766e;
        color: #ffffff;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.5px;
        text-align: left;
        padding: 9px 12px;
    }
    table.items th.right, table.items td.right { text-align: right; }
    table.items td { padding: 11px 12px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
    tr.total td { background: #f0fdfa; border-bottom: 0; padding: 12px; }
    tr.total .label { font-size: 13px; font-weight: 700; color: #1a1a2e; }
    tr.total .amount { font-size: 17px; font-weight: 700; color: #0f766e; }
    .paymeta { font-size: 11px; color: #1a1a2e; margin-top: 16px; }
    .note { font-size: 10px; color: #64748b; margin-top: 6px; }
    .quote { font-size: 10px; color: #64748b; font-style: italic; margin-top: 10px; }
    .footer { margin-top: 40px; padding-top: 14px; border-top: 1px solid #e2e8f0; }
    .footer .fname { font-size: 12px; font-weight: 700; color: #1a1a2e; }
    .footer .fcontact { font-size: 10px; color: #64748b; margin-top: 3px; }
    .footer .fdisc { font-size: 10px; color: #64748b; }
    .footer .fbrand { font-size: 10px; font-weight: 700; color: #0f766e; }
</style>
