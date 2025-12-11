@extends('layouts.admin')

@section('content')
    <form method="post" action="{{ Route('sekolah.save') }}">
        @csrf
        <div class="row justify-content-center">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h4 class="card-title">{{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Provinsi</label>
                            <select name="province_id" class="form-select" required="true">
                                @foreach ($ref['provinsi'] as $item)
                                    @if ($item->id == @$edit['province_id'])
                                        <option value="{{ $item->id }}" selected>{{ $item->name }}</option>
                                    @else
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Kabupaten/Kota</label>
                            <select name="regency_id" class="form-select" required="true">
                                @foreach ($ref['kabupaten'] as $item)
                                    @if ($item->id == @$edit['regency_id'])
                                        <option value="{{ $item->id }}" selected>{{ $item->name }}</option>
                                    @else
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Kecamatan</label>
                            <select name="district_id" class="form-select" required="true">
                                @foreach ($ref['kecamatan'] as $item)
                                    @if ($item->id == @$edit['district_id'])
                                        <option value="{{ $item->id }}" selected>{{ $item->name }}</option>
                                    @else
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Desa/Kelurahan</label>
                            <select name="village_id" class="form-select" required="true">
                                @foreach ($ref['desa'] as $item)
                                    @if ($item->id == @$edit['village_id'])
                                        <option value="{{ $item->id }}" selected>{{ $item->name }}</option>
                                    @else
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Kode Pos</label>
                            <input type="text" name="postal_id" value="{{ @$edit['postal_id'] }}" required="true"
                                class="form-control" autocomplete="off" />
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Latitude</label>
                            <input type="text" name="latitude" value="{{ @$edit['latitude'] }}" required="true"
                                class="form-control" autocomplete="off" />
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Longitude</label>
                            <input type="text" name="longitude" value="{{ @$edit['longitude'] }}" required="true"
                                class="form-control" autocomplete="off" />
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Profile</h4>
                        </div>
                        <div class="card-action">
                            <a href="{{ Route('guru.index') }}" class="btn btn-sm btn-primary"role="button">Kembali</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="form-group col col-md-6">
                                <label class="form-label mb-0">Kode Sekolah</label>
                                <input type="text" name="id" value="{{ @$edit['id'] }}" required="true"
                                    class="form-control" autocomplete="off" />
                            </div>
                            <div class="form-group col col-md-6">
                                <label class="form-label mb-0">Nama Sekolah</label>
                                <input type="text" name="school_name" value="{{ @$edit['school_name'] }}"
                                    required="true" class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="form-group col col-md-4">
                                <label class="form-label mb-0">NPSN</label>
                                <input type="text" name="npsn" required="true" class="form-control" autocomplete="off"
                                    value="{{ @$edit['npsn'] }}" />
                            </div>
                            <div class="form-group col col-md-4">
                                <label class="form-label mb-0">Bentuk Pendidikan</label>
                                <input type="text" value="{{ @$edit['bentuk_pendidikan'] }}" name="bentuk_pendidikan"
                                    class="form-control" autocomplete="off" />
                            </div>
                            <div class="form-group col col-md-4">
                                <label class="form-label mb-0">Status Kepemilikan</label>
                                <input type="text" name="status_kepemilikan" required class="form-control"
                                    autocomplete="off" value="{{ @$edit['status_kepemilikan'] }}" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="form-group col col-md-6">
                                <label class="form-label mb-0">SK Pendirian Sekolah</label>
                                <input type="text" value="{{ @$edit['sk_pendirian_sekolah'] }}"
                                    name="sk_pendirian_sekolah" class="form-control" autocomplete="off" />
                            </div>
                            <div class="form-group col col-md-6">
                                <label class="form-label mb-0">Tanggal SK Pendirian</label>
                                <input type="date" name="tanggal_sk_pendirian" required class="form-control"
                                    autocomplete="off" value="{{ @$edit['tanggal_sk_pendirian'] }}" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="form-group col col-md-6">
                                <label class="form-label mb-0">SK Izin Operasional</label>
                                <input type="text" value="{{ @$edit['sk_izin_operasional'] }}"
                                    name="sk_izin_operasional" class="form-control" autocomplete="off" />
                            </div>
                            <div class="form-group col col-md-6">
                                <label class="form-label mb-0">Tanggal SK Izin Operasional</label>
                                <input type="date" name="tanggal_sk_izin_operasional" required class="form-control"
                                    autocomplete="off" value="{{ @$edit['tanggal_sk_izin_operasional'] }}" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="form-group col col-md-4">
                                <label class="form-label mb-0">Jumlah Rombongan Belajar</label>
                                <input type="number" value="{{ @$edit['jumlah_rombongan_belajar'] }}"
                                    name="jumlah_rombongan_belajar" class="form-control" autocomplete="off" />
                            </div>
                            <div class="form-group col col-md-4">
                                <label class="form-label mb-0">Jumlah Siswa</label>
                                <input type="number" name="jumlah_siswa" required class="form-control"
                                    autocomplete="off" value="{{ @$edit['jumlah_siswa'] }}" />
                            </div>
                            <div class="form-group col col-md-4">
                                <label class="form-label mb-0">Jumlah Guru</label>
                                <input type="number" name="jumlah_guru" required class="form-control"
                                    autocomplete="off" value="{{ @$edit['jumlah_guru'] }}" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="form-group col">
                                <label class="form-label mb-0">Alamat Sekolah</label>
                                <textarea name="alamat_sekolah" class="form-control" rows="3">{{ @$edit['alamat_sekolah'] }}</textarea>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="form-group col col-md-3">
                                <label class="form-label mb-0">RT</label>
                                <input type="text" value="{{ @$edit['rt'] }}" name="rt" class="form-control"
                                    autocomplete="off" />
                            </div>
                            <div class="form-group col col-md-3">
                                <label class="form-label mb-0">RW</label>
                                <input type="text" name="rw" required class="form-control" autocomplete="off"
                                    value="{{ @$edit['rw'] }}" />
                            </div>
                            <div class="form-group col col-md-6">
                                <label class="form-label mb-0">Dusun</label>
                                <input type="text" name="dusun" required class="form-control" autocomplete="off"
                                    value="{{ @$edit['dusun'] }}" />
                            </div>
                        </div> <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop
