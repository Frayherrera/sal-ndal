<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoTerminadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:30', 'unique:producto_terminados,codigo,'.$this->route('producto')?->id],
            'nombre' => ['required', 'string', 'max:120'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'presentacion' => ['nullable', 'in:bolsa,frasco'],
            'peso_neto' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'presentacion.in' => 'La presentación debe ser bolsa o frasco.',
            'codigo.unique' => 'El código ya está en uso.',
        ];
    }
}
