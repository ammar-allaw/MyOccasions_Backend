<?php

namespace App\Http\Requests\Concerns;

trait NormalizesSyrianPhoneNumber
{
    protected function mergeNormalizedSyrianPhoneNumber(string $field = 'phone_number'): void
    {
        if ($this->has($field)) {
            $this->merge([
                $field => $this->normalizeSyrianPhoneNumber($this->input($field)),
            ]);
        }
    }

    protected function normalizeSyrianPhoneNumber(?string $phoneNumber): ?string
    {
        if ($phoneNumber === null) {
            return null;
        }

        $phoneNumber = preg_replace('/[\s\-\(\)]/', '', trim($phoneNumber));

        if ($phoneNumber === '') {
            return $phoneNumber;
        }

        if (substr($phoneNumber, 0, 2) === '00') {
            $phoneNumber = '+' . substr($phoneNumber, 2);
        }

        if (substr($phoneNumber, 0, 5) === '+9630') {
            return '+963' . substr($phoneNumber, 5);
        }

        if (substr($phoneNumber, 0, 4) === '9630') {
            return '+963' . substr($phoneNumber, 4);
        }

        if (substr($phoneNumber, 0, 2) === '09') {
            return '+963' . substr($phoneNumber, 1);
        }

        if (substr($phoneNumber, 0, 3) === '963') {
            return '+' . $phoneNumber;
        }

        return $phoneNumber;
    }

    protected function syrianPhoneNumberRule(): string
    {
        return 'regex:/^\+9639[0-9]{8}$/';
    }
}
