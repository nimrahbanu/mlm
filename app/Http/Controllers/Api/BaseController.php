<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message,$filter=array())
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $result,
            'filter'    =>[$filter],
        ];


        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 200)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];


        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }
    // public function sendError($error, $errorMessages = [], $code = 400)
    // {
    //     $response = [
    //         'success' => false,
    //         'message' => $error,
    //     ];
    //
    //
    //     if (!empty($errorMessages)) {
    //         $response['data'] = $errorMessages;
    //     }
    //
    //
    //     return response()->json($response, $code);
    // }
    public function sendSuccessError($error, $errorMessages = [], $code = 200)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];


        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }
}