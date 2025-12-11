@extends('layouts.pdf')

@section('content')
<div class="conteiner-fluid">
    <h3 class="title center">Presensi Pegawai </h3>
    <p class="label center">{{$pegawai->nip}} {{$pegawai->name}} {{$pegawai->dinas->name}}</p>
    <p class="label center">{{ $periode }}</p>

    <table class="table">
        <thead>
            <tr>
                <th class="center">NO</th>
                <th class="center">Tanggal</th>
                <th class="center">Status Masuk</th>
                <th class="center">Jam Masuk</th>
                <th class="center">Status Pulang</th>
                <th class="center">Jam Pulang</th>
                <th class="center">Apel Pagi</th>
                <th class="center">Apel Sore</th>
                <th class="center">Status</th>
                <th class="center">Potongan TPP</th>
                <th class="center">TPP Diterima</th>
            </tr>
        </thead>
        <tbody>
            @php $i=1;$tot_pot=0;$tot_tpp=0; @endphp
            @foreach ($presensi as $item)
            {{ $tot_pot +=  $item->total_potongan_tpp; }}
            {{ $tot_tpp += $item->tpp_diterima; }}

            <tr>
                <td class="center">{{ $i++ }}</td>
                <td>{{ date('d-m-Y', strtotime($item->date_attendance)) }}</td>
                <td class="center">{{$item->status_masuk }}</td>
                <td class="center">{{$item->incoming_time }}</td>
                <td class="center">{{$item->status_pulang }}</td>
                <td class="center">{{$item->outgoing_time }}</td>
                <td class="center">{{$item->status_apel_pagi }}</td>
                <td class="center">{{$item->status_apel_sore }}</td>
                <td class="center">{{$item->status }}</td>
                <td class="center">{{number_format($item->total_potongan_tpp) }}</td>
                <td class="center">{{number_format($item->tpp_diterima) }}</td>

            </tr>
            <tr>
                <td class="center" style="padding: 5px; vertical-align: top;">

                </td>
                <td class="center" style="padding: 5px; vertical-align: top;" colspan=2>
                    Foto Presensi Masuk
                </td>
                <td class="center" style="padding: 5px; vertical-align: top;" colspan=2>
                    Foto Presensi Pulang
                </td>
                <td class="center" style="padding: 5px; vertical-align: top;" colspan=2>
                    Foto Apel Pagi
                </td>
                <td class="center" style="padding: 5px; vertical-align: top;" colspan=2>
                    Foto Apel Sore
                </td>
                <td class="center" style="padding: 5px; vertical-align: top;"></td>
                <td class="center" style="padding: 5px; vertical-align: top;"></td>
            </tr>
            <tr>
                <td class="center" style="padding: 5px; vertical-align: top;">

                </td>
                <td class="center" style="padding: 5px; vertical-align: top;" colspan=2>
                    <img src="{{ is_file(public_path('storage/'.$item->foto_absen_masuk_path)) ? public_path('storage/'.$item->foto_absen_masuk_path) : public_path('images/no_image.png') }}" width="80px" height="80px">
                </td>
                <td class="center" style="padding: 5px; vertical-align: top;" colspan=2>
                    <img src="{{ is_file(public_path('storage/'.$item->foto_absen_pulang_path)) ? public_path('storage/'.$item->foto_absen_pulang_path) : public_path('images/no_image.png') }}" width="80px" height="80px">
                </td>
                <td class="center" style="padding: 5px; vertical-align: top;" colspan=2>
                    <img src="{{ is_file(public_path('storage/'.$item->foto_apel_pagi_path)) ? public_path('storage/'.$item->foto_apel_pagi_path) : public_path('images/no_image.png') }}" width="80px" height="80px">
                </td>
                <td class="center" style="padding: 5px; vertical-align: top;" colspan=2>
                    <img src="{{ is_file(public_path('storage/'.$item->foto_apel_sore_path)) ? public_path('storage/'.$item->foto_apel_sore_path) : public_path('images/no_image.png') }}" width="80px" height="80px">
                </td>
                <td class="center" style="padding: 5px; vertical-align: top;"></td>
                <td class="center" style="padding: 5px; vertical-align: top;"></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="center" colspan=9><strong>Total</strong></td>
                <td class="center"><strong>@php echo number_format($tot_pot); @endphp</strong></td>
                <td class="center"><strong>@php echo number_format($tot_tpp); @endphp</strong></td>
            </tr>

        </tfoot>
    </table>
</div>
@stop