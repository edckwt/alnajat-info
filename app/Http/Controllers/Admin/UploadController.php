<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/** مكتبة الملفات: رفع ملفات عامة (صور، PDF، Word، صوت، فيديو) لاستخدام روابطها في الأخبار. */
class UploadController extends Controller
{
    public const ACCEPT = 'image/*,audio/*,video/*,.pdf,.doc,.docx';

    public function index(): View
    {
        return view('admin.uploads.index', [
            'uploads' => Upload::with('user:id,name')->latest('id')->paginate(40),
            'accept' => self::ACCEPT,
        ]);
    }

    public function store(Request $request, ImageUploader $uploader): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,mp3,m4a,wav,ogg,mp4,webm,mov'],
        ], [], ['files' => 'الملفات', 'files.*' => 'الملف']);

        foreach ($request->file('files') as $file) {
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $size = $file->getSize();
            $mime = $file->getMimeType();
            $path = $uploader->store($file, 'upload');

            Upload::create([
                'title' => mb_substr($name, 0, 255),
                'path' => $path,
                'size' => $size,
                'extension' => pathinfo($path, PATHINFO_EXTENSION),
                'mime' => $mime,
                'user_id' => $request->user()->id,
            ]);
        }

        return back()->with('status', 'تم رفع '.count($request->file('files')).' ملف.');
    }

    public function destroy(Upload $upload): RedirectResponse
    {
        // يُحذف السجل فقط إن كان الملف خارج مجلد upload/؛ وإلا يُحذف الملف أيضاً.
        if (str_starts_with($upload->path, 'upload/')) {
            File::delete(public_path($upload->path));
        }

        $upload->delete();

        return back()->with('status', 'تم حذف الملف.');
    }
}
