<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMateriaPrimaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:30', 'unique:materias_primas,codigo'],
            'nombre' => ['required', 'string', 'max:120'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'unidad_base' => ['required', 'in:kg,g'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:120'],
            'ubicacion' => ['nullable', 'string', 'max:120'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'unidad_base.in' => 'La unidad base debe ser kg o g.',
            'codigo.unique' => 'El código ya está en uso.',
        ];
    }
}
