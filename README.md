# Bulk Import & Chunked Image Upload Assessment

This project is a technical assessment for a Senior Laravel Developer position. It implements a robust system for bulk CSV product imports and a resumable, chunked image upload flow with automatic variant generation.

## 🚀 Features

### 📦 Bulk Product Import
- **Upsert Logic**: Automatically creates new products or updates existing ones based on the unique `sku` field.
- **Detailed Summary**: Produces a summary of the operation: total rows, imported, updated, invalid, and duplicate counts.
- **Fault Tolerant**: Missing columns in specific rows do not stop the entire import process.
- **Scalable**: Handles large datasets (10,000+ rows) efficiently using Laravel Jobs.

### 🖼️ Chunked Image Upload
- **Resumable & Idempotent**: Supports uploading large files in chunks. Re-sending the same chunk is idempotent and does not corrupt data.
- **Checksum Validation**: Ensures data integrity by validating the MD5 checksum of the merged file before completion.
- **Automatic Variant Generation**: Generates multiple image sizes (256px, 512px, 1024px) while preserving aspect ratio using `Intervention Image`.
- **Primary Image Management**: Automatically links the "Original" size as the primary image for the product.

## 🛠️ Tech Stack
- **Framework**: Laravel 12.x
- **Database**: MySQL
- **Image Processing**: Intervention Image 3.x (with GD Driver)
- **Queue**: Database Driver
- **Testing**: PHPUnit / Laravel Feature Tests

---

## ⚙️ Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/kamaljeetswami79/bulk_import_assignment.git
cd bulk_import_assignment
```

### 2. Install Dependencies
```bash
composer install
npm install && npm run build
```

### 3. Environment Configuration
Copy the example environment file and update your database credentials.
```bash
cp .env.example .env
php artisan key:generate
```
*Note: Ensure `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` are set correctly in your `.env` for MySQL.*

### 4. Database Migrations
```bash
php artisan migrate
```

### 5. Link Storage
```bash
php artisan storage:link
```

---

## 🏃 Running the Application

### Generate Mock Data
Use the custom command to generate a CSV with 10,000 product rows for testing:
```bash
php artisan make:mock-csv 10000
```
This will create a file at `storage/app/products_mock.csv`.

### Run Tests
Execute the feature tests to validate the upsert logic:
```bash
php artisan test tests/Feature/ProductUpsertTest.php
```

---

## 🛣️ API Endpoints

### 📝 CSV Import
- **POST** `/api/import-csv`
  - Body: `file` (CSV/TXT)
  - Returns: JSON summary of import results.

### 📤 Chunked Upload Flow
1. **Initiate**: `POST /api/upload/initiate`
   - Fields: `filename`, `size`, `total_chunks`, `checksum` (optional)
2. **Upload Chunk**: `POST /api/upload/{uuid}/chunk`
   - Fields: `chunk` (File), `chunk_index` (int)
3. **Complete**: `POST /api/upload/{uuid}/complete`
   - Fields: `product_id` (int)

---

## 🧠 Technical Highlights

- **Service Layer**: Image processing logic is decoupled into `App\Services\ImageProcessorService` for better maintainability.
- **Job Processing**: `App\Jobs\ProcessCsvImport` handles the heavy lifting of CSV parsing, allowing for future asynchronous queueing.
- **Idempotency**: The system ensures that re-attaching the same upload to the same product results in a no-op, preventing redundant processing.

---

**Developed for technical evaluation.**
