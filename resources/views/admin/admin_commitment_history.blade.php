@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">{{ CUSTOMERS }}</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin_commitment_history') }}" method="GET">
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
                <label for="memberID">MemberID</label>
                <input name="id" type="text" id="id" class="form-control" placeholder="Member ID" value="{{ request('id') }}" required>
            </div>
            <div class="col-md-2 mt-4">
                <button type="submit" class="btn btn-info btn-sm">Search</button>
                <a type="reset" href="{{ route('admin_commitment_history') }}" class="btn btn-dark btn-sm">Reset</a>
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
                        <th>Member ID</th>
                        <th>Member Name</th>
                        <th>Commitment Date</th>
                        <th>Commitment Amount</th>
                        <th>Confirm Date</th>
                        <th>Commitment Status</th>
                      
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                         <td>{{ $row->sender }}</td>
                        <td>{{ $row->senderData->name ?? 'anonymous' }}</td>
                        <td>{{ $row->commitment_date }}</td>
                        <td>{{ $row->amount }}</td>
                        <td>{{ $row->confirm_date }}</td>
                        <td>{{ $row->status == '0' ? 'Pending' : 'Active' }}</td>
                      
                     
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function customerStatus(id) {
    $.ajax({
        type: "get",
        url: "{{url('/admin/customer-status/')}}" + "/" + id,
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