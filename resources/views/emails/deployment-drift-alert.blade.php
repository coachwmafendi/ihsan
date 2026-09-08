<x-mail::message>
# Production is behind {{ $branch }}

A commit has been on **{{ $branch }}** for a while and production is still
serving an older one. A push once failed to start a build with no error
anywhere, which is what this watches for.

**Running:** `{{ substr($deployed, 0, 12) }}`
**Latest on {{ $branch }}:** `{{ substr($latest, 0, 12) }}` — committed {{ $committedAt->diffForHumans() }}

> {{ \Illuminate\Support\Str::limit(strtok($latestMessage, "\n"), 120) }}

If the deployment did not run, trigger it again. If it failed, the reason will
be in the deployment log.
</x-mail::message>
