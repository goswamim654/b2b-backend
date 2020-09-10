<?php

namespace App\Http\Helper;
class ResponseBuilder {
    public static function result($status = "", $message ="", $data) {
        return [
            "status" => $status,
            "message" => $message,
            "data" => $data
        ];
    }
}
?>