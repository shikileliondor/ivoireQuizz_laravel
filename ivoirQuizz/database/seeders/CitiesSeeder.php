<?php
namespace Database\Seeders;
use App\Models\City; use App\Models\Region; use Illuminate\Database\Seeder; use Illuminate\Support\Str;
class CitiesSeeder extends Seeder { public function run(): void { $abidjan = Region::where('slug','abidjan')->firstOrFail(); foreach (['Cocody','Plateau','Yopougon','Treichville','Marcory','Abobo','Koumassi','Port-Bouët'] as $i => $name) { City::updateOrCreate(['region_id'=>$abidjan->id,'slug'=>Str::slug($name)], ['name'=>$name,'order'=>$i+1,'is_active'=>true]); } } }
