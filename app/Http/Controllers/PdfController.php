<?php

namespace App\Http\Controllers;

use App\Models\UploadModel;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function upload(Request $request)
   {

    $request->validate([
        'file' => 'required|mimes:pdf|max:2048', // Validate file type and size
    ]);

    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('uploads', $fileName, 'public');

        $uploadedFile = new UploadModel();
        $uploadedFile->file_name = $fileName;
        $uploadedFile->file_path = $filePath;
        $uploadedFile->save();

        return response()->json(['message' => 'File uploaded and stored in the database'], 201);
    }

    return response()->json(['message' => 'No file uploaded'], 400);
    }
}
