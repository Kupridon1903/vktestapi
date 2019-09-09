<?php
require 'vendor/autoload.php';

use MiniUpload\VkApi as VkApi;
use MiniUpload\Helpers as Helpers;

if (!isset($_REQUEST)) {
    return;
}
$vkApi = new VkApi();
$helpers = new Helpers();

//Парсим JSON
$data = json_decode(file_get_contents('php://input'), true);

//Проверяем, что находится в поле type
switch ($data['type']) {
    //Если это уведомление для подтверждения адреса сервера
    case 'confirmation':
        //...отправляем строку для подтверждения адреса
        echo $vkApi::CALLBACK_API_CONFIRMATION_TOKEN;
        break;

    //Если это уведомление о новом сообщении
    case 'message_new':
        //Считываем информацию
        $message = $data['object'];
        //Читаем id
        $peer_id = empty($message['peer_id']) ? $message['user_id'] : $message['peer_id'];
        $text = $message['text'];
        $word_arr = array("https://", "http://", "www.", "vk.com/");

        if ($helpers->check_link($text) == '200'){
            $text = str_replace($word_arr, "", $text);
            try {
                if ($vkApi->upload_cover($text) == true) {
                    $response = $vkApi->get_users($text, "");
                    $vkApi->send_message($peer_id, "Пользователь " . $response['response'][0]['first_name'] . " "
                        . $response['response'][0]['last_name'] . " добавлен на обложку");
                } else {
                    $vkApi->send_message($peer_id, "Такого пользователя не существует/неправильно введен id");
                }
            }
            catch (Exception $e) {
                $vkApi->send_message($peer_id, "Такого пользователя не существует/неправильно введен id");
            }
        }
        else {
            $vkApi->send_message($peer_id, "Такого пользователя не существует/неправильно введен id");
        }

        //Возвращаем "ok" серверу Callback API
        echo "ok";
        break;

    // Если это уведомление о вступлении в группу
    case 'group_join':
        //Считываем информацию
        $message = $data['object'];
        //Читаем id
        $peer_id = empty($message['peer_id']) ? $message['user_id'] : $message['peer_id'];
        // Загружаем новую обложку
        $response = $vkApi->upload_cover($peer_id);

        //Возвращаем "ok" серверу Callback API
        echo "ok";
        break;

    //При любом уведомлении по типу message_reply
    default:
        //Возвращаем "ok" серверу Callback API
        echo "ok";
        break;
}


