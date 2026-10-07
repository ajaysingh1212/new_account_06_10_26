@extends('layouts.admin')

@section('title', 'Party Statement')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.parties.index') }}">
            Parties
        </a>
    </li>

    <li class="breadcrumb-item active">
        {{ $party->display_name }}
    </li>
@endsection

@section('content')
@include('admin.parties.partials.styles')
<div class="party-workspace">

<div class="party-metrics">
    @foreach(['receivable' => ['Receivable', '#168577'], 'payable' => ['Payable', '#c45265']] as $kind => $side)
        @foreach(['opening' => 'Opening Balance', 'invoice' => 'Invoice Amount'] as $source => $label)
            @php
                $amount = $outstandingRows->where('kind', $kind)->filter(fn($row) => ($row['model'] === \App\Models\Party::class) === ($source === 'opening'))->sum('due');
            @endphp
            <div class="party-metric" style="--metric-color:{{ $side[1] }}">
                <i class="fas {{ $source === 'opening' ? 'fa-folder-open' : 'fa-file-invoice' }}"></i>
                <label>{{ $label }} / {{ $side[0] }}</label>
                <strong>Rs {{ number_format($amount, 2) }}</strong>
                <small>{{ $source === 'opening' ? ($party->opening_balance_date?->format('d M Y') ?: '-') : 'Outstanding after returns & payments' }}</small>
            </div>
        @endforeach
    @endforeach
</div>

<div class="row">

    {{-- Party Details --}}
    <div class="col-lg-4 mb-4">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex align-items-center mb-3">

                    <div class="user-avatar mr-3"
                         style="width:48px;height:48px;font-size:18px;">

                        {{ substr($party->display_name, 0, 1) }}

                    </div>

                    <div>

                        <h5 class="mb-0"
                            style="font-weight:800;color:#1A0A3D;">

                            {{ $party->display_name }}

                        </h5>

                        <div style="font-size:12px;color:#9090B0;">

                            {{ $party->party_code }}
                            ·
                            {{ ucfirst($party->party_type) }}

                        </div>

                    </div>

                </div>

                <div class="mb-2">
                    <strong>Phone:</strong>
                    {{ $party->phone ?: '—' }}
                </div>

                <div class="mb-2">
                    <strong>Email:</strong>
                    {{ $party->email ?: '—' }}
                </div>

                <div class="mb-2">
                    <strong>GSTIN:</strong>
                    {{ $party->gstin ?: '—' }}
                </div>

                <div class="mb-2">
                    <strong>PAN:</strong>
                    {{ $party->pan_number ?: '—' }}
                </div>

                <hr>

                @php
                    $receivable = (float) ($ageingBalance['receivable'] ?? 0);
                    $payable = (float) ($ageingBalance['payable'] ?? 0);
                    $netBalance = (float) ($ageingBalance['net'] ?? 0);
                    $balanceLabel = $netBalance < 0 ? 'Receivable' : ($netBalance > 0 ? 'Payable' : 'Settled');
                @endphp

                <div style="font-size:12px;color:#9090B0;">
                    Current Balance
                </div>

                <div style="font-size:30px;font-weight:800;color:#1A0A3D;">

                    ₹ {{ number_format(abs($netBalance), 2) }}

                </div>

                <span class="{{ $netBalance < 0 ? 'badge-active' : ($netBalance > 0 ? 'badge-inactive' : 'badge-user') }}">

                    {{ $balanceLabel }}

                </span>

                <div class="mt-3" style="font-size:13px;color:#64748b;">
                    <div>Receivable: <strong class="text-success">₹ {{ number_format($receivable, 2) }}</strong></div>
                    <div>Payable: <strong class="text-danger">₹ {{ number_format($payable, 2) }}</strong></div>
                </div>

            </div>

        </div>

    </div>

    {{-- Address & Terms --}}
    <div class="col-lg-8 mb-4">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h3 class="card-title m-0">

                    <i class="fas fa-map-marker-alt mr-2 text-purple"></i>

                    Address & Terms

                </h3>

                <a href="{{ route('admin.parties.edit', $party) }}"
                   class="btn btn-primary btn-sm">

                    <i class="fas fa-edit mr-1"></i>

                    Edit

                </a>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6">

                        <h6>Billing Address</h6>

                        <p class="text-muted">

                            {{ $party->billing_address ?: '—' }}

                        </p>

                    </div>

                    <div class="col-md-6">

                        <h6>Shipping Address</h6>

                        <p class="text-muted">

                            {{ $party->shipping_address ?: '—' }}

                        </p>

                    </div>

                    <div class="col-md-4">

                        <strong>Credit Limit:</strong>

                        <br>

                        ₹ {{ number_format((float) $party->credit_limit, 2) }}

                    </div>

                    <div class="col-md-4">

                        <strong>Credit Days:</strong>

                        <br>

                        {{ $party->credit_days ?? 0 }} days

                    </div>

                    <div class="col-md-4">

                        <strong>Terms:</strong>

                        <br>

                        {{ $party->payment_terms ?: '—' }}

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@php
    $ledgerFinal = (float) ($statementSummary['final_balance'] ?? 0);
    $ledgerFinalLabel = $ledgerFinal < 0 ? 'Receivable' : ($ledgerFinal > 0 ? 'Payable' : 'Settled');
    $customerAdvanceTotal = (float) ($availableCustomerAdvances->sum('remaining_amount') ?? 0);
    $supplierAdvanceTotal = (float) ($availableSupplierAdvances->sum('remaining_amount') ?? 0);
