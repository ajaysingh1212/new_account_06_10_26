@push('styles')
<style>
.party-workspace {color:#26343c}
.party-workspace .card {border:0;border-radius:6px;box-shadow:none!important;margin-bottom:24px}
.party-workspace .card-header {background:#fff;border-bottom:1px solid #e4e9ed;padding:18px 20px;flex-wrap:wrap;gap:12px}
.party-workspace .card-title {font-size:16px;font-weight:700}
.party-workspace .card-body {padding:20px}
.party-workspace .stat-card {background:#fff!important;color:#26343c;border:1px solid #e1e7eb;border-radius:6px;box-shadow:none;padding:18px;min-height:110px}
.party-workspace .stat-card .stat-value {font-size:25px;overflow-wrap:anywhere}
.party-workspace .stat-card .stat-label {color:#667781}
.party-workspace .stat-card .stat-icon {color:#218ca6;opacity:.2}
.party-workspace .advance-summary {padding:14px 0;border-bottom:1px solid #e4e9ed}
.party-metrics {display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:24px}
.party-metric {background:#fff;border:1px solid #e1e7eb;border-top:3px solid var(--metric-color,#168577);border-radius:6px;padding:18px;min-width:0}
.party-metric label {font-size:12px;color:#667781;margin-bottom:8px;display:block}
.party-metric strong {font-size:23px;line-height:1.3;overflow-wrap:anywhere;display:block}
.party-metric small {display:block;margin-top:8px;color:#667781}
.party-metric i {float:right;color:var(--metric-color,#168577)}
.party-workspace .table thead th {background:#f3f6f8;color:#61717b;font-size:11px;text-transform:uppercase;letter-spacing:0;border-top:0;white-space:nowrap}
.party-workspace .table td {vertical-align:middle;font-size:13px;border-color:#edf0f2;padding:13px 10px}
.party-workspace .table-bordered,.party-workspace .table-bordered th,.party-workspace .table-bordered td {border-left:0;border-right:0}
.party-workspace .dataTables_wrapper .row {align-items:center}
.party-workspace .dataTables_filter input {max-width:100%;border-radius:4px}
.statement-tools {display:flex;flex-wrap:wrap;align-items:end;gap:12px;margin-bottom:20px}
.statement-tools label {font-size:12px;color:#667781;display:block;margin-bottom:5px}
.statement-tools .form-control {height:36px;min-width:145px;font-size:13px}
.statement-tools>div {min-width:0}
.party-workspace .table td:nth-child(4) {overflow-wrap:anywhere}
.balance-detail {display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:18px;padding-top:16px;border-top:1px solid #e4e9ed}
.balance-detail small {display:block;color:#667781}
.entry-type {display:inline-block;padding:4px 8px;background:#eef3f6;border-radius:4px;font-size:11px;font-weight:600;white-space:nowrap}
@media(max-width:767px){.party-metrics{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.party-metric{padding:13px}.party-metric strong{font-size:19px}.party-workspace .card-body{padding:14px}.statement-tools>div{flex:1 1 130px}}
@media print{.main-sidebar,.main-header,.main-footer,.statement-tools,.dataTables_length,.dataTables_filter,.dataTables_paginate,.breadcrumb,.btn{display:none!important}.content-wrapper{margin:0!important}.party-workspace .card{break-inside:avoid}.party-metrics{grid-template-columns:repeat(4,1fr)}}
</style>
@endpush
