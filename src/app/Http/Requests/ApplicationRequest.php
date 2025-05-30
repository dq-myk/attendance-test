<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
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
            'clock_in' => 'required|date_format:H:i|before:clock_out',
            'clock_out' => 'required|date_format:H:i|after:clock_in',
            'rest_start' => 'nullable|array',
            'rest_start.*' => 'nullable|date_format:H:i|after:clock_in|before:clock_out',
            'rest_end' => 'nullable|array',
            'rest_end.*' => 'nullable|date_format:H:i|after:clock_in|before:clock_out',
            'remarks' => 'required|max:255',
        ];
    }

    public function messages()
    {
        return [
            'clock_in.before' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'rest_start.*.before' => '休憩時間が勤務時間外です',
            'rest_start.*.after' => '休憩時間が勤務時間外です',
            'rest_end.*.before' => '休憩時間が勤務時間外です',
            'rest_end.*.after' => '休憩時間が勤務時間外です',
            'remarks.required' => '備考を記入してください',
            'remarks.max' => '備考内容は255文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockOut = $this->input('clock_out');
            $restEnd = $this->input('rest_end');
            $restStart = $this->input('rest_start');

            if (
                ($restEnd && $clockOut && collect($restEnd)->contains(fn ($r) => $r > $clockOut)) ||
                ($restStart && $clockOut && collect($restStart)->contains(fn ($r) => $r > $clockOut))
            ) {
                $validator->errors()->add('custom_error', '出勤時間もしくは退勤時間が不適切な値です');
            }
        });
    }
}
