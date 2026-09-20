<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Validation\Rule;

class UpdateMediaRequest extends BaseMediaRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:500'],
            'folder' => ['required', Rule::in($this->folderKeys())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->filled('title') ? trim((string) $this->input('title')) : null,
            'alt_text' => $this->filled('alt_text') ? trim((string) $this->input('alt_text')) : null,
            'tags' => $this->filled('tags') ? trim((string) $this->input('tags')) : null,
        ]);
    }
}
