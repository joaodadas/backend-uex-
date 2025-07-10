<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleMapsController extends Controller
{
    public function getLatLongFromCep(Request $request)
    {
        $request->validate([
            'cep' => 'required|string'
        ]);

        $cep = $request->input('cep');
        $apiKey = env('GOOGLE_MAPS_API_KEY');

        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
            'address' => $cep,
            'key' => $apiKey,
        ]);

        if ($response->successful() && $response['status'] === 'OK') {
            $location = $response['results'][0]['geometry']['location'];

            return response()->json([
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'address' => $response['results'][0]['formatted_address']
            ]);
        }

        return response()->json([
            'error' => 'Não foi possível obter os dados do endereço.'
        ], 400);
    }
}
