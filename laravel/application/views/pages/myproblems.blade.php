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
			<br>
			Attachments:
				@foreach ($prob->attachments AS $attachment)
					
					<!-- <img src="{{$attachment->link}}" alt="" width="" height="" border="0" /><br /> -->
					<div>{{$attachment->imgsrc}}{{$attachment->pivot->description}}</div> 	



				@endforeach
					
		<hr/>
	@endforeach


@endsection
