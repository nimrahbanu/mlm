@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$page_other_item->customer_panel_page_banner) }}')">
	<div class="page-banner-bg"></div>
	<h1>{{ ALL_PROPERTIES }}</h1>
	<nav>
		<ol class="breadcrumb justify-content-center">
			<li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
			<li class="breadcrumb-item active">{{ ALL_PROPERTIES }}</li>
		</ol>
	</nav>
</div>

<div class="page-content">
	<div class="container">
		<div class="row">
			<div class="col-md-3">
				<div class="user-sidebar">
					@include('front.customer_sidebar')
				</div>
			</div>
			<div class="col-md-9">

				@if($property->isEmpty())
				<span class="text-danger">{{ NO_RESULT_FOUND }}</span>
				@else

				<div class="table-responsive-md">
					<table class="table table-bordered">
						<thead>
							<tr class="table-primary">
								<th scope="col">{{ SERIAL }}</th>
								<th scope="col">{{ FEATURED_PHOTO }}</th>
								<th scope="col">{{ PROPERTY_NAME }}</th>
								<th scope="col">{{ CATEGORY }}</th>
								<th scope="col">{{ LOCATION }}</th>
								<th scope="col">{{ STATUS }}</th>
								<th scope="col" class="w-150">{{ ACTION }}</th>
							</tr>
						</thead>
						<tbody>
							@php $i=0; @endphp
                        	@foreach($property as $row)
							<tr>
								<td>{{ $loop->iteration }}</td>
								<td>
									<img src="{{ asset('uploads/property_featured_photos/'.$row->property_featured_photo) }}" alt="" class="w-100">
								</td>
								<td>
                                    {{ $row->property_name }} <br>
                                    @if($row->is_featured == 'Yes')
                                        <span class="badge badge-success">{{ FEATURED }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ NOT_FEATURED }}</span>
                                    @endif
                                </td>
								<td>{{ $row->rPropertyCategory->property_category_name }}</td>
								<td>{{ $row->rPropertyLocation->property_location_name }}</td>
								<td>
									@if($row->property_status == 'Active')
	                                <h6><span class="badge badge-success">
	                                @else
	                                <h6><span class="badge badge-danger">
	                                @endif
	                                {{ $row->property_status }}</span></h6>
								</td>
								<td>
									<a href="{{ route('customer_property_view_detail',$row->id) }}" class="btn btn-success btn-sm"><i class="fas fa-eye"></i></a>

	                                <a href="{{ route('customer_property_edit',$row->id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>

	                                <a href="{{ route('customer_property_delete',$row->id) }}" class="btn btn-danger btn-sm" onClick="return confirm('{{ ARE_YOU_SURE }}');"><i class="fas fa-trash-alt"></i></a>
								</td>
							</tr>
                        	@endforeach

						</tbody>
					</table>
				</div>
				@endif

			</div>
		</div>
	</div>
</div>

@endsection
