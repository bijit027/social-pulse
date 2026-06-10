<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWebsiteRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $method = $this->method();
        
        // For PATCH requests (partial updates), make fields optional
        $required = in_array($method, ['POST']) ? 'required' : 'nullable';
        
        return [
            'name'   => $required . '|string|max:255',
            'domain' => $required . '|string|max:255',
            // Display settings - always optional
            'display_for' => 'nullable|integer|min:1|max:60',
            'display_last' => 'nullable|integer|min:1|max:50',
            'display_from_days' => 'nullable|integer|min:0|max:365',
            'display_from_hours' => 'nullable|integer|min:0|max:23',
            'display_from_minutes' => 'nullable|integer|min:0|max:59',
            'loop' => 'nullable|boolean',
            'link_open' => 'nullable|boolean',
            'show_on_display' => 'nullable|in:always,logged_out_user,logged_in_user',
            'close_button' => 'nullable|boolean',
            'hide_on_mobile' => 'nullable|boolean',
        ];
    }
}
