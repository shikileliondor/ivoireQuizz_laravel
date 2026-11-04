{{-- Sans tâche Cron, les envois programmés et les relances automatiques ne partent pas. --}}
<section class="card p-5">
    <div class="flex items-start gap-3">
        <span @class(['mt-1.5 size-2.5 shrink-0 rounded-full', 'bg-ci-500' => $scheduler['healthy'], 'bg-red-500' => ! $scheduler['healthy']])></span>
        <div class="min-w-0 flex-1">
            <h2 class="text-sm font-semibold text-slate-900">Planificateur</h2>
            <p class="mt-0.5 text-sm text-slate-600">
                @if ($scheduler['healthy'])
                    Actif : dernier passage {{ $scheduler['last_run']->diffForHumans() }}.
                @elseif ($scheduler['last_run'])
                    <strong class="text-red-700">Arrêté</strong> depuis {{ $scheduler['last_run']->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }} : envois programmés et relances en pause.
                @else
                    <strong class="text-red-700">Jamais lancé</strong> : créez la tâche Cron ci-dessous pour activer les envois programmés et les relances.
                @endif
            </p>

            <dl class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
                <div>File : <span class="font-medium text-slate-700">{{ $scheduler['queue'] }}</span></div>
                @if ($scheduler['pending_jobs'] !== null)
                    <div>En attente : <span class="font-medium text-slate-700 tabular-nums">{{ $scheduler['pending_jobs'] }}</span></div>
                @endif
                <div>En échec : <span @class(['font-medium tabular-nums', 'text-red-600' => $scheduler['failed_jobs'] > 0, 'text-slate-700' => $scheduler['failed_jobs'] === 0])>{{ $scheduler['failed_jobs'] }}</span></div>
            </dl>
            @if ($scheduler['queue'] === 'sync')
                <p class="mt-2 text-xs text-amber-700">QUEUE_CONNECTION=sync : les envois partent pendant le passage. Pour les grosses audiences, mettez <code>QUEUE_CONNECTION=database</code> dans le .env.</p>
            @endif

            <details class="mt-3 text-xs" @if (! $scheduler['healthy']) open @endif>
                <summary class="cursor-pointer font-medium text-slate-700">Tâche Cron à créer (cPanel → Tâches Cron, « Une fois par minute »)</summary>
                <code class="mt-2 block rounded-lg bg-slate-900 px-3 py-2 font-mono break-all text-slate-100 select-all">{{ $scheduler['command'] }}</code>
            </details>
        </div>
    </div>
</section>
