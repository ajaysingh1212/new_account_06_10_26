@push('scripts')
@php
    $estimateSavedUnits = old('selected_units', $estimate->items->map(fn($line) => json_encode($line->selected_units ?? []))->all());
@endphp
<script>
$(function() {
const estimateUnitPool = @json($unitPool ?? []);
const estimateSavedUnits = @json($estimateSavedUnits);

function estimateUnitKey(unit) {
    return String(unit.scope_key || unit.key || unit.serial_no || unit.vts_sim || '');
}
function estimateUnitLabel(unit) {
    const parts = [];
    if (unit.serial_no) parts.push('Serial: ' + unit.serial_no);
    if (unit.vts_sim) parts.push('VTS/SIM: ' + unit.vts_sim);
    if (unit.vts_no || unit.vts_number) parts.push('VTS: ' + (unit.vts_no || unit.vts_number));
    if (unit.sim_no || unit.sim_number) parts.push('SIM: ' + (unit.sim_no || unit.sim_number));
    return parts.join(' | ');
}
function fillEstimateUnits($row, saved = []) {
    const itemId = $row.find('.item-select').val();
    const pool = [...(estimateUnitPool[itemId] || []).filter(unit => !unit.sold), ...saved];
    const $select = $row.find('.estimate-unit-select').empty();
    const byKey = {};
    pool.forEach(unit => {
        const key = estimateUnitKey(unit);
        const label = estimateUnitLabel(unit);
        if (!key || !label || byKey[key]) return;
        byKey[key] = unit;
        $select.append($('<option>').val(key).text(label));
    });
    $row.data('estimateUnits', byKey);
    $select.val(saved.map(estimateUnitKey));
    syncEstimateUnits($row);
}
function syncEstimateUnits($row) {
    const $select = $row.find('.estimate-unit-select');
    const byKey = $row.data('estimateUnits') || {};
    const selected = ($select.val() || []).map(key => byKey[key]).filter(Boolean);
    const quantity = Number($row.find('[name="quantity[]"]').val()) || 0;
    $select[0].setCustomValidity(selected.length > quantity ? 'Selected units cannot exceed quotation quantity.' : '');
    $row.find('.estimate-units-json').val(JSON.stringify(selected));
}
function initEstimateUnitPicker($row, saved = []) {
    if ($row.find('.estimate-unit-select').length) return;
    const $picker = $('<div class="mt-2 estimate-unit-picker">');
    $picker.append($('<label class="small d-block">').text('Serial / VTS / SIM (optional)'));
    const $select = $('<select multiple class="form-control estimate-unit-select" aria-label="Quotation serial, VTS or SIM numbers">');
    $picker.append($select, $('<input type="hidden" name="selected_units[]" class="estimate-units-json" value="[]">'));
    $picker.append($('<small class="text-muted d-block">').text('Optional selection; quotation does not reserve stock.'));
    $row.find('[name="description[]"]').after($picker);
    fillEstimateUnits($row, saved);
    $select.select2({width:'100%', placeholder:'Select Serial / VTS / SIM', closeOnSelect:false});
}
$('#lineTable tbody tr').each(function(index) {
    let saved = [];
    try { saved = JSON.parse(estimateSavedUnits[index] || '[]'); } catch (_) {}
    initEstimateUnitPicker($(this), saved);
});
$('#addLine').on('click', function() {
    $('#lineTable tbody tr').each(function() { initEstimateUnitPicker($(this)); });
});
$(document).on('change', '#lineTable .item-select', function() { fillEstimateUnits($(this).closest('tr')); });
$(document).on('change', '.estimate-unit-select', function() { syncEstimateUnits($(this).closest('tr')); });
$(document).on('input change', '#lineTable [name="quantity[]"]', function() { syncEstimateUnits($(this).closest('tr')); });
});
</script>
@endpush
