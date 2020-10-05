<?php

class telegram
{
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


    /**
     * @param $db
     * @param string $title
     * @param int $id_chat
     * @return string
     */
    function add_need_buy($db, string $title, int $id_chat)
    {
        if (empty($title) && empty($id_chat)) {
            return 'Надо назвать то что собираетесь купить';
        }
        try {
            $stmt = $db->prepare("INSERT INTO need_buy SET `title` = ?, `id_chat` = ?");
            $stmt->execute([$title, $id_chat]);

            return 'В покупки записаны следующие товары: ' . $title;
        } catch (PDOException $e) {
            $this->sendBot('sendMessage', array('text' => 'Какие то проблемы с запросом add_need_buy:' . $e->getMessage(), 'chat_id' => '563626742'));
            return 'Какие то проблемы, подождите пока @enerdzaiser их решит, извините за мои кривые руки(';
        }
    }

    /**
     * @param $db
     * @param int $id_chat
     * @param int $status
     * @return array|string
     */
    function get_buy($db, int $id_chat, $status = 0)
    {
        try {
            $results = array();

            $stmt = $db->prepare("SELECT * FROM need_buy WHERE `id_chat` = ? AND `status` = ?");
            $stmt->execute([$id_chat, $status]);

            foreach ($stmt->fetch() as $row) {
                $results[$row['id']] = $row['title'];
            }

            return $results;
        } catch (PDOException $e) {
            $this->sendBot('sendMessage', array('text' => 'Какие то проблемы с запросом get_buy:' . $e->getMessage(), 'chat_id' => '563626742'));
            return 'Какие то проблемы, подождите пока @enerdzaiser их решит, извините за мои кривые руки(';
        }
    }

    /**
     * @param $db
     * @param null|int $id
     * @param int $id_chat
     * @param int $status
     * @return string|null
     */
    function update_buy($db, int $id_chat, $id = null, $status = 0)
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
            return null;
        } catch (PDOException $e) {
            $this->sendBot('sendMessage', array('text' => 'Какие то проблемы с запросом update_buy:' . $e->getMessage(), 'chat_id' => '563626742'));
            return 'Какие то проблемы, подождите пока @enerdzaiser их решит, извините за мои кривые руки(';
        }
    }
}
