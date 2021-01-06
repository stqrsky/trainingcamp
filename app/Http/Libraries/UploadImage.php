<?php

namespace App\Http\Libraries;

use Intervention\Image\ImageManager;
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
            $path = $file->storeAs($file_path, $filename);
            $image = new ImageManager();
            $img = $image->make($path);
            $img->fit(400);
            $img->save($storage_path);
            if ($user->userDetail->image_id) {
                Image::find($user->userDetail->image_id)->update([
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
            if (!$user->userDetail->image_id) {
                $user->userDetail()->update([
                    'image_id' => $images->id
                ]);
            }
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage()
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
                'error' => $e->getMessage()
            ];
        }
    }
}