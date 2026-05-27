<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssessmentRequest extends FormRequest
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
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.answer' => ['required', 'string'],
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
            'answers.required' => 'As respostas são obrigatórias.',
            'answers.array' => 'As respostas devem ser um array.',
            'answers.min' => 'É necessário enviar pelo menos uma resposta.',
            'answers.*.question_id.required' => 'O campo question_id é obrigatório.',
            'answers.*.question_id.exists' => 'A questão informada não existe.',
            'answers.*.answer.required' => 'O campo answer é obrigatório.',
        ];
    }
}
