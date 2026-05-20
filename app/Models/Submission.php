<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    // Tambahkan ini agar kolomnya bisa di-update oleh controller-mu
    protected $fillable = ['current_round']; 
}