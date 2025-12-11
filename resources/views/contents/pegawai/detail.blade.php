@extends('layouts.admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-sm">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h5 class="card-title">PROFILE</h5>
                </div>
                <div class="card-action">
                    <a href="{{ Route('pegawai.index') }}" class="btn btn-sm btn-primary" role="button">
                        <svg class="icon-20" width="20" fill="none">
                            <use href="#arrow"></use>
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-striped">
                    <tbody>
                        <tr>
                            <td style="width:150px">NIK</td>
                            <td style="width:5px">:</td>
                            <td>{{ $data->nik }}</td>
                        </tr>
                        <tr>
                            <td>Nama</td>
                            <td>:</td>
                            <td>{{ ucwords(strtolower($data->name)) }}</td>
                        </tr>
                        <tr>
                            <td>Jenis Kelamin</td>
                            <td>:</td>
                            <td>{{ ucwords(strtolower($data->gender)) }}</td>
                        </tr>
                        <tr>
                            <td>Tempat/Tanggal Lahir</td>
                            <td>:</td>
                            <td>{{ ucwords(strtolower($data->place_of_birth)) }},
                                {{ date('d-m-Y', strtotime($data->date_of_birth)) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Agama</td>
                            <td>:</td>
                            <td>{{ ucfirst(strtolower($data->religion)) }}</td>
                        </tr>
                        <tr>
                            <td>Status Perkawinan</td>
                            <td>:</td>
                            <td>{{ ucfirst(strtolower($data->marriage)) }}</td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td>:</td>
                            <td>{{ strtolower($data->email) }}</td>
                        </tr>
                        <tr>
                            <td>Nomor HP</td>
                            <td>:</td>
                            <td>{{ $data->no_hp }}</td>
                        </tr>
                        <tr>
                            <td>Dinas</td>
                            <td>:</td>
                            <td>{{ ucwords(strtolower($data->dinas)) }}</td>
                        </tr>
                        <tr>
                            <td>Jabatan</td>
                            <td>:</td>
                            <td>{{ ucwords(strtolower($data->position_pegawai)) }}</td>
                        </tr>
                        <tr>
                            <td>NIP</td>
                            <td>:</td>
                            <td>{{ $data->nip }}</td>
                        </tr>
                        <tr>
                            <td>Gelar Depan</td>
                            <td>:</td>
                            <td> {{ $data->gelar_depan }}</td>
                        </tr>
                        <tr>
                            <td>Gelar Belakang</td>
                            <td>:</td>
                            <td> {{ $data->gelar_belakang }}</td>
                        </tr>
                        <tr>
                            <td>TMT JABATAN</td>
                            <td>:</td>
                            <td>{{ date('d-m-Y', strtotime($data->tmt_pengangkatan)) }}</td>
                        </tr>
                        <tr>
                            <td>Pangkat/golongan</td>
                            <td>:</td>
                            <td>{{ $data->pangkat .' '. $data->gol }}</td>
                        </tr>
                        <tr>
                            <td>TMT Pangkat</td>
                            <td>:</td>
                            <td>{{ date('d-m-Y', strtotime($data->tmt_pangkat)) }}</td>
                        </tr>
                        <tr>
                            <td>Masa Kerja</td>
                            <td>:</td>
                            <td>{{ $data->masa_kerja_tahun }} Tahun {{ $data->masa_kerja_bulan }} Bulan</td>
                        </tr>
                        <tr>
                            <td>TPP</td>
                            <td>:</td>
                            <td>Rp. {{ number_format($data->tpp, 0, ',', '.'); }}</td>
                        </tr>
                        <tr>
                            <td colspan="3"><span style="font-weight:bold">Sekolah / Perguruan Tinggi</span></td>
                        </tr>
                        <tr>
                            <td>Jenjang</td>
                            <td>:</td>
                            <td>{{ ucwords(strtolower($data->nama_pendidikan)) }}</td>
                        </tr>
                        <tr>
                            <td>Jurusan</td>
                            <td>:</td>
                            <td>{{ ucwords(strtolower($data->jenjang_pendidikan)) }}</td>
                        </tr>

                        <tr>
                            <td>Tahun Lulus</td>
                            <td>:</td>
                            <td>{{ $data->thn_lulus_pendidikan }}</td>
                        </tr>
                        <tr>
                            <td colspan="3"><span style="font-weight:bold">Diklat Struktural</span></td>
                        </tr>
                        <tr>
                            <td>Nama Diklat</td>
                            <td>:</td>
                            <td>{{ ucwords(strtolower($data->nama_diklat)) }}</td>
                        </tr>
                        <tr>
                            <td>Tangal</td>
                            <td>:</td>
                            <td>{{date('d-m-Y', strtotime($data->tgl_diklat))}}</td>
                        </tr>
                        <tr>
                            <td>Jam</td>
                            <td>:</td>
                            <td>{{ ucwords(strtolower($data->jam_diklat)) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@push('script')
<style>
    .blue {
        background: blue;
    }
</style>
<script type="text/javascript">
    $('.pendidikan-edit-trigger').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            'url': $(this).data('href'),
            success: function(d) {
                $.each(d, function(k, v) {
                    $('#pendidikan').find('#' + k).val(v);
                });
                $('#riwayatPendidikan').modal('show');
            }
        })
    });
    $("#riwayatPendidikan").on('hide.bs.modal', function() {
        $('#pendidikan').find('#id, #jenjang, #nama_sekolah, #tahun_masuk, #tahun_lulus').val('');
    });
    $('.pendidikan-edit-trigger').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            'url': $(this).data('href'),
            success: function(d) {
                $.each(d, function(k, v) {
                    $('#pendidikan').find('#' + k).val(v);
                });
                $('#riwayatPendidikan').modal('show');
            }
        })
    });
    $("#riwayatJabatan").on('hide.bs.modal', function() {
        $('#jabatan').find('#id, #nama_pangkat, #jabatan, #nomor_sk, #tanggal_sk, #tmt').val('');
    });
</script>
@endpush