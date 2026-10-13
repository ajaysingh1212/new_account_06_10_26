@foreach(($units ?? []) as $unit)
    @if(is_array($unit))
        @php
            $identifiers = collect([
                'Serial' => $unit['serial_no'] ?? null,
                'VTS/SIM' => $unit['vts_sim'] ?? null,
                'VTS' => $unit['vts_no'] ?? $unit['vts_number'] ?? null,
                'SIM' => $unit['sim_no'] ?? $unit['sim_number'] ?? null,
            ])->map(fn($value) => trim((string) $value))->filter(fn($value) => $value !== '');
        @endphp
        @if($identifiers->isNotEmpty())
            <div class="item-identifiers">@foreach($identifiers as $label => $value)<span><strong>{{ $label }}:</strong> {{ $value }}</span>@if(!$loop->last) <span aria-hidden="true"> | </span> @endif@endforeach</div>
        @endif
    @endif
@endforeach
