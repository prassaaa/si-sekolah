<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RfidScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('rfid_device');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uid' => ['required', 'string', 'max:32'],
            'scanned_at' => ['nullable', 'date', 'before:+2 minutes', 'after:-1 day'],
            'device_kode' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'uid.required' => 'UID kartu wajib diisi.',
            'scanned_at.date' => 'Format scanned_at tidak valid (gunakan ISO 8601).',
            'scanned_at.before' => 'Waktu scan tidak boleh terlalu jauh di masa depan.',
            'scanned_at.after' => 'Waktu scan terlalu lama (lebih dari 1 hari).',
        ];
    }
}
