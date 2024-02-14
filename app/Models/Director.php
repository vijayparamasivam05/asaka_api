<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class Director extends Model
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
        'company_id',
        'name',
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
     * Get the examinees that own by the Director.
     */
    public function examinees()
    {
        return $this->belongsTo(Examinee::class);
    }

    /**
     * Get the Collection containing user validation rules
     *
     * @return object Collection
     */
    protected function rules($id=0)
    {
        return collect([
            'directors' => 'required|array',
            'directors.*.new.*.id' => 'required|string|unique:directors,id',
            'directors.*.new.*.company_id' => 'required|string|exists:companies,id' ,
            'directors.*.new.*.name' => 'required|string',
            'directors.*.new.*.order_num' => 'required|integer'
        ]);
    }
}
