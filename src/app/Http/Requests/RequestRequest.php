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
            'clock_in' => 'required|date|before:end_time',
            'clock_out' => 'required|date|after:start_time',
            'rest_start' => 'nullable|date|after:start_time|before:end_time',
            'rest_end' => 'nullable|date|after:break_start|before:end_time',
            'remarks' => 'required|max:50',
        ];
    }

    public function messages()
    {
        return [
            'clock_in.before' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'rest_start.after' => '休憩時間が勤務時間外です',
            'rest_end.before' => '休憩時間が勤務時間外です',
            'remarks.required' => '備考を入力してください',
            'remarks.max' => '備考内容は50文字以内で入力してください',
        ];
    }
}
