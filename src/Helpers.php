<?php

namespace MiniUpload;

class Helpers
{
    // Проверка ссылки
    function check_link($link){
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $info = curl_getinfo($ch);
        // Получаем http code
        $http_code = $info['http_code'];
        return $http_code;
    }
}