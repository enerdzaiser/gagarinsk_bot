<?php

var_dump_file('Старт программы');

include 'config/config.php';
require_once 'core/telegram.php';

// Connect db
$db = '';
try {
    $db = new PDO("mysql:host=" . HOST . ";dbname=" . DB_NAME . ";charset=UTF8", USER, PASS);
} catch (PDOException $e) {
    var_dump_file('Не удалось подключиться к БД ' . $e->getMessage());
}
// Connect db

//обработка всех входящих запросов
$data = '';
$data = json_decode(file_get_contents('php://input'), TRUE);
//var_dump_file($data);

$data = $data['callback_query'] ? $data['callback_query'] : $data['message'];

$message = mb_strtolower(($data['text'] ? $data['text'] : $data['data']));

if ($message == 'test') {
    $method = 'sendMessage';
    $send_data = [
        'text' => 'Так и задумано',
        'chat_id' => $data['chat']['id'],
    ];
    $this->sendBot($method, $send_data);
} elseif (preg_match('/^(купить)([0-9a-zа-я ,.]+)/u', $message, $need_buy)) {
    $return = $this->add_need_buy($db, $need_buy[2], $data['chat']['id']);
    $method = 'sendMessage';
    $send_data = [
        'text' => $return,
        'chat_id' => $data['chat']['id'],
    ];
    $this->sendBot($method, $send_data);
} elseif (preg_match('/^(что купить)/u', $message)) {
    $returns = $this->get_buy($db, $data['chat']['id']);

    $method = 'sendMessage';
    if (!$returns) {
        $send_data = [
            'text' => "***Список пуст***\nДля того чтобы пополнить список покупок добавьте то что нужно купить командой \n'купить колы х2'",
            'chat_id' => $data['chat']['id'],
        ];
    } else {
        $a = 0;
        $b = 3; // указываем количество столбцов
        $c = 0;
        $return = array();
        foreach ($returns as $key => $value) {
            if (!empty($a) && is_int($a / $b)) {
                $c++;
            }
            $return['keyboard'][$c][] = ['text' => $key . ':' . $value];

            $return['text'] .= $key . ':' . $value . "\n";
            $a++;
        }
        $send_data = [
            'text' => "Список покупок\n{$return['text']}",
            'chat_id' => $data['chat']['id'],
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => $return['keyboard']
            ]
        ];
    }

    $this->sendBot($method, $send_data);
} elseif (preg_match('/^([\d]+):/u', $message, $bay)) {
    // переводим полученный
    $return = $this->update_buy($db, $bay[1], $data['chat']['id'], 1);
    if ($return) {
        $method = 'sendMessage';
        $send_data = [
            'text' => "{$return}",
            'chat_id' => $data['chat']['id']
        ];
        $this->sendBot($method, $send_data);
    }
} elseif (preg_match('/^(куплено|корзина)/u', $message)) {
    $returns = $this->get_buy($db, $data['chat']['id'], 1);

    $method = 'sendMessage';
    if ($returns) {
        $a = 0;
        $b = 3; // указываем количество столбцов
        $c = 0;
        $return = array();
        foreach ($returns as $key => $value) {
            if (!empty($a) && is_int($a / $b)) {
                $c++;
            }
            $return['keyboard'][$c][] = ['text' => $key . ':' . $value];
            $return['text'] .= $key . ':' . $value . "\n";
            $a++;
        }

        $send_data = [
            'text' => "Список покупок\n{$return['text']}",
            'chat_id' => $data['chat']['id'],
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => $return['keyboard']
            ]
        ];
    } else {
        $send_data = [
            'text' => "***Список пуст***\nЧтобы пополнить список воспользуйтесь командой \n'Что купить?'\nЗатем кликните на товар из списка, чтобы положить его в корзину",
            'chat_id' => $data['chat']['id'],
            'reply_markup' => [
                'resize_keyboard' => true,
                'keyboard' => [
                    [
                        ['text' => 'Что купить?']
                    ]
                ]
            ]
        ];
    }

    $this->sendBot($method, $send_data);
} elseif (preg_match('/^(архив)/u', $message)) {
    $return = $this->update_buy($db, '', $data['chat']['id'], 2);

    if ($return) {
        $method = 'sendMessage';
        $send_data = [
            'text' => "{$return}",
            'chat_id' => $data['chat']['id']
        ];
        $this->sendBot($method, $send_data);
    }
}

function var_dump_file($text)
{
    file_put_contents('file.txt', '$data: ' . print_r($text, 1) . "\n", FILE_APPEND);
}

echo '<pre>';

//echo $_SERVER['REMOTE_ADDR'];
if ($_SERVER['REMOTE_ADDR'] != '93.80.10.154') {
    exit();
}


include('file.txt');


unlink('file.txt');
