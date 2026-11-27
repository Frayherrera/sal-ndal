{{--
    <option> de presentaciones agrupadas por producto (<optgroup>).
    Requiere $presentaciones (colección de ProductoTerminado, ya ordenada con ->ordenado())
    y $seleccionado (id). Opcional $sufijo: fn (ProductoTerminado $pt) => string.
--}}
@foreach ($presentaciones->groupBy('nombre') as $nombre => $grupo)
    <optgroup label="{{ $nombre }}">
        @foreach ($grupo as $pt)
            <option value="{{ $pt->id }}" @selected((string) $seleccionado === (string) $pt->id)>
                {{ $nombre }} · {{ $pt->etiqueta() }}{{ isset($sufijo) ? $sufijo($pt) : '' }}
            </option>
        @endforeach
    </optgroup>
@endforeach
