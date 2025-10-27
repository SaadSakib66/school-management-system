<?php

namespace App\Support\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait BuildsSchoolHeader
{
    protected function schoolHeaderData(?int $forceSchoolId = null): array
    {
        // Resolve school id from several sources
        $schoolId =
            $forceSchoolId
            ?? (method_exists($this, 'currentSchoolId') ? $this->currentSchoolId() : null)
            ?? (Auth::check() ? Auth::user()->school_id : null)
            ?? session('school_id'); // optional extra session key you may use

        // As a last resort, pick the first school so the PDF doesn't explode
        if (!$schoolId) {
            $fallback = \App\Models\School::query()->orderBy('id')->value('id');
            if ($fallback) {
                $schoolId = (int) $fallback;
            } else {
                abort(403, 'No school context.');
            }
        }

        /** @var \App\Models\School $school */
        $school = \App\Models\School::findOrFail($schoolId);

        // ---- Logo to data URI ----
        $logoFile = $school->logo ?? $school->school_logo ?? $school->photo ?? null;
        $logoSrc  = null;

        if ($logoFile) {
            $normalized = ltrim(str_replace(['public/', 'storage/'], '', $logoFile), '/');
            $candidates = [$normalized, 'schools/'.basename($normalized), 'school_logos/'.basename($normalized)];
            foreach ($candidates as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $bin  = Storage::disk('public')->get($path);

                    $mime = 'image/png';
                    if (class_exists(\finfo::class)) {
                        $fi  = new \finfo(FILEINFO_MIME_TYPE);
                        $det = $fi->buffer($bin);
                        if ($det) $mime = $det;
                    } else {
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        $map = [
                            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif',
                            'webp'=>'image/webp','bmp'=>'image/bmp','svg'=>'image/svg+xml'
                        ];
                        if (isset($map[$ext])) $mime = $map[$ext];
                    }

                    $logoSrc = 'data:'.$mime.';base64,'.base64_encode($bin);
                    break;
                }
            }
        }

        // ---- EIIN ----
        $eiin = null;
        foreach (['eiin_num', 'eiin', 'eiin_code', 'eiin_no'] as $field) {
            if (isset($school->{$field})) {
                $val = trim((string)$school->{$field});
                if ($val !== '') { $eiin = $val; break; }
            }
        }

        $website = $school->website ?? $school->website_url ?? $school->domain ?? null;
        if (is_string($website)) $website = trim($website);

        return [
            'school'        => $school,
            'schoolLogoSrc' => $logoSrc,
            'schoolPrint'   => [
                'name'    => $school->name ?? $school->short_name ?? 'Unknown School',
                'eiin'    => $eiin,
                'address' => $school->address ?? $school->full_address ?? null,
                'website' => $website,
            ],
        ];
    }
}
