<?php

namespace App\Http\Requests;

use App\Services\Validation\JsonSchemaValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class CreateLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|string|in:website,referral,social,email,phone,advertisement,event,other',
            'metadata' => 'nullable|array',
            'metadata.*' => 'nullable',
            'correlation_id' => 'nullable|string|uuid',
            'tenant_id' => 'nullable|string',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        // Prepare data before validation
        $this->prepareForValidation();
        
        $validator->after(function (Validator $validator) {
            // Perform JSON Schema validation after data preparation
            $schemaValidator = app(JsonSchemaValidator::class);
            $errors = $schemaValidator->validate($this->all(), 'schemas/lead.json');
            
            foreach ($errors as $error) {
                $validator->errors()->add(
                    $error['property'] ?: 'schema',
                    $error['message']
                );
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Generate correlation ID if not provided
        if (!$this->has('correlation_id')) {
            $this->merge([
                'correlation_id' => Str::uuid()->toString(),
            ]);
        }

        // Set tenant ID from authenticated user
        if ($this->user()) {
            $this->merge([
                'tenant_id' => (string) $this->user()->id,
            ]);
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'source.in' => 'Source must be one of: website, referral, social, email, phone, advertisement, event, other.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @return void
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
