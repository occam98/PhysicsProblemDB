@layout('templates.main')

@section('content')

@foreach ($probs AS $prob)
<div>
<h1>
{{$prob->link}}
</h1>
<div>
{{$prob->question}}
</div>
Tags:
@foreach ($prob->tags AS $tag)
{{$tag->link}}, 
@endforeach
<hr/>
@endforeach

@endsection
