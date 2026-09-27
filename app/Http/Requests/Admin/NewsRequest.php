<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // نوع الخبر لا يتغير بعد الإنشاء (مثل اللوحة القديمة).
        $news = $this->route('news');

        $this->merge([
            'type' => $news?->type ?? (int) $this->input('type', 1),
            'is_active' => $this->boolean('is_active'),
            'hide_title' => $this->boolean('hide_title'),
            'hide_description' => $this->boolean('hide_description'),
            'hide_more' => $this->boolean('hide_more'),
            'hide_in_pdf' => $this->boolean('hide_in_pdf'),
        ]);
    }

    public function rules(): array
    {
        $types = array_keys(config('alnajat.news_types'));
        $allowed = config('alnajat.news_types.'.$this->input('type').'.categories', []);

        return [
            'type' => ['required', 'integer', Rule::in($types)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'body' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'max:'.config('alnajat.image_max_kb', 10240)],
            'source_url' => ['nullable', 'string', 'max:2000'],
            'tweet_url' => ['nullable', 'url', 'max:2000'],
            'sound_url' => ['nullable', 'url', 'max:2000'],
            'video_url' => ['nullable', 'url', 'max:2000'],
            'newspaper_id' => ['nullable', 'integer', 'exists:newspapers,id'],
            'newspaper_number' => ['nullable', 'integer', 'min:0'],
            'published_date' => ['required', 'date_format:Y-m-d'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::in($allowed), 'exists:categories,id'],
            'is_active' => ['boolean'],
            'hide_title' => ['boolean'],
            'hide_description' => ['boolean'],
            'hide_more' => ['boolean'],
            'hide_in_pdf' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'العنوان',
            'description' => 'الوصف',
            'body' => 'التفاصيل',
            'image_file' => 'الصورة',
            'source_url' => 'الرابط',
            'tweet_url' => 'رابط التغريدة',
            'sound_url' => 'رابط الصوت',
            'video_url' => 'رابط الفيديو',
            'newspaper_id' => 'الصحيفة',
            'newspaper_number' => 'رقم العدد',
            'published_date' => 'تاريخ النشر',
            'categories' => 'الأقسام',
            'categories.*' => 'القسم',
        ];
    }
}
