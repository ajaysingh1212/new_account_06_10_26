@extends('layouts.admin')
@section('title', 'Parties')
@section('breadcrumb')
    <li class="breadcrumb-item active">Parties</li>
@endsection

@section('content')
@include('admin.parties.partials.styles')
<div class="party-workspace">
<div class="row">
    <div class="col-6 col-lg-3 mb-4">
        <div class="stat-card stat-purple"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-value">{{ $summary['total'] }}</div><div class="stat-label">Total Parties</div></div>
    </div>
    <div class="col-6 col-lg-3 mb-4">
        <div class="stat-card stat-green"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-value">{{ $summary['active'] }}</div><div class="stat-label">Active Parties</div></div>
    </div>
    <div class="col-6 col-lg-3 mb-4">
        <div class="stat-card stat-orange"><div class="stat-icon"><i class="fas fa-arrow-up"></i></div><div class="stat-value">₹ {{ number_format($summary['payable'], 2) }}</div><div class="stat-label">Total Payable</div></div>
    </div>
    <div class="col-6 col-lg-3 mb-4">
        <div class="stat-card stat-cyan"><div class="stat-icon"><i class="fas fa-arrow-down"></i></div><div class="stat-value">₹ {{ number_format($summary['receivable'], 2) }}</div><div class="stat-label">Total Receivable</div></div>
    </div>
</div>

<div class="party-metrics">
    @foreach(['opening_receivable' => ['Opening Receivable', '#168577', 'fa-folder-open'], 'invoice_receivable' => ['Invoice Receivable', '#218ca6', 'fa-file-invoice'], 'opening_payable' => ['Opening Payable', '#b7791f', 'fa-folder-open'], 'invoice_payable' => ['Invoice Payable', '#c45265', 'fa-file-invoice']] as $key => $metric)
        <div class="party-metric" style="--metric-color:{{ $metric[1] }}">
            <i class="fas {{ $metric[2] }}"></i><label>{{ $metric[0] }}</label>
            <strong>Rs {{ number_format($summary[$key], 2) }}</strong>
        </div>
    @endforeach
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-id-card mr-2 text-purple"></i> Party Master</h3>
        <a href="{{ route('admin.parties.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Party</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="partiesTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Party</th>
                        <th>Type</th>
                        <th>Contact</th>
                        <th>GST / PAN</th>
                        <th>Balance</th>
                        <th>Created By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($parties as $party)
                    <tr>
                        <td><strong>{{ $party->party_code }}</strong></td>
                        <td>
                            <div style="font-weight:700;color:#1A0A3D;">{{ $party->display_name }}</div>
                            <div style="font-size:12px;color:#9090B0;">{{ $party->legal_name ?: $party->city }}</div>
                        </td>
                        <td><span class="badge-admin">{{ ucfirst($party->party_type) }}</span></td>
                        <td>
                            <div>{{ $party->phone ?: '—' }}</div>
                            <div style="font-size:12px;color:#9090B0;">{{ $party->email }}</div>
                        </td>
                        <td>
                            <div>{{ $party->gstin ?: '—' }}</div>
                            <div style="font-size:12px;color:#9090B0;">{{ $party->pan_number }}</div>
                        </td>
                        <td>
                            @php
                                $receivable = (float) $party->ageing_receivable;
                                $payable = (float) $party->ageing_payable;
                            @endphp
                            @foreach($party->balance_breakdown as $component)
                                <div class="mb-1">
                                    <strong class="{{ $component['kind'] === 'receivable' ? 'text-success' : 'text-danger' }}">₹ {{ number_format($component['amount'], 2) }}</strong>
                                    <div style="font-size:12px;color:#9090B0;">{{ $component['label'] }} · {{ ucfirst($component['kind']) }}</div>
                                </div>
                            @endforeach
                            @if($receivable <= 0 && $payable <= 0)
                                <strong class="text-muted">₹ 0.00</strong>
                                <div style="font-size:12px;color:#9090B0;">Settled</div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $party->creator?->name ?? 'System' }}</strong>
                            <div style="font-size:12px;color:#9090B0;">{{ $party->creator?->rolesForCompany($party->company_id)->pluck('name')->join(', ') ?: 'No role' }}</div>
                        </td>
                        <td><span class="{{ $party->status === 'active' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($party->status) }}</span></td>
                        <td>
                            @php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $party))
                            <a href="{{ route('admin.parties.show', $party) }}" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                            @if($canManage)
                            <a href="{{ route('admin.parties.edit', $party) }}" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.parties.destroy', $party) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm btn-delete" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
$('#partiesTable').DataTable({ responsive: true, pageLength: 25, columnDefs: [{ orderable: false, targets: 8 }] });
</script>
@endpush
