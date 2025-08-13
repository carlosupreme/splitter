<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class BudgetImage extends Model
{
    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'path',
        'description',
        'uploaded_by',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::temporaryUrl($this->path, now()->addMinutes(30));
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    public function delete()
    {
        // Delete the actual file when model is deleted
        if (Storage::exists($this->path)) {
            Storage::delete($this->path);
        }

        return parent::delete();
    }
}
