@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ EDIT_DREAM_PROPERTIES_INFO }}</h1>

    <form action="{{ route('admin_dream_property_location_update') }}" method="post" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="current_banner" value=" ">
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="">{{ NAME }}</label>
                            <input type="text" name="name" class="form-control" value="{{ $page_dream_property->name }}">
                        </div>
                        <div class="col-md-6">
                            <label for="" class="text-danger">{{ RED_TITLE }}</label>
                            <input type="text" name="red_title" class="form-control text-danger" value="{{ $page_dream_property->red_title }}">
                        </div>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="">{{ CHANGE_IMAGE }}</label>
                            <div>
                                <img src="{{ asset('uploads/page_dream_property/'.$page_dream_property->image_1) }}" alt="" class="w_300">
                            </div>
                            <div class="mt-3">

                                <input type="file" name="image_1"></div>
                        </div>
                        <div class="form-group">
                            <label for="">{{ CITY }}</label>
                            <input type="text" name="city_1" class="form-control"value="{{ $page_dream_property->city_1 }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="">{{ CHANGE_IMAGE }}</label>
                            <div>
                                <img src="{{ asset('uploads/page_dream_property/'.$page_dream_property->image_2) }}" alt="" class="w_300">
                            </div>
                        <div class="mt-3">
                            <input type="file" name="image_2"></div>
                        </div>
                        <div class="form-group">
                            <label for="">{{ CITY }}</label>
                            <input type="text" name="city_2" class="form-control"value="{{ $page_dream_property->city_2 }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="">{{ CHANGE_IMAGE }}</label>
                            <div>
                                <img src="{{ asset('uploads/page_dream_property/'.$page_dream_property->image_3) }}" alt="" class="w_300">
                            </div>
                            <div class="mt-3">
                                <input type="file" name="image_3"></div>
                        </div>
                        <div class="form-group">
                            <label for="">{{ CITY }}</label>
                            <input type="text" name="city_3" class="form-control"value="{{ $page_dream_property->city_3 }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="">{{ CHANGE_IMAGE }}</label>
                            <div>
                                <img src="{{ asset('uploads/page_dream_property/'.$page_dream_property->image_4) }}" alt="" class="w_300">
                            </div>
                            <div class="mt-3">
                                <input type="file" name="image_4"></div>
                        </div>
                        <div class="form-group">
                            <label for="">{{ CITY }}</label>
                            <input type="text" name="city_4" class="form-control"value="{{ $page_dream_property->city_4 }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">{{ SEO_INFORMATION }}</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="">{{ TITLE }}</label>
                    <input type="text" name="seo_title" class="form-control"value="{{ $page_dream_property->seo_title }}">
                </div>
                <div class="form-group">
                    <label for="">{{ META_DESCRIPTION }}</label>
                    <textarea name="seo_meta_description" class="form-control h_100" cols="30" rows="10">{{ $page_dream_property->seo_meta_description }} </textarea>
                </div>
                <button type="submit" class="btn btn-success">{{ UPDATE }}</button>
            </div>
        </div>
    </form>
@endsection
