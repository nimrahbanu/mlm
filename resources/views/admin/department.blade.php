@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ POSTS }}</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 mt-2 font-weight-bold text-primary"></h6>
            <div class="float-right d-inline">
                <a href="{{ route('department_create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> {{ ADD_NEW }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>			
                        <th>{{ SERIAL }}</th>
                        <th>DeptName</th>
                        <th>Create Date</th>
                        <th>Last Update</th>
                        <th>Status</th>
                        <th>{{ ACTION }}</th>
                    </tr>
                    </thead>
                    <tbody>
                        @php $i=0; @endphp
                        @foreach($departments as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->created_at }}</td>
                            <td>{{ $row->updated_at }}</td>
                            <td>
                            @if ($row->status == 'Active')
                            <a href="" onclick="departmentStatus({{ $row->id }})"><input type="checkbox" checked
                                    data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success"
                                    data-offstyle="danger"></a>
                            @else
                            <a href="" onclick="departmentStatus({{ $row->id }})"><input type="checkbox"
                                    data-toggle="toggle" data-on="Active" data-off="Pending" data-onstyle="success"
                                    data-offstyle="danger"></a>
                            @endif</td>
                            </td>
                            <td>
                                <a href="{{ route('department_destroy',$row->id) }}" class="btn btn-danger btn-sm" onClick="return confirm('{{ ARE_YOU_SURE }}');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
function departmentStatus(id) {
    $.ajax({
        type: "get",
        url: "{{url('/admin/department-status/')}}" + "/" + id,
        success: function(response) {
            toastr.success(response)
        },
        error: function(err) {
            console.log(err);
        }
    })
}
</script>
@endsection
