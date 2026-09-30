<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Relationship extends Model
{
    use BelongsToFamily;
    use SoftDeletes;

    public const DIRECTIONAL = ['parent', 'adoptive_parent', 'step_parent', 'guardian'];

    public const SYMMETRIC = ['spouse', 'partner', 'sibling'];

    public const LABELS = [
        'parent' => 'parent',
        'adoptive_parent' => 'adoptive parent',
        'step_parent' => 'step-parent',
        'guardian' => 'guardian',
        'spouse' => 'spouse',
        'partner' => 'partner',
        'sibling' => 'sibling',
    ];

    protected $fillable = [
        'family_id',
        'person_one_id',
        'person_two_id',
        'relationship_type',
        'is_directional',
        'start_date',
        'start_date_precision',
        'start_date_text',
        'start_date_range_end',
        'end_date',
        'end_date_precision',
        'end_date_text',
        'end_date_range_end',
        'confidence_level',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_directional' => 'boolean',
        'start_date' => 'date',
        'start_date_range_end' => 'date',
        'end_date' => 'date',
        'end_date_range_end' => 'date',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function personOne(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_one_id');
    }

    public function personTwo(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_two_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function citations(): MorphMany
    {
        return $this->morphMany(Citation::class, 'citable');
    }

    public static function isDirectionalType(string $type): bool
    {
        return in_array($type, self::DIRECTIONAL, true);
    }

    public static function canonicalize(int $one, int $two, string $type): array
    {
        if (! self::isDirectionalType($type) && $one > $two) {
            return [$two, $one];
        }

        return [$one, $two];
    }

    public function label(): string
    {
        return self::LABELS[$this->relationship_type] ?? $this->relationship_type;
    }

    public function previewSentence(): string
    {
        $one = $this->personOne?->full_name ?? 'Person A';
        $two = $this->personTwo?->full_name ?? 'Person B';
        $type = $this->label();

        if (self::isDirectionalType($this->relationship_type)) {
            return "{$one} will become the {$type} of {$two}";
        }

        return "{$one} and {$two} will be recorded as {$type}s";
    }
}
