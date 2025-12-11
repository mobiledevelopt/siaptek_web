<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SaveFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $file; // Path of the file on the server
    protected $folder; // Path where the file should be saved
    protected $fileName;
    protected $tempFolder;
    /**
     * Create a new job instance.
     */
    public function __construct($file, $folder, $fileName,$tempFolder)
    {
        $this->file = $file;
        $this->folder = $folder;
        $this->fileName = $fileName;
        $this->tempFolder = $tempFolder;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ini_set('memory_limit', '512M');
        Storage::disk('public')->putFileAs($this->folder, new \Illuminate\Http\File(storage_path("app/public/".$this->file)), $this->fileName);
        // Storage::disk('public')->delete($this->$tempFolder.'/'.$this->fileName);
    }
}
