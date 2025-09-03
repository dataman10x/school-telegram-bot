<?php
namespace App\Classes;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class MediaHandler
{
    public $siteDisc;
    public $mainDisc;

    public function __construct()
    {
        $this->siteDisc = env('STORAGE_DISC_SITE');
        $this->mainDisc = env('STORAGE_DISC_MAIN');
    }

    public function publicPath(string $name, $disc = null)
    {
        $path = null;
        $setDisc = is_null($disc)? $this->siteDisc: $disc;

        try {
            if(Storage::disk($setDisc)->exists("$name")) {
                $path = asset("storage/$setDisc/$name");
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $path;
    }

    public function localPath(string $name, $disc = null)
    {
        $path = null;
        $setDisc = is_null($disc)? $this->siteDisc: $disc;

        try {
            if(Storage::disk($setDisc)->exists("$name")) {
                // $path = public_path("storage/$setDisc/$name");
                $path = storage_path("app/public/$setDisc/$name");
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $path;
    }

    public function saveMedia(string $name, $content, $disc = null)
    {
        $setDisc = is_null($disc)? $this->siteDisc: $disc;
        $media = Storage::disk($setDisc)->put($name, $content);
        return $media;
    }

    public function getSize(string $name, $disc = null)
    {
        $size = 0;
        $setDisc = is_null($disc)? $this->siteDisc: $disc;

        try {
            if(Storage::disk($setDisc)->exists("$name")) {
                $size = Storage::disk($setDisc)->size($name);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $size;
    }

    public function getLastModified(string $name, $disc = null)
    {
        $lastmodified = null;
        $setDisc = is_null($disc)? $this->siteDisc: $disc;

        try {
            if(Storage::disk($setDisc)->exists("$name")) {
                $getUnixTime = Storage::disk($setDisc)->lastModified($name);
                $lastmodified = date('Y-m-d H:i:s', $getUnixTime);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $lastmodified;
    }

    public function getMime(string $name, $disc = null)
    {
        $mimeType = null;
        $setDisc = is_null($disc)? $this->siteDisc: $disc;

        try {
            if(Storage::disk($setDisc)->exists("$name")) {
                $path = $this->localPath($name, $setDisc);
                $mimeType = File::mimeType($path);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $mimeType;
    }

    public function download(string $name, string $newName = null, $disc = null)
    {
        $content = false;
        $setDisc = is_null($disc)? $this->siteDisc: $disc;
        $setNewName = is_null($newName)? $name: $newName;

        $headers = [
            'Content-Type' => $this->getMime($name, $disc),
            'Content-Disposition' => 'attachment; filename="' .$setNewName .'"'
        ];

        try {
            if(Storage::disk($setDisc)->exists("$name")) {
                $path = $this->localPath($name, $setDisc);
                $content = File::get($path);
                return Response::make($content, 200, $headers);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function deleteFile(string $name, $disc = null)
    {
        $success = false;
        $setDisc = is_null($disc)? $this->siteDisc: $disc;

        try {
            if(Storage::disk($setDisc)->exists("$name")) {
                $success = Storage::disk($setDisc)->delete($name);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $success;
    }

    public function deleteFiles(array $paths, $disc = null)
    {
        $success = false;
        $setDisc = is_null($disc)? $this->siteDisc: $disc;

        try {
            Storage::disk($setDisc)->delete($paths);
            $success = true;
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $success;
    }

    public function getFiles(string $path = '', $disc = null)
    {
        $files = [];
        $setDisc = is_null($disc)? $this->siteDisc: $disc;

        try {
            $files = Storage::disk($setDisc)->files($path);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $files;
    }

    public function getFilesWithDir(string $path = '', $disc = null)
    {
        $files = [];
        $setDisc = is_null($disc)? $this->siteDisc: $disc;

        try {
            $files = Storage::disk($setDisc)->allFiles($path);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $files;
    }

}
