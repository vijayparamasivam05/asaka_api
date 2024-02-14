<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class ClassificationExaminee extends Model
{
    use HasFactory;
    use HasFactory, SoftDeletes, CascadeSoftDeletes;
    const UPDATED_AT = null;
    protected $table = 'classifications_examinee_relation';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'examinee_id',
        'classification_id'
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
            'classificationsexaminee' => 'required|array',
            'classificationsexaminee.*.id' => "required|integer",
            'classificationsexaminee.*.examinee_id' => 'required|integer|exists:examinee,id' ,
            'classificationsexaminee.*.classification_id' =>  'required|integer|exists:classifications,id'
        ]);
    }
}
