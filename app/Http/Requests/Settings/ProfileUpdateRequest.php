<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class ProfileUpdateRequest extends FormRequest
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
      'name' => [
        'required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-\.\']+$/'
      ],
      'email' => [
        'required',
        'string',
        'lowercase',
        'email',
        'max:255',
        Rule::unique(User::class)->ignore($this->user()->id),
      ],
      'gender' => [
        'nullable',
        'string',
        Rule::in(['male', 'female']),
      ],
      'work_phone' => [
        'nullable',
        'string',
        'max:20',
        (new Phone)->country(['MW', 'ZM', 'SA', 'ZW']), // 'regex:/^[\+]?[1-9]?[\d\s\-\(\)\.]{7,15}$/' // Flexible phone number format
      ],
      'office_location' => [
        'nullable',
        'string',
        'max:100',
      ],
    ];
  }

  /**
   * Get custom error messages for validation rules.
   */
  public function messages(): array
  {
    return [
      'name.required' => 'Your full name is required.',
      'name.regex' => 'Name can only contain letters, spaces, hyphens, dots, and apostrophes.',
      'email.required' => 'Email address is required.',
      'email.email' => 'Please enter a valid email address.',
      'email.unique' => 'This email address is already taken.',
      'gender.in' => 'Please select a valid gender option.',
      'work_phone.phone' => 'Please enter a valid phone number.',
      'office_location.max' => 'Office location cannot exceed 100 characters.',
    ];
  }

  /**
   * Get custom attribute names for validation errors.
   */
  public function attributes(): array
  {
    return [
      'name' => 'full name',
      'email' => 'email address',
      'work_phone' => 'work phone number',
      'office_location' => 'office location',
    ];
  }

  /**
   * Prepare the data for validation.
   */
  protected function prepareForValidation(): void
  {
    // Clean up phone number - remove formatting for storage
    if ($this->work_phone) {
      // $cleanPhone = preg_replace('/[^\d\+]/', '', $this->work_phone);
      $cleanPhone = trim($this->work_phone);

      $this->merge([
        'work_phone' => $cleanPhone ?: null,
      ]);
    }

    // Trim and clean other fields
    $this->merge([
      'name' => trim($this->name),
      'email' => strtolower(trim($this->email)),
      'office_location' => $this->office_location ? trim($this->office_location) : null,
    ]);
  }

  /**
   * Get the validated data with properly formatted phone number.
   */
  public function validated($key = null, $default = null)
  {
    $validated = parent::validated($key, $default);

    // Format the phone number for storage using Laravel-Phone
    if (!empty($validated['work_phone'])) {
      try {
        $phone = phone($validated['work_phone'], ['US', 'CA', 'GB', 'AU', 'MW']);
        $validated['work_phone'] = $phone->formatE164(); // Store in E164 format
      } catch (\Exception $e) {
        // If formatting fails, keep the original value
        // The validation will catch invalid numbers
      }
    }

    return $key ? data_get($validated, $key, $default) : $validated;
  }
}
