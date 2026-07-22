<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Services\AccountDeletionService;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function destroy(DeleteAccountRequest $request, AccountDeletionService $accountDeletionService): JsonResponse
    {
        abort_if($request->user()->is_admin, 403, 'Un account amministratore non puo essere eliminato da questa API.');

        $accountDeletionService->delete($request->user());

        return response()->json([
            'message' => 'Account eliminato definitivamente.',
            'data' => [
                'deleted' => true,
            ],
        ]);
    }
}
