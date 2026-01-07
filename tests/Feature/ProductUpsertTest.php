<?php

namespace Tests\Feature;

use App\Jobs\ProcessCsvImport;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductUpsertTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_upserts_products()
    {
        Storage::fake('local');
        
        $filePath = storage_path('app/test_import.csv');
        $content = "sku,name,price,description\n";
        $content .= "SKU001,Product 1,10.00,Desc 1\n";
        $content .= "SKU002,Product 2,20.00,Desc 2\n";
        
        file_put_contents($filePath, $content);

        // First import (Create)
        $job = new ProcessCsvImport($filePath);
        $summary = $job->handle();

        $this->assertEquals(2, $summary['total']);
        $this->assertEquals(2, $summary['imported']);
        $this->assertDatabaseHas('products', ['sku' => 'SKU001', 'name' => 'Product 1']);

        // Second import with updates
        $content = "sku,name,price,description\n";
        $content .= "SKU001,Product 1 Updated,15.00,Desc 1 Updated\n";
        $content .= "SKU003,Product 3,30.00,Desc 3\n";
        
        file_put_contents($filePath, $content);

        $job = new ProcessCsvImport($filePath);
        $summary = $job->handle();

        $this->assertEquals(2, $summary['total']);
        $this->assertEquals(1, $summary['imported']); // SKU003
        $this->assertEquals(1, $summary['updated']);  // SKU001
        
        $this->assertDatabaseHas('products', ['sku' => 'SKU001', 'name' => 'Product 1 Updated']);
        $this->assertDatabaseHas('products', ['sku' => 'SKU003']);

        unlink($filePath);
    }
}
