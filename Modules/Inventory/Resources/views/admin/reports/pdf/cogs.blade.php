<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
h1 { font-size: 16px; margin-bottom: 4px; }
.subtitle { color: #666; font-size: 10px; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
th { background: #f0f0f0; padding: 6px 8px; text-align: left; border: 1px solid #ccc; }
td { padding: 5px 8px; border: 1px solid #ddd; }
tfoot td { font-weight: bold; background: #f8f8f8; }
.text-right { text-align: right; }
</style>
</head>
<body>

<h1>Cost of Goods Sold (COGS) Report</h1>
<p class="subtitle">
    Period: {{ $from }} — {{ $to }} &nbsp;|&nbsp;
    Generated: {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp;
    Total COGS: {{ number_format($totalCogs, 2) }}
</p>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Item</th>
            <th>Vendor</th>
            <th class="text-right">Qty Sold</th>
            <th class="text-right">Unit Cost</th>
            <th class="text-right">COGS</th>
            <th>Reference</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $m)
        <tr>
            <td>{{ $m->created_at->format('d M Y H:i') }}</td>
            <td>{{ $m->item?->name ?? "Item #{$m->item_id}" }}</td>
            <td>{{ $m->store?->name ?? '—' }}</td>
            <td class="text-right">{{ abs($m->qty) }}</td>
            <td class="text-right">{{ number_format($m->unit_cost, 2) }}</td>
            <td class="text-right">{{ number_format(abs($m->total_cost ?? ($m->qty * $m->unit_cost)), 2) }}</td>
            <td>{{ $m->note }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">Total</td>
            <td class="text-right">{{ number_format($totalCogs, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

</body>
</html>
