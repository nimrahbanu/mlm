@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">{{ EDIT_FAQ }}</h1>


<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
        <div class="float-right d-inline">
            <a href="{{ route('support') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i>
                {{ VIEW_ALL }}</a>
        </div>
    </div>
    <div class="card-body">
    <form action="{{ route('admin_support_update',$support->id) }}" method="post">
                @csrf
        <div class="row">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <td>{{ NAME }}</td>
                            <td>{{ $support->userData->name }} <br>({{ $support->id }})</td>
                        </tr>
                        <tr>
                            <td>{{ PHOTO }}</td>
                            <td>
                                @if($support->user_image == '')
                                <img src="{{ asset('uploads/user_photos/default_photo.jpg') }}" class="w_100">
                                @else
                                <img src="{{ asset('uploads/user_photos/'.$support->user_image) }}" class="w_100">
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td>Message</td>
                            <td>{{ $support->user_message }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label for="">Message</label>
                    <textarea name="admin_message" class="form-control editor" cols="30"
                        rows="10">{{ $support->admin_message }}</textarea>
                </div>
                <div class="form-group">
                    <label for="">Image</label>
                    <input type="file" name="admin_image" class="form-control" value="{{ $support->admin_image }}">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-success">{{ UPDATE }}</button>
        </form>

    </div>
</div>

@endsection