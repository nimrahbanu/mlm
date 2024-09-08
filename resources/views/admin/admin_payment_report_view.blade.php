@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">{{ CUSTOMERS }}</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin_payment_report_view') }}" method="GET">
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
                <label for="memberID">PH MemberID</label>
                <input name="sender" type="text" id="sender" class="form-control" placeholder="Member ID" value="{{ request('sender') }}">
            </div>
            <div class="col-md-2">
                <label for="memberID">GH MemberID</label>
                <input name="receiver" type="text" id="receiver" class="form-control" placeholder="Member ID" value="{{ request('receiver') }}">
            </div>
            <div class="col-md-2">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">--Select One--</option>
                    <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 mt-4">
                <button type="submit" class="btn btn-info btn-sm">Search</button>
                <a type="reset" href="{{ route('admin_payment_report_view') }}" class="btn btn-dark btn-sm">Reset</a>
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
                        <th>Date</th>
                        <th>Receiver</th>
                        <th>Sender</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Transaction No</th>
                        <th>Narration</th>
                        <th>Payment Slip</th>
                      
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                         <td>{{ $row->created_at }}</td>
                         <td>{{ $row->receiverByData->name ?? 'anonymous' }} <br>({{ $row->receiverByData->id ?? '' }}) <br>{{ $row->receiverByData->phone ?? '' }}</td>
                         <td>{{ $row->senderData->name ?? 'anonymous' }} <br>({{ $row->senderData->id ?? '' }}) <br>{{ $row->senderData->phone ?? '' }}</td>
                        <td>{{ $row->amount }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->transaction_no }}</td>
                        <td>{{ $row->narration }}</td>
                        <td>{{ $row->image }}</td>
                      
                     
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