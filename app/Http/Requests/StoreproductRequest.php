<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use App\Providers\GlobalVariablesServiceProvider as GlobalVariables;


class StoreproductRequest extends FormRequest
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
            'scientific_name' => 'nullable|string|max:50',
            'scientific_name_ar' => 'nullable|string|max:50',
            'brand_name' => 'required|string|max:50',
            'brand_name_ar' => 'required|string|max:50',
            'category_id' => 'required|string|exists:categories,id',
            'manufacturer' => 'required|string|max:50',
            'manufacturer_ar' => 'required|string|max:50',
            'stock' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0.01|regex:/^\d+(\.\d{1,2})?$/',
            'expiration_date' => 'required|date|date_format:Y-m-d|after:now',
            'description' => 'nullable|string|max:1024',
            'description_ar' => 'nullable|string|max:1024',
            'image' => 'nullable|image|mimes:png,jpeg,webp|max:2048'
        ];
    }
    // throw exception to be handled and sent as a json response (do not change if uneeded)
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