@endphp
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Total Sale</div><strong>Rs {{ number_format((float) ($statementSummary['sale'] ?? 0), 2) }}</strong></div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Sales Return</div><strong>Rs {{ number_format((float) ($statementSummary['sales_return'] ?? 0), 2) }}</strong></div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Payment In</div><strong>Rs {{ number_format((float) ($statementSummary['payment_in'] ?? 0), 2) }}</strong></div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Final {{ $ledgerFinalLabel }}</div><strong>Rs {{ number_format(abs($ledgerFinal), 2) }}</strong></div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Total Purchase</div><strong>Rs {{ number_format((float) ($statementSummary['purchase'] ?? 0), 2) }}</strong></div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Purchase Return</div><strong>Rs {{ number_format((float) ($statementSummary['purchase_return'] ?? 0), 2) }}</strong></div></div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Payment Out</div><strong>Rs {{ number_format((float) ($statementSummary['payment_out'] ?? 0), 2) }}</strong></div></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-hand-holding-usd mr-2 text-purple"></i> Advance Payments</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="advance-summary">
                    <div class="card-body">
                        <div class="text-muted small">Customer Advance</div>
                        <strong>Rs {{ number_format($customerAdvanceTotal, 2) }}</strong>
                        <div class="text-muted small mt-1">{{ $availableCustomerAdvances->count() }} active advance(s)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="advance-summary">
                    <div class="card-body">
                        <div class="text-muted small">Supplier Advance</div>
                        <strong>Rs {{ number_format($supplierAdvanceTotal, 2) }}</strong>
                        <div class="text-muted small mt-1">{{ $availableSupplierAdvances->count() }} active advance(s)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="advance-summary">
                    <div class="card-body">
                        <div class="text-muted small">Advance Records</div>
                        <strong>{{ $advanceHistory->count() }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="advanceTable" class="table table-hover mb-0 w-100">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Direction</th>
                        <th>Reference</th>
                        <th>Payment Mode</th>
                        <th>Original</th>
                        <th>Used</th>
                        <th>Remaining</th>
                        <th>Description</th>
                        <th>Settlements</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($advanceHistory as $advance)
                        @php
                            $usedAmount = (float) $advance->original_amount - (float) $advance->remaining_amount;
                        @endphp
                        <tr>
                            <td>{{ $advance->advance_date?->format('d M Y') ?: '—' }}</td>
                            <td>
                                <span class="badge {{ $advance->direction === 'in' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $advance->direction === 'in' ? 'Customer Advance' : 'Supplier Advance' }}
                                </span>
                            </td>
                            <td>{{ $advance->reference_no ?: '-' }}</td>
                            <td>{{ $advance->payment_mode ?: '-' }}</td>
                            <td>Rs {{ number_format((float) $advance->original_amount, 2) }}</td>
                            <td>Rs {{ number_format($usedAmount, 2) }}</td>
                            <td><strong>Rs {{ number_format((float) $advance->remaining_amount, 2) }}</strong></td>
                            <td>{{ $advance->description ?: '-' }}</td>
                            <td>
                                @forelse($advance->allocations as $allocation)
                                    <div class="small">
                                        <strong>{{ $allocation->document_no ?: '-' }}</strong> |
                                        Rs {{ number_format((float) $allocation->amount, 2) }}
                                    </div>
                                @empty
                                    <span class="text-muted">No settlement yet.</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-balance-scale mr-2 text-purple"></i> Opening Balance Adjustments</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="adjustmentTable" class="table table-hover mb-0 w-100">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Changed By</th>
                        <th>Role</th>
                        <th>Previous</th>
                        <th>Action</th>
                        <th>Adjusted</th>
                        <th>New Amount</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($openingBalanceAdjustments as $adjustment)
                        <tr style="background:{{ $adjustment->direction === 'increase' ? '#f0fdf4' : '#fff7ed' }};">
                            <td><strong>{{ $adjustment->adjustment_date?->format('d M Y') ?: '—' }}</strong></td>
                            <td>{{ $adjustment->creator?->name ?: 'System' }}</td>
                            <td>{{ $adjustment->created_role ?: 'No role' }}</td>
                            <td>Rs {{ number_format((float) $adjustment->previous_amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $adjustment->direction === 'increase' ? 'badge-success' : 'badge-warning' }}">
                                    {{ ucfirst($adjustment->direction) }}
                                </span>
                            </td>
                            <td>Rs {{ number_format(abs((float) $adjustment->adjustment_amount), 2) }}</td>
                            <td><strong>Rs {{ number_format((float) $adjustment->new_amount, 2) }}</strong></td>
                            <td>{{ $adjustment->reason ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Ledger Statement --}}
<div class="card border-0 shadow-sm">

    <div class="card-header">

        <h3 class="card-title m-0">

            <i class="fas fa-book mr-2 text-purple"></i>

            Account Statement

        </h3>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <div class="statement-tools">
                <div><label for="statementType">Entry Type</label><select id="statementType" class="form-control"><option value="">All entries</option>@foreach($statementRows->pluck('entry_type')->unique()->sort() as $type)<option value="{{ str_replace('_', ' ', ucfirst($type)) }}">{{ str_replace('_', ' ', ucfirst($type)) }}</option>@endforeach</select></div>
                <div><label for="statementFrom">From</label><input type="date" id="statementFrom" class="form-control"></div>
                <div><label for="statementTo">To</label><input type="date" id="statementTo" class="form-control"></div>
                <button type="button" id="resetStatement" class="btn btn-outline-secondary btn-sm" title="Reset filters"><i class="fas fa-undo"></i></button>
                <button type="button" id="printStatement" class="btn btn-outline-secondary btn-sm" title="Print statement"><i class="fas fa-print"></i></button>
            </div>
            <table id="ledgerTable"
                   class="table table-hover table-bordered w-100">

                <thead>

                    <tr>

                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>

                    </tr>

                </thead>

                <tbody>

                    @if($statementRows && $statementRows->count())

                        @foreach($statementRows as $ledger)

                            <tr>

                                <td data-order="{{ $ledger->entry_date?->format('Y-m-d') }}" data-date="{{ $ledger->entry_date?->format('Y-m-d') }}">

                                    {{ $ledger->entry_date ? $ledger->entry_date->format('d M Y') : '—' }}

                                </td>

                                <td>

                                    <span class="entry-type">{{ str_replace('_', ' ', ucfirst($ledger->entry_type ?? '')) }}</span>

                                </td>

                                <td>

                                    {{ $ledger->reference_no ?: '—' }}

                                </td>

                                <td>

                                    {{ strip_tags($ledger->description ?? '—') }}

                                </td>

                                <td class="text-right">

                                    ₹ {{ number_format((float) $ledger->debit, 2) }}

                                </td>

                                <td class="text-right">

                                    ₹ {{ number_format((float) $ledger->credit, 2) }}

                                </td>

                                <td class="text-right">

                                    ₹ {{ number_format(abs((float) $ledger->balance_after), 2) }}
                                    <div class="small text-muted">{{ $ledger->balance_after < 0 ? 'Receivable' : ($ledger->balance_after > 0 ? 'Payable' : 'Settled') }}</div>

                                </td>

                            </tr>

                        @endforeach

                    @endif

                </tbody>

            </table>

        </div>

    </div>

</div>

</div>
@endsection

@push('scripts')

<script>
$(document).ready(function () {

    // Prevent duplicate initialization
    if ($.fn.DataTable.isDataTable('#ledgerTable')) {
        $('#ledgerTable').DataTable().destroy();
    }

    var statement = $('#ledgerTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        autoWidth: false,
        ordering: true,
        searching: true,
        paging: true,
        info: true,
        language: {
            emptyTable: "No ledger entries found"
        },
        columnDefs: [
            {
                targets: [4, 5, 6],
                className: 'text-right'
            }
        ]
    });

    $.fn.dataTable.ext.search.push(function(settings, data, index) {
        if (settings.nTable.id !== 'ledgerTable') return true;
        var date = statement.row(index).node().querySelector('[data-date]').dataset.date;
        var from = $('#statementFrom').val(), to = $('#statementTo').val();
        return (!from || date >= from) && (!to || date <= to);
    });
    $('#statementType').on('change', function() { statement.column(1).search(this.value).draw(); });
    $('#statementFrom, #statementTo').on('change', function() { statement.draw(); });
    $('#resetStatement').on('click', function() {
        $('#statementType, #statementFrom, #statementTo').val('');
        statement.search('').columns().search('').draw();
    });
    $('#printStatement').on('click', function() {
        var length = statement.page.len();
        statement.page.len(-1).draw();
        window.print();
        statement.page.len(length).draw();
    });
    $('#advanceTable, #adjustmentTable').DataTable({pageLength:10, autoWidth:false, order:[], language:{emptyTable:'No records found'}});

});
</script>

@endpush
