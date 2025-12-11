<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Image;
use Illuminate\Support\Facades\Log;

class SaveImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

     // Set the number of retry attempts
    public $tries = 5;

    // Set the delay between retries (optional)
    public $retryAfter = 60; // Retry after 60 seconds
    public $timeout = 120;
    
    protected $pathFolder;
    protected $path;
    protected $imageName;
    protected $tempFolder;

    /**
     * Create a new job instance.
     */
    public function __construct($path, $pathFolder,$imageName,$tempFolder)
    {
        $this->path = $path;
        $this->pathFolder = $pathFolder;
        $this->imageName = $imageName;
        $this->tempFolder = $tempFolder;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ini_set('memory_limit', '512M');
        try {
            $img = Image::make(storage_path("app/public/{$this->path}"));
            $img->orientate()->resize(400, 400, function ($constraint) {
                $constraint->aspectRatio();
            });
            
            Storage::disk('public')->put($this->pathFolder, $img->stream());
            
            Log::info("Image move compress successfully: {$this->imageName}");

        } catch (\Exception $e) {
            Log::error("Error processing image move compress: {$this->imageName}. Error: {$e->getMessage()} .Path: ". storage_path("app/public/{$this->path}"));
        }
            
        
        //delete temp foto file
        // Storage::disk('public')->delete($this->pathFolder.'/'.$this->imageName);
        // Log::info("Storage delete " . $storage);
        
    }
}
