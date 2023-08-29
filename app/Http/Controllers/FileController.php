<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function uploadFile(Request $request)
    {
        // Validate and store the uploaded file
        $this->validate($request, [
            'file' => 'required|file|mimes:pdf,docx',
        ]);

        $uploadedFile = $request->file('file');
        $storedFilePath = $uploadedFile->store('uploads');

        // Store file data in the database
        $file = new File([
            'file_name' => $storedFilePath,
            // Add other relevant data fields
        ]);
        $file->save();

        return response()->json(['message' => 'File uploaded successfully']);
    }public function getFiles()
    {
        $files = File::all(); // Assuming you have a "File" model

        return response()->json($files);
    }
}
