<?php

use Illuminate\Support\Facades\File;

define('has_itemname_chk',fn ($a,$b) => $a == $b );

/**
* Comprueba si para cada elemento de $somethings
* al menos 1 tiene $check mediante $callback
* @param array<mixed> $sometings
* @param array<string>|string $check
* @param callback $callback
* @return bool
*/
function hasSomething($somethings,$check, $callback=has_itemname_chk) : bool
{
    if (is_array($check)) {
        foreach ($somethings as $item) {
            foreach ($check as $chk) {
                if ($callback($item->name,$chk)) {
                    return true;
                }
            }
        }
    } elseif(is_string($check)) {
        $chk = &$check;
        foreach ($somethings as $item) {
            if ($callback($item->name,$chk)) {
                return true;
            }
        }
    }
    return false;
};



function dataconv($value, $force=null){
    if (!is_int($value)) {
        $value = intval($value);
    }
    if (!is_null($force)) {
        if ($force == 'KB') {
            return [$value / 1000,'KB'];
        } elseif ($force == 'MB') {
            return [$value / 1000000,'MB'];
        } elseif ($force == 'GB') {
            return [$value / 1000000000,'GB'];
        }
    } else {
        if($value > 1000) {
            return [$value / 1000,'KB'];
        } elseif ($value > 1000000) {
            return [$value / 1000000,'MB'];
        } elseif ($value > 1000000000) {
            return [$value / 1000000000,'GB'];
        }
    }
    return $value;
}

/**
     * Proporciona informacion acerca del archivo
     *
     * @var string
     * @return array
    */

function fileInfo($filePath){
    $file = [];
    $file['name'] = File::name($filePath);
    $file['extension'] = File::extension($filePath);
    $size = dataconv(File::size($filePath));
    if($size[1]=='KB') {
        $size = number_format($size[0],3,'.','\'') . ' KB';
    } elseif ($size[1]='MB') {
        $size = number_format($size[0],3,'.','\'') . ' MB';
    } elseif ($size[1]='GB') {
        $size  = number_format($size[0],3,'.','\'') . ' GB';
    }
    $file['created'] = [
        'date' => date('Y/m/d', filectime($filePath)),
        'time' => date('h:i:s a', filectime($filePath))
    ];
    $file['modified'] = filectime($filePath) != filemtime($filePath);

    $file['size'] = $size;

    return $file;
}

/**
     * Muestra el arbol de directorios, remueve Directorios Vacios
     *
     * @var string path
     * @var bool Remover Directorios Vacios
     * @return array|null
    */
function getTree($path,bool $removeEmptyFolders=true) {
    RemoveEmptySubFolders($path);
    if (!File::exists($path)) return;

    $tree = [];

    $branch = [
        'label' => basename($path)
    ];

    foreach (File::files($path) as $file) {
        $branch['files'][] = fileInfo($file);
    }

    foreach (File::directories($path) as $directory) {
        if(File::isEmptyDirectory($directory)){
            File::deleteDirectory($directory);
            continue;
        }
        $branch['folders'][] = getTree($directory);
    }

    return array_merge($tree, $branch);
}

/**
     * Remueve directorios vacios
     *
     * @var string path
     * @return bool|null ignore
    */
function RemoveEmptySubFolders($path){
    if (!File::exists($path)) return;
    $empty = true;
    foreach (glob($path.DIRECTORY_SEPARATOR."*") as $file) {
        $empty &= is_dir($file) && RemoveEmptySubFolders($file);
    }
    return $empty && rmdir($path);
}


/**
     * Crea archivos Zip
     *
     * @var string target path
     * @var string filename (with path)
     * @return ZipArchive|null
    */
    function createZip(string $targetPath , string $filename){
        if(!File::exists($targetPath)) return null;

        $zip = new ZipArchive;
        $newZip = $zip->open($filename,ZipArchive::CREATE);

        if($newZip === true){
            $files = File::allFiles($targetPath);

            foreach ($files as $key => $value) {
                $relativeNameInZipFile = basename($value);
                $zip->addFile($value, $relativeNameInZipFile);
            }

            $zip->close();

            return $zip;
        }

        return null;
    }





    /**
     * Convierte arreglo de booleanos en un numero
     *
     * @var array<bool> arr
     * @return int num
    */
    function booleanArrayToInt($arr) {
        if(is_integer($arr)) return $arr;
        else if(!is_array($arr) || sizeof($arr)==0) return 0;
        $num = 0;
        $length = count($arr);
        for ($i = 0; $i < $length; $i++) {
            $num = ($num << 1) | $arr[$i];
        }
        return $num;
    }

    /**
     * Convierte un numero en un arreglo de booleanos
     *
     * @var int num
     * @return array<bool> arr
    */
    function intToBooleanArray($num,$fill) {
        if(is_null($fill)) $fill=-1;

        if($num==0 && $fill==-1) return [0];

        $array = [];
        while ($num > 0) {
            array_unshift($array, $num & 1);
            $num = $num >> 1;
            $fill--;
        }
        while ($fill>0) {
            $array[] = 0;
            $fill--;
        }
        if($fill<0) $array = array_reverse($array);
        return $array;
    }

    function toBase64($type,$ext,$data) : string
    {
        if(is_null($data)) return '';
        return "data:{$type}/{$ext};base64,".base64_encode($data);
    }
