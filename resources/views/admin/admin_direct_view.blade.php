@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">{{ CUSTOMERS }}</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin_direct_view') }}" method="GET">
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
                <label for="memberID">SponsorID</label>
                <input name="SponsorID" type="text" id="SponsorID" class="form-control" placeholder="Sponsor ID" value="{{ request('SponsorID') }}" required>
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
                <a type="reset" href="{{ route('admin_direct_view') }}" class="btn btn-dark btn-sm">Reset</a>
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
                        <th>Member </th>
                        <th>DOJ</th>
                        <th>DOA</th>
                        <th>Sponsor </th>
                        <th>Member Status</th>
                        <th>LevelNo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                         <td>{{ $row->user_id }} <br>{{ $row->name }} <br>
                            {{ $row->phone }}  <br>
                            {{ $row->email }}<br>
                           <b>{{ $row->package->name }} </b></td>
                        <td>{{(($row->created_at))  }}</td>
                        <td>{{(($row->activated_date))  }}</td>

                        <td>{{ $row->sponsor_id }} <br> {{ $row->sponsor->name ?? 'anonymous' }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->status }}</td>
                     
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