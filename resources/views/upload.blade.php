<x-app-layout>
    <div class="ms-5 mt-5">
        <h1>Upload Resume (PDF)</h1>
        <form action="/upload" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="resume" accept="application/pdf" required>
            <button type="submit">Analyze</button>
        </form>

        @if (isset($analysis))
            <h2>AI Analysis:</h2>
            <pre>{{ $analysis }}</pre>
        @endif
    </div>
</x-app-layout>
