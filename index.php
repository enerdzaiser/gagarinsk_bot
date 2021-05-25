<?php
require_once 'config/config.php';
require_once 'core/telegram.php';
require_once 'core/log.php';

$t = new telegram();

//обработка всех входящих запросов
$data = json_decode(file_get_contents('php://input'), TRUE);

$data_message = $data['message'] ? $data['message'] : $data['callback_query'];

$message = mb_strtolower(($data_message['text'] ? $data_message['text'] : $data_message['data']));

$chat_id = $data_message['chat']['id'] ? $data_message['chat']['id'] : $data_message['message']['chat']['id'];

switch ($message) {
    case 'test':
        $send_data = [
            'text' => 'Так и задумано',
            'chat_id' => $chat_id,
        ];
        $t->sendMessage($send_data);
        break;
    case 'id':
        $send_data = [
            'text' => $data_message['from']['id'],
            'chat_id' => $chat_id,
        ];
        $t->sendMessage($send_data);
        break;
    case 'chat':
        $send_data = [
            'text' => $chat_id,
            'chat_id' => $chat_id,
        ];
        $t->sendMessage($send_data);
        break;
    case (bool)preg_match('/^(купить)([0-9a-zа-я ,.-]+)/u', $message, $need_buy):
        $return = $t->add_need_buy($need_buy[2], $chat_id);
        $send_data = [
            'text' => $return,
            'chat_id' => $chat_id,
        ];
        $t->sendMessage($send_data);
        break;
    case (bool)preg_match('/^(что купить)/u', $message):
        $returns = $t->get_buy($chat_id);
        if (!$returns) {
            $send_data = [
                'text' => "***Список пуст***\nДля того чтобы пополнить список покупок добавьте то что нужно купить командой \n'купить колы х2'",
                'chat_id' => $chat_id,
                'reply_markup' => [
                    'inline_keyboard' => common_inline_keyboard()
                ]
            ];
        } else {
            $return = return_item_list($returns, 'buy');

            $return['keyboard'][] = what_buy();
            $return['keyboard'][] = buy_and_cart();

            $send_data = [
                'text' => "
*** Список покупок ***:\n
- Выберите предметы которые вы собираетесь купить\n
                ",
                'chat_id' => $chat_id,
                'reply_markup' => [
                    'inline_keyboard' => $return['keyboard']
                ]
            ];
        }
        $t->sendMessage($send_data);
        break;
    case (bool)preg_match('/^([\d]+):buy/u', $message, $buy):
        $return = $t->update_buy($chat_id, $buy[1], 1);
        if ($return) {
            $send_data = [
                'text' => "{$return}",
                'chat_id' => $chat_id
            ];
            $t->sendMessage($send_data);
        }
        break;
    case (bool)preg_match('/^([\d]+):del/u', $message, $buy):
        $return = $t->update_buy($chat_id, $buy[1], 0);
        if ($return) {
            $send_data = [
                'text' => "{$return}",
                'chat_id' => $chat_id
            ];
            $t->sendMessage($send_data);
        }
        break;
    case (bool)preg_match('/^(куплено|корзина)/u', $message):
        $returns = $t->get_buy($chat_id, 1);

        if ($returns) {
            $return = return_item_list($returns, 'del');

            $return['keyboard'][] = save_buy();

            $send_data = [
                'text' => "
*** Корзина ***:\n
- Если какой то товар на самом деле не куплен\n
- Если все правильно, то нажмите кнопку архив 🧺
                ",
                'chat_id' => $chat_id,
                'reply_markup' => [
                    'inline_keyboard' => $return['keyboard']
                ]
            ];
        } else {
            $send_data = [
                'text' => "*** Список пуст ***\nЧтобы пополнить список воспользуйтесь командой \n'Что купить?'\nЗатем кликните на товар из списка, чтобы положить его в корзину",
                'chat_id' => $chat_id,
                'reply_markup' => [
                    'inline_keyboard' => common_inline_keyboard()
                ]
            ];
        }

        $t->sendMessage($send_data);
        break;
    case 'архив':
        $return = $t->update_buy($chat_id, null, 2);

        $send_data = [
            'text' => '*** Все сохранено ***',
            'chat_id' => $chat_id,
            'reply_markup' => [
                'inline_keyboard' => common_inline_keyboard()
            ]
        ];
        $t->sendMessage($send_data);

        break;
    case (bool)preg_match('/^(\/start|help|хелп|помощь|что делать)/u', $message):
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
            'chat_id' => $chat_id,
            'reply_markup' => [
                'inline_keyboard' => common_inline_keyboard(),
            ]
        ];
        $t->sendMessage($send_data);
        break;
}

function common_inline_keyboard () : array
{
    return [
        what_buy(),
        buy_and_cart()
    ];
}

function what_buy() : array
{
    return [
        [
            'text' => '❓ Что купить? ❓',
            'callback_data' => 'Что купить?'
        ],
    ];
}

function buy_and_cart() : array
{
    return [
        [
            'text' => '✔ Куплено / Корзина ✔ ',
            'callback_data' => 'Куплено',
            'left'
        ],
    ];
}

function save_buy() : array
{
    return [
        [
            'text' => '🧺 Архив 🧺',
            'callback_data' => 'архив'
        ],
    ];
}

function return_item_list($returns, $action) : array
{
    $return = [];
    $action == 'buy' ? $smile = '✔ ' : $smile = '✖ ';
    foreach ($returns as $key => $value) {
        $return['keyboard'][][] = [
            'text' => $smile . $value,
            'callback_data' => $key . ':' . $action
        ];
    }
    return $return;
}