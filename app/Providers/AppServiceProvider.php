<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Validator::extend('base64',function($attribute,$value,$parameters, $validator){
            $parameters[0] = json_decode(str_replace(';',',',$parameters[0]));
            $correct_format = ((fn($str, $arr) =>
                array_reduce(
                    $arr,
                    fn($carry, $valor) =>
                        $carry || preg_match('/^data:[^;]*\/' . preg_quote($valor, '/') . ';base64,/', $str),
                    false
                    )
                )
            )($value, $parameters[0]);
            //in_array(
                //str_replace([',','base64'],'', $value ),$parameters[0]
            //);
            $value = preg_replace('#^data:[^;]*;base64,#', '', $value );
            $base64_check = base64_decode($value, true);
            if($base64_check===false)
            {
                return false;
            }
            if($correct_format)
            {
                $size = strlen($base64_check)/1024;
                $min = true;
                $max = true;
                if(array_key_exists(1,$parameters)) $min = $size>=$parameters[1];
                if(array_key_exists(2,$parameters)) $max = $size<=$parameters[2];
                return ($min && $max);
            }
            return false;
        },'El campo :attribute debe ser un archivo valido {{$parameters[0]} entre {{$parameters[1]}} y {{$parameters[2]}}');
    }
}
