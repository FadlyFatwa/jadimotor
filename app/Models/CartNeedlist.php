<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartNeedlist extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'id_variasi', 'qty'];

    protected $casts = ['qty' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function variasi()
    {
        return $this->belongsTo(Variasi::class, 'id_variasi');
    }
}
