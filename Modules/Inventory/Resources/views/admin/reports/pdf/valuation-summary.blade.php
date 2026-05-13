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
.badge { background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-size: 10px; }
</style>
</head>
<body>

<h1>Stock Valuation Summary</h1>
<p class="subtitle">Generated: {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp; Grand Total: {{ number_format($grandTotal, 2) }}</p>

<h3>Breakdown by Valuation Method</h3>
<table>
    <thead>
        <tr>
            <th>Valuation Method</th>
            <th class="text-right">Items</th>
            <th class="text-right">Total Qty</th>
            <th class="text-right">Total Value</th>
            <th class="text-right">% of Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($byMethod as $method => $data)
        <tr>
            <td>{{ strtoupper(str_replace('_', ' ', $method)) }}</td>
            <td class="text-right">{{ $data['count'] }}</td>
            <td class="text-right">{{ number_format($data['total_stock'], 2) }}</td>
            <td class="text-right">{{ number_format($data['total_value'], 2) }}</td>
            <td class="text-right">{{ $grandTotal > 0 ? number_format($data['total_value'] / $grandTotal * 100, 1) : 0 }}%</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">Total</td>
            <td class="text-right">{{ number_format($rows->sum('stock'), 2) }}</td>
            <td class="text-right">{{ number_format($grandTotal, 2) }}</td>
            <td class="text-right">100%</td>
        </tr>
    </tfoot>
</table>

<h3>Item Detail</h3>
<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Vendor</th>
            <th class="text-right">Stock</th>
            <th class="text-right">Avg Cost</th>
            <th class="text-right">Total Value</th>
            <th>Method</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td>{{ $item->store?->name ?? '—' }}</td>
            <td class="text-right">{{ number_format($item->stock, 2) }}</td>
            <td class="text-right">{{ number_format($item->average_cost ?? 0, 2) }}</td>
            <td class="text-right">{{ number_format($item->total_stock_value ?? 0, 2) }}</td>
            <td>{{ strtoupper($item->valuation_method ?? 'default') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
