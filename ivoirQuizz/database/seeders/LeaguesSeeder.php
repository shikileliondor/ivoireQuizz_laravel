<?php
namespace Database\Seeders;
use App\Models\League; use Illuminate\Database\Seeder; use Illuminate\Support\Str;
class LeaguesSeeder extends Seeder { public function run(): void { foreach (['Bronze','Argent','Or','Platine','Diamant'] as $i => $name) { League::updateOrCreate(['slug'=>Str::slug($name)], ['name'=>$name,'rank_order'=>$i+1,'is_active'=>true]); } } }
