<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, CascadeSoftDeletes;
    public $incrementing = false;
    const UPDATED_AT = null;


    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id',
        'fixed_company_id',
        'email',
        'password',
        'role',
        'firstname',
        'lastname',
        'email_verified_at',
        'email_token',
        'password_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
        'email_verified_at',
        'email_token',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
    * Get the Classifications associated with User (one to many relation)
    */
    public function classifications()
    {
        return $this->hasMany(Classification::class);
    }

    /**
     * Get the owner of Examinee (one to one relation)
     */
    public function examinee()
    {
        return $this->hasMany(Examinee::class)->orderBy('yearmm', 'DESC');
    }
    /**
     * Get the owner of Examinee alias history (one to many relation)
     */
    public function history()
    {
        return $this->hasMany(Examinee::class, 'user_id', 'id')->select(['id','yearmm', 'question_method','questionnaire_type','notification_type','question_output_method','language','job_status'])->orderBy('yearmm', 'desc');
    }

    /**
     * Get the company associated with the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the Collection containing user validation rules
     *
     * @return object Collection
     */
    protected function rules($unique=false, $id=0)
    {
        return collect([
            'users' => 'required|array',
            'users.*.new.*.id' => "required|string",
            'users.*.new.*.fixed_company_id' => "required|string|exists:companies,fixed_company_id",
            'users.*.new.*.email' =>"nullable|email:filter",
            'users.*.new.*.password' =>"required|string",
            'users.*.new.*.role' => ['required', Rule::in([5])],
            'users.*.new.*.firstname' => 'required|string',
            'users.*.new.*.lastname' => 'required|string',

            'users.*.update.*.id' => "required|string|exists:users,id",
            'users.*.update.*.fixed_company_id' => "required|string|exists:companies,fixed_company_id",
            'users.*.update.*.email' =>"nullable|email:filter",
            'users.*.update.*.password' =>"required|string",
            'users.*.update.*.role' => ['required', Rule::in([5])],
            'users.*.update.*.firstname' => 'required|string',
            'users.*.update.*.lastname' => 'required|string',

            //delete user rules
            'users.*.delete.*.id' => "required|string|exists:users,id",
        ]);
    }

    /**
     * Rules for new users
     *
     * @return object Collection
     */
    protected function adminNewRules($id=0)
    {
        return collect([
            'admin' => 'required|array',
            'admin.*.new.*.id' => "required|string|unique:users,id",
            'admin.*.new.*.fixed_company_id' => "required|string|exists:companies,fixed_company_id",
            'admin.*.new.*.email' =>"required|email:filter",
            'admin.*.new.*.password' =>"required|string",
            'admin.*.new.*.role' => ['required', Rule::in([1,2,3,4])],
            'admin.*.new.*.firstname' => 'required|string',
            'admin.*.new.*.lastname' => 'required|string'
        ]);
    }
}
