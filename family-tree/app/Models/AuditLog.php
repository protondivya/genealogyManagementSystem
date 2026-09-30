<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToFamily;

    public $timestamps = false;

    protected $fillable = [
        'family_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'changes',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(Family $family, User $user, string $action, Model $model, ?array $changes = null): self
    {
        return static::create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'action' => $action,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
