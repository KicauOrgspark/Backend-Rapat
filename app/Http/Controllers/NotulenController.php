<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotulenRequest;
use App\Models\Rapat;
use Illuminate\Http\JsonResponse;

class NotulenController extends Controller
{
    public function GetNotulenByRapatID(Rapat $rapat): JsonResponse
    {
        $notulen = $rapat->notulen;

        if (!$notulen) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Notulen rapat tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'data'    => [
                'rapat_id' => $rapat->id,
                'isi_notulen' => $notulen->isi_notulen,
                'kesimpulan' => $notulen->kesimpulan,
            ],
        ], 200);
    }

    public function createNotulen(Rapat $rapat, NotulenRequest $request): JsonResponse
    {

        $rapat->notulen()->updateOrCreate(
            ['rapat_id' => $rapat->id],
            ['isi_notulen' => $request->input('isi_notulen')],
            ['kesimpulan' => $request->input('kesimpulan') ?? null]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Notulen rapat berhasil disimpan.',
            'data'    => [
                'rapat_id' => $rapat->id,
                'isi_notulen' => $request->input('isi_notulen'),
                'kesimpulan' => $request->input('kesimpulan') ?? null,
            ],
        ], 200);
    }
}
