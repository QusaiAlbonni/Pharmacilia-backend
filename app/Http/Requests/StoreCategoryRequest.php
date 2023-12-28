<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class StoreCategoryRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'category_name'=>'required|string|unique:categories,category_name|min:2|max:255',
            'category_name_ar'=>'required|string|unique:categories,category_name_ar|min:2|max:255',
            'description'=>'nullable|string|min:2|max:2048',
            'image' => 'nullable|image|mimes:png,jpeg,webp|max:2048'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator, $this->response($validator));
    }

    //response when validation fails
    protected function response($validator)
    {
        return response()->json([
            'status' => false,
            'message' => 'validation failed',
            'errors' => $validator->errors()
        ], 422);
    }
}
