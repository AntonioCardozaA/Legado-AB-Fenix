<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminVideoRequest;
use App\Models\AdminVideo;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminVideoController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $videos = AdminVideo::query()
            ->with('uploadedBy')
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->paginate(12);

        return view('admin.videos.index', compact('videos'));
    }

    public function store(StoreAdminVideoRequest $request): RedirectResponse
    {
        $file = $request->file('video');
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = (string) Str::uuid() . ($extension !== '' ? '.' . $extension : '');
        $storagePath = $file->storeAs('admin-videos', $filename, 'local');

        if (!is_string($storagePath) || $storagePath === '') {
            return back()
                ->withInput()
                ->withErrors(['video' => 'No se pudo guardar el video. Verifica el almacenamiento del servidor.']);
        }

        AdminVideo::create([
            'title' => $request->string('title')->trim()->toString(),
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
        ]);

        return redirect()
            ->route('admin.videos.index')
            ->with('success', 'Video cargado correctamente.');
    }

    public function stream(Request $request, AdminVideo $video)
    {
        $this->ensureAdmin($request);

        $disk = Storage::disk($video->storage_disk ?: 'local');
        abort_unless($disk->exists($video->storage_path), 404);

        $filename = addcslashes($video->original_filename ?: 'video', "\\\"");

        return response()->file($disk->path($video->storage_path), [
            'Content-Type' => $video->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    public function destroy(Request $request, AdminVideo $video): RedirectResponse
    {
        $this->ensureAdmin($request);

        Storage::disk($video->storage_disk ?: 'local')->delete($video->storage_path);
        $video->delete();

        return redirect()
            ->route('admin.videos.index')
            ->with('success', 'Video eliminado correctamente.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole(User::ROLE_ADMIN),
            403,
            'Solo los administradores pueden gestionar videos.'
        );
    }
}
