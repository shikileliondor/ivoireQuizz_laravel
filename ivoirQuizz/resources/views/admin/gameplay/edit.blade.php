@extends('admin.layouts.app')
@section('title', 'Pièces et mode Chrono')
@section('content')
<x-admin.page-header title="Pièces et mode Chrono" subtitle="Réglez les aides communes à tous les modes et les règles propres au mode Chrono." />
<form method="POST" action="{{ route('admin.gameplay.update') }}" class="grid max-w-5xl gap-6 lg:grid-cols-2">
 @csrf @method('PUT')
 <section class="card space-y-5 p-6">
  <div><h2 class="font-semibold text-slate-900">Aides avec des pièces</h2><p class="text-sm text-slate-500">Disponibles dans tous les modes de quiz.</p></div>
  <x-admin.checkbox name="hint_enabled" label="Autoriser les indices" :checked="$settings['hint_enabled']" />
  <x-admin.input name="hint_cost" label="Prix d’un indice" type="number" min="0" max="100000" :value="$settings['hint_cost']" required />
  <x-admin.checkbox name="skip_enabled" label="Autoriser le passage d’une question" :checked="$settings['skip_enabled']" />
  <x-admin.input name="skip_cost" label="Prix pour passer" type="number" min="0" max="100000" :value="$settings['skip_cost']" required />
 </section>
 <section class="card space-y-5 p-6">
  <div><h2 class="font-semibold text-slate-900">Mode Chrono</h2><p class="text-sm text-slate-500">Ces valeurs sont copiées dans chaque nouvelle partie.</p></div>
  <x-admin.input name="chrono_seconds" label="Secondes par question" type="number" min="5" max="60" :value="$settings['chrono_seconds']" required />
  <x-admin.input name="chrono_questions" label="Questions par partie" type="number" min="5" max="30" :value="$settings['chrono_questions']" required />
  <x-admin.checkbox name="chrono_pause_on_hint" label="Arrêter le chrono pendant l’indice" :checked="$settings['chrono_pause_on_hint']" />
  <x-admin.input name="chrono_hint_pause_seconds" label="Durée maximale de pause" type="number" min="0" max="30" :value="$settings['chrono_hint_pause_seconds']" required />
 </section>
 <div class="flex justify-end lg:col-span-2"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
</form>
@endsection
