@extends('admin.app_admin')
@section('admin_content')
    <h1 class="h3 mb-3 text-gray-800">{{ POSTS }}</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>	
                        
                        <th>{{ SERIAL }}</th>
                        <th>TicketID</th>
                        <th>Member</th>
                        <th>Subject</th>
                        <th>Dept Name</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Create Date</th>
                        <th>Ticket</th>
                        <th>Close / Reopen Ticket</th>
                    </tr>
                    </thead>
                    <tbody>
                        @php $i=0; @endphp
                        @foreach($supports as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->id  }}</td>
                            <td>{{ @$row->userData->name ?? 'anonymous'}} <br>{{ $row->user_id }}</td>
                            <td>{{ $row->subject }}</td>
                            <td>{{ @$row->departmentData->name }}</td>
                            <td>{{ $row->priority }}</td>
                            <td>{{ $row->status }}</td>
                            <td>{{ $row->created_at }}</td>
                            <td>
                            <a href="{{ route('support_edit',$row->id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>

                            </td>
                            <td>
                                @if ($row->status == 'Open')
                                <a href="" onclick="supportStatus({{ $row->id }})"><input type="checkbox" checked
                                        data-toggle="toggle" data-on="Open" data-off="Close" data-onstyle="success"
                                        data-offstyle="danger"></a>
                                @else
                                <a href="" onclick="supportStatus({{ $row->id }})"><input type="checkbox"
                                        data-toggle="toggle" data-on="Open" data-off="Close" data-onstyle="success"
                                        data-offstyle="danger"></a>
                                @endif
                            </td>
                           
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
function supportStatus(id) {
    $.ajax({
        type: "get",
        url: "{{url('/admin/support-status/')}}" + "/" + id,
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
