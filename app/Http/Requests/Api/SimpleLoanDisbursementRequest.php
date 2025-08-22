<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Simplified request validation for automatic loan creation and disbursement
 * Only requires client_number and amount
 * 
 * @package App\Http\Requests\Api
 * @version 1.0
 */
class SimpleLoanDisbursementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'client_number' => 'required|string|exists:clients,client_number',
            'amount' => 'required|numeric|min:100000|max:100000000', // Min 100k, Max 100M TZS
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array
     */
    public function messages()
    {
        return [
            'client_number.required' => 'Client number is required',
            'client_number.exists' => 'Client not found in the system',
            'amount.required' => 'Loan amount is required',
            'amount.numeric' => 'Loan amount must be a number',
            'amount.min' => 'Minimum loan amount is TZS 100,000',
            'amount.max' => 'Maximum loan amount is TZS 100,000,000',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()->toArray(),
            'meta' => [
                'timestamp' => now()->toISOString()
            ]
        ], 422));
    }
}