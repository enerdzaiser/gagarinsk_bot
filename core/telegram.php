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
            $stmt = $db->prepare("INSERT INTO need_buy SET `title` = ?, `id_chat` = ?");
            $stmt->execute([$title, $id_chat]);

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

            $stmt = $db->prepare("SELECT * FROM need_buy WHERE `id_chat` = ? AND `status` = ?");
            $stmt->execute([$id_chat, $status]);

            foreach ($stmt->fetch() as $row) {
                $results[$row['id']] = $row['title'];
            }

            return $results;
        } catch (PDOException $e) {
            var_dump_file('Что-то пошло не так__ ' . $e->getMessage());
            return 'Что-то пошло не так__';
        }
    }
}
