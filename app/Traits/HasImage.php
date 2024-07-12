<?php
namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use App\Models\Image;

trait HasImage
{
    public function getImageAttribute()
    {
        $image = $this->image()->first();
        if($image!=null)
        try {
            $ext =  explode('.',$image->path);
            $ext = end($ext);
            return toBase64('image',$ext,Storage::disk('images')->get($image->path))?:'';
        } catch (\Throwable $th) {}
        return '';
    }

    public function setImageAttribute($string)
    {
        if($img = $this->image()->first()){
            Storage::disk('images')->delete($img->path);
        }

        @list($extension, $data) = explode(';base64,', $string);
        @list(, $extension) = explode('/',$extension);
        $image_name = '';
        do {
            $image_name = uniqid('up'.time(),true).'.'.$extension;
        } while (Storage::disk('images')->exists($image_name));

        Storage::disk('images')->put($image_name,base64_decode($data));

        $image = new Image([
            'path' => $image_name
        ]);

        $this->image()->save($image);
    }
}
