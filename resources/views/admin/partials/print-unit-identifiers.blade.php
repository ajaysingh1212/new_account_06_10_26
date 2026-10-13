<?php
foreach (($units ?? []) as $unit) {
    if (!is_array($unit)) {
        continue;
    }

    $unitPrintFields = [
        'Serial' => $unit['serial_no'] ?? null,
        'VTS/SIM' => $unit['vts_sim'] ?? null,
        'VTS' => $unit['vts_no'] ?? $unit['vts_number'] ?? null,
        'SIM' => $unit['sim_no'] ?? $unit['sim_number'] ?? null,
    ];
    $unitPrintParts = [];
    foreach ($unitPrintFields as $label => $value) {
        if (!is_scalar($value) || trim((string) $value) === '') {
            continue;
        }
        $unitPrintParts[] = '<span><strong>'.e($label).':</strong> '.e(trim((string) $value)).'</span>';
    }

    if ($unitPrintParts !== []) {
        echo '<div class="item-identifiers">'.implode(' <span aria-hidden="true"> | </span> ', $unitPrintParts).'</div>';
    }
}
?>
