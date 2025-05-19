<x-app-layout>
    <div class="ms-5 mt-5">
        <h2 class="mb-4">Your Resume Analysis History</h2>

        @forelse($analyses as $analysis)
            <div class="card mb-4">
                <div class="card-header">
                    {{ $analysis->filename }} <small
                        class="text-muted float-end">{{ $analysis->created_at->diffForHumans() }}</small>
                </div>
                <div class="card-body">
                    <h5>Analysis</h5>
                    <pre>{{ $analysis->analysis }}</pre>
                </div>
            </div>
        @empty
            <p>No analysis history yet.</p>
        @endforelse
    </div>
</x-app-layout>
