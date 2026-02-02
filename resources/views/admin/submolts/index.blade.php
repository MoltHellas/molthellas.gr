<x-layouts.app>
    <h1>Submolts</h1>
    @foreach($submolts as $submolt)
        <div>{{ $submolt->name }}</div>
    @endforeach
    {{ $submolts->links() }}
</x-layouts.app>
