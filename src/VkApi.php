<?php
namespace MiniUpload;

use Imagick;
use ImagickDraw;
use VK\Client\VKApiClient;

class VkApi {

    const CALLBACK_API_CONFIRMATION_TOKEN = 'ab118b9c'; // Строка, которую должен вернуть сервер
    const VK_API_ACCESS_TOKEN = 'ea0bc2b73f93cdedc6c93e41610de740375a10e5d8bd306668a9b964aafa1b60d11918ceda9cad3149d54'; // Ключ доступа сообщества
    const VK_API_ENDPOINT = 'https://api.vk.com/method/'; // Адрес обращения к API
    const VK_API_VERSION = '5.95'; // Используемая версия API

    private $vk;
    private $vkGraphics;

    function __construct() {
        $this->vk = new VKApiClient('5.95');
        $this->vkGraphics = new VkGraphics();
    }

    // Функция отправки сообщения
    function send_message($peer_id, $message) {
        $this->vk->messages()->send(self::VK_API_ACCESS_TOKEN, array(
            'peer_id' => $peer_id, // id пользователя
            'message' => $message, // Сообщение
            'random_id' => time() + rand(0,1000) // Рандомное число для предотвращения отправки одних и тех же сообщений
        ));
    }

    // Функция получения информации о пользователе
    function get_users($user_id, $fields) {
        $response = $this->vk->users()->get(self::VK_API_ACCESS_TOKEN, array(
            'user_ids' => array($user_id), // id пользователя
            'fields' => array($fields), // Дополнительные поля
            'name_case' => 'nom' // Склонение имени
        ));
        return $response;
    }

    // Функция получения ссылки для загрузки обложки
    function get_cover_link() {
        $response = $this->vk->photos()->getOwnerCoverPhotoUploadServer(self::VK_API_ACCESS_TOKEN, array(
            'group_id' => '186251455', // id группы
            'crop_x' => '0', // Координата X верхнего левого угла для обрезки изображения
            'crop_y' => '0', // Координата Y верхнего левого угла для обрезки изображения
            'crop_x2' => '1590', // Координата X нижнего правого угла для обрезки изображения
            'crop_y2' => '400' // Координата Y нижнего правого угла для обрезки изображения
        ));
        return $response;
    }

    //Функция загрузки обложки
    function upload_cover($user_id){
        // Загружаем ссылку на фото
        $data = $this->get_cover_link();
        // Изменяем обложку
        $this->vkGraphics->change_cover($user_id);
        $upload_url = $data['upload_url']; // Получаем ссылку для загрузки обложки
        $ch = curl_init($upload_url);
        $curlfile = curl_file_create('images/upload.png');
        $data = array("file"=>$curlfile);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Получаем hash и photo
        $response = json_decode(curl_exec($ch),true);
        curl_close($ch);
        // Загружаем фото на сервер
        $this->vk->photos()->saveOwnerCoverPhoto(self::VK_API_ACCESS_TOKEN, array(
            'hash' => $response['hash'],
            'photo' => $response['photo']
        ));
        return true;
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
        curl_close($curl);
        $response = json_decode($json, true); // Парсим ответ
        return $response;
    }
}

