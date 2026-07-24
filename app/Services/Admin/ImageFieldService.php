<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ImageManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class ImageFieldService
{
    public function attachUploaded(Model $record, UploadedFile $file, string $folder, string $field = 'image'): void
    {
        $record->{$field} = ImageManager::upload($file, $folder);
        $record->save();
    }

    public function replaceUploaded(Model $record, UploadedFile $file, string $folder, string $field = 'image'): void
    {
        $record->{$field} = ImageManager::update($file, $record->{$field}, $folder);
        $record->save();
    }

    public function attachSelected(Model $record, string $filename, string $field = 'image'): void
    {
        $record->{$field} = $filename;
        $record->save();
    }

    public function delete(?string $filename, string $folder): void
    {
        ImageManager::delete($filename, $folder);
    }
}
