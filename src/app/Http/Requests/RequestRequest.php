<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'remarks' => 'required|max:50',
        ];
    }

    public function messages()
    {
        return [
            'remarks.required' => 'コメントを入力してください',
            'remarks.max' => 'コメント内容は50文字以内で入力してください',
        ];
    }
}
