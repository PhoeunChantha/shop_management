<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Http\Controllers\Backend\MediaAssetController;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseMediaRequest extends FormRequest
{
    /**
     * Authorization is handled by the controller's permission checks.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<int, string>
     */
    protected function folderKeys(): array
    {
        return array_keys(MediaAssetController::folders());
    }
}
