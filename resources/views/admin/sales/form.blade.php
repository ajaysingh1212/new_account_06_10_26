@php
    $isEdit = isset($invoice);
    $lines = old('item_id')
        ? collect(old('item_id'))->map(fn($id, $i) => [
            'item_id' => $id,
            'description' => old("description.$i"),
            'quantity' => old("quantity.$i", 1),
            'unit' => old("unit.$i"),
            'unit_price' => old("unit_price.$i", 0),
            'discount_type' => old("discount_type.$i", 'percent'),
            'discount_value' => old("discount_value.$i", 0),
            'tax_mode' => old("tax_mode.$i", 'with_gst'),
            'tax_percent' => old("tax_percent.$i", 0),
            'selected_units' => json_decode(old("selected_units.$i", '[]'), true) ?: [],
        ])
        : ($isEdit ? $invoice->items->map(fn($line) => [
            'item_id' => $line->item_id,
            'description' => $line->description,
            'quantity' => (float) $line->quantity,
            'unit' => $line->unit,
            'unit_price' => (float) $line->unit_price,
            'discount_type' => $line->discount_type,
            'discount_value' => (float) $line->discount_value,
            'tax_mode' => (float) $line->tax_percent > 0 ? 'with_gst' : 'without_gst',
            'tax_percent' => (float) $line->tax_percent,
            'selected_units' => $line->selected_units ?? [],
        ]) : collect());
@endphp

