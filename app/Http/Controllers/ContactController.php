<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class ContactController extends Controller
{

    public function index()
    {
        $contacts = Contact::where('user_id', Auth::id())->get();
        return response()->json($contacts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'cep' => 'required|string|size:8', // Exatamente 8 dígitos
        ]);

        // Requisição ao ViaCEP
        $response = Http::get("https://viacep.com.br/ws/{$validated['cep']}/json/");

        if ($response->failed() || isset($response['erro'])) {
            return response()->json(['message' => 'CEP inválido ou não encontrado.'], 422);
        }

        $viaCepData = $response->json();

        // Monta o endereço completo
        $validated['address'] = "{$viaCepData['logradouro']}, {$viaCepData['bairro']} - {$viaCepData['localidade']}/{$viaCepData['uf']}";

        // Vincula ao usuário logado
        $validated['user_id'] = Auth::id();

        $contact = Contact::create($validated);

        return response()->json($contact, 201);
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

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string|max:255',
        ]);

        $contact->update($validated);

        return response()->json($contact);
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
