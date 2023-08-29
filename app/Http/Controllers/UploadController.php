<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;

class UploadController extends Controller
{

    public function index(Request $request)
    {
        $files = File::all(); // Retrieve files from the database
        return view('upload')->with('files', $files); //

    }public function fetch()
    {
        $files = File::all(); // Retrieve all records from the 'files' table
        return response()->json(['data' => $files]);
    }
   public function store(Request $request){
    $message=[
        'required'=>'Please select file to upload',
    ];
    $this->validate($request,[
        'file'=>'required',
    ],$message);
    foreach ($request->file('file') as $file) {
        $filename = time() . '_' . $file->getClientOriginalName();
        $filesize = $file->getSize();
        $file->storeAs('public/', $filename);

        File::create([
            'name' => $filename,
            'size' => $filesize,
            'location' => 'storage/' . $filename,
            'context' => $request->input('context'), // Add context
        ]);
    }

    return redirect('/index')->with('success','File/s Uploaded Successfully');
   }
}

