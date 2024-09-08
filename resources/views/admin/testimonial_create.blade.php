@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ ADD_TESTIMONIAL }}</h1>

    <form action="{{ route('admin_testimonial_store') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
                <div class="float-right d-inline">
                    <a href="{{ route('admin_testimonial_view') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> {{ VIEW_ALL }}</a>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="">{{ NAME }} *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" autofocus>
                </div>
                <div class="form-group">
                    <label for="">{{ DESIGNATION }} *</label>
                    <input type="text" name="designation" class="form-control" value="{{ old('designation') }}" autofocus>
                </div>
                <div class="form-group">
                    <label for="">{{ CONTENT }} *</label>
                    <textarea name="comment" class="form-control h_100" cols="30" rows="10">{{ old('comment') }}</textarea>
                </div>
                <div class="form-group">
                    <label for="">{{ PHOTO }} *</label>
                    <div>
                        <input type="file" name="photo">
                    </div>
                </div>
                <div class="form-group">
                    <label for="">{{ PROJECT_NAME }} *</label>
                    <input type="text" name="project_name" class="form-control" value="{{ old('project_name') }}" autofocus>
                </div>
                <div class="form-group">
                    <label for="">{{ PROJECT_DESCRIPTION }} *</label>
                    <input type="text" name="project_description" class="form-control" value="{{ old('project_description') }}" autofocus>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="">{{ PROJECT_START_DATE }} *</label>
                        <input type="date" name="project_start_date" class="form-control" value="{{ old('project_start_date') }}" autofocus>
                    </div>
                    <div class="col-md-4">
                        <label for="">{{ PROJECT_END_DATE }} *</label>
                        <input type="date" name="project_end_date" class="form-control date_disable" value="{{ old('project_end_date') }}" autofocus>
                    </div>
                    <div class="col-md-4 align-self-end">
                        <div class="pl-4">
                            <input class="form-check-input project_end_date" name="ongoing" type="checkbox" value="ongoing" id="end_date">
                            <label for="end_date">{{ ONGOING }}</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1">
                        <label for="">{{ SERVICE }}</label>
                    </div>
                        <div class="col-md-3">
                            <select name="service_rating" class="form-control">
                                <option value="1">1 Star</option>
                                <option value="2">2 Star</option>
                                <option value="3">3 Star</option>
                                <option value="4">4 Star</option>
                                <option value="5">5 Star</option>
                            </select>
                    </div>
                    <div class="offset-md-3 col-md-1">
                        <label for="">{{ SCHEDULE }}</label>
                    </div>
                        <div class="col-md-4">
                            <select name="schedule_rating" class="form-control">
                                <option value="1">1 Star</option>
                                <option value="2">2 Star</option>
                                <option value="3">3 Star</option>
                                <option value="4">4 Star</option>
                                <option value="5">5 Star</option>
                            </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-1">
                        <label for="">{{ COST }}</label>
                    </div>
                        <div class="col-md-3">
                            <select name="cost_rating" class="form-control">
                                <option value="1">1 Star</option>
                                <option value="2">2 Star</option>
                                <option value="3">3 Star</option>
                                <option value="4">4 Star</option>
                                <option value="5">5 Star</option>
                            </select>
                    </div>
                    <div class="offset-md-3 col-md-1">
                        <label for="">{{ WILLING_TO_REFER }}</label>
                    </div>
                        <div class="col-md-4">
                            <select name="willing_to_refer_rating" class="form-control">
                                <option value="1">1 Star</option>
                                <option value="2">2 Star</option>
                                <option value="3">3 Star</option>
                                <option value="4">4 Star</option>
                                <option value="5">5 Star</option>
                            </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success">{{ SUBMIT }}</button>
        </div>
    </form>
<script defer>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.project_end_date').forEach(function(element) {
        element.addEventListener('click', function() {

            document.querySelectorAll('.date_disable').forEach(function(el) {
                if (el.disabled) {
                    el.disabled = false; // Enable the element
                } else {
                    el.disabled = true; // Disable the element
                }
            });
        });
    });
});
</script>
@endsection
