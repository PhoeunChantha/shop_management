<?php

declare(strict_types=1);

namespace App\Services;

use App\Imports\ProductsImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

final class ProductImportService
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * @return array{status: string, message: string}
     */
    public function preview(UploadedFile $file, Store $session): array
    {
        $path = $file->storeAs(
            'imports/product-previews',
            Str::uuid().'.'.$file->getClientOriginalExtension(),
        );

        $import = new ProductsImport($this->settings, dryRun: true);

        try {
            Excel::import($import, Storage::path($path));
        } catch (\Throwable) {
            Storage::delete($path);

            return ['status' => 'error', 'message' => 'Could not read the file. Make sure it matches the template.'];
        }

        $session->put('product_import_preview', [
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'valid' => $import->valid,
            'errors' => $import->errors,
            'rows' => $import->previewRows,
            'created_at' => now()->toDateTimeString(),
        ]);

        return [
            'status' => empty($import->errors) ? 'success' : 'warning',
            'message' => $import->valid.' valid row(s) ready. '.count($import->errors).' row(s) need review.',
        ];
    }

    /**
     * @return array{status: string, message: string, errors?: array<int, mixed>}
     */
    public function confirm(Store $session): array
    {
        $preview = $session->get('product_import_preview');

        if (! is_array($preview) || empty($preview['path']) || ! Storage::exists($preview['path'])) {
            return ['status' => 'warning', 'message' => 'Upload a product file before confirming import.'];
        }

        $import = new ProductsImport($this->settings);

        try {
            Excel::import($import, Storage::path($preview['path']));
        } catch (\Throwable) {
            return ['status' => 'error', 'message' => 'Could not import the reviewed file. Upload it again and retry.'];
        } finally {
            Storage::delete($preview['path']);
            $session->forget('product_import_preview');
        }

        $result = [
            'status' => 'success',
            'message' => "Import finished - {$import->created} created, {$import->updated} updated.",
        ];

        if (! empty($import->errors)) {
            $result['errors'] = $import->errors;
        }

        return $result;
    }

    public function cancel(Store $session): void
    {
        $preview = $session->pull('product_import_preview');

        if (is_array($preview) && ! empty($preview['path'])) {
            Storage::delete($preview['path']);
        }
    }

    /**
     * @return array{status: string, message: string, errors?: array<int, mixed>}
     */
    public function import(UploadedFile $file): array
    {
        $import = new ProductsImport($this->settings);

        try {
            Excel::import($import, $file);
        } catch (\Throwable) {
            return ['status' => 'error', 'message' => 'Could not read the file. Make sure it matches the template.'];
        }

        $result = [
            'status' => 'success',
            'message' => "Import finished - {$import->created} created, {$import->updated} updated.",
        ];

        if (! empty($import->errors)) {
            $result['errors'] = $import->errors;
        }

        return $result;
    }
}
