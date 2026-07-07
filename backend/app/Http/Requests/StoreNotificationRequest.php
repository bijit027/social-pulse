<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
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
        return [
            'type'        => 'required|in:purchase,signup,review,banner,page_analytics,gdpr,email_subscription,video,custom',
            'message'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'country'     => 'nullable|string|max:100',
            'emoji'       => 'nullable|string|max:255',
            'product_url' => 'nullable|url|max:255',
            'rating'      => 'nullable|integer|min:1|max:5',
            'button_text' => 'nullable|string|max:50',
            'metadata'    => 'nullable|array',
        ];
    }
}
