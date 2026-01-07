<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\Product;
use App\Services\ImageProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    protected $imageProcessor;

    public function __construct(ImageProcessorService $imageProcessor)
    {
        $this->imageProcessor = $imageProcessor;
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'size' => 'required|integer',
            'total_chunks' => 'required|integer',
            'checksum' => 'nullable|string',
        ]);

        $uuid = (string) Str::uuid();
        $extension = pathinfo($request->filename, PATHINFO_EXTENSION);

        $upload = Upload::create([
            'uuid' => $uuid,
            'filename' => $request->filename,
            'extension' => $extension,
            'size' => $request->size,
            'checksum' => $request->checksum,
            'total_chunks' => $request->total_chunks,
            'status' => 'pending',
        ]);

        return response()->json([
            'uuid' => $uuid,
            'status' => 'initiated',
        ]);
    }

    public function uploadChunk(Request $request, $uuid)
    {
        $upload = Upload::where('uuid', $uuid)->firstOrFail();
        
        $chunkIndex = $request->input('chunk_index');
        $chunkData = $request->file('chunk');

        $chunkPath = "chunks/{$uuid}/{$chunkIndex}";
        Storage::put($chunkPath, file_get_contents($chunkData));

        // In a production app, we'd use a more robust way to track uploaded chunks (e.g. database or redis)
        // For simplicity, we'll increment a counter if it's a new chunk
        // Note: Task says "Re-sending chunks must not corrupt data"
        // Since we overwrite the same chunk file, it's idempotent.

        // We should check how many chunks are actually there
        $chunks = Storage::files("chunks/{$uuid}");
        $upload->update(['uploaded_chunks' => count($chunks)]);

        return response()->json([
            'status' => 'chunk_uploaded',
            'uploaded_chunks' => count($chunks),
        ]);
    }

    public function complete(Request $request, $uuid)
    {
        $upload = Upload::where('uuid', $uuid)->firstOrFail();
        $productId = $request->input('product_id');

        if ($upload->uploaded_chunks < $upload->total_chunks) {
            return response()->json(['error' => 'Incomplete upload'], 400);
        }

        // Merge chunks
        $finalPath = "uploads/{$uuid}.{$upload->extension}";
        $finalFullPath = Storage::path($finalPath);
        
        // Ensure directory exists
        if (!Storage::exists('uploads')) {
            Storage::makeDirectory('uploads');
        }

        $out = fopen($finalFullPath, 'wb');
        for ($i = 0; $i < $upload->total_chunks; $i++) {
            $chunkPath = Storage::path("chunks/{$uuid}/{$i}");
            $in = fopen($chunkPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        // Checksum validation
        if ($upload->checksum && md5_file($finalFullPath) !== $upload->checksum) {
            Storage::delete($finalPath);
            $upload->update(['status' => 'failed']);
            return response()->json(['error' => 'Checksum mismatch'], 422);
        }

        $upload->update([
            'status' => 'completed',
            'path' => $finalPath,
        ]);

        // Cleanup chunks
        Storage::deleteDirectory("chunks/{$uuid}");

        // Process images if product_id is provided
        if ($productId) {
            $product = Product::findOrFail($productId);
            
            // Check if this upload is already linked to this product (idempotent)
            $existing = $product->images()
                ->where('upload_id', $upload->id)
                ->where('size_label', 'original')
                ->first();

            if (!$existing) {
                $this->imageProcessor->process($upload, $product, true);
            }
        }

        return response()->json([
            'status' => 'completed',
            'path' => $finalPath,
        ]);
    }
}
