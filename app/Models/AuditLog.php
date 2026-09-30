<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'details',
        'raison',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(Model $model, string $action, ?string $champ = null, $old = null, $new = null, ?int $userId = null): void
    {
        try {
            static::create([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => class_basename($model),
                'entity_id' => $model->getKey(),
                'details' => json_encode([
                    'champ' => $champ,
                    'ancienne_valeur' => $old,
                    'nouvelle_valeur' => $new,
                ], JSON_UNESCAPED_UNICODE),
                'raison' => $champ,
            ]);
        } catch (\Throwable $e) {
            // Never block business operations on audit failure
            report($e);
        }
    }
}
