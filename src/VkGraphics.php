<?php

namespace MiniUpload;

use Imagick;
use ImagickDraw;

class VkGraphics
{
    function change_cover($user_id){
        $vkApi = new VkApi();
        $data = $vkApi->get_users($user_id,'photo_200'); // Получаем необходимую информацию
        $oblozhka = new Imagick("images/oblozhka.jpg"); // Фон обложки
        $mini =  new Imagick($data[0]['photo_200']); // Фотография
        $mask =  new Imagick("images/mask.png"); // Маска для скругления углов
        $draw = new ImagickDraw();
        $draw->setFillColor('rgb(255, 255, 255)'); // Цвет шрифта
        $draw->setFontSize(36); // Размер шрифта
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER); // Центрируем

        $mini->resizeImage(200, 200, Imagick::FILTER_LANCZOS, 1); // Настраиваем размеры
        $mask->resizeImage(200, 200, Imagick::FILTER_LANCZOS, 1);
        $mask->compositeImage($mini, Imagick::COMPOSITE_ATOP, 0, 0); // Скругляем края фотографии
        $mini->clear();
        $oblozhka->compositeImage($mask, Imagick::COMPOSITE_OVER, 670, 70); // Добавляем фото на обложку
        $mask->clear();
        $oblozhka->annotateImage($draw, 770, 300, 0, $data[0]['first_name'] . ' '
            . $data[0]['last_name']); // Добавляем текст на обложку

        $oblozhka->writeImage('images/upload.png'); // Сохраняем изображение
        $oblozhka->clear();
    }
}