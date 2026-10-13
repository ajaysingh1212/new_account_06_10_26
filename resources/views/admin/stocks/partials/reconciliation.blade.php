<div class="modal fade" id="stockReconciliationModal" tabindex="-1" role="dialog" aria-labelledby="stockReconciliationTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document"><div class="modal-content">
        <div class="modal-header"><h5 id="stockReconciliationTitle" class="modal-title">Purchase and Stock — Difference Details</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button></div>
        <div class="modal-body">
            <p>All posted purchase bills for the current company are compared with all available stock. If there are no opening balances, returns, sales, adjustments or other stock changes, these totals should match. Production moves cost from raw material to finished goods; its net value should cancel.</p>
            <div class="table-responsive"><table class="table table-sm table-bordered">
                <tbody>
                    <tr><th>Total purchase bills (including GST, after discounts)</th><td class="text-right">Rs {{ number_format($stockReconciliation['purchase_amount'], 2) }}</td></tr>
                    @foreach($stockReconciliation['bridge'] as $type => $value)
                        <tr><td>{{ ucwords(str_replace('_', ' ', $type)) }}</td><td class="text-right">Rs {{ number_format($value, 2) }}</td></tr>
                    @endforeach
                    <tr><th>Expected stock after these movements</th><th class="text-right">Rs {{ number_format($stockReconciliation['expected_value'], 2) }}</th></tr>
                    <tr><th>Actual available stock valuation</th><th class="text-right">Rs {{ number_format($stockReconciliation['stock_value'], 2) }}</th></tr>
                    <tr class="{{ $stockReconciliation['unexplained_difference'] != 0 ? 'table-warning' : 'table-success' }}"><th>Unreconciled difference (actual − expected)</th><th class="text-right">Rs {{ number_format($stockReconciliation['unexplained_difference'], 2) }}</th></tr>
                </tbody>
            </table></div>
            <h6 class="font-weight-bold">Purchase bill posting checks</h6>
            <div class="table-responsive"><table class="table table-sm table-bordered">
                <thead><tr><th>Purchase Bill</th><th>Item / Check</th><th>Bill Quantity</th><th>Posted Quantity</th><th>Bill Amount</th><th>Posted / Line Amount</th><th>Reason / What to check</th></tr></thead>
                <tbody>
                @forelse($stockReconciliation['bill_issues'] as $issue)
                    @if(app(\App\Services\EntryVisibilityService::class)->canView(auth()->user(), $issue['bill']))
                        <tr><td><a href="{{ route('admin.purchases.show', $issue['bill']) }}">{{ $issue['bill']->invoice_no }}</a></td><td>{{ $issue['item_name'] }}</td><td>{{ $issue['bill_qty'] === null ? '-' : number_format($issue['bill_qty'], 3) }}</td><td>{{ $issue['posted_qty'] === null ? '-' : number_format($issue['posted_qty'], 3) }}</td><td>Rs {{ number_format($issue['bill_value'], 2) }}</td><td>Rs {{ number_format($issue['posted_value'], 2) }}</td><td>{{ $issue['reason'] }}</td></tr>
                    @else
                        <tr><td colspan="7">A restricted purchase bill has a posting difference. Ask your company admin to review its details.</td></tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="text-success">No purchase bill posting difference found.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <h6 class="font-weight-bold">Item-wise reconciliation</h6>
            <div class="table-responsive"><table class="table table-sm table-bordered">
                <thead><tr><th>Item</th><th>Available Qty</th><th>Purchase Amount</th><th>Expected Stock</th><th>Actual Stock</th><th>Stock − Purchase</th><th>Unreconciled</th><th>Reason / History</th></tr></thead>
                <tbody>
                @forelse($stockReconciliation['rows'] as $row)
                    <tr class="{{ $row['unexplained'] != 0 ? 'table-warning' : '' }}"><td>{{ $row['item']->name }}<small class="d-block text-muted">{{ $row['item']->item_code }}</small></td><td>{{ number_format($row['quantity'], 3) }} {{ $row['item']->unit }}</td><td>Rs {{ number_format($row['purchase'], 2) }}</td><td>Rs {{ number_format($row['expected'], 2) }}</td><td>Rs {{ number_format($row['stock'], 2) }}</td><td>Rs {{ number_format($row['difference'], 2) }}</td><td>Rs {{ number_format($row['unexplained'], 2) }}</td><td>@forelse($row['reasons'] as $reason)<div class="mb-1">{{ $reason }}</div>@empty <span class="text-success">Purchase and stock match.</span> @endforelse<a href="{{ route('admin.stocks.history', ['item_id' => $row['item']->id, 'period' => 'all']) }}">View all stock history</a></td></tr>
                @empty
                    <tr><td colspan="8">No purchase or stock entries found.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
    </div></div>
</div>
