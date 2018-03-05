<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cpu extends Model
{
  protected $fillable = [
    'name',
    'passmark',
    'rank',
    'cpu_id',
    'cpu_type_id'
  ];

}
