<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateMockCsv extends Command
{
    protected $signature = 'make:mock-csv {rows=10000}';
    protected $description = 'Generate a large mock CSV file for product import';

    public function handle()
    {
        $rows = $this->argument('rows');
        $filename = 'products_mock.csv';
        $path = storage_path('app/' . $filename);

        $file = fopen($path, 'w');
        
        // Header
        fputcsv($file, ['sku', 'name', 'price', 'description', 'image_url']);

        $this->info("Generating {$rows} rows...");
        $bar = $this->output->createProgressBar($rows);

        for ($i = 1; $i <= $rows; $i++) {
            fputcsv($file, [
                'SKU-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'Product Name ' . $i,
                rand(10, 1000) . '.' . rand(0, 99),
                'Description for product ' . $i,
                'https://picsum.photos/seed/' . $i . '/800/600',
            ]);
            $bar->advance();
        }

        fclose($file);
        $bar->finish();
        
        $this->newLine();
        $this->info("Generated: {$path}");
    }
}
