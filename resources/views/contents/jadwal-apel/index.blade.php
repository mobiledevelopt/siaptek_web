@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">{{ $title }}</h4>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="Datatable" class="table table-bordered" style="table-layout:fixed">
                        <thead>
                            <tr>
                                <th>Dinas</th>
                                <th>Hari</th>
                                <th>Apel Pagi</th>
                                <th>Apel Sore</th>
                                <th>Jam Apel Pagi</th>
                                <th>Maksimal Jam Apel Pagi</th>
                                <th>Jam Apel Sore</th>
                                <th>Maksimal Jam Apel Sore</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
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
<script src="{{asset('vendor/moment.min.js')}}" async></script>
<script>
    $(document).ready(function() {
        var groupColumn = 0;
        let dataTable = $('#Datatable').DataTable({
            lengthChange: true,
            responsive: true,
            scrollX: true,
            serverSide: true,
            processing: true,
            order: [
                // [0, 'desc']
            ],
            columnDefs: [{
                visible: false,
                targets: groupColumn
            }],
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
                    data: 'dinas.name',
                    name: 'dinas.name',
                },
                {
                    data: 'hari',
                    name: 'hari',
                    render: function(data, type, row) {
                        if (data == '1') {
                            return 'Senin';
                        } else if (data == '2') {
                            return 'Selasa';
                        } else if (data == '3') {
                            return 'Rabu';
                        } else if (data == '4') {
                            return 'Kamis';
                        } else if (data == '5') {
                            return 'Jumat';
                        }
                        return 'Tidak Diketahui';
                    }
                },
                {
                    data: 'apel_pagi',
                    name: 'apel_pagi',
                    render: function(data, type, row) {
                        if (data == '1') {
                            return 'Ya';
                        }
                        return 'Tidak';
                    }
                },
                {
                    data: 'apel_sore',
                    name: 'apel_sore',
                    render: function(data, type, row) {
                        if (data == '1') {
                            return 'Ya';
                        }
                        return 'Tidak';
                    }
                },
                {
                    data: 'jam_apel_pagi',
                    name: 'jam_apel_pagi',
                    render: function(data, type, row) {
                        if (data != null) {
                            return moment(data.toString(), "HH:mm:ss").format("HH:mm");
                        }
                        return data;
                    }
                },
                {
                    data: 'max_apel_pagi',
                    name: 'max_apel_pagi',
                    render: function(data, type, row) {
                        if (data != null) {
                            return moment(data.toString(), "HH:mm:ss").format("HH:mm");
                        }
                        return data;

                    }
                },
                {
                    data: 'jam_apel_sore',
                    name: 'jam_apel_sore',
                    render: function(data, type, row) {
                        if (data != null) {
                            return moment(data.toString(), "HH:mm:ss").format("HH:mm");
                        }
                        return data;
                    }
                },
                {
                    data: 'max_apel_sore',
                    name: 'minmax_apel_sore_pulang',
                    render: function(data, type, row) {
                        if (data != null) {
                            return moment(data.toString(), "HH:mm:ss").format("HH:mm");
                        }
                        return data;
                    }
                },
                {
                    data: 'latitude',
                    name: 'latitude',
                },
                {
                    data: 'longitude',
                    name: 'longitude',
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
            drawCallback: function(settings) {
                var api = this.api();
                var rows = api.rows({
                    page: 'current'
                }).nodes();
                var last = null;

                api.column(groupColumn, {
                        page: 'current'
                    })
                    .data()
                    .each(function(group, i) {
                        if (last !== group) {
                            $(rows)
                                .eq(i)
                                .before(
                                    '<tr class="group" style="background-color: #c9d2ff !important"><td colspan="10">' +
                                    group +
                                    '</td></tr>'
                                );

                            last = group;
                        }
                    });
            }
        });

    });
</script>
@endpush