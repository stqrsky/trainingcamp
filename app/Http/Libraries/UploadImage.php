<?php

namespace App\Http\Libraries;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\Image;

class UploadImage
{
    public static function uploadProfilePicture($file, $user)
    {
        $fileExtension = $file->extension();
        $filename = $user->id . '.' . $fileExtension;
        $file_path = 'images/profile';
        $storage_path = storage_path() . "/app/$file_path/$filename";
        $public_path = "$file_path/$filename";
        try {
            $detail = $user->userDetail ?? $user->userDetail()->create([]);
            $path = $file->storeAs($file_path, $filename);
            $img = (new ImageManager(new Driver()))->read($storage_path);
            $img->cover(400, 400);
            $img->save($storage_path);
            if ($detail->image_id) {
                Image::find($detail->image_id)->update([
                    'file_path' => "storage/app/$file_path/$filename",
                    'file_name' => $public_path
                ]);
            } else {
                $images = Image::create([
                    'file_path' => "storage/app/$file_path/$filename",
                    'file_name' => $public_path,
                    'status' => 1
                ]);
            }
            if (!$detail->image_id) {
                $detail->update([
                    'image_id' => $images->id
                ]);
            }
        } catch (\Throwable $e) {
            return [
                'error' => self::errorMessage($e)
            ];
        }
    }

    public static function uploadNotificationPicture($file, $notification)
    {
        $fileExtension = $file->extension();
        $filename = $notification->id . '.' . $fileExtension;
        $file_path = 'images/notification';
        $storage_path = storage_path() . "/app/$file_path/$filename";
        $public_path = "$file_path/$filename";
        try {
            $path = $file->storeAs($file_path, $filename);
            if ($notification->image_id) {
                Image::find($notification->image_id)->update([
                    'file_path' => "storage/app/$file_path/$filename",
                    'file_name' => $public_path
                ]);
            } else {
                $images = Image::create([
                    'file_path' => "storage/app/$file_path/$filename",
                    'file_name' => $public_path,
                    'status' => 1
                ]);
            }
            if (!$notification->image_id) {
                $notification->update([
                    'image_id' => $images->id
                ]);
            }
        } catch (\Throwable $e) {
            return [
                'error' => self::errorMessage($e)
            ];
        }
    }

    private static function errorMessage(\Throwable $e): string
    {
        report($e);

        return config('app.debug') ? $e->getMessage() : 'Image upload failed. Please try another file.';
    }
}
