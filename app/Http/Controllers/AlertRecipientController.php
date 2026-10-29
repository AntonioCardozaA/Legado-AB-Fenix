<?php

namespace App\Http\Controllers;

use App\Models\AlertRecipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlertRecipientController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->hasRole(\App\Models\User::ROLE_ADMIN), 403);

        return view('admin.alert-recipients.index', [
            'recipients' => AlertRecipient::query()->orderByDesc('active')->orderBy('name')->orderBy('phone_number')->get(),
            'alertTypes' => AlertRecipient::alertTypeLabels(),
            'channels' => AlertRecipient::channelLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_ADMIN), 403);

        $data = $this->validated($request);
        $existing = AlertRecipient::query()
            ->where('phone_number', $data['phone_number'])
            ->where('channel', $data['channel'])
            ->first();

        if ($existing) {
            $existing->update($data);

            return back()->with('success', 'El destinatario ya existía y fue actualizado.');
        }

        AlertRecipient::create($data);

        return back()->with('success', 'Destinatario de alertas guardado correctamente.');
    }

    public function update(Request $request, AlertRecipient $alertRecipient): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_ADMIN), 403);

        $data = $this->validated($request);
        $duplicate = AlertRecipient::query()
            ->where('phone_number', $data['phone_number'])
            ->where('channel', $data['channel'])
            ->where('id', '!=', $alertRecipient->getKey())
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['phone_number' => 'Ya existe otro destinatario con ese teléfono y canal.'])
                ->withInput();
        }

        $alertRecipient->update($data);

        return back()->with('success', 'Destinatario actualizado correctamente.');
    }

    public function destroy(Request $request, AlertRecipient $alertRecipient): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_ADMIN), 403);

        $phone = $alertRecipient->phone_number;
        $alertRecipient->delete();
        Log::info('Destinatario global de alertas eliminado.', ['phone_number' => $phone]);

        return back()->with('success', 'Destinatario eliminado de las alertas futuras.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $types = array_keys(AlertRecipient::alertTypeLabels());

        return $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+() .-]{7,30}$/'],
            'channel' => ['required', Rule::in(array_keys(AlertRecipient::channelLabels()))],
            'alert_types' => ['required', 'array', 'min:1'],
            'alert_types.*' => ['string', Rule::in($types)],
            'active' => ['nullable', 'boolean'],
        ]) + [
            'active' => $request->boolean('active'),
        ];
    }
}
