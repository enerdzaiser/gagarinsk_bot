<?php
require_once 'config/config.php';
require_once 'core/telegram.php';

$t = new telegram();
// Connect db
$db = '';
try {
    $db = new PDO("mysql:host=" . HOST . ";dbname=" . DB_NAME . ";charset=UTF8", USER, PASS);
} catch (PDOException $e) {
    $t->sendBot('sendMessage', array('text' => 'Какие то проблемы с подключением : 0001', 'chat_id' => '563626742'));
}
// Connect db

//обработка всех входящих запросов
$data = '';
$data = json_decode(file_get_contents('php://input'), TRUE);

$data = $data['callback_query'] ? $data['callback_query'] : $data['message'];

$message = mb_strtolower(($data['text'] ? $data['text'] : $data['data']));

if ($message == 'test') {
    $method = 'sendMessage';
    $send_data = [
        'text' => 'Так и задумано',
        'chat_id' => $data['chat']['id'],
    ];
    $t->sendBot($method, $send_data);
} elseif (preg_match('/^(купить)([0-9a-zа-я ,.]+)/u', $message, $need_buy)) {
    $return = $t->add_need_buy($db, $need_buy[2], $data['chat']['id']);
    $method = 'sendMessage';
    $send_data = [
        'text' => $return,
        'chat_id' => $data['chat']['id'],
    ];
    $t->sendBot($method, $send_data);
} elseif (preg_match('/^(что купить)/u', $message)) {
    $returns = $t->get_buy($db, $data['chat']['id']);
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
    $t->sendBot($method, $send_data);
} elseif (preg_match('/^([\d]+):/u', $message, $bay)) {
    // переводим полученный товары в статус куплено
    $return = $t->update_buy($db, $data['chat']['id'], $bay[1], 1);
    if ($return) {
        $method = 'sendMessage';
        $send_data = [
            'text' => "{$return}",
            'chat_id' => $data['chat']['id']
        ];
        $t->sendBot($method, $send_data);
    }
} elseif (preg_match('/^(куплено|корзина)/u', $message)) {
    $returns = $t->get_buy($db, $data['chat']['id'], 1);

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

    $t->sendBot($method, $send_data);
} elseif (preg_match('/^(архив)/u', $message)) {
    $return = $t->update_buy($db, $data['chat']['id'], '', 2);

    if ($return) {
        $method = 'sendMessage';
        $send_data = [
            'text' => "{$return}",
            'chat_id' => $data['chat']['id']
        ];
        $t->sendBot($method, $send_data);
    }
} elseif (preg_match('/^(\/start|help|хелп|помощь|что делать)/u', $message)) {
    $method = 'sendMessage';
    $send_data = [
        'text' => "
Возможности:
Помощь в покупках
    1. Чтобы пополнить список планируемых покупок, воспользуйтесь командой 'купить хлебобулочное изделие'
    
    2. Чтобы посмотреть список планируемых покупок, воспользуйтесь командой 'что купить?'
    
    3. После предыдущей команды появится кнопки продуктов, чтобы переместить продукт в список купленных кликаем на тот продукт который уже купили
    
    4. Чтобы проверить список купленных продуктов воспользуйтесь командой 'куплено' или 'корзина'
    
    5. Чтобы подтвердить купленный товары воспользуйтесь командой 'архив'
    
    И всё что пока может этот бот

Предложения по улучшению бота принимаются в личку @enerdzaiser
        ",
        'chat_id' => $data['chat']['id'],
        'reply_markup' => [
            'resize_keyboard' => true,
            'keyboard' => [
                [
                    ['text' => 'Что купить?']
                ],
                [
                    ['text' => 'Куплено'],
                    ['text' => 'Корзина']
                ]
            ]
        ]
    ];
    $t->sendBot($method, $send_data);
}