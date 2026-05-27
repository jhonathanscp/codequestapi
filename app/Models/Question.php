<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'content', 'options', 'correct_answer', 'order'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'order' => 'integer',
        ];
    }

    /**
     * Verifica se a questão é do tipo perfil.
     */
    public function isProfile(): bool
    {
        return $this->type === 'profile';
    }

    /**
     * Verifica se a questão é do tipo técnica.
     */
    public function isTechnical(): bool
    {
        return $this->type === 'technical';
    }
}
