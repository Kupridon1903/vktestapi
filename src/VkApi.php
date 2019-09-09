<?php
namespace MiniUpload;

use Imagick;
use ImagickDraw;

class VkApi {

    const CALLBACK_API_CONFIRMATION_TOKEN = 'ab118b9c'; // Строка, которую должен вернуть сервер
    const VK_API_ACCESS_TOKEN = 'ea0bc2b73f93cdedc6c93e41610de740375a10e5d8bd306668a9b964aafa1b60d11918ceda9cad3149d54'; // Ключ доступа сообщества
    const VK_API_ENDPOINT = 'https://api.vk.com/method/'; // Адрес обращения к API
    const VK_API_VERSION = '5.95'; // Используемая версия API

    // Функция отправки сообщения
    function send_message($peer_id, $message) {
        $this->api('messages.send', array(
            'peer_id' => $peer_id, // id пользователя
            'message' => $message, // Сообщение
            'random_id' => time() + rand(0,1000) // Рандомное число для предотвращения отправки одних и тех же сообщений
        ));
    }

    // Функция получения информации о пользователе
    function get_users($user_id, $fields) {
        $response = $this->api('users.get', array(
            'user_ids' => $user_id, // id пользователя
            'fields' => $fields, // Дополнительные поля
            'name_case' => 'nom' // Склонение имени
        ));
        return $response;
    }

    // Функция изменения обложки
    function change_cover($user_id){
        $data = $this->get_users($user_id,'photo_200'); // Получаем необходимую информацию
        $oblozhka = new Imagick("images/oblozhka.jpg"); // Фон обложки
        $mini =  new Imagick($data['response'][0]['photo_200']); // Фотография
        $mask =  new Imagick("images/mask.png"); // Маска для скругления углов
        $draw = new ImagickDraw();
        $draw->setFillColor('rgb(30, 30, 30)'); // Цвет шрифта
        $draw->setFontSize(36); // Размер шрифта
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER); // Центрируем

        $mini->resizeImage(200, 200, Imagick::FILTER_LANCZOS, 1); // Настраиваем размеры
        $mask->resizeImage(200, 200, Imagick::FILTER_LANCZOS, 1);
        $mask->compositeImage($mini, Imagick::COMPOSITE_ATOP, 0, 0); // Скругляем края фотографии
        $oblozhka->compositeImage($mask, Imagick::COMPOSITE_OVER, 670, 70); // Добавляем фото на обложку
        $oblozhka->annotateImage($draw, 770, 300, 0, $data['response'][0]['first_name'] . ' '
            . $data['response'][0]['last_name']); // Добавляем текст на обложку

        $oblozhka->writeImage('images/upload.png'); // Сохраняем изображение
    }

    // Функция получения ссылки для загрузки обложки
    function get_cover_link() {
        $response = $this->api('photos.getOwnerCoverPhotoUploadServer', array(
            'group_id' => '186251455', // id группы
            'crop_x' => '0', //
            'crop_y' => '0', //
            'crop_x2' => '1590', //
            'crop_y2' => '400' //
        ));
        return $response;
    }

    //Функция загрузки обложки
    function upload_cover($user_id){
        // Загружаем ссылку на фото
        $data = $this->get_cover_link();
        // Изменяем обложку
        $this->change_cover($user_id);

        $upload_url = $data['response']['upload_url']; // Получаем ссылку для загрузки обложки
        $ch = curl_init($upload_url);
        $curlfile = curl_file_create('images/upload.png');
        $data = array("file"=>$curlfile);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Получаем hash и photo
        $response = json_decode(curl_exec($ch),true);
        // Загружаем фото на сервер
        $this->api('photos.saveOwnerCoverPhoto', array(
            'hash' => $response['hash'],
            'photo' => $response['photo']
        ));
        return true;
    }

    // Проверка ссылки
    function check_link($link){
        $ch = curl_init($link);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $info = curl_getinfo($ch);
        // Получаем http code
        $http_code = $info['http_code'];
        return $http_code;
    }

    // Функция вызова любого метода api
    function api($method, $params) {
        $params['access_token'] = self::VK_API_ACCESS_TOKEN;
        $params['v'] = self::VK_API_VERSION;
        $query = http_build_query($params); // Формируем строку для get запроса
        $url = self::VK_API_ENDPOINT . $method . '?' . $query; // Формируем get запрос
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($curl);
        $response = json_decode($json, true); // Парсим ответ
        return $response;
    }
}

