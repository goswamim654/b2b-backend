<?php

namespace App\Http\Helper;
class ResponseBuilder {
    public static function result($status = "", $message ="", $data) {
        return [
            "success" => $status,
            "message" => $message,
            "data" => $data
        ];
    }
}
?>