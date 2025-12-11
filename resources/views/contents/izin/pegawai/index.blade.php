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
            <div class="card-header justify-content-between">
                <input class="form-control" name="status" type="hidden" value="{{ @$status }}" readonly>
                <div class="row">
                    <div class="col-sm-6 col-lg-6">
                        <div class="form-group">
                            <label class="form-label">Filter Dinas</label>
                            <select class="select2-basic-single js-states form-select form-control-sm" id="dinas" name="dinas"></select>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="form-group">
                            <label class="form-label">Filter Tanggal</label>
                            <div class="input-group">
                                <input class="form-control datePicker" name="dari" type="text" value="{{ @$start }}" readonly>
                                <span class="input-group-text" id="basic-addon2">-</span>
                                <input class="form-control datePicker" name="hingga" type="text" value="{{ @$end }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="Datatable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Jenis</th>
                                <th>Pegawai</th>
                                <th>Dinas</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('scripts')
<link rel="stylesheet" href="{{asset('vendor/flatpickr/flatpickr.min.css')}}">
<script src="{{asset('vendor/flatpickr/flatpickr.js')}}"></script>
<script type="text/javascript" src="{{ asset('js/users.js') }}"></script>
<script src="{{asset('vendor/moment.min.js')}}" async></script>
<style>
    .select2-selection.select2-selection--single {
        padding-right: 30px !important;
    }
</style>
<script>
    $(document).ready(function() {
        let dataTable = $('#Datatable').DataTable({
            scrollX: true,
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
                    d.tgl_awal = $('input[name=dari]').val();
                    d.tgl_akhir = $('input[name=hingga]').val();
                    d.dinas = $('#dinas').find(':selected').val();
                    d.status = $('input[name=status]').val();
                }
            },
            columns: [{
                    data: 'tgl',
                    name: 'tgl',
                    render: function(data, type, row) {
                        if (type === "sort" || type === "type") {
                            return data;
                        }
                        return moment(data).format("DD-MM-YYYY");
                    }
                },
                {
                    data: 'desc',
                    name: 'desc'
                },
                {
                    data: 'jenis_izin.title',
                    name: 'jenis_izin.title'
                },
                {
                    data: 'pegawai_.name',
                    name: 'pegawai_.name'
                },
                {
                    data: 'dinas.name',
                    name: 'dinas.name'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
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

        $(".datePicker").flatpickr({
            disableMobile: true,
            altInput: true,
            dateFormat: 'Y-m-d',
            altFormat: "d-m-Y",
            allowInput: true,
            onChange: function(selectedDates, date_str, instance) {
                dataTable.draw();
            },
            onReady: function(dateObj, dateStr, instance) {
                const $clear = $('<button class="btn btn-danger btn-sm flatpickr-clear mb-2">Clear</button>')
                    .on('click', () => {
                        instance.clear();
                        instance.close();
                    })
                    .appendTo($(instance.calendarContainer));
            }
        });

    });
</script>
@endpush