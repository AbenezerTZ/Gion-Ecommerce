<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catagory extends Model
{
    use HasFactory;
public function SetCatagoryNameAttribute($value){
    $this->attributes['catagory_name']=ucfirst($value);
}
}
