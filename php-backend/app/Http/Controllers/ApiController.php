<?php

namespace App\Http\Controllers;

use App\Support\LegacyCms;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    public function contact(Request $request)
    {
        $fullName = trim((string) $request->input('full_name', ''));
        $email = trim((string) $request->input('email', ''));
        $phone = LegacyCms::normalizeContactPhone((string) $request->input('phone', ''));
        $note = trim((string) $request->input('note', ''));

        if ($fullName === '' || strlen($fullName) > 160) {
            return response()->json(['error' => 'שם מלא אינו תקין.'], 422);
        }
        if (!LegacyCms::looksLikeEmail($email) || strlen($email) > 255) {
            return response()->json(['error' => 'כתובת אימייל אינה תקינה.'], 422);
        }
        if ($request->input('phone', '') !== '' && $phone === null) {
            return response()->json(['error' => 'מספר הטלפון אינו תקין.'], 422);
        }
        if (strlen($note) > 1000) {
            return response()->json(['error' => 'ההערה ארוכה מדי.'], 422);
        }

        DB::table('contact_inquiries')->insert([
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'note' => $note !== '' ? $note : null,
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function uploadImage(Request $request)
    {
        $image = $request->file('image');
        if (!$image instanceof UploadedFile || !$image->isValid()) {
            return response()->json(['error' => 'לא התקבל קובץ תמונה תקין.'], 422);
        }

        $extension = strtolower((string) ($image->getClientOriginalExtension() ?: $image->extension()));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        if (!in_array($extension, $allowedExtensions, true)) {
            return response()->json(['error' => 'אפשר להעלות רק תמונות מסוג JPG, PNG, GIF, WEBP, BMP או SVG.'], 422);
        }

        if (($image->getSize() ?? 0) > 25 * 1024 * 1024) {
            return response()->json(['error' => 'קובץ התמונה גדול מדי. המגבלה היא 25MB.'], 422);
        }

        try {
            [$name] = $this->storeUploadedFile($image, 'image', $extension !== '' ? $extension : 'jpg');
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'לא הצלחנו לשמור את התמונה על השרת.'], 500);
        }

        return response()->json([
            'url' => '/uploads/'.$name,
        ]);
    }

    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return response()->json(['error' => 'לא התקבל קובץ תקין.'], 422);
        }

        if (($file->getSize() ?? 0) > 50 * 1024 * 1024) {
            return response()->json(['error' => 'הקובץ גדול מדי. המגבלה היא 50MB.'], 422);
        }

        try {
            [$name, $originalName] = $this->storeUploadedFile($file, 'file');
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'לא הצלחנו לשמור את הקובץ על השרת.'], 500);
        }

        return response()->json([
            'url' => '/uploads/'.$name,
            'original_name' => $originalName,
        ]);
    }

    public function items(Request $request)
    {
        $pageName = trim((string) $request->query('page_name', 'farm_1')) ?: 'farm_1';

        if ($request->isMethod('get')) {
            return response()->json([
                'content' => LegacyCms::getPageContent($pageName, 'he'),
                'farm_cards' => LegacyCms::getFarmCards($pageName, 'he'),
                'team_members' => LegacyCms::getOrgTeamMembers($pageName, 'he'),
                'portfolio_projects' => LegacyCms::getPortfolioProjects($pageName, 'he'),
            ]);
        }

        return response()->json(['error' => 'Not implemented'], 501);
    }

    public function uploadedFile(Request $request, string $filename)
    {
        $safeName = basename($filename);
        $path = null;
        foreach ($this->uploadDirectories() as $directory) {
            $candidate = $directory.DIRECTORY_SEPARATOR.$safeName;
            if (is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if (!$path || !is_file($path)) {
            abort(404);
        }

        if ($request->boolean('download')) {
            return Response::download($path, $this->downloadNameFromStoredFile($safeName), [
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        return Response::file($path, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function storeUploadedFile(UploadedFile $file, string $fallbackBaseName, string $fallbackExtension = ''): array
    {
        $targetDir = $this->uploadDirectory();
        if (!$this->ensureWritableDirectory($targetDir)) {
            throw new \RuntimeException("Upload directory is not writable: {$targetDir}");
        }

        $originalName = trim((string) $file->getClientOriginalName());
        $baseName = pathinfo($originalName !== '' ? $originalName : $fallbackBaseName, PATHINFO_FILENAME);
        $baseName = trim((string) $baseName);
        $safeBaseName = Str::limit(Str::slug($baseName, '_'), 80, '');
        if ($safeBaseName === '') {
            $safeBaseName = $fallbackBaseName;
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = $fallbackExtension;
        }

        $name = Str::uuid()->toString().'_'.$safeBaseName.($ext !== '' ? '.'.$ext : '');
        $file->move($targetDir, $name);

        return [$name, $originalName !== '' ? $originalName : $name];
    }

    private function downloadNameFromStoredFile(string $storedName): string
    {
        $parts = explode('_', $storedName, 2);
        return $parts[1] ?? $storedName;
    }

    private function uploadDirectory(): string
    {
        foreach ($this->uploadDirectories() as $directory) {
            if ($this->ensureWritableDirectory($directory)) {
                return $directory;
            }
        }

        return $this->uploadDirectories()[0];
    }

    private function uploadDirectories(): array
    {
        if (app()->environment('testing')) {
            return [sys_get_temp_dir().DIRECTORY_SEPARATOR.'orot-hatera-testing-uploads'];
        }

        return [
            storage_path('app/public-uploads'),
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'orot-hatera-public-uploads',
        ];
    }

    private function ensureWritableDirectory(string $directory): bool
    {
        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            return false;
        }

        return is_writable($directory);
    }
}
