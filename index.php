<?php

var_dump_file('Старт программы');


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
    sendBot($method, $send_data);
} elseif (preg_match('/^(купить)([0-9a-zа-я ,.]+)/u', $message, $need_buy)) {
    $return = add_need_buy($db, $need_buy[2], $data['chat']['id']);
    $method = 'sendMessage';
    $send_data = [
        'text' => $return,
        'chat_id' => $data['chat']['id'],
    ];
    sendBot($method, $send_data);
} elseif (preg_match('/^(что купить)/u', $message)) {
    $returns = get_buy($db, $data['chat']['id']);

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

    sendBot($method, $send_data);
} elseif (preg_match('/^([\d]+):/u', $message, $bay)) {
    // переводим полученный
    $return = update_buy($db, $bay[1], $data['chat']['id'], 1);
    if ($return) {
        $method = 'sendMessage';
        $send_data = [
            'text' => "{$return}",
            'chat_id' => $data['chat']['id']
        ];
        sendBot($method, $send_data);
    }
} elseif (preg_match('/^(куплено|корзина)/u', $message)) {
    $returns = get_buy($db, $data['chat']['id'], 1);

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


    sendBot($method, $send_data);
} elseif (preg_match('/^(архив)/u', $message)) {
    $return = update_buy($db, '', $data['chat']['id'], 2);

    if ($return) {
        $method = 'sendMessage';
        $send_data = [
            'text' => "{$return}",
            'chat_id' => $data['chat']['id']
        ];
        sendBot($method, $send_data);
    }
}

function sendBot($method, $data, $header = [])
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://api.telegram.org/bot' . TOKEN . '/' . $method,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"), $header)
    ]);

    $result = curl_exec($curl);
    curl_close($curl);
    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}

function var_dump_file($text)
{
    file_put_contents('file.txt', '$data: ' . print_r($text, 1) . "\n", FILE_APPEND);
}

/**
 * @param $db
 * @param $title
 * @param $id_chat
 * @return string
 */
function add_need_buy($db, string $title, $id_chat)
{
    if (empty($title) && empty($id_chat)) {
        return 'Надо назвать то что собираетесь купить';
    }
    try {
        $db->query("INSERT INTO need_buy SET title = '{$title}', id_chat = '{$id_chat}'");
        return 'В покупки записаны следующие товары: ' . $title;
    } catch (PDOException $e) {
        var_dump_file('Что-то пошло не так__ ' . $e->getMessage());
        return 'Что-то пошло не так: 0001';
    }

}

function get_buy($db, $id_chat, $status = 0)
{
    try {
        $results = array();
        foreach ($db->query("SELECT * FROM need_buy WHERE id_chat = '{$id_chat}' AND status = '{$status}'") as $row) {
            $results[$row['id']] = $row['title'];
        }
        return $results;
    } catch (PDOException $e) {
        var_dump_file('Что-то пошло не так__ ' . $e->getMessage());
        return 'Что-то пошло не так__';
    }
}

function update_buy($db, $id = null, $id_chat, $status = 0)
{
    try {
        $date = '';
        $where_id = '';
        $where_status = '';
        if ($status == 2) {
            $date = ', `date_zip` = ' . time();
        }
        if ($id) {
            $where_id = 'AND `id` =' . $id;
        } else {
            $where_status = 'AND `status` = 1';
        }
        $db->query("UPDATE `need_buy` SET `status` = '{$status}'{$date} WHERE 1 {$where_id} {$where_status} AND id_chat = {$id_chat}; ");
        return '';
    } catch (PDOException $e) {
        var_dump_file('Что-то пошло не так__ ' . $e->getMessage());
        return 'Что-то пошло не так: 0002';
    }
}

echo '<pre>';

//echo $_SERVER['REMOTE_ADDR'];
if ($_SERVER['REMOTE_ADDR'] != '93.80.10.154') {
    exit();
}


include('file.txt');


unlink('file.txt');
