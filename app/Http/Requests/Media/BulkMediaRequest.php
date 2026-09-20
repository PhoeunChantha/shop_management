<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkMediaRequest extends BaseMediaRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['delete', 'move', 'optimize'])],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:media_assets,id'],
            'folder' => ['nullable', Rule::in($this->folderKeys())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('action') === 'move' && ! $this->filled('folder')) {
                $validator->errors()->add('folder', __('Choose the folder to move the selected media into.'));
            }
        });
    }
}
