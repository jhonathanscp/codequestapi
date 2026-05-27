<?php

namespace App\Http\Requests\Tutor;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:5000'],
            'roadmap_node_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'A mensagem é obrigatória.',
            'message.min' => 'A mensagem não pode estar vazia.',
            'message.max' => 'A mensagem excede o limite de 5000 caracteres.',
            'roadmap_node_id.integer' => 'O ID do nó do roadmap deve ser um número inteiro.',
        ];
    }
}
