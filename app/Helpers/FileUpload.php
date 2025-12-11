<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Image;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class FileUpload
{
  public static function uploadImage($file, $dimensions, $location = 'storage', $old_file = NULL, $fileName = NULL)
  {
    if (request()->hasFile($file)) {
      if ($location == 'storage') {
        $image_path = storage_path('app/public/images');
        $file = request()->file($file);
        $ext = $file->getClientOriginalExtension();
        if (!$fileName) {
          $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . Carbon::now()->timestamp . rand(1, 9999)) . '.' . $ext;
        }

        if (!File::isDirectory("$image_path/original")) {
          File::makeDirectory("$image_path/original", 0755, true);
        }

        Storage::disk('public')->delete("images/original/$old_file");

        Image::make($file)->save($image_path . '/original/' . $fileName);
        foreach ($dimensions as $row) {
          //          $canvas = Image::canvas($row[0], $row[1]);
          //          $resizeImage = Image::make($file)->resize($row[0], $row[1], function ($constraint) {
          //            $constraint->aspectRatio();
          //          });
          $resizeImage = Image::make($file)->fit($row[0], $row[1]);
          if (!File::isDirectory($image_path . '/' . $row[2])) {
            File::makeDirectory($image_path . '/' . $row[2], 0755, true);
          }
          Storage::disk('public')->delete("images/$row[2]/$old_file");
          //          $canvas->insert($resizeImage, 'center');
          $resizeImage->save($image_path . '/' . $row[2] . '/' . $fileName);
        }
      } else {
        $image_path = public_path('images');
        $file = request()->file($file);
        $ext = $file->getClientOriginalExtension();
        if (!$fileName) {
          $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . Carbon::now()->timestamp . rand(1, 9999)) . '.' . $ext;
        } else {
          $fileName .= "." . $ext;
        }
        if (!File::isDirectory("$image_path/original")) {
          File::makeDirectory("$image_path/original", 0755, true);
        }
        Storage::disk('public')->delete("images/original/$old_file");
        Image::make($file)->save($image_path . '/original/' . $fileName);

        foreach ($dimensions as $row) {
          $canvas = Image::canvas($row[0], $row[1]);
          $resizeImage = Image::make($file)->resize($row[0], $row[1], function ($constraint) {
            $constraint->aspectRatio();
          });
          if (!File::isDirectory($image_path . '/' . $row[2])) {
            File::makeDirectory($image_path . '/' . $row[2], 0755, true);
          }
          Storage::disk('public')->delete("images/$row[2]/$old_file");
          $canvas->insert($resizeImage, 'center');
          $canvas->save($image_path . '/' . $row[2] . '/' . $fileName);
        }
      }
      return $fileName;
    }
  }

  public static function upload($file, $dimensions, $path = 'images', $location = 'storage', $old_file = NULL, $fileName = NULL)
  {
    // $image_path = storage_path($path);
    // dd(File::isDirectory("/original"));
    // dd($image_path);
    if (request()->hasFile($file)) {
      if ($location == 'storage') {
        $image_path = storage_path($path);
        $file = request()->file($file);
        $ext = $file->getClientOriginalExtension();
        if (!$fileName) {
          $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . Carbon::now()->timestamp . rand(1, 9999)) . '.' . $ext;
        }

        // dd(File::isDirectory("$image_path/original"));
        if (!File::isDirectory("$image_path/original")) {
          File::makeDirectory("$image_path/original", 0755, true);
        }

        Storage::disk('public')->delete("$image_path/original/$old_file");
        Image::make($file)->save($image_path . '/original/' . $fileName);
        foreach ($dimensions as $row) {
          $resizeImage = Image::make($file)->fit($row[0], $row[1]);
          if (!File::isDirectory($image_path . '/' . $row[2])) {
            File::makeDirectory($image_path . '/' . $row[2], 0755, true);
          }
          Storage::disk('public')->delete("$image_path/$row[2]/$old_file");
          $resizeImage->save($image_path . '/' . $row[2] . '/' . $fileName);
        }
      } else {
        $image_path = public_path('images');
        $file = request()->file($file);
        $ext = $file->getClientOriginalExtension();
        if (!$fileName) {
          $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . Carbon::now()->timestamp . rand(1, 9999)) . '.' . $ext;
        } else {
          $fileName .= "." . $ext;
        }
        if (!File::isDirectory("$image_path/original")) {
          File::makeDirectory("$image_path/original", 0755, true);
        }
        Storage::disk('public')->delete("images/original/$old_file");
        Image::make($file)->save($image_path . '/original/' . $fileName);

        foreach ($dimensions as $row) {
          $canvas = Image::canvas($row[0], $row[1]);
          $resizeImage = Image::make($file)->resize($row[0], $row[1], function ($constraint) {
            $constraint->aspectRatio();
          });
          if (!File::isDirectory($image_path . '/' . $row[2])) {
            File::makeDirectory($image_path . '/' . $row[2], 0755, true);
          }
          Storage::disk('public')->delete("images/$row[2]/$old_file");
          $canvas->insert($resizeImage, 'center');
          $canvas->save($image_path . '/' . $row[2] . '/' . $fileName);
        }
      }
      return $fileName;
    }
  }

  public function UploadFile(UploadedFile $file, $folder = null, $disk = 'public', $filename = null)
  {
    $FileName = auth()->id() . '_' . time();
    return $file->storeAs(
      $folder,
      $FileName . "." . $file->getClientOriginalExtension(),
      $disk
    );
  }

  public function deleteFile($path, $disk = 'public')
  {
    Storage::disk($disk)->delete($path);
  }
}
