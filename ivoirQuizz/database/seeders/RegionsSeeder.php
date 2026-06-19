<?php
namespace Database\Seeders;
use App\Models\Region; use Illuminate\Database\Seeder; use Illuminate\Support\Str;
class RegionsSeeder extends Seeder { public function run(): void { foreach (['Abidjan','Yamoussoukro','Bouaké','Korhogo','Man','San Pedro'] as $i => $name) { Region::updateOrCreate(['slug'=>Str::slug($name)], ['name'=>$name,'order'=>$i+1,'is_active'=>true]); } } }
