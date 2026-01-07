<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $summary = [
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'invalid' => 0,
        'duplicates' => 0, // In this context, duplicates might be rows in the same CSV with same SKU
    ];

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        if (!file_exists($this->filePath)) {
            Log::error("Import file not found: {$this->filePath}");
            return;
        }

        $file = fopen($this->filePath, 'r');
        $header = fgetcsv($file);

        if (!$header) {
            Log::error("Empty CSV file: {$this->filePath}");
            return;
        }

        // Expected columns: sku, name, price, description
        $expectedColumns = ['sku', 'name', 'price', 'description'];
        $columnMap = array_flip($header);

        $processedSkus = [];

        while (($row = fgetcsv($file)) !== false) {
            $this->summary['total']++;

            $data = [];
            $isValid = true;
            foreach ($expectedColumns as $col) {
                if (!isset($columnMap[$col]) || !isset($row[$columnMap[$col]])) {
                    $isValid = false;
                    break;
                }
                $data[$col] = $row[$columnMap[$col]];
            }

            if (!$isValid || empty($data['sku'])) {
                $this->summary['invalid']++;
                continue;
            }

            if (isset($processedSkus[$data['sku']])) {
                $this->summary['duplicates']++;
                // We'll treat the first one as valid, others as duplicates for summary
                // But we could also update. The requirement says "upsert by unique key".
                // If the same SKU appears twice IN THE CSV, we count as duplicate? 
                // Or does duplicate mean already in DB? 
                // "Upsert" usually means if it exists in DB, update.
                // "Summary (total, imported, updated, invalid, duplicates)"
                // Let's assume duplicates are redundant rows in the CSV.
            }

            $product = Product::where('sku', $data['sku'])->first();
            
            if ($product) {
                $product->update([
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'description' => $data['description'],
                ]);
                $this->summary['updated']++;
            } else {
                Product::create($data);
                $this->summary['imported']++;
            }

            $processedSkus[$data['sku']] = true;
        }

        fclose($file);

        Log::info("Import Completed", $this->summary);
        // In a real app, we'd notify the user or store the result in a table.
        return $this->summary;
    }

    public function getSummary()
    {
        return $this->summary;
    }
}
