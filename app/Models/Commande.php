<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;
    protected $fillable = ['nom_client', 'prenom_client', 'telephone_client', 'email_client', 'total','date','statut'];

    protected $dates = ['date'];//transformer 'date' en objet Carbon


    public function burgers(){
        return $this->belongsToMany(Burger::class, 'details_commandes')->withPivot('quantite','prix')->withTimestamps();
    }
    public function details_commandes(){
        return $this->hasMany(DetailsCommande::class);
    }
    public function paiements()
    {
        return $this->hasOne(Paiement::class);

    }
    public function calculateTotal()
    {
        $total = $this->details_commandes()->sum('montant');
        $this->total = $total;
        $this->save();
    }

    public function isPaid(){
        return $this->paiements()->exists();
    }
}
