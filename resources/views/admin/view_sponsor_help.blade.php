@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">{{ CUSTOMERS }}</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin_downline_view') }}" method="GET">
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
                <input name="SponsorID" type="text" id="SponsorID" class="form-control" placeholder="Sponsor ID" value="{{ request('SponsorID') }}" required>
            </div>
            <div class="col-md-2">
                <label for="memberID">GH MemberID</label>
                <input name="SponsorID" type="text" id="SponsorID" class="form-control" placeholder="Sponsor ID" value="{{ request('SponsorID') }}" required>
            </div>
            

            <div class="col-md-2">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">--Select One--</option>
                    <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Star</option>
                    <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>silver</option>
                    <option value="4" {{ request('status') === '4' ? 'selected' : '' }}>gold</option>
                    <option value="5" {{ request('status') === '5' ? 'selected' : '' }}>platinum</option>
                    <option value="6" {{ request('status') === '6' ? 'selected' : '' }}>ruby</option>
                    <option value="7" {{ request('status') === '7' ? 'selected' : '' }}>emerald</option>
                    <option value="8" {{ request('status') === '8' ? 'selected' : '' }}>diamond</option>
                </select>
            </div>
            <div class="col-md-2 mt-4">
                <button type="submit" class="btn btn-info btn-sm">Search</button>
                <a type="reset" href="{{ route('admin_downline_view') }}" class="btn btn-dark btn-sm">Reset</a>
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
                        <th>Receiver Details</th>
                        <th>Doner Details</th>
                        <th>Pool Name</th>
                        <th>Sponsor Help</th>
                        <th>Help Date</th>
                       
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                         <td>DLH300594 (Vikram Singh)</td>
                        <td>DLH220578 (RABIN MRIDHA)</td>
                        <td>	Silver</td>
                        <td>	600.00</td>
                        <td>07 Jul 2024</td>
                     
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection