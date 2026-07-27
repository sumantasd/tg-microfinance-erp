<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class GalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = (bool) $this->route('gallery');

        return [
            'title' => 'required|string|max:255',
            'image' => $isUpdate ? 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096' : 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
            'category' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'integer|min:0',
        ];
    }
}
