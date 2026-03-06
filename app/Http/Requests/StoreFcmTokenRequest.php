<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFcmTokenRequest extends FormRequest
{
    public function authorize()
    {
        // pastikan user sudah login
        return auth()->check();
    }

    public function rules()
    {
        return [
            'token' => 'required|string|max:255',
        ];
    }
}