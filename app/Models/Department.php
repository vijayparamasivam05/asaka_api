<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class Department extends Model
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
        'ms_submission',
        'problem_interview',
        'no_problem_interview',
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
     * Get the company that owns the comment.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'id');
    }
    /**
     * Get the user that owns the department.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    /**
    * Get all the users under a department
    */
    public function departmentUser()
    {
        return $this->belongsToMany(User::class, 'department_permission_users', 'department_id', 'user_id');
    }

    /**
     * Get the Collection containing user validation rules
     *
     * @return object Collection
     */
    protected function rules($id=0)
    {
        return collect([
            'departments' => 'required|array',
            'departments.*.new.*.id' => "required|string|unique:departments,id",
            'departments.*.new.*.company_id' => "required|string|exists:companies,id",
            'departments.*.new.*.name' =>  "required|string" ,
            'departments.*.new.*.ms_submission' =>  "nullable|string",
            'departments.*.new.*.problem_interview' =>  "required|string",
            'departments.*.new.*.no_problem_interview' =>  "required|string",
            'departments.*.new.*.order_num' =>  "required|integer",
            'departments.*.new.*.users_id.*' => 'exists:users,id',
            'departments.*.update.*.users_id.*' => 'exists:users,id',
            'departments.*.delete.*.users_id.*' => 'exists:users,id'
        ]);
    }
}