@push('styles')
<style>
.trade-shell{background:#fff;border:1px solid #e8edf5;border-radius:16px;box-shadow:0 16px 36px rgba(15,23,42,.08);overflow:hidden}
.trade-head{background:linear-gradient(115deg,#122033,#164e63);color:#fff;padding:28px 24px;display:flex;justify-content:space-between;gap:16px;align-items:center}
.trade-head h2{font-weight:800;margin:0;font-size:24px;letter-spacing:0}
.trade-head small{color:#c5d9e6;display:block;margin-top:6px}
.trade-section{padding:20px 24px;border-bottom:1px solid #edf1f7}
.trade-title{font-size:12px;font-weight:800;color:#0f766e;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px}
.trade-table{display:block;width:100%;margin:0}
.trade-table tbody{display:grid;gap:16px;padding:4px 0}
.trade-table thead{display:none}
.trade-table tbody tr{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px;align-items:start;background:#fff;border:1px solid #dfe7ef;border-radius:12px;padding:18px;box-shadow:0 3px 12px rgba(15,23,42,.03)}
.trade-table td{display:block;border:0!important;padding:0!important}
.trade-table td{grid-column:span 2;min-width:0}
.trade-table .item-cell,.trade-table .desc-cell,.trade-table .serial-cell{grid-column:span 4}
.trade-table .line-heading,.trade-table .line-footer{grid-column:1 / -1;display:flex;align-items:center;justify-content:space-between;gap:12px}
.trade-table .line-heading{padding-bottom:12px!important;border-bottom:1px solid #edf1f7!important}
.trade-table .line-footer{padding-top:14px!important;border-top:1px dashed #dfe7ef!important}
.trade-table .line-heading:before,.trade-table .line-footer:before{display:none}
.line-number{color:#0f766e;font-size:12px;font-weight:800;background:#e6f5f3;border-radius:7px;padding:6px 10px}
.line-amount{color:#0f172a;font-size:17px;font-weight:800;white-space:nowrap}
.line-amount small{color:#667085;font-size:11px;font-weight:600;margin-right:10px}
.line-count{background:#edf6f7;color:#0f766e;border-radius:999px;padding:5px 12px;font-size:12px;font-weight:700}
.trade-items{background:#f6f9fc}
.trade-shell label{font-size:12px;font-weight:600;color:#475569}
.trade-shell .form-control:focus{border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.1)}
.trade-shell .select2-container{max-width:100%}
.trade-shell .select2-container--default .select2-selection--single{border-color:#ced4da;border-radius:6px;min-height:38px}
.trade-actions{background:#f8fafc;border-top:1px solid #edf1f7;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px 24px}
.trade-actions .btn-primary{background:#0f766e;border-color:#0f766e;border-radius:9px}
.trade-table td:before{content:attr(data-label);display:block;font-size:10px;text-transform:uppercase;color:#667085;font-weight:800;margin-bottom:5px}
.trade-table .item-cell,.trade-table .desc-cell{min-width:0}
.total-box{background:#0f172a;color:#fff;border-radius:8px;padding:16px}
.total-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.08)}
.total-row:last-child{border-bottom:0;color:#5eead4;font-weight:800;font-size:18px}
.unit-drawer{position:fixed;top:0;right:-480px;width:460px;max-width:calc(100vw - 20px);height:100vh;background:#fff;z-index:1060;box-shadow:-18px 0 36px rgba(15,23,42,.22);transition:right .2s ease;display:flex;flex-direction:column}
.unit-drawer.open{right:0}
.unit-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.35);z-index:1059;display:none}
.unit-backdrop.open{display:block}
.unit-head{padding:18px;border-bottom:1px solid #e5e7eb;background:#f8fafc}
.unit-list{padding:12px 14px;overflow:auto;flex:1}
.unit-card{border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:10px;display:grid;grid-template-columns:28px 1fr;gap:10px}
.unit-card.sold{background:#f9fafb;color:#98a2b3}
.unit-card.selected{border-color:#0f766e;background:#f0fdfa}
.unit-card input{margin-top:4px;accent-color:#0f766e}
.unit-card>span{min-width:0;overflow-wrap:anywhere}
.unit-search{position:relative;margin-top:16px}
.unit-search>i{position:absolute;left:12px;top:13px;color:#64748b}
.unit-search input{padding-left:34px;padding-right:40px}
.unit-search button{position:absolute;right:3px;top:3px;width:32px;height:32px;color:#64748b}
.unit-results{font-size:12px;color:#64748b;margin-top:10px}
.unit-empty{text-align:center;padding:36px 16px;color:#64748b}
.unit-empty i{display:block;font-size:26px;margin-bottom:12px;color:#94a3b8}
.unit-foot{padding:14px 18px;border-top:1px solid #e5e7eb;background:#f8fafc;display:flex;align-items:center;justify-content:space-between;gap:12px}
.unit-foot .btn{background:#0f766e;border-color:#0f766e}
.unit-meta{font-size:12px;color:#667085}
.selected-pill{display:inline-flex;align-items:center;gap:6px;border:1px solid #99f6e4;background:#f0fdfa;color:#0f766e;border-radius:999px;padding:3px 9px;font-size:12px;margin:2px}
.advance-panel{border:1px solid #bfdbfe;background:linear-gradient(135deg,#eff6ff,#f8fafc);border-radius:10px;padding:16px;margin-top:12px}
.advance-row{border:1px solid #dbeafe;background:#fff;border-radius:10px;padding:12px;margin-top:10px}
.advance-row.active{border-color:#2563eb;box-shadow:0 8px 18px rgba(37,99,235,.08)}
.icon-btn{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px}
.filter-pills .btn{border-radius:999px}
.form-control,.custom-select{border-radius:6px}
.wide-select{min-width:100%}

/* ── Max-discount warning badge ── */
.disc-warn{display:none;align-items:center;gap:5px;background:#fef3c7;border:1px solid #fde68a;border-radius:6px;padding:4px 8px;font-size:10px;font-weight:800;color:#92400e;margin-top:4px;white-space:nowrap}
.disc-warn.show{display:flex}
.disc-limit-badge{display:none;font-size:10px;font-weight:700;color:#0f766e;margin-top:3px}
.disc-limit-badge.show{display:block}

@media(max-width:1100px){.trade-table .item-cell,.trade-table .desc-cell{grid-column:span 6}.trade-table .serial-cell{grid-column:span 12}.trade-table td{grid-column:span 4}}
@media(max-width:576px){.trade-section{padding:16px}.trade-head{padding:22px 16px}.trade-head h2{font-size:20px}.trade-table tbody tr{padding:14px;gap:14px}.trade-table td{grid-column:span 6}.trade-table .item-cell,.trade-table .desc-cell{grid-column:span 12}.trade-actions{padding:16px;flex-wrap:wrap}.trade-actions .btn{width:100%}.line-amount small{display:block;margin:0}.unit-drawer{max-width:100vw}}
</style>
@endpush

<form method="POST" action="{{ $isEdit ? route('admin.sales.update', $invoice) : route('admin.sales.store') }}" enctype="multipart/form-data">
@csrf
@if($isEdit) @method('PUT') @endif
<div class="trade-shell">
    <div class="trade-head">
        <div>
            <h2><i class="fas fa-file-invoice-dollar mr-2"></i>{{ $isEdit ? 'Edit Sales Invoice' : 'Sales Invoice' }}</h2>
            <small>Add your items, choose serial numbers and review your invoice.</small>
        </div>
        <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    </div>

    {{-- ── Invoice Details ── --}}
    <div class="trade-section">
        <div class="trade-title">Invoice Details</div>
        <div class="row">
            <div class="col-md-2 form-group">
                <label>Type</label>
                <select name="sale_type" class="form-control">
                    <option value="credit" @selected(old('sale_type',$invoice->sale_type ?? 'credit')==='credit')>Credit</option>
                    <option value="cash"   @selected(old('sale_type',$invoice->sale_type ?? '')==='cash')>Cash</option>
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label>Party</label>
                <div class="input-group">
                    <select name="party_id" class="form-control select2">
                        <option value="">Cash/No Party</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}" @selected((string)old('party_id',$invoice->party_id ?? '')===(string)$party->id)>
                                {{ $party->display_name }} | {{ $party->phone }}
                            </option>
                        @endforeach
                    </select>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-primary" id="advanceIndicator" style="display:none" title="View available advance">
                            <i class="fas fa-wallet"></i>
                            <span class="badge badge-primary ml-1" id="advanceIndicatorAmount">0</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-2 form-group">
                <label>Invoice No</label>
                <input name="invoice_no" class="form-control" value="{{ old('invoice_no',$invoiceNo) }}">
            </div>
            <div class="col-md-2 form-group">
                <label>Billing Date</label>
                <input type="date" name="billing_date" class="form-control"
                    value="{{ old('billing_date', isset($invoice) ? $invoice->billing_date?->format('Y-m-d') : now()->toDateString()) }}" required>
            </div>
            <div class="col-md-2 form-group">
                <label>PO Date</label>
                <input type="date" name="po_date" class="form-control"
                    value="{{ old('po_date', isset($invoice) ? $invoice->po_date?->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-2 form-group">
                <label>Reference</label>
                <input name="reference_no" class="form-control" value="{{ old('reference_no',$invoice->reference_no ?? '') }}">
            </div>
        </div>
        <div id="advancePanel" class="advance-panel" style="display:none">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:12px">
                <div>
                    <div class="trade-title mb-1" style="margin-bottom:4px">Available Advance</div>
                    <div class="text-muted small" id="advanceSummary">No advance loaded.</div>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="toggleAdvancePanel">Apply advance</button>
            </div>
            <div id="advanceList"></div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group">
                <label>Cost Center</label>
                <select name="cost_center_id" class="form-control">
                    <option value="">Select</option>
                    @foreach($costCenters as $cc)
                        <option value="{{ $cc->id }}" @selected((string)old('cost_center_id',$invoice->cost_center_id ?? '')===(string)$cc->id)>{{ $cc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label>Sub Cost Center</label>
                <select name="sub_cost_center_id" class="form-control">
                    <option value="">Select</option>
                    @foreach($subCostCenters as $scc)
                        <option value="{{ $scc->id }}" @selected((string)old('sub_cost_center_id',$invoice->sub_cost_center_id ?? '')===(string)$scc->id)>{{ $scc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label>Phone</label>
                <input name="phone" class="form-control" value="{{ old('phone',$invoice->phone ?? '') }}">
            </div>
            <div class="col-md-3 form-group">
                <label>Attachment</label>
                <input type="file" name="attachment" class="form-control">
                @if($isEdit && $invoice->attachment)
                    <small><a target="_blank" href="{{ asset('storage/'.$invoice->attachment) }}">Current attachment</a></small>
                @endif
            </div>
        </div>

        @if($mergedCompanies->count())
        <div class="border rounded p-3 mt-2">
            <label class="mb-2">
                <input type="checkbox" name="inter_company_transfer" value="1" id="interCompanyTransfer"
                    @checked(old('inter_company_transfer', $invoice->inter_company_transfer ?? false))>
                Auto purchase in merged company
            </label>
            <div id="mergedCompanyBox" class="row" style="display:none">
                @foreach($mergedCompanies as $company)
                <div class="col-md-4">
                    <label class="d-block border rounded p-2">
                        <input type="checkbox" name="target_company_ids[]" value="{{ $company->id }}"
                            @checked(in_array($company->id, old('target_company_ids', $invoice->inter_company_target_company_ids ?? [])))>
                        <strong>{{ $company->name }}</strong><br>
                        <small>{{ $company->phone ?: 'No phone' }} | GST {{ $company->gst_number ?: '-' }}</small>
                    </label>
                    <div class="pl-3 pr-2 pb-2">
                        <label class="small mb-1">Purchase visible to roles</label>
                        <select name="purchase_visible_to_roles[{{ $company->id }}][]" class="form-control select2" multiple style="width:100%">
                            @foreach(($interCompanyVisibility[$company->id]['roles'] ?? collect()) as $role)
                                <option value="{{ $role->id }}" @selected(in_array($role->id, old("purchase_visible_to_roles.{$company->id}", $interCompanySelectedVisibility[$company->id]['roles'] ?? [])))>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <label class="small mt-2 mb-1">Purchase visible to users</label>
                        <select name="purchase_visible_to_users[{{ $company->id }}][]" class="form-control select2" multiple style="width:100%">
                            @foreach(($interCompanyVisibility[$company->id]['users'] ?? collect()) as $user)
                                <option value="{{ $user->id }}" @selected(in_array($user->id, old("purchase_visible_to_users.{$company->id}", $interCompanySelectedVisibility[$company->id]['users'] ?? [])))>{{ $user->name }} | {{ $user->email }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @if($isEdit && ($interCompanyStockStatus ?? collect())->isNotEmpty())
        <div class="mt-3 p-3 border rounded" style="background:#f8fafc">
            <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px">
                <div><strong><i class="fas fa-warehouse mr-1 text-primary"></i> Auto-purchase stock status</strong><br><small class="text-muted">Har merged company ka stock independently verify kiya gaya hai.</small></div>
                @if($interCompanyStockStatus->sum('missing') > 0)<button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#sourceSaleStockRepairModal"><i class="fas fa-wrench mr-1"></i>Review & add missing stock</button>@endif
            </div>
            <div class="table-responsive mt-2"><table class="table table-sm mb-0"><thead><tr><th>Target company</th><th>Auto purchase</th><th>Stock status</th><th>Details</th></tr></thead><tbody>@foreach($interCompanyStockStatus as $status)<tr><td>{{ $status['company'] }}</td><td>{{ $status['purchase'] ?: 'Not created' }}</td><td class="{{ $status['missing'] > 0 ? 'text-danger font-weight-bold' : 'text-success font-weight-bold' }}">{{ $status['missing'] > 0 ? 'Missing '.number_format($status['missing'],3).' unit(s)' : 'Added in stock' }}</td><td><button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#stockDetailsModal{{ $status['company_id'] }}"><i class="fas fa-list mr-1"></i>Details</button></td></tr>@endforeach</tbody></table></div>
        </div>
        @endif
    </div>

    {{-- ── Line Items ── --}}
    <div class="trade-section trade-items">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div><div class="trade-title mb-1">Invoice Items</div><small class="text-muted">Choose a product and its serials. Add the next row below any item.</small></div>
            <span class="line-count" id="lineCount" aria-live="polite">0 items</span>
        </div>
        <div class="table-responsive">
            <table class="table trade-table" id="lineTable">
                <thead>
                    <tr>
                        <th class="item-cell">Item</th>
                        <th class="desc-cell">Description</th>
                        <th>Qty</th>
                        <th>Units</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Disc</th>
                        <th>GST Mode</th>
                        <th>Tax %</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- ── Totals & Footer ── --}}
    <div class="trade-section">
        <div class="row">
            <div class="col-md-4 form-group">
                <label>Overall Discount</label>
                <input type="number" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount',$invoice->discount_amount ?? 0) }}">
            </div>
            <div class="col-md-4 form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes',$invoice->notes ?? '') }}</textarea>
            </div>
            <div class="col-md-4">
                <div class="total-box">
                    <div class="total-row"><span>Subtotal</span><b id="uiSubtotal">Rs 0.00</b></div>
                    <div class="total-row"><span>Tax</span><b id="uiTax">Rs 0.00</b></div>
                    <div class="total-row"><span>Total Weight</span><b id="uiWeight">0.000 kg</b></div>
                    <div class="total-row"><span>Total</span><b id="uiTotal">Rs 0.00</b></div>
                </div>
            </div>
            <div class="col-md-8 form-group">
                <label>Terms</label>
                <select id="termsTemplate" class="form-control mb-2">
                    <option value="">Manual / no template</option>
                    @foreach($termsTemplates as $template)
                        <option value="{{ e($template->content) }}" @selected(!$isEdit && $template->is_default)>
                            {{ $template->title }}{{ $template->is_default ? ' (Default)' : '' }}
                        </option>
                    @endforeach
                </select>
                <textarea name="terms" id="termsBox" class="form-control" rows="2">{{ old('terms',$invoice->terms ?? ($termsTemplates->firstWhere('is_default', true)?->content ?? '')) }}</textarea>
            </div>
        </div>
        @include('admin.partials.entry-visibility', ['entry' => $invoice ?? null])
    </div>

    <div class="trade-actions">
        <span class="text-muted small"><i class="fas fa-check-circle mr-1 text-success"></i>Review items, serials and totals before saving.</span>
        <button class="btn btn-primary btn-lg"><i class="fas fa-save mr-1"></i>{{ $isEdit ? 'Update Sale' : 'Post Sale' }}</button>
    </div>
</div>
</form>

@if($isEdit && ($interCompanyStockStatus ?? collect())->sum('missing') > 0)
<div class="modal fade" id="sourceSaleStockRepairModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document"><div class="modal-content">
        <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-wrench mr-1"></i> Repair target-company stock</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <form method="POST" action="{{ route('admin.sales.repair-inter-company-stock', $invoice) }}">@csrf
            <div class="modal-body"><p>System target company ke auto-purchase movements ko purchase quantity ke against compare karega. Sirf missing net quantity/serial add honge; existing stock duplicate nahi hoga.</p><ul class="mb-0">@foreach($interCompanyStockStatus->where('missing','>',0) as $status)<li><strong>{{ $status['company'] }}</strong>: {{ number_format($status['missing'],3) }} unit(s) missing</li>@endforeach</ul></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button class="btn btn-warning"><i class="fas fa-check mr-1"></i> Update stock</button></div>
        </form>
    </div></div>
</div>
@endif

@if($isEdit && ($interCompanyStockStatus ?? collect())->isNotEmpty())
@foreach($interCompanyStockStatus as $status)
<div class="modal fade" id="stockDetailsModal{{ $status['company_id'] }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document"><div class="modal-content">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-boxes mr-1"></i> Stock details: {{ $status['company'] }}</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Item</th><th>Serial no.</th><th>SKU</th><th>SIM / VTS</th><th>Status</th><th>Reason / last movement</th><th>Action</th></tr></thead><tbody>
            @forelse($status['details'] ?? [] as $detail)
                <tr><td>{{ $detail['item'] }}</td><td>{{ $detail['serial_no'] ?: '-' }}</td><td>{{ $detail['sku'] ?: '-' }}</td><td>{{ $detail['vts_sim'] ?: ($detail['buyer_code'] ?: '-') }}</td><td class="{{ $detail['status'] === 'added' ? 'text-success font-weight-bold' : 'text-danger font-weight-bold' }}">{{ $detail['status'] === 'added' ? 'Added in stock' : 'Missing' }}</td><td>{{ $detail['reason'] }} @if($detail['last_movement'])<br><small>Last: {{ $detail['last_movement'] }}</small>@endif @if(!empty($detail['locations']))<br><strong>Current location:</strong> @foreach($detail['locations'] as $location){{ $location['company'] }} ({{ number_format($location['net'],3) }})@if(!$loop->last), @endif @endforeach @endif @if(!empty($detail['history']))<details class="mt-1"><summary>Movement history</summary><small>@foreach($detail['history'] as $event){{ $event['date'] }} | {{ $event['company'] }} | {{ $event['type'] }} | {{ $event['direction'] }} | {{ $event['reference'] ?: '-' }} | {{ $event['by'] ?: '-' }}<br>@endforeach</small></details>@endif</td><td>@if($detail['status'] !== 'added')<form method="POST" action="{{ route('admin.sales.repair-inter-company-stock', $invoice) }}" class="d-inline">@csrf<input type="hidden" name="target_company_ids[]" value="{{ $status['company_id'] }}"><input type="hidden" name="line_ids[]" value="{{ $detail['line_id'] }}"><input type="hidden" name="unit_token" value="{{ $detail['serial_no'] ?: ($detail['vts_sim'] ?: ($detail['buyer_code'] ?: ($detail['sku'] ?: $detail['key']))) }}"><button class="btn btn-warning btn-sm"><i class="fas fa-wrench mr-1"></i>Add</button></form>@else<span class="text-success"><i class="fas fa-check"></i></span>@endif</td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No serial detail available for this auto purchase.</td></tr>
            @endforelse
            </tbody></table></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
    </div></div>
</div>
@endforeach
@endif

{{-- ── Unit serial drawer ── --}}
<div class="unit-backdrop" id="unitBackdrop"></div>
<aside class="unit-drawer" id="unitDrawer" role="dialog" aria-modal="true" aria-labelledby="drawerTitle" tabindex="-1" inert>
    <div class="unit-head">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <b id="drawerTitle">Finished Goods Units</b>
                <div class="unit-meta" id="drawerHint"></div>
            </div>
            <button type="button" class="btn btn-light icon-btn" id="closeDrawer" aria-label="Close serial selection"><i class="fas fa-times"></i></button>
        </div>
        <div class="unit-search">
            <label for="unitSearch" class="sr-only">Search serial numbers, SIM, batch or warehouse</label>
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="unitSearch" class="form-control" placeholder="Search serial, SIM, batch, warehouse..." autocomplete="off" aria-controls="unitList">
            <button type="button" class="btn btn-sm" id="clearUnitSearch" aria-label="Clear serial search" hidden><i class="fas fa-times"></i></button>
        </div>
        <div class="filter-pills mt-3 btn-group btn-group-sm">
            <button type="button" class="btn btn-primary unit-filter" data-filter="available">Available</button>
            <button type="button" class="btn btn-outline-secondary unit-filter" data-filter="selected">Selected</button>
            <button type="button" class="btn btn-outline-secondary unit-filter" data-filter="sold">Sold</button>
            <button type="button" class="btn btn-outline-secondary unit-filter" data-filter="all">All</button>
        </div>
        <div id="unitResults" class="unit-results" role="status" aria-live="polite"></div>
    </div>
    <div class="unit-list" id="unitList"></div>
    <div class="unit-foot"><span class="unit-meta">Selections are applied to this item.</span><button type="button" id="doneDrawer" class="btn btn-primary btn-sm"><i class="fas fa-check mr-1"></i> Done</button></div>
</aside>

{{-- ── Line row template ── --}}
<template id="lineTpl">
<tr>
    <td class="line-heading"><span class="line-number">Item <span class="line-index"></span></span><button type="button" class="btn btn-outline-danger btn-sm remove-row icon-btn" aria-label="Remove item" title="Remove item"><i class="fas fa-trash-alt"></i></button></td>
    <td class="item-cell" data-label="Item">
        <select name="item_id[]" class="form-control item-select select2-item wide-select" required>
            <option value="">Select finished goods</option>
            @foreach($items as $it)
                <option value="{{ $it->id }}"
                    data-unit="{{ $it->unit }}"
                    data-price="{{ $it->sale_price }}"
                    data-tax="{{ (float) $it->sale_gst_percent ?: 18 }}"
                    data-weight="{{ (float) ($it->per_quantity_weight ?? 0) }}"
                    data-max-disc="{{ (float) ($it->max_discount_percent ?? 0) }}"
                    data-gps="{{ str_contains(strtolower(implode(' ', array_filter([$it->name, $it->item_code, $it->sku, $it->brand, $it->model, $it->description]))), 'gps') ? 1 : 0 }}">
                    {{ $it->name }} | {{ $it->item_code }} | Stock {{ $it->current_stock }}
                    @if($it->per_quantity_weight)
                        | {{ $it->per_quantity_weight }} kg/qty
                    @endif
                    @if($it->max_discount_percent)
                        | Max disc {{ $it->max_discount_percent }}%
                    @endif
                </option>
            @endforeach
        </select>
    </td>
    <td class="desc-cell" data-label="Description">
        <input name="description[]" class="form-control">
    </td>
    <td class="num-cell" data-label="Qty">
        <input type="number" step="1" min="1" name="quantity[]" class="form-control line-qty" value="1" required>
    </td>
    <td class="serial-cell" data-label="Serial Selection">
        <button type="button" class="btn btn-outline-info btn-sm choose-units" title="Select serials">
            <i class="fas fa-barcode mr-1"></i> Choose serials
        </button>
        <input type="hidden" name="selected_units[]" class="selected-units-json">
        <div class="selected-units mt-1"></div>
    </td>
    <td class="mini-cell" data-label="Unit">
        <input name="unit[]" class="form-control">
    </td>
    <td class="num-cell" data-label="Price">
        <input type="number" step="0.01" name="unit_price[]" class="form-control" required>
    </td>
    <td class="mini-cell" data-label="Discount">
        <select name="discount_type[]" class="form-control disc-type">
            <option value="percent">%</option>
            <option value="flat">Flat</option>
        </select>
        <input type="number" step="0.01" name="discount_value[]" class="form-control disc-value mt-1" value="0">
        {{-- warning shown when discount exceeds item's max --}}
        <div class="disc-warn"><i class="fas fa-exclamation-triangle"></i> <span class="disc-warn-text">Max disc exceeded!</span></div>
        <div class="disc-limit-badge">Max allowed: <b class="disc-limit-val">—</b></div>
    </td>
    <td class="mini-cell" data-label="GST Mode">
        <select name="tax_mode[]" class="form-control tax-mode">
            <option value="with_gst" selected>With GST</option>
            <option value="without_gst">Without GST</option>
        </select>
    </td>
    <td class="mini-cell" data-label="Tax %">
        <input type="number" step="0.01" min="0" name="tax_percent[]" class="form-control tax-percent" value="18">
    </td>
    <td class="line-footer"><button type="button" class="btn btn-outline-primary btn-sm add-line"><i class="fas fa-plus mr-1"></i> Add Row</button><span class="line-amount"><small>Line total</small><span class="line-total">Rs 0.00</span></span></td>
</tr>
</template>

@push('scripts')
<script>
const UNIT_POOL       = @json($unitPool);
const PREFILL_LINES   = @json($lines->values());
// ITEM_META must include max_discount_percent — add it in your controller where $itemMeta is built:
// 'max_discount_percent' => (float) ($item->max_discount_percent ?? 0)
const ITEM_META       = @json($itemMeta);

let activeRow   = null;
let activeFilter = 'available';

// ── Helpers ───────────────────────────────────────────────────────────────────
function money(n){
    return 'Rs '+(Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});
}
function escapeHtml(value){
    return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
}
let availableAdvances = [];
let existingAdvanceApplications = @json($advanceApplications ?? []);
function renderAdvanceSection(){
    const selectedIds = new Set(existingAdvanceApplications.map(row => String(row.party_advance_id || row.advance?.id || row.id)));
    const existingRows = existingAdvanceApplications.map(row => ({...row, existing: true}));
    const availableRows = availableAdvances.filter(row => !selectedIds.has(String(row.id))).map(row => ({...row, existing: false}));
    const rows = [...existingRows, ...availableRows];
    const total = rows.reduce((sum, row) => {
        const advance = row.advance || row;
        const remaining = Number(advance.remaining_amount) || 0;
        const applied = Number(row.amount) || 0;
        return sum + (row.existing ? remaining + applied : remaining);
    }, 0);
    if(total <= 0){
        $('#advanceIndicator').hide();
        $('#advanceIndicatorAmount').text('0');
        $('#advancePanel').hide();
        $('#advanceList').empty();
        return;
    }
    $('#advanceIndicator').show();
    $('#advanceIndicatorAmount').text(money(total).replace('Rs ',''));
    $('#advanceSummary').text(`${money(total)} advance available for this party.`);
    $('#advanceList').html(rows.map((row, idx) => {
        const advance = row.advance || row;
        const remaining = Number(advance.remaining_amount) || 0;
        const applied = Number(row.amount) || 0;
        const maxAmount = row.existing ? (remaining + applied) : remaining;
        const checked = row.existing ? 'checked' : '';
        const amountValue = row.existing ? applied.toFixed(2) : '';
        return `
        <div class="advance-row ${row.existing ? 'active' : ''}" data-remaining="${maxAmount}">
            <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:12px">
                <label class="m-0"><input type="checkbox" class="advance-check mr-2" ${checked}> <b>${advance.advance_date_label || advance.advance_date || '-'}</b> <span class="text-muted">${advance.reference_no || '-'}</span></label>
                <div class="text-right"><div><b>${money(maxAmount)}</b> remaining</div><small class="text-muted">${advance.payment_mode || '-'}</small></div>
            </div>
            <div class="mt-2" style="${row.existing ? '' : 'display:none'}">
                <input type="hidden" name="advance_applications[${idx}][party_advance_id]" value="${advance.id}" ${row.existing ? '' : 'disabled'}>
                <input type="number" step="0.01" min="0.01" max="${maxAmount}" name="advance_applications[${idx}][amount]" class="form-control advance-amount" placeholder="Advance amount to apply" value="${amountValue}" ${row.existing ? '' : 'disabled'}>
                <small class="text-muted d-block mt-1">${advance.description || ''}</small>
            </div>
        </div>
    `}).join(''));
}
async function fetchAdvances(clearExisting = false){
    if(clearExisting){
        existingAdvanceApplications = [];
    }
    const partyId = $('[name="party_id"]').val();
    if(!partyId){ availableAdvances = []; renderAdvanceSection(); return; }
    const res = await fetch(`{{ route('admin.party-advances.available') }}?party_id=${partyId}&flow=sales`, { headers:{Accept:'application/json'} });
    const payload = res.ok ? await res.json() : { advances: [] };
    availableAdvances = payload.advances || [];
    renderAdvanceSection();
}
function rowSelected($row){
    try{ return JSON.parse($row.find('.selected-units-json').val()||'[]'); }catch(e){ return []; }
}
function setRowSelected($row, units){
    $row.find('.selected-units-json').val(JSON.stringify(units));
    $row.find('.selected-units').html(
        units.map(u =>
            `<span class="selected-pill">${escapeHtml(u.serial_no||'No serial')}${u.vts_sim?' / '+escapeHtml(u.vts_sim):''} <small>${escapeHtml(u.production_batch_no||'')}</small></span>`
        ).join('')
    );
    calc();
}

// ── Max-discount enforcement ──────────────────────────────────────────────────
/**
 * Returns the effective max-discount percent for a row.
 * Priority: data-max-disc on the selected item option.
 * 0 means "no limit".
 */
function getMaxDisc($row){
    return parseFloat($row.find('.item-select option:selected').data('max-disc')) || 0;
}

/**
 * Calculates effective discount % for a row (converts flat → %).
 */
function effectiveDiscPct($row){
    const price = parseFloat($row.find('[name="unit_price[]"]').val()) || 0;
    const type  = $row.find('.disc-type').val();
    const val   = parseFloat($row.find('.disc-value').val()) || 0;
    if(price <= 0) return 0;
    return type === 'flat' ? (val / price) * 100 : val;
}

/**
 * Auto-fills max discount % into the discount field when item is selected,
 * then clamps if user manually types more than allowed.
 * @param {boolean} autoFill - true only on item-change (first load), fills the value automatically
 */
function enforceMaxDisc($row, autoFill = false){
    const maxDisc = getMaxDisc($row);
    const $warn   = $row.find('.disc-warn');
    const $badge  = $row.find('.disc-limit-badge');
    const $val    = $row.find('.disc-value');
    const $type   = $row.find('.disc-type');
    const price   = parseFloat($row.find('[name="unit_price[]"]').val()) || 0;

    // Always show "Max allowed" badge when item has a limit
    if(maxDisc > 0){
        $badge.addClass('show').find('.disc-limit-val').text(maxDisc + '%');
    } else {
        $badge.removeClass('show');
    }

    if(maxDisc <= 0){ $warn.removeClass('show'); return false; }

    // Auto-fill: on item selection, pre-fill the discount field with max%
    if(autoFill){
        $type.val('percent');           // ensure type is %
        $val.val(maxDisc.toFixed(2));   // fill the value
        $warn.removeClass('show');
        return false;
    }

    // Guard: if user manually entered more than allowed → clamp
    const pct = effectiveDiscPct($row);
    if(pct <= maxDisc){ $warn.removeClass('show'); return false; }

    if($type.val() === 'percent'){
        $val.val(maxDisc.toFixed(2));
    } else {
        const maxFlat = price * maxDisc / 100;
        $val.val(maxFlat.toFixed(2));
    }
    $warn.addClass('show').find('.disc-warn-text').text('Max ' + maxDisc + '% se zyada discount nahi de sakte!');
    return true;
}

// ── Tax mode sync ─────────────────────────────────────────────────────────────
function syncTaxMode($row){
    const mode  = $row.find('[name="tax_mode[]"]').val();
    const $tax  = $row.find('[name="tax_percent[]"]');
    if(mode === 'without_gst'){
        $tax.val(0);
    } else if($tax.val() === ''){
        $tax.val(+$row.find('.item-select option:selected').data('tax') || 18);
    }
}

// ── Totals ────────────────────────────────────────────────────────────────────
function calc(){
    let sub = 0, tax = 0, weight = 0;
    $('#lineCount').text($('#lineTable tbody tr').length + ' item(s)');
    $('#lineTable tbody tr').each(function(index){
        const r  = $(this);
        r.find('.line-index').text(index + 1);
        const q  = +r.find('[name="quantity[]"]').val()       || 0;
        const p  = +r.find('[name="unit_price[]"]').val()     || 0;
        const d  = +r.find('[name="discount_value[]"]').val() || 0;
        const dt = r.find('[name="discount_type[]"]').val();
        const tx = r.find('[name="tax_mode[]"]').val() === 'without_gst'
                   ? 0
                   : (+r.find('[name="tax_percent[]"]').val() || 0);
        const itemWeight = +r.find('.item-select option:selected').data('weight') || 0;
        const b  = q * p;
        const da = dt === 'flat' ? d : b * d / 100;
        const g  = Math.max(0, b - da);
        const ta = tx > 0 ? g * tx / (100 + tx) : 0;
        r.find('.line-total').text(money(g));
        sub    += g - ta;
        tax    += ta;
        weight += q * itemWeight;
    });
    const od = +$('[name="discount_amount"]').val() || 0;
    $('#uiSubtotal').text(money(sub));
    $('#uiTax').text(money(tax));
    $('#uiWeight').text(weight.toLocaleString('en-IN',{minimumFractionDigits:3,maximumFractionDigits:3})+' kg');
    $('#uiTotal').text(money(Math.max(0, sub + tax - od)));
}

// ── Auto-select serial units ──────────────────────────────────────────────────
function autoSelectUnits($row){
    const itemId = $row.find('[name="item_id[]"]').val();
    const qty    = parseInt($row.find('[name="quantity[]"]').val()) || 1;
    if(!itemId) return;

    let used = [];
    $('#lineTable tbody tr').not($row).each(function(){
        used = used.concat(rowSelected($(this)).map(u => u.key));
    });

    const requiresGps = ITEM_META[itemId] && ITEM_META[itemId].requires_gps;
    const available   = (UNIT_POOL[itemId] || []).filter(
        u => !u.sold && !used.includes(u.key) && (!requiresGps || u.vts_sim)
    );
    const availableByKey = new Map(available.map(u => [u.key, u]));
    const selected = rowSelected($row)
        .map(u => availableByKey.get(u.key))
        .filter(Boolean)
        .slice(0, qty);
    const selectedKeys = selected.map(u => u.key);

    available.forEach(unit => {
        if(selected.length < qty && !selectedKeys.includes(unit.key)){
            selected.push(unit);
            selectedKeys.push(unit.key);
        }
    });
    setRowSelected($row, selected);
}

// ── Add line row ──────────────────────────────────────────────────────────────
function addLine(data = {}, $after = null) {

    const $row = $($('#lineTpl').html());

    if($after && $after.jquery){ $after.after($row); }
    else { $('#lineTable tbody').append($row); }

    // Destroy if already initialized
    if ($row.find('.select2-item').hasClass("select2-hidden-accessible")) {
        $row.find('.select2-item').select2('destroy');
    }

    // Initialize Select2
    $row.find('.select2-item').select2({
        width: '100%',
        placeholder: 'Select finished goods',
        allowClear: true,
        dropdownAutoWidth: true
    });

    if(data.item_id){
        $row.find('[name="item_id[]"]').val(data.item_id).trigger('change');
    }

    ['description','quantity','unit','unit_price','discount_type','discount_value','tax_mode','tax_percent'].forEach(k => {
        $row.find(`[name="${k}[]"]`).val(data[k] ?? $row.find(`[name="${k}[]"]`).val());
    });

    syncTaxMode($row);

    setRowSelected($row, data.selected_units || []);

    if(data.item_id){
        if(!data.selected_units || !data.selected_units.length){
            autoSelectUnits($row);
        }

        const hasDisc = data.discount_value !== undefined && parseFloat(data.discount_value) !== 0;

        enforceMaxDisc($row, !hasDisc && data.unit_price === undefined);
    }

    calc();
    return $row;
}

// ── Drawer render ─────────────────────────────────────────────────────────────
function renderDrawer(){
    if(!activeRow) return;
    const itemId       = activeRow.find('[name="item_id[]"]').val();
    const qty          = parseInt(activeRow.find('[name="quantity[]"]').val()) || 1;
    const selected     = rowSelected(activeRow);
    const selectedKeys = selected.map(u => u.key);
    let usedElsewhere  = [];
    $('#lineTable tbody tr').not(activeRow).each(function(){
        usedElsewhere = usedElsewhere.concat(rowSelected($(this)).map(u => u.key));
    });
    const units = UNIT_POOL[itemId] || [];
    $('#drawerTitle').text(activeRow.find('.item-select option:selected').text() || 'Finished Goods Units');
    $('#drawerHint').text(`Select ${qty} unit(s). ${selected.length} selected.`);

    const query = $('#unitSearch').val().trim().toLowerCase();
    $('#clearUnitSearch').prop('hidden', !query);
    const matchingStatus = units.filter(u =>
        activeFilter === 'all' ||
        (activeFilter === 'sold'      && u.sold) ||
        (activeFilter === 'selected'  && selectedKeys.includes(u.key)) ||
        (activeFilter === 'available' && !u.sold && !usedElsewhere.includes(u.key))
    );
    const filtered = matchingStatus.filter(u => !query || [u.serial_no, u.batch_no, u.vts_sim, u.buyer_code, u.production_batch_no, u.production_date, u.warehouse]
        .some(value => String(value ?? '').toLowerCase().includes(query)));
    $('#unitResults').text(`${filtered.length} of ${matchingStatus.length} units shown`);

    $('#unitList').html(filtered.map(u => {
        const checked     = selectedKeys.includes(u.key);
        const unavailable = (u.sold || usedElsewhere.includes(u.key)) && !checked;
        return `<label class="unit-card ${unavailable?'sold':''} ${checked?'selected':''}">
            <input type="checkbox" class="unit-check" data-key="${escapeHtml(u.key)}"
                ${checked?'checked':''} ${unavailable?'disabled':''}>
            <span>
                <b>${escapeHtml(u.serial_no||'No serial')} / ${escapeHtml(u.batch_no||'No purchase batch')}</b>
                <div class="unit-meta">VTS/SIM ${escapeHtml(u.vts_sim||'-')} | Buyer ${escapeHtml(u.buyer_code||'-')} | Production ${escapeHtml(u.production_batch_no||'-')} | ${escapeHtml(u.production_date||'-')}</div>
                <div class="unit-meta">${unavailable?'Unavailable':(checked?'Selected':'Available')} | Warehouse ${escapeHtml(u.warehouse||'-')} | Cost ${money(u.cost_per_unit)}</div>
            </span>
        </label>`;
    }).join('') || '<div class="unit-empty"><i class="fas fa-search" aria-hidden="true"></i><strong>No matching units</strong><div class="small mt-2">Try another search or switch the status filter.</div></div>');
}

function openDrawer($row){
    activeRow    = $row;
    activeFilter = 'available';
    $('#unitSearch').val('');
    $('.unit-filter').removeClass('btn-primary').addClass('btn-outline-secondary');
    $('.unit-filter[data-filter="available"]').addClass('btn-primary').removeClass('btn-outline-secondary');
    $('#unitDrawer,#unitBackdrop').addClass('open');
    $('#unitDrawer').prop('inert', false);
    renderDrawer();
    $('#unitSearch').trigger('focus');
}
function closeDrawer(){
    $('#unitDrawer,#unitBackdrop').removeClass('open');
    if(activeRow){ activeRow.find('.choose-units').trigger('focus'); }
    $('#unitDrawer').prop('inert', true);
    activeRow = null;
}

// ── Event bindings ────────────────────────────────────────────────────────────

$(document).on('click', '.add-line', function(){
    const $row = addLine({}, $(this).closest('tr'));
    $row[0].scrollIntoView({behavior:'smooth', block:'nearest'});
    $row.find('.item-select').select2('open');
});

// Recalc on any input/select change
$(document).on('input change', '#lineTable input,#lineTable select,[name="discount_amount"]', calc);

// Remove row
$(document).on('click', '.remove-row', function(){
    const $row = $(this).closest('tr');
    $row.find('.select2-item').select2('destroy');
    $row.remove();
    if(!$('#lineTable tbody tr').length){ addLine(); }
    calc();
});

// Item changed
$(document).on('change', '.item-select', function(){
    const o = $(this).find(':selected');
    const r = $(this).closest('tr');
    r.find('[name="unit[]"]').val(o.data('unit'));
    r.find('[name="unit_price[]"]').val(o.data('price'));
    if(r.find('[name="tax_mode[]"]').val() !== 'without_gst'){
        r.find('[name="tax_percent[]"]').val(o.data('tax') || 18);
    }
    // Auto-fill max discount from item setting, then show badge
    r.find('.disc-value').val(0);
    enforceMaxDisc(r, true);   // true = auto-fill: prefills max% into field
    autoSelectUnits(r);
    calc();
});

// Discount value or type changed → enforce max
$(document).on('input change', '.disc-value, .disc-type', function(){
    const $row = $(this).closest('tr');
    enforceMaxDisc($row);
    calc();
});

// Price changed → re-check flat discount cap
$(document).on('input', '[name="unit_price[]"]', function(){
    enforceMaxDisc($(this).closest('tr'));
    calc();
});

// Tax mode changed
$(document).on('change', '.tax-mode', function(){
    syncTaxMode($(this).closest('tr'));
    calc();
});

// Open unit serial drawer
$(document).on('click', '.choose-units', function(){ openDrawer($(this).closest('tr')); });

// Qty changed → re-auto-select serials
$(document).on('change', '.line-qty', function(){ autoSelectUnits($(this).closest('tr')); });

$(document).on('change', '[name="party_id"]', () => fetchAdvances(true));
$('#advanceIndicator,#toggleAdvancePanel').on('click', function(){
    $('#advancePanel').toggle();
});
$(document).on('change', '.advance-check', function(){
    const row = $(this).closest('.advance-row');
    row.toggleClass('active', this.checked);
    row.children('.mt-2').toggle(this.checked);
    row.find('input[name]').prop('disabled', !this.checked);
    if(this.checked && !row.find('.advance-amount').val()){
        row.find('.advance-amount').val((parseFloat(row.data('remaining'))||0).toFixed(2));
    }
});
$(document).on('input', '.advance-amount', function(){
    const row = $(this).closest('.advance-row');
    const remaining = parseFloat(row.data('remaining')||0);
    if(parseFloat(this.value||0) > remaining){
        alert('Advance amount cannot exceed remaining balance.');
        this.value = remaining.toFixed(2);
    }
});

// Unit checkbox in drawer
$(document).on('change', '.unit-check', function(){
    let selected = rowSelected(activeRow);
    const itemId = activeRow.find('[name="item_id[]"]').val();
    const units  = UNIT_POOL[itemId] || [];
    const unit   = units.find(u => String(u.key) === $(this).attr('data-key'));
    const qty    = parseInt(activeRow.find('[name="quantity[]"]').val()) || 1;

    if(this.checked){
        if(selected.length >= qty){ this.checked = false; alert(`You can select only ${qty} unit(s).`); return; }
        if(ITEM_META[itemId] && ITEM_META[itemId].requires_gps && !unit.vts_sim &&
           !confirm('Aap GPS product select kiye hain, lekin is unit me SIM/VTS number nahi hai. Kya aap isko select karna chahte hain?')){
            this.checked = false; return;
        }
        selected.push(unit);
    } else {
        selected = selected.filter(u => String(u.key) !== $(this).attr('data-key'));
    }
    setRowSelected(activeRow, selected);
    renderDrawer();
});

// Inter-company toggle
$('#interCompanyTransfer').on('change', function(){ $('#mergedCompanyBox').toggle(this.checked); }).trigger('change');

// Drawer filter pills
$('.unit-filter').click(function(){
    activeFilter = $(this).data('filter');
    $('.unit-filter').removeClass('btn-primary').addClass('btn-outline-secondary');
    $(this).addClass('btn-primary').removeClass('btn-outline-secondary');
    renderDrawer();
});

// Close drawer
$('#closeDrawer,#doneDrawer,#unitBackdrop').click(closeDrawer);
$('#unitSearch').on('input', renderDrawer).on('keydown', function(event){
    if(event.key === 'Enter'){ event.preventDefault(); }
});
$('#clearUnitSearch').on('click', function(){ $('#unitSearch').val('').trigger('input').trigger('focus'); });
$(document).on('keydown', function(event){
    if(!$('#unitDrawer').hasClass('open')) return;
    if(event.key === 'Escape'){ event.preventDefault(); closeDrawer(); }
    if(event.key === 'Tab'){
        const $focusable = $('#unitDrawer').find('button:not([hidden]), input:not(:disabled)');
        const first = $focusable[0], last = $focusable[$focusable.length - 1];
        if(event.shiftKey && document.activeElement === first){ event.preventDefault(); last.focus(); }
        else if(!event.shiftKey && document.activeElement === last){ event.preventDefault(); first.focus(); }
    }
});

// Terms template
$('#termsTemplate').on('change', function(){ if(this.value){ $('#termsBox').val(this.value); } });

// ── Init ──────────────────────────────────────────────────────────────────────
if(PREFILL_LINES.length){ PREFILL_LINES.forEach(data => addLine(data)); } else { addLine(); }
if($('[name="party_id"]').val()){ fetchAdvances(false); }
</script>
@endpush
