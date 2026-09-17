<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesFamily;
use App\Models\AuditLog;
use App\Models\LifeEvent;
use App\Models\MediaItem;
use App\Models\MediaLink;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    use ResolvesFamily;

    public function index(Request $request): Response
    {
        $family = $this->currentFamily($request);
        $redact = $this->redactLiving($request, $family);

        $items = $family->mediaItems()
            ->with('links')
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $items->getCollection()->transform(function (MediaItem $item) use ($redact) {
            $hide = $redact && $item->file_type === 'photo';

            return [
                'id' => $item->id,
                'url' => $hide ? null : $item->url,
                'caption' => $item->caption,
                'file_type' => $item->file_type,
                'original_name' => $item->original_name,
                'created_at' => optional($item->created_at)?->toDateString(),
            ];
        });

        return Inertia::render('Media/Index', [
            'items' => $items,
            'people' => $family->people()->orderBy('first_name')->get()->map(fn (Person $p) => [
                'id' => $p->id,
                'name' => $p->full_name,
            ]),
            'canContribute' => $family->canContribute($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $family = $this->currentFamily($request);
        $this->authorizeContribute($request, $family);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,mp3,mp4,mov,wav'],
            'caption' => ['nullable', 'string', 'max:255'],
            'person_id' => ['nullable', 'integer'],
            'life_event_id' => ['nullable', 'integer'],
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();
        $type = str_starts_with((string) $mime, 'image/') ? 'photo'
            : (str_starts_with((string) $mime, 'audio/') ? 'audio'
            : (str_starts_with((string) $mime, 'video/') ? 'video' : 'document'));

        $path = $file->store('family-media/'.$family->id, 'local');

        $item = $family->mediaItems()->create([
            'file_path' => $path,
            'file_type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'caption' => $data['caption'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        if (! empty($data['person_id'])) {
            $person = Person::where('family_id', $family->id)->find($data['person_id']);
            if ($person) {
                MediaLink::create([
                    'media_item_id' => $item->id,
                    'linkable_type' => 'person',
                    'linkable_id' => $person->id,
                    'created_at' => now(),
                ]);
            }
        }

        if (! empty($data['life_event_id'])) {
            $event = LifeEvent::where('family_id', $family->id)->find($data['life_event_id']);
            if ($event) {
                MediaLink::create([
                    'media_item_id' => $item->id,
                    'linkable_type' => 'life_event',
                    'linkable_id' => $event->id,
                    'created_at' => now(),
                ]);
            }
        }

        AuditLog::record($family, $request->user(), 'created', $item, ['caption' => $item->caption]);

        return back()->with('success', 'File uploaded.');
    }

    public function serve(Request $request, string $path): StreamedResponse
    {
        $path = ltrim($path, '/');
        abort_unless(str_starts_with($path, 'family-media/'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $familyId = (int) explode('/', $path)[1] ?? 0;
        $user = $request->user();
        abort_unless($user && $user->families()->where('families.id', $familyId)->exists(), 403);

        return Storage::disk('local')->response($path);
    }
}
