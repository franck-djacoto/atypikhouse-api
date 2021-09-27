<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable  implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telephone',
        'adresse',
        'code_postale',
        'ville',
        'role',
        'wantToAddHabitat',
        'canAddHabitat',
        'google_id',
        'facebook_id',
        'complement_adresse',
        'password2'

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'email_verified_at',
        'role',
        'siren',
        'nomEntreprise',
        'password2',
        'google_id',
        'facebook_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * Permet de récupérer tous les habitats ajoutés par un utilisateur
     */
    public function getHabitats(){
        return $this->hasMany('App\Models\Habitat', 'proprietaire')->orderBy('created_at','desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * Permet de récuprérer toutes les réservations effectuées par un utilisateurs
     */
    public function getReservations(){
        return $this->hasMany('App\Models\Reservation','locataire')->orderBy('created_at','desc');
    }

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }

}
