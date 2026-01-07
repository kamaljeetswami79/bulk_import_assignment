<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'upload_id',
        'disk',
        'path',
        'size_label',
        'width',
        'height',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }
}
