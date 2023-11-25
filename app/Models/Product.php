<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    Public function setTitleAttribute($value){
       $this->attributes['title']=ucfirst($value);
    }
    Public function setDescriptionAttribute($value){
        $this->attributes['description']=ucfirst($value);
     }
     Public function setProductStatusAttribute($value){
      $this->attributes['product_status']=ucfirst($value);
   }
    
}
