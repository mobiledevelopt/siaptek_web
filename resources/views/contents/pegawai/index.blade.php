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
                    <a href="{{ Route('pegawai.create') }}" class="btn btn-primary" role="button">
                        <svg class="icon-20" fill="none">
                            <use href="#plus"></use>
                        </svg>
                        <span>Pegawai Baru</span>
                    </a>
                    <button class="btn btn-success" id="export">Export Excel</button>
                </div>

            </div>
            <div class="card-header justify-content-between">
                <div class="row">
                    <div class="col-sm-6 col-lg-6">
                        <div class="form-group">
                            <label class="form-label">Filter Dinas</label>
                            <select class="select2-basic-single js-states form-select form-control-sm" id="dinas" name="dinas"></select>
                        </div>
                    </div>
                    <!-- <div class="col-sm-6 col-lg-4">
                        <div class="form-group">
                            <label class="form-label">Export</label>
                            <div class="input-group">
                                <button class="btn btn-success" name="act" value="excel">Excel</button>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="Datatable1" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>NIP</th>
                                <th>Email</th>
                                <th>Dinas</th>
                                <th>Status</th>
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
<script src="{{ asset('js/plugins/select2.js') }}" defer></script>
<script src="{{asset('vendor/toastr/toastr.min.js')}}"></script>
<link rel="stylesheet" href="{{asset('vendor/toastr/toastr.min.css')}}">
<style>
    .select2-selection.select2-selection--single {
        padding-right: 30px !important;
    }
</style>
<script>
    $(document).ready(function() {

        let dataTable = $('#Datatable1').DataTable({
            lengthChange: true,
            responsive: true,
            // scrollX: true,
            serverSide: true,
            processing: true,
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
                    d.dinas = $('#dinas').find(':selected').val();
                }
            },
            columns: [{
                    data: 'name',
                    name: 'name',
                    render: function(data, type, row) {
                        return (row.gelar_depan == null ? '' : row.gelar_depan + ". ") + data + (row.gelar_belakang == null ? '' : ", " + row.gelar_belakang);
                    }
                },
                {
                    data: 'nip',
                    name: 'nip',
                },
                {
                    data: 'email',
                    name: 'email',
                },
                {
                    data: 'dinas.name',
                    name: 'dinas.name',
                    render: function(data, type, row) {
                        str = data.toLowerCase().replace(/\b[a-z]/g, function(letter) {
                            return letter.toUpperCase();
                        });
                        return str;
                    }
                },
                {
                    data: 'active',
                    name: 'active',
                    render: function(data, type) {
                        if (data === 1) {
                            return '<span class ="badge rounded-pill bg-success"> Active </span>';
                        }

                        return '<span class="badge rounded-pill bg-danger ">Non Active</span >';
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ],
            rowCallback: function(row, data) {
                let api = this.api();
                $(row).find('.btn-delete').click(function() {
                    console.log($(this).data('id'));
                    let pk = $(this).data('id'),
                        url = `{{ route("pegawai.index") }}/` + pk;
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

        $('#dinas').select2({
            dropdownParent: $('#dinas').parent(),
            placeholder: "Pilih Dinas",
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('dinas.select2') }}",
                dataType: "json",
                cache: true,
                data: function(e) {
                    return {
                        q: e.term || '',
                        page: e.page || 1
                    }
                },
            },
        }).on('change', function(e) {
            dataTable.draw();
        });

        $('#Datatable1').css('overflow', 'auto');
        $('.dataTables_scrollBody').css('min-height', '130px')

        $('#export').on('click', function(e) {
            e.preventDefault();
            let btnSubmitHtml = $('#export').html();
            let dinas = $('#dinas').val();
            $.ajax({
                beforeSend: function() {
                    $('#export').addClass("disabled").html("<i class='bx bx-hourglass bx-spin font-size-16 align-middle me-2'></i> Loading ...").prop("disabled", "disabled");
                },
                type: "GET",
                data: {
                    dinas: dinas,
                },
                url: "{{ route('pegawai.export') }}",
                success: function(response) {
                    console.log(response.url);
                    let errorCreate = $('#errorCreate');
                    errorCreate.css('display', 'none');
                    errorCreate.find('.alert-text').html('');
                    $('#export').removeClass("disabled").html(btnSubmitHtml).removeAttr("disabled");
                    if (response.status === "success") {
                        toastr.success(response.message, 'Success !');
                        window.open(response.url, '_blank');
                    } else {
                        toastr.error((response.message ? response.message : "Gagal refresh data"), 'Failed !');
                        if (response.error !== undefined) {
                            errorCreate.removeAttr('style');
                            $.each(response.error, function(key, value) {
                                errorCreate.find('.alert-text').append('<span style="display: block">' + value + '</span>');
                            });
                        }
                    }
                },
                error: function(response) {
                    $('#export').removeClass("disabled").html(btnSubmitHtml).removeAttr("disabled");
                    toastr.error(response.responseJSON.message, 'Failed !');
                }
            });
        });

    });
</script>
@endpush