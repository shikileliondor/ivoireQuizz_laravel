<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une notification automatique adressée à un joueur. Sert au plafond quotidien des relances,
 * à ne jamais envoyer deux fois la même (type + ref) et aux statistiques de l'admin.
 * Purgé après 90 jours.
 */
class PushLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['is_reminder' => 'boolean'];
}
