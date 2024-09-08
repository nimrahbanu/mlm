@extends('front.app_front')

@section('content')

<div class="page-banner" style="background-image: url('{{ asset('uploads/page_banners/'.$page_other_item->customer_panel_page_banner) }}')">
	<div class="page-banner-bg"></div>
        <h1>{{ WISHLIST }}</h1>
        <nav>
            <ol class="breadcrumb justify-content-center">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ HOME }}</a></li>
                <li class="breadcrumb-item active">{{ WISHLIST }}</li>
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

                    @if($wishlist->isEmpty())
                        <span class="text-danger">{{ NO_RESULT_FOUND }}</span>
                    @else

                        <div class="table-responsive-md">
                            <table class="table table-bordered">
                                <thead>
                                <tr class="table-primary">
                                    <th scope="col">{{ SERIAL }}</th>
                                    <th scope="col">{{ FEATURED_PHOTO }}</th>
                                    <th scope="col">{{ NAME }}</th>
                                    <th scope="col" class="w-150">{{ ACTION }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $i=0; @endphp
                                @foreach($wishlist as $row)
                                    @php
                                        $property_detail = \App\Models\Property::where('id', $row->property_id)->first();
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <img src="{{ asset('uploads/property_featured_photos/'.$property_detail->property_featured_photo) }}" alt="" class="w-200">
                                        </td>
                                        <td>
                                            {{ $property_detail->property_name }}<br>
                                            <a href="{{ route('front_property_detail', $property_detail->property_slug) }}" class="badge badge-primary" target="_blank">{{ SEE_DETAIL }}</a>
                                        </td>
                                        <td>
                                            <a href="{{ route('customer_wishlist_delete',$row->id) }}" class="btn btn-danger btn-sm" onClick="return confirm('{{ ARE_YOU_SURE }}');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>
                                @endforeach

                                </tbody>
                            </table>
                        </div>
                        <div>
                            {{ $wishlist->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

@endsection
