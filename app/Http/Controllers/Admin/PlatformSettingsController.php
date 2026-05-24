<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlatformApiKeyRequest;
use App\Services\Grading\ClaudeApiClient;
use App\Services\Security\PlatformApiKeyVault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PlatformSettingsController extends Controller
{
    public function __construct(
        private readonly PlatformApiKeyVault $vault,
        private readonly ClaudeApiClient $claude,
    ) {
    }

    public function edit(): View
    {
        return view('admin.settings', [
            'hasKey' => $this->vault->hasKey(),
            'model' => $this->vault->getModel(),
        ]);
    }

    public function update(UpdatePlatformApiKeyRequest $request): RedirectResponse
    {
        $apiKey = $request->string('api_key')->toString();
        $model = $request->string('model')->toString() ?: $this->vault->getModel();

        try {
            $this->claude->ping($apiKey, $model);
        } catch (Throwable $e) {
            return back()->withErrors([
                'api_key' => 'La clé a été refusée par Anthropic: '.$e->getMessage(),
            ])->withInput($request->except('api_key'));
        }

        $this->vault->setKey($apiKey, $request->user()->id);
        $this->vault->setModel($model, $request->user()->id);

        return back()->with('success', 'Clé API enregistrée et testée avec succès.');
    }

    public function testCurrent(Request $request): RedirectResponse
    {
        $key = $this->vault->getDecryptedKey();
        if (! $key) {
            return back()->withErrors(['api_key' => 'Aucune clé configurée.']);
        }

        try {
            $this->claude->ping($key, $this->vault->getModel());
        } catch (Throwable $e) {
            return back()->withErrors(['api_key' => 'Test échoué: '.$e->getMessage()]);
        }

        return back()->with('success', 'La clé répond correctement.');
    }
}
