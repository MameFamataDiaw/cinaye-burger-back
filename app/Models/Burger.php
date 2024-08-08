<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Burger extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'image',
        'prix',
        'statut',
        'archived'
    ];

    public function commandes(){
        return $this->belongsToMany(Commande::class,'details_commandes')->withPivot('quantite','prix')->withTimestamps();
    }

    //Ajoutez cette methode pour la portee locale
    protected static function booted(){
        static::addGlobalScope('noArchived', function (Builder $builder){
            $builder->where('archived', false);
        });
    }

    //Scope local pour recuperer les burgers archives
    public function scopeArchived($query){
        return $query->where('archived',true);
    }

    //Scolpe local pour recuperer les burgers non arxhives
    public function scopeNotArchived($query){
        return $query->where('archived', false);
    }

}
