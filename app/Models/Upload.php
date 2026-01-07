<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    /** @use HasFactory<\Database\Factories\UploadFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'filename',
        'extension',
        'size',
        'checksum',
        'status',
        'total_chunks',
        'uploaded_chunks',
        'path',
    ];

    public function images()
    {
        return $this->hasMany(Image::class);
    }
}
