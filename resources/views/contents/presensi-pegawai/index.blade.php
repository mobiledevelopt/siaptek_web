 @extends('layouts.admin')

 @section('content')
 <div class="row">
     <div class="col-sm-12">
         <div class="card">
             <div class="card-header justify-content-between">
                 <div class="header-title">
                     <div class="col-sm-12 col-lg-12">
                         <h4 class="card-title">Data Presensi Pegawai</h4>
                     </div>
                     <form>
                         <input class="form-control" name="status" type="hidden" value="{{ @$status }}" readonly>
                         <div class="row mt-4">
                             <div class="col-sm-6 col-lg-4">
                                 <div class="form-group">
                                     <label class="form-label">Filter Dinas</label>
                                     <select class="select2-basic-single js-states form-select form-control-sm" id="dinas" name="dinas"></select>
                                 </div>
                             </div>
                             <div class="col-sm-6 col-lg-4">
                                 <div class="form-group">
                                     <label class="form-label">Filter Tanggal</label>
                                     <div class="input-group">
                                         <input class="form-control datePicker" id="dari" name="dari" type="text" value="{{ @$start }}" readonly>
                                         <span class="input-group-text" id="basic-addon2">-</span>
                                         <input class="form-control datePicker" id="hingga" name="hingga" type="text" value="{{ @$end }}" readonly>
                                     </div>
                                 </div>
                             </div>
                             <div class="col-sm-4">
                                 <div class="form-group">
                                     <label class="form-label">Export</label>
                                     <div class="input-group">
                                         <button class="btn btn-success" id="export">Excel</button>
                                         <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">PDF</button>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </form>
                 </div>
             </div>
             <div class="card-body">
                 <div class="table-responsive">
                     <table id="Datatable" class="table table-bordered">
                         <thead>
                             <tr>
                                 <th>ID</th>
                                 <th>Tanggal</th>
                                 <th>Dinas</th>
                                 <th>NIP</th>
                                 <th>Nama</th>
                                 <th>Foto Presensi Masuk</th>
                                 <th>Jam Masuk</th>
                                 <th>Foto Presensi Pulang</th>
                                 <th>Jam Pulang</th>
                                 <th>Apel Pagi</th>
                                 <th>Foto Apel Pagi</th>
                                 <th>Apel Sore</th>
                                 <th>Foto Apel Sore</th>
                                 <th>Status</th>
                                 <th>Potongan TPP</th>
                                 <th>TPP diterima</th>
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
 
 <!-- modal -->
 <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="exampleModalLabel">Export PDF</h5>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>
             <div class="modal-body">
                 <div class="form-group">
                     <label class="form-label">Nama Pegawai</label>
                     <select class="select2-basic-single js-states form-select form-control-sm" id="pegawai" name="pegawai"></select>
                 </div>
                 <div class="form-group">
                     <label class="form-label">Filter Tanggal</label>
                     <div class="input-group">
                         <input class="form-control datePicker" id="p_dari" name="dari" type="text" value="{{ @$start }}" readonly>
                         <span class="input-group-text" id="basic-addon2">-</span>
                         <input class="form-control datePicker" id="p_hingga" name="hingga" type="text" value="{{ @$end }}" readonly>
                     </div>
                 </div>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                 <button type="button" id="exportPdf" class=" btn btn-primary">Export PDF</button>
             </div>
         </div>
     </div>
 </div>
 
 @stop

 @push('scripts')
 <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.css') }}">
 <script src="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.js') }}" async></script>


 <script src="{{asset('vendor/toastr/toastr.min.js')}}"></script>
 <link rel="stylesheet" href="{{asset('vendor/toastr/toastr.min.css')}}">
 <link rel=" stylesheet" href="{{asset('vendor/flatpickr/flatpickr.min.css')}}">
 <script src="{{asset('vendor/flatpickr/flatpickr.js')}}"></script>
 <link rel="stylesheet" href="{{asset('vendor/flatpickr/flatpickr_picker.min.css')}}">
 <script src="{{asset('vendor/flatpickr/flatpickr_month.js')}}"></script>
 <script type="text/javascript" src="{{ asset('vendor/datatables/dataTables.buttons.min.js') }}"></script>
 <script type="text/javascript" src="{{ asset('vendor/datatables/jszip.min.js') }}"></script>
 <script type="text/javascript" src="{{ asset('vendor/datatables/pdfmake.min.js') }}"></script>
 <script type="text/javascript" src="{{ asset('vendor/datatables/vfs_fonts.js') }}"></script>
 <script type="text/javascript" src="{{ asset('vendor/datatables/buttons.html5.min.js') }}"></script>
 <script type="text/javascript" src="{{ asset('vendor/datatables/buttons.print.min.js') }}"></script>
 <script type="text/javascript" src="{{ asset('js/users.js') }}"></script>
 <script src="{{ asset('js/plugins/select2.js') }}" defer></script>
 <script src="{{asset('vendor/moment.min.js')}}" async></script>
 <style>
     .select2-selection.select2-selection--single {
         padding-right: 30px !important;
     }
 </style>
 <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
 <script>
     // Enable pusher logging - don't include this in production
     //  Pusher.logToConsole = true;

     //  var pusher = new Pusher('f39fa26c540d7fd43d40', {
     //      cluster: 'ap1'
     //  });

     //  var channel = pusher.subscribe('global-notif');
     //  channel.bind('App\Events\SendGlobalNotification', function(data) {
     //      console.log("pusher");
     //      console.log(JSON.stringify(data));
     //  });
 </script>
 <script>
     $(document).ready(function() {
         let dataTable = $('#Datatable').DataTable({
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
                     d.tgl_awal = $('input[name=dari]').val();
                     d.tgl_akhir = $('input[name=hingga]').val();
                     d.dinas = $('#dinas').find(':selected').val();
                     d.status = $('input[name=status]').val();
                 }
             },
             columns: [{
                     data: 'id',
                     name: 'id'
                 },
                 {
                     data: 'date_attendance',
                     name: 'date_attendance',
                     render: function(data, type, row) {
                         if (type === "sort" || type === "type") {
                             return data;
                         }
                         return moment(data).format("DD-MM-YYYY");
                     }
                 },
                 {
                     data: 'pegawai.dinas.name',
                     name: 'pegawai.dinas.name',
                     render: function(data, type, row) {
                         str = data.toLowerCase().replace(/\b[a-z]/g, function(letter) {
                             return letter.toUpperCase();
                         });
                         return str;
                     }
                 },
                 {
                     data: 'pegawai.nip',
                     name: 'pegawai.nip'
                 },
                 {
                     data: 'pegawai.name',
                     name: 'pegawai.name',
                     render: function(data, type, row) {
                         str = data.toLowerCase().replace(/\b[a-z]/g, function(letter) {
                             return letter.toUpperCase();
                         });
                         return str;
                     }
                 },
                 {
                     data: 'foto_absen_masuk',
                     name: 'foto_absen_masuk',
                     render: function(data, type) {
                         if (typeof data === "string" && data.length === 0 || data === null) {
                             return '<img src="{{asset("images/no_image.png")}}" width="50px" height="50px">';
                         }
                         return '<a class="btn" href="' + data + '" role="button" title="Download"><img src="' + data + '" width="80px" height="80px"></a>';
                     }
                 }, {
                     data: 'incoming_time',
                     name: 'incoming_time'
                 }, {
                     data: 'foto_absen_pulang',
                     name: 'foto_absen_pulang',
                     render: function(data, type) {
                         if (typeof data === "string" && data.length === 0 || data === null) {
                             return '<img src="{{asset("images/no_image.png")}}" width="50px" height="50px">';
                         }
                         return '<a class="btn" href="' + data + '" role="button" title="Download"><img src="' + data + '" width="80px" height="80px"></a>';
                     }
                 },
                 {
                     data: 'outgoing_time',
                     name: 'outgoing_time'
                 },
                 {
                     data: 'status_apel_pagi',
                     name: 'status_apel_pagi'
                 },
                 {
                     data: 'foto_apel_pagi',
                     name: 'foto_apel_pagi',
                     render: function(data, type) {
                         if (typeof data === "string" && data.length === 0 || data === null) {
                             return '<img src="{{asset("images/no_image.png")}}" width="50px" height="50px">';
                         }
                         return '<a class="btn" href="' + data + '" role="button" title="Download"><img src="' + data + '" width="80px" height="80px"></a>';
                     }
                 },
                 {
                     data: 'status_apel_sore',
                     name: 'status_apel_sore'
                 },
                 {
                     data: 'foto_apel_sore',
                     name: 'foto_apel_sore',
                     render: function(data, type) {
                         if (typeof data === "string" && data.length === 0 || data === null) {
                             return '<img src="{{asset("images/no_image.png")}}" width="50px" height="50px">';
                         }
                         return '<a class="btn" href="' + data + '" role="button" title="Download"><img src="' + data + '" width="80px" height="80px"></a>';
                     }
                 },
                 {
                     data: 'status',
                     name: 'status',
                     render: function(data, type, row) {
                         str = data.toLowerCase().replace(/\b[a-z]/g, function(letter) {
                             return letter.toUpperCase();
                         });
                         return str;
                     }
                 },
                 {
                     data: 'total_potongan_tpp',
                     name: 'total_potongan_tpp',
                     render: $.fn.dataTable.render.number(',', '.', 0, 'Rp. ')
                 },
                 {
                     data: 'tpp_diterima',
                     name: 'tpp_diterima',
                     render: $.fn.dataTable.render.number(',', '.', 0, 'Rp. ')
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
                         url = `{{ route("presensi-pegawai.updateTidakMasuk") }}`;
                     Swal.fire({
                         title: "Anda Yakin ?",
                         text: "Data tidak dapat dikembalikan setelah di proses!",
                         input: 'text',
                         inputPlaceholder: "Keterangan",
                         icon: "warning",
                         showCancelButton: true,
                         confirmButtonColor: "#DD6B55",
                         confirmButtonText: "Ya, Proses!",
                         cancelButtonText: "Tidak, Batalkan",
                     }).then((result) => {
                         if (result.value) {
                             $.ajax({
                                 url: url,
                                 type: "GET",
                                 data: {
                                     // _token: '{{ csrf_token() }}',
                                     id: pk,
                                     keterangan: result.value
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
                         }else {
                             toastr.error("Keterangan Wajib di Isi !");
                         }
                     });
                 });
             }
         });

         $(".datePicker").flatpickr({
             disableMobile: true,
             // dateFormat: 'Y-m-d',
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

         $('#dinas').select2({
             dropdownParent: $('#dinas').parent(),
             placeholder: "Filter Dinas",
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

         $('#export').on('click', function(e) {
             e.preventDefault();
             let btnSubmitHtml = $('#export').html();
             let dinas = $('#dinas').val();
             let dari = $('#dari').val();
             let hingga = $('#hingga').val();
             $.ajax({
                 beforeSend: function() {
                     $('#export').addClass("disabled").html("<i class='bx bx-hourglass bx-spin font-size-16 align-middle me-2'></i> Loading ...").prop("disabled", "disabled");
                 },
                 type: "GET",
                 data: {
                     dinas: dinas,
                     dari: dari,
                     hingga: hingga
                 },
                 url: "{{ route('presensi-pegawai.export') }}",
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
         
         $('#pegawai').select2({
             dropdownParent: $('#pegawai').parent(),
             placeholder: "Pilih Pegawai",
             allowClear: true,
             width: '100%',
             ajax: {
                 url: "{{ route('pegawai.select2') }}",
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
         
         
         $('#exportPdf').on('click', function(e) {
             e.preventDefault();
             let btnSubmitHtml = $('#exportPdf').html();
             let pegawai = $('#pegawai').val();
             let dari = $('#p_dari').val();
             let hingga = $('#p_hingga').val();
             $.ajax({
                 beforeSend: function() {
                     $('#exportPdf').addClass("disabled").html("<i class='bx bx-hourglass bx-spin font-size-16 align-middle me-2'></i> Loading ...").prop("disabled", "disabled");
                 },
                 type: "GET",
                 data: {
                     pegawai_id: pegawai,
                     dari: dari,
                     hingga: hingga
                 },
                 url: "{{ route('presensi-pegawai.exportPdf') }}",
                 success: function(response) {
                     console.log(response.url);
                     let errorCreate = $('#errorCreate');
                     errorCreate.css('display', 'none');
                     errorCreate.find('.alert-text').html('');
                     $('#exportPdf').removeClass("disabled").html(btnSubmitHtml).removeAttr("disabled");
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
                     $('#exportPdf').removeClass("disabled").html(btnSubmitHtml).removeAttr("disabled");
                     //  toastr.error(response.responseJSON.message, 'Failed !');
                 }
             });
         });
     });
 </script>
 @endpush