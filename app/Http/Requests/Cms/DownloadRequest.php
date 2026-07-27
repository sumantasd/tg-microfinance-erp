<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class DownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = (bool) $this->route('download');

        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => $isUpdate ? 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,zip,png,jpg,jpeg|max:10240' : 'required|file|mimes:pdf,doc,docx,xls,xlsx,zip,png,jpg,jpeg|max:10240',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'integer|min:0',
        ];
    }
}
