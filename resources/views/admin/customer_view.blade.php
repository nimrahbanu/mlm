@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">{{ CUSTOMERS }}</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin_customer_view') }}" method="GET">
        <div class="row">
            <div class="col-md-2">
                <label for="fromDate">From Date</label>
                <input name="fromDate" id="fromDate" class="form-control" type="date" value="{{ request('fromDate') }}">
            </div>
            <div class="col-md-2">
                <label for="toDate">To Date</label>
                <input name="toDate" id="toDate" class="form-control" type="date" value="{{ request('toDate') }}">
            </div>
            <div class="col-md-2">
                <label for="memberID">Member ID</label>
                <input name="memberID" type="text" id="memberID" class="form-control" placeholder="Member ID" value="{{ request('memberID') }}">
            </div>
            <div class="col-md-2">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">--Select One--</option>
                    <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="Block" {{ request('status') === 'Block' ? 'selected' : '' }}>Block</option>
                </select>
            </div>
            <div class="col-md-2 mt-4">
                <button type="submit" class="btn btn-info btn-sm">Search</button>
                <a type="reset" href="{{ route('admin_customer_view') }}" class="btn btn-dark btn-sm">Reset</a>
            </div>
        </div>
    </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>{{ SERIAL }}</th>
                        <th>Edit</th>
                        <th>Block</th>
                        <th>Member</th>
                        <th>DOJ</th>
                        <th>DOA</th>
                        <th>Sponsor</th>
                        <th>Direct</th>
                        <th>Block Reason</th>
                        <th>Login</th>
                        <th>Member Detail</th>	
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td> <a href="{{ route('admin_customer_detail',$row->id) }}" class="btn btn-info btn-sm btn-block">{{DETAIL }}</a></td>
                       <td>
                            <!-- @if ($row->status == 'Active')
                            <a href="" onclick="customerStatus({{ $row->id }})"><input type="checkbox" checked
                                    data-toggle="toggle" data-on="Active" data-off="Block" data-onstyle="success"
                                    data-offstyle="danger"></a>
                            @else
                            <a href="" onclick="customerStatus({{ $row->id }})"><input type="checkbox"
                                    data-toggle="toggle" data-on="Active" data-off="Block" data-onstyle="success"
                                    data-offstyle="danger"></a>
                            @endif -->
                            <select onchange="customerStatus({{ $row->id }}, this.value)">
                                <option value="Active" {{ $row->status == 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="InActive" {{ $row->status == 'InActive' ? 'selected' : '' }}>Inactive</option>
                                <option value="Block" {{ $row->status == 'Block' ? 'selected' : '' }}>Block</option>
                            </select>

                        </td>
                         <td> <b>{{ $row->user_id }}</b> <br>{{ $row->name }}</td>
                      
                        <td>{{date('d M,y', strtotime($row->created_at))  }}</td>
                        <td>{{ $row->activated_date ? date('d M,y', strtotime($row->activated_date)) : null  }}</td>
                        <td> <b>{{ $row->sponsor_id }}</b> <br> {{ $row->sponsor->name ?? 'anonymous' }}</td>
                        <td> 
                            @php $direct = App\Models\User::where('sponsor_id', $row->user_id)->count();
                            echo $direct; @endphp</td>
                        <td> {{ $row->block_reason }}</td>
                        <td><button class="btn btn-warning">Login</button></td>
                        <td>{{ $row->phone }} <br>{{ $row->email }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// function customerStatus(id) {
//     $.ajax({
//         type: "get",
//         url: "{{url('/admin/customer-status/')}}" + "/" + id,
//         success: function(response) {
//             toastr.success(response)
//         },
//         error: function(err) {
//             console.log(err);
//         }
//     })
// }
function customerStatus(id, status) {
    $.ajax({
        type: "get",
        url: "{{url('/admin/customer-status/')}}" + "/" + id,
        data: { status: status },  // Pass the selected status to the server
        success: function(response) {
            toastr.success(response);
        },
        error: function(err) {
            console.log(err);
        }
    });
}

</script>

@endsection