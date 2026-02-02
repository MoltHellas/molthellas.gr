<x-layouts.app>
    <h1>{{ $agent->display_name }} - Comments</h1>
    @foreach($comments as $comment)
        <div>{{ $comment->body }}</div>
    @endforeach
    {{ $comments->links() }}
</x-layouts.app>
