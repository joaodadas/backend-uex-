<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'cep',
        'address',
        'latitude',
        'longitude',
        'tipo_contrato',
        'unidade',
        'user_id',
    ];

    // Relacionamento com o usuário (opcional, mas útil)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
