<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ExpensesModel;
use App\Models\grants;

class Employee extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table='employees';

    public function benefits()
    {
        return $this->hasMany(ExpensesModel::class);
    }

    public function absences()
    {
        return $this->hasMany(ExpensesModel::class);
    }

    /**
     * Get the user associated with the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'employeeId');
    }

    /**
     * Get the roles that belong to the employee through the user.
     */
    public function roles()
    {
        return $this->user->roles();
    }
}
