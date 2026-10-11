{{-- Parcial reutilizable: opciones <option> de grado, agrupadas por nivel (Primaria/Secundaria).
     Uso: @include('partials.grado-options', ['grados' => $grados, 'seleccionado' => $gradoId ?? null]) --}}
@php
    $gradosPorNivel = $grados->groupBy('nivel');
@endphp
@foreach($gradosPorNivel as $nivel => $gradosNivel)
    <optgroup label="{{ $nivel }}">
        @foreach($gradosNivel as $g)
            <option value="{{ $g->id }}" data-nivel="{{ $g->nivel }}" @selected(($seleccionado ?? null) == $g->id)>{{ $g->nombre }}</option>
        @endforeach
    </optgroup>
@endforeach
