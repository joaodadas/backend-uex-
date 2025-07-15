<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function index()
    {
        $contacts = Contact::where('user_id', Auth::id())->get();
        return response()->json($contacts);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:contacts,email',
                'phone' => 'nullable|string',
                'cep' => 'required|string|size:8',
                'tipo_contrato' => 'nullable|string',
                'unidade' => 'nullable|string',
            ]);

            // VIA CEP
            $cepResponse = Http::get("https://viacep.com.br/ws/{$validated['cep']}/json/");
            $cepData = $cepResponse->json();

            if (!empty($cepData['erro'])) {
                Log::error('Erro no ViaCEP', ['cep' => $validated['cep'], 'response' => $cepData]);
                return response()->json(['message' => 'CEP inválido ou não encontrado.'], 422);
            }

            $validated['address'] = "{$cepData['logradouro']}, {$cepData['bairro']} - {$cepData['localidade']}/{$cepData['uf']}";

            // GOOGLE MAPS
            $fullAddress = $validated['address'];
            Log::info('Endereço enviado ao Google Maps:', ['address' => $fullAddress]);

            $geoResponse = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
                'address' => $fullAddress,
                'key' => env('GOOGLE_MAPS_API_KEY'),
            ]);

            $geoData = $geoResponse->json();
            Log::info('Resposta do Google Maps:', $geoData);

            if (
                $geoResponse->successful() &&
                isset($geoData['results'][0]['geometry']['location']['lat']) &&
                isset($geoData['results'][0]['geometry']['location']['lng'])
            ) {
                $location = $geoData['results'][0]['geometry']['location'];
                $validated['latitude'] = $location['lat'];
                $validated['longitude'] = $location['lng'];
            } else {
                Log::warning('Coordenadas não encontradas no Google Maps', [
                    'address' => $fullAddress,
                    'response' => $geoData
                ]);
            }

            $validated['user_id'] = Auth::id();

            $contact = Contact::create($validated);
            Log::info('Contato salvo com sucesso:', $contact->toArray());

            return response()->json($contact, 201);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar contato', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao processar o contato.'], 500);
        }
    }

    public function show($id)
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json(['message' => 'Contato não encontrado'], 404);
        }

        return response()->json($contact);
    }

    public function update(Request $request, $id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json(['message' => 'Contato não encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|email|unique:contacts,email,' . $id,
                'phone' => 'sometimes|string|max:20',
                'cep' => 'sometimes|string|size:8',
                'tipo_contrato' => 'nullable|string',
                'unidade' => 'nullable|string',
            ]);

            if (isset($validated['cep'])) {
                $cepResponse = Http::get("https://viacep.com.br/ws/{$validated['cep']}/json/");
                $cepData = $cepResponse->json();

                if (!empty($cepData['erro'])) {
                    return response()->json(['message' => 'CEP inválido ou não encontrado.'], 422);
                }

                $validated['address'] = "{$cepData['logradouro']}, {$cepData['bairro']} - {$cepData['localidade']}/{$cepData['uf']}";

                $geoResponse = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
                    'address' => $validated['address'],
                    'key' => env('GOOGLE_MAPS_API_KEY'),
                ]);

                $geoData = $geoResponse->json();

                if (
                    $geoResponse->successful() &&
                    isset($geoData['results'][0]['geometry']['location']['lat']) &&
                    isset($geoData['results'][0]['geometry']['location']['lng'])
                ) {
                    $location = $geoData['results'][0]['geometry']['location'];
                    $validated['latitude'] = $location['lat'];
                    $validated['longitude'] = $location['lng'];
                }
            }

            $contact->update($validated);

            return response()->json($contact);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar contato', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro interno ao atualizar o contato.'], 500);
        }
    }

    public function destroy($id)
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json(['message' => 'Contato não encontrado'], 404);
        }

        $contact->delete();

        return response()->json(['message' => 'Contato deletado com sucesso']);
    }
}
