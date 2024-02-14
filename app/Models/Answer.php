<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class Answer extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;
    const UPDATED_AT = null;
    public $incrementing = false;

    /**
     * except these fields all other fields are mass assignable
     */
    protected $guarded = ['created_at', 'updated_at','deleted_at'];

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
     * Get the user associated with the answer.
     */
    public function examinee()
    {
        return $this->belongsTo(Examinee::class, 'id', 'examinee_id');
    }

    /**
    * Get the Collection containing user validation rules
    *
    * @return object Collection
    */
    protected function rules($id=0)
    {
        return collect([
            'answers' => 'required|array',
            'answers.*.id' => "required|string|unique:answers,id",
            'answers.*.examinee_id' => "required|integer|exists:examinee,id",
            'answers.*.invalid_flg' => "nullable|boolean"
        ]);
    }
    protected function rulesAdminUpdate($id=0)
    {
        return collect([
            'answers' => 'required|array',
            'answers.*.update.*.id' => "required|string",
            'answers.*.update.*.examinee_id' => "required|integer|exists:examinee,id",
            'answers.*.update.*.invalid_flg' => "nullable|boolean"
        ]);
    }
}
