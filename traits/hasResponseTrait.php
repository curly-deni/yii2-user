<?php

namespace aesis\user\traits;

use Yii;
use yii\web\Response;

trait hasResponseTrait
{
    public function makeResponse($data = '', $message = '', $statusCode = 200, $forceSendResponse = false)
    {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->response;
        } else {
            $response = new Response();
        }

//        \Yii::$app->response->statusCode = $statusCode;

        $response->statusCode = $statusCode;
        if ($statusCode != 204) {
            $responseData = [
                'status' => !(intdiv($statusCode, 100) != 2),
            ];

            if ($data)
                $responseData['data'] = $data;

            if ($message)
                $responseData['message'] = $message;
        } else {
            $responseData = [];
        }

        if ($forceSendResponse) {
            $response->data = $responseData;
            $response->send();
        }
        return $responseData;

    }
}