@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">{{ $title }}</h4>
                </div>
                <div class="card-action">
                    <a href="{{ Route('kalendar-libur.create') }}" class="btn btn-primary" role="button">
                        <svg class="icon-20" fill="none">
                            <use href="#plus"></use>
                        </svg>
                        <span>Tambah Data</span>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="Datatable" class="table table-bordered" width="100%">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Attachment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('scripts')
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.css') }}">
<script src="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.js') }}" async></script>
<script type="text/javascript" src="{{ asset('js/users.js') }}"></script>
<script src="{{asset('vendor/moment.min.js')}}" async></script>
<script src="{{asset('vendor/toastr/toastr.min.js')}}"></script>
<link rel="stylesheet" href="{{asset('vendor/toastr/toastr.min.css')}}">
<script>
    $(document).ready(function() {
        let dataTable = $('#Datatable').DataTable({
            lengthChange: true,
            responsive: true,
            serverSide: true,
            processing: false,
            order: [
                [1, 'desc']
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            pageLength: 10,
            ajax: {
                url: `{{ url()->current() }}`,
                data: function(d) {
                    // d.tgl_awal = $('input[name=dari]').val();
                    // d.tgl_akhir = $('input[name=hingga]').val();
                    // d.dinas = $('#dinas').find(':selected').val();
                }
            },
            columns: [{
                    data: 'tgl',
                    name: 'tgl',
                    render: function(data, type, row) {
                        return moment(data).format("DD-MM-YYYY");
                    }
                },
                {
                    data: 'desc',
                    name: 'desc'
                },
                {
                    data: 'attachment',
                    name: 'attachment',
                    render: function(data, type) {
                        if (typeof data === "string" && data.length === 0 || data === null) {
                            return '';
                        }
                        return '<a class="btn btn-primary" data-bs-toggle="tooltip" href="' + data + '" role="button">Download</a>';
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
            rowCallback: function(row, data) {
                let api = this.api();
                $(row).find('.btn-delete').click(function() {
                    let pk = $(this).data('id'),
                        url = `{{ route("kalendar-libur.index") }}/` + pk;
                    Swal.fire({
                        title: "Anda Yakin ?",
                        text: "Data tidak dapat dikembalikan setelah di hapus!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Ya, Hapus!",
                        cancelButtonText: "Tidak, Batalkan",
                    }).then((result) => {
                        if (result.value) {
                            $.ajax({
                                url: url,
                                type: "DELETE",
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    _method: 'DELETE'
                                },
                                error: function(response) {
                                    toastr.error(response, 'Failed !');
                                },
                                success: function(response) {
                                    if (response.status === "success") {
                                        toastr.success(response.message, 'Success !');
                                        api.draw();
                                    } else {
                                        toastr.error((response.message ? response.message : "Please complete your form"), 'Failed !');
                                    }
                                }
                            });
                        }
                    });
                });
            }
        });

    });
</script>
@endpush