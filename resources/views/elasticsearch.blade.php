<!-- resources/views/elasticsearch-form.blade.php -->
<form method="post" action="{{ route('elasticsearch.store') }}">
    @csrf
    <textarea name="content" rows="5" cols="40" placeholder="Enter your content here"></textarea>
    <button type="submit">Send to Elasticsearch</button>
</form>
