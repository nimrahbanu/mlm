@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ LANGUAGE_MENU_TEXT }}</h1>

    <form action="{{ route('admin_language_menu_text_store') }}" method="post">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 mt-2 font-weight-bold text-primary">{{ SETUP_KEY_VALUES }}</h6>
                        <div class="float-right d-inline">
                            <a href="{{route('admin_language_menu_text')}}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> {{ VIEW_ALL }}</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="">{{ KEY }} *</label>
                                    <input type="text" name="lang_key" class="form-control" value="{{ old('lang_key') }}" required autofocus>
                                </div>
                                <div class="form-group">
                                    <label for="">{{ VALUE }} *</label>
                                    <textarea name="lang_value" class="form-control" required>{{ old('lang_value') }} </textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">{{ SUBMIT }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection
