<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\PersonBankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PersonBankAccountController extends Controller
{
    public function index(Person $person): JsonResponse
    {
        $accounts = $person->personalBankAccounts()->with(['bank', 'accountType'])->orderByDesc('is_default')->orderBy('id')->get();
        return response()->json(['data' => $accounts]);
    }

    public function store(Request $request, Person $person): JsonResponse
    {
        $data = $request->validate([
            'bank_id' => 'nullable|exists:banks,id',
            'account_type_id' => 'nullable|exists:account_types,id',
            'account_number' => 'nullable|string|max:50',
            'alias' => 'nullable|string|max:100',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        // Ensure boolean flags
        $data['is_default'] = (bool) ($data['is_default'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        // If setting default, unset others
        if ($data['is_default']) {
            $person->personalBankAccounts()->update(['is_default' => false]);
        }

        $account = $person->personalBankAccounts()->create($data);
        $account->load(['bank', 'accountType']);

        return response()->json(['success' => true, 'data' => $account]);
    }

    public function update(Request $request, Person $person, PersonBankAccount $account): JsonResponse
    {
        abort_unless($account->person_id === $person->id, 404);

        $data = $request->validate([
            'bank_id' => 'nullable|exists:banks,id',
            'account_type_id' => 'nullable|exists:account_types,id',
            'account_number' => 'nullable|string|max:50',
            'alias' => 'nullable|string|max:100',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        // Handle default switching
        if (array_key_exists('is_default', $data)) {
            $data['is_default'] = (bool) $data['is_default'];
            if ($data['is_default']) {
                $person->personalBankAccounts()->where('id', '!=', $account->id)->update(['is_default' => false]);
            }
        }

        $account->update($data);
        $account->load(['bank', 'accountType']);

        return response()->json(['success' => true, 'data' => $account]);
    }

    public function destroy(Person $person, PersonBankAccount $account): JsonResponse
    {
        abort_unless($account->person_id === $person->id, 404);

        $account->delete();
        return response()->json(['success' => true]);
    }
}
