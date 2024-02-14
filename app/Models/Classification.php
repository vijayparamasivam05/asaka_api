<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class Classification extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;
    public $incrementing = false;
    const UPDATED_AT = null;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'company_id',
        'subject',
        'name',
        'class_text',
        'order_num'
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
            'classifications' => 'required|array',
            'classifications.*.new.*.id' => "required|string|unique:classifications,id",
            'classifications.*.new.*.company_id' => 'required|string|exists:companies,id' ,
            'classifications.*.new.*.subject' =>  "required|string",
            'classifications.*.new.*.name' => 'required|string',
            'classifications.*.new.*.class_text' =>['required', Rule::in(['分析対象', '分類1','分類2','分類3','分類4'])],
            'classifications.*.new.*.order_num' => 'required|integer'
        ]);
    }
}
