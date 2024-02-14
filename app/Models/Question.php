<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;
    const UPDATED_AT = null;
    public $incrementing = false;
    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = [
        'id',
        'ans_id',
        'title',
        'subtitle',
        'question',
        'answer_text_1',
        'answer_text_2',
        'answer_text_3',
        'answer_text_4',
        'questionnaire_type',
        'language'
    ];

    /**
    * The attributes that should be hidden for serialization.
    *
    * @var array
    */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    /**
     * Get the Collection containing user validation rules
     *
     * @return object Collection
     */
    protected function rules($id=0)
    {
        return collect([
            'questions' => 'required|array',
            'questions.*.id' => "required|string|unique:questions,id",
            'questions.*.ans_id' => "required|string",
            'questions.*.title' => 'required|string',
            'questions.*.subtitle' => 'nullable|string',
            'questions.*.answer_text_1' => "nullable|string",
            'questions.*.answer_text_2' => "nullable|string",
            'questions.*.answer_text_3' => "nullable|string",
            'questions.*.answer_text_4' => "nullable|string",
            'questions.*.questionnaire_type' => ['required', Rule::in(['BJSQ', 'Mirror'])],
            'questions.*.language' => ['required', Rule::in(['JA', 'EN'])]
        ]);
    }
}
