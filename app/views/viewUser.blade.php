{{-- View a single user --}}

@extends('layouts._master')

@section('banner')
	<h1>{{{$user->first_name}}} {{{$user->last_name}}}</h1>
@stop

{{--show the logo and vital info of the org in the top-left corner --}}
@section('content')
<div class="nameplate">
	<div class="nameplate-image">
		<img src="{{{$user->image_url}}}" alt="{{{$user->first_name}}} {{{$user->last_name}}}" />
	</div>
	<div class="nameplate-name">
		<h2>{{{$user->first_name}}} {{{$user->last_name}}}</h2>
	</div>
	<div class="nameplate-vitals">
		<p>{{{$user->city}}}, {{{$user->state}}} {{{$user->country}}}</p>
		<p>{{{$user->profile}}}</p>
	</div>
	@if($permission == 'self')
	<div class="nameplate-edit">
		<p><a href="/user/{{$user->username}}/edit">Edit Profile</a></p>
	</div>
	@endif
</div>

{{-- List all organizations in top-right --}}
<div class="user-organization">
	<h3>Organizations</h3>
	<div class="list-header">
		<div class="list-col1">
			<h4>Name</h4>
		</div>
		<div class="list-col">
			<h4>Title</h4>
		</div>
		@if($permission == 'self')
		<div class="list-col">
			<h4>Permissions</h4>
		</div>
		@endif
	</div>
	@foreach($roles as $role)
	<div class="list-line">
		<div class="list-col1">
			<p><a href="/{{$role->organization->slug}}">{{$role->organization->name}}</a></p>
		</div>
		<div class="list-col">
			<p>{{$role->title}}</p>
		</div>
		@if($permission == 'self')
		<div class="list-col">
			<p>can {{$role->permissions}}</p>
		</div>
		@endif
	</div>
	@endforeach
</div>

@if($permission == 'self')
{{-- Listing of all outstanding assignment on bottom --}}

<div class="user-assignments"</div>
	<h3>Outstanding Assignment</h3>
	<div class="list-header">
		<div class="list-col1">
			<h4>Organization</h4>
		</div>
		<div class="list-col">
			<h4>Flatplan</h4>
		</div>
		<div class="list-col">
			<h4>Page</h4>
		</div>
		<div class="list-col">
			<h4>Deadline</h4>
		</div>
		<div class="list-col-notes">
			<h4>Notes</h4>
		</div>
	</div>
	@if($roles)
	@foreach($roles as $role)
		@if($role->organization->flatplans)
		@foreach($role->organization->flatplans as $flatplan)
			@if($flatplan->pages)
			@foreach($flatplan->pages as $page)
				@if($page->assignments)
				@foreach($page->assignments as $assignment)
					<div class="list-line">
						<div class="list-col1">
							<a href="/{{$role->organization->slug}}">{{$role->organization->name}}</a>
						</div>
						<div class="list-col">
							<a href="/{{$role->organization->slug}}/{{$flatplan->slug}}">{{$flatplan->name}}</a>
						</div>
						<div class="list-col">
							<a href="/{{$role->organization->slug}}/{{$flatplan->slug}}/{{$page->page_number}}">{{$page->page_number}}</a>
						</div>
						<div class="list-col">
							<p>{{$assignment->deadline}}</p>
						</div>
						<div class="list-col-notes">
							<p>{{$assignment->notes}}</p>
						</div>
					</div>
				@endforeach
				@endif
			@endforeach
			@endif
		@endforeach
		@endif
	@endforeach
	@endif
</div>
@endif

@stop