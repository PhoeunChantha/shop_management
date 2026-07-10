<style>
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
        font-family: 'Helvetica Neue', Arial, sans-serif;
        color: #1f2937;
        background: #f3f4f6;
        font-size: 13px;
        line-height: 1.5;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .sheet {
        max-width: 820px;
        margin: 24px auto;
        background: #fff;
        padding: 48px 52px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        border-radius: 4px;
    }

    /* Screen-only toolbar */
    .toolbar {
        max-width: 820px;
        margin: 20px auto 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .toolbar a, .toolbar button {
        font: inherit;
        font-weight: 700;
        font-size: 13px;
        padding: 9px 16px;
        border-radius: 9px;
        border: 1px solid #d1d5db;
        background: #fff;
        color: #111827;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }
    .toolbar .btn-print { background: #101928; color: #fff; border-color: #101928; }

    /* Header */
    .doc-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 26px;
        border-bottom: 2px solid #111827;
    }
    .brand { display: flex; align-items: center; gap: 12px; }
    .brand img { max-height: 46px; max-width: 160px; object-fit: contain; }
    .brand__name { font-size: 20px; font-weight: 800; letter-spacing: -0.01em; color: #111827; }
    .brand__meta { margin-top: 6px; font-size: 11.5px; color: #6b7280; line-height: 1.55; }

    .doc-title { text-align: right; }
    .doc-title h1 { margin: 0; font-size: 30px; font-weight: 800; letter-spacing: 0.04em; color: #111827; }
    .doc-title .num { margin-top: 4px; font-size: 13px; font-weight: 700; color: #374151; font-family: 'Courier New', monospace; }
    .doc-title .date { margin-top: 2px; font-size: 12px; color: #6b7280; }

    .pill {
        display: inline-block;
        margin-top: 8px;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border: 1px solid currentColor;
    }

    /* Parties */
    .parties { display: flex; gap: 40px; margin: 28px 0; }
    .party { flex: 1; }
    .party h3 {
        margin: 0 0 6px;
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #9ca3af;
    }
    .party p { margin: 0; font-size: 12.5px; color: #374151; line-height: 1.6; }
    .party .name { font-weight: 700; color: #111827; font-size: 13.5px; }

    /* Items table */
    table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.items thead th {
        background: #111827;
        color: #fff;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 11px 14px;
        text-align: left;
    }
    table.items thead th.r { text-align: right; }
    table.items thead th.c { text-align: center; }
    table.items tbody td { padding: 13px 14px; border-bottom: 1px solid #eceef1; vertical-align: top; }
    table.items tbody td.r { text-align: right; }
    table.items tbody td.c { text-align: center; }
    .item-name { font-weight: 700; color: #111827; }
    .item-variant { font-size: 11.5px; color: #6b7280; margin-top: 2px; }
    .item-sku { font-size: 11px; color: #9ca3af; font-family: 'Courier New', monospace; }
    .mono { font-variant-numeric: tabular-nums; }

    /* Totals */
    .totals { display: flex; justify-content: flex-end; margin-top: 22px; }
    .totals table { width: 300px; border-collapse: collapse; }
    .totals td { padding: 6px 4px; font-size: 13px; }
    .totals td.lbl { color: #6b7280; }
    .totals td.val { text-align: right; font-weight: 600; color: #111827; }
    .totals tr.grand td { padding-top: 12px; border-top: 2px solid #111827; font-size: 16px; font-weight: 800; color: #111827; }

    .note-box {
        margin-top: 26px;
        padding: 14px 16px;
        background: #f9fafb;
        border: 1px solid #eceef1;
        border-radius: 8px;
        font-size: 12px;
        color: #4b5563;
    }
    .note-box strong { display: block; color: #111827; margin-bottom: 3px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }

    .doc-foot {
        margin-top: 34px;
        padding-top: 18px;
        border-top: 1px solid #eceef1;
        text-align: center;
        font-size: 11.5px;
        color: #9ca3af;
    }

    @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
        .sheet { margin: 0; max-width: none; box-shadow: none; padding: 0; border-radius: 0; }
        @page { margin: 16mm; }
    }
</style>
