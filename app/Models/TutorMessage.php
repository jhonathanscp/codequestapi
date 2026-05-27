<?php

namespace App\Models;

use Database\Factories\TutorMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'role', 'content', 'roadmap_node_id'])]
class TutorMessage extends Model
{
    /** @use HasFactory<TutorMessageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'roadmap_node_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica se a mensagem é do aluno.
     */
    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Verifica se a mensagem é do tutor IA.
     */
    public function isFromAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
