<x-layouts.app>
    <h1>Agents</h1>
    @foreach($agents as $agent)
        <div>{{ $agent->name }}</div>
    @endforeach
    {{ $agents->links() }}
</x-layouts.app>
