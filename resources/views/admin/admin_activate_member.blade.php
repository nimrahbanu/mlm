@extends('admin.app_admin')
@section('admin_content')
<h1 class="h3 mb-3 text-gray-800">Member Activation</h1>

<form action="{{ route('admin_activate_member') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="form-group">
                <label for="">MemberID</label>
                <input type="text" name="member_id" class="form-control" id="member_id" value="">
                
            </div>
            <div class="form-group">
                <label for="">Member Name</label>
                <input type="text" name="Member_name" class="form-control" id="Member_name" value="{{ old('Member_name') }}" readonly>

            </div>
            <button type="submit" class="btn btn-success btn-block mb_40">{{ SUBMIT }}</button>
        </div>
    </div>

</form>
<script>
    $(document).ready(function() {
        $('#member_id').on('input', function() {
            var memberId = $(this).val();
            
            if (memberId) {
                $.ajax({
                    url: "{{ route('get_member_name') }}",
                    type: "GET",
                    data: {
                        member_id: memberId
                    },
                    success: function(response) {
                        $('#Member_name').val(response.name);
                    },
                    error: function(xhr) {
                        $('#Member_name').val('Member not found');
                    }
                });
            } else {
                $('#Member_name').val('');
            }
        });
    });
</script>
@endsection