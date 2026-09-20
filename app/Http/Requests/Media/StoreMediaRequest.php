<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Validation\Rule;

class StoreMediaRequest extends BaseMediaRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxFiles = (int) config('media.max_files', 24);
        $maxKb = (int) config('media.max_file_kb', 8192);

        return [
            // A folder only organizes the library; an uploaded image can be
            // used by any feature, so picking one is never required.
            'folder' => ['nullable', Rule::in($this->folderKeys())],
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:500'],
            'files' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:'.$maxKb],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'folder' => $this->filled('folder') ? $this->input('folder') : 'media',
            'title' => $this->filled('title') ? trim((string) $this->input('title')) : null,
            'alt_text' => $this->filled('alt_text') ? trim((string) $this->input('alt_text')) : null,
            'tags' => $this->filled('tags') ? trim((string) $this->input('tags')) : null,
        ]);
    }
}
