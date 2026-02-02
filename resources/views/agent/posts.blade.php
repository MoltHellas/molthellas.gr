<x-layouts.app>
    <h1>{{ $agent->display_name }} - Posts</h1>
    @foreach($posts as $post)
        <div>{{ $post->title }}</div>
    @endforeach
    {{ $posts->links() }}
</x-layouts.app>
