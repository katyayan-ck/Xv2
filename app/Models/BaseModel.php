<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;
use App\Models\Traits\HasAuditFields;
use App\Models\Traits\HasSlug;
use App\Models\Traits\HasColumnTransformations;
use App\Models\Traits\HasTreeStructure;

/**
 * BaseModel - Foundation model for all VDMS models
 * 
 * Features:
 * - Soft deletes for data safety
 * - Audit trail tracking (created_by, updated_by, deleted_by)
 * - Media library integration (file uploads)
 * - Automatic timestamp handling
 * - Common scopes and relationships
 * - Translations
 * - Tree structure
 * - Slug generation
 * - Column transformations
 */
abstract class BaseModel extends Model implements Auditable, HasMedia
{
    use SoftDeletes;
    use AuditableTrait;
    use InteractsWithMedia;
    use HasTranslations;
    use HasAuditFields;
    // use HasSlug;
    //use HasTreeStructure;
    use HasColumnTransformations;
    //use FilterByDataScope;
    //use HasTreeStructure;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'extra_data' => 'array',
        'is_active' => 'boolean',
    ];

    public $translatable = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Enable timestamps
     *
     * @var bool
     */
    public $timestamps = true;

    protected static function booted()
    {
        // $user = auth()->user();

        // //  Rule 1: SuperAdmin must NEVER be scoped
        // if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
        //     return; // Do NOT add FilterByDataScope at all
        // }

        // //  Rule 2: Only models with $scopeType are scoped
        // if (property_exists(static::class, 'scopeType')) {
        //     static::addGlobalScope(new \App\Models\Traits\FilterByDataScope());
        // }
    }

    /**
     * Get audit trail history
     */
    public function getHistory($limit = 50)
    {
        return $this->audits()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'user' => $audit->user?->name ?? 'System',
                    'changes' => $audit->getModified(),
                    'created_at' => $audit->created_at->format('Y-m-d H:i:s'),
                ];
            });
    }

    /**
     * Register media collections for file uploads
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword'])
            ->useDisk('public');

        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif'])
            ->useDisk('public');

        $this->addMediaCollection('attachments')
            ->useDisk('public');
    }

    /**
     * Scope: Get only active records
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    /**
     * Scope: Get only inactive records
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Get only deleted records
     */
    public function scopeDeleted($query)
    {
        return $query->whereNotNull('deleted_at');
    }

    /**
     * Scope: Get all records including deleted (for admin)
     */
    public function scopeWithDeleted($query)
    {
        return $query->withTrashed();
    }

    /**
     * Scope: Order by created date newest first
     */
    public function scopeNewest($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope: Order by created date oldest first
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at');
    }
}
