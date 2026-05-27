<?php

namespace App\Models;

use Database\Factories\RoadmapFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'nivel_calculado', 'pontuacao_tecnica', 'trilha_json'])]
class Roadmap extends Model
{
    /** @use HasFactory<RoadmapFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pontuacao_tecnica' => 'integer',
            'trilha_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
