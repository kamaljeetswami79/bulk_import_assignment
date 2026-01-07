<?php

namespace App\Services;

use App\Models\Image as ImageModel;
use App\Models\Product;
use App\Models\Upload;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageProcessorService
{
    protected $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function process(Upload $upload, Product $product, $isPrimary = false)
    {
        $sourcePath = Storage::path($upload->path);
        $extension = $upload->extension;
        $baseName = pathinfo($upload->filename, PATHINFO_FILENAME);

        $sizes = [
            'original' => null,
            '256' => 256,
            '512' => 512,
            '1024' => 1024,
        ];

        $images = [];

        foreach ($sizes as $label => $width) {
            $img = $this->manager->read($sourcePath);
            
            if ($width) {
                $img->scale(width: $width);
            }

            $fileName = "{$baseName}_{$label}.{$extension}";
            $destPath = "images/{$product->id}/{$fileName}";
            
            Storage::disk('public')->put($destPath, (string) $img->encode());

            $newImage = ImageModel::create([
                'product_id' => $product->id,
                'upload_id' => $upload->id,
                'disk' => 'public',
                'path' => $destPath,
                'size_label' => $label,
                'width' => $img->width(),
                'height' => $img->height(),
            ]);

            $images[$label] = $newImage;

            if ($isPrimary && $label === 'original') {
                $product->update(['primary_image_id' => $newImage->id]);
            }
        }

        return $images;
    }
}
