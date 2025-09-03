<?php
namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\Request;

final class SmartResponse
{
    /** @var @string */
    protected $reason;
    protected $message;
    protected $help;
    protected $status;
    protected $response;

    public function __construct(string $message = 'Successful request', string $reason = 'Completed', int $status = 200, bool $is_help = true)
    {
        $email = env("MAIL_FROM_ADDRESS");
        $prefix = config('constants.success.help');
        $help = "{$prefix} {$email}";

        $this->help = $is_help? $help: '';

        $this->message = $message;

        $this->reason = $reason;

        $this->status = $status;

        $this->response = [];
    }

    public function add(array $request, string $message, string $reason)
    {
        $success = $message !==''? $message: $this->message;
        $reason = $reason !==''? $reason: $this->reason;

        $getOutput = [
            "message" => $success,
            "reason" => $reason
        ];

        array_push($request, $getOutput);

        return $request;
    }

    public function init(array $request, string $title = '', int $status = 200, bool $is_help = false) // for API
    {
        $status = $status === 200? $this->status: $status;
        $help = $is_help? $this->help: '';

        $response = json_encode([
            "status" => $status,
            "total" => count($request),
            "title" => $title,
            "response" => $request,
            "help" => $help
        ]);

        echo($response);
    }

    public function json(mixed $response = '', bool $isSuccess = false, array $errors = [], mixed $exception = null) // for json response
    {
        try {
            $getResponse = is_array($response)? implode('; ', $response): $response;
        } catch (\Throwable $th) {
            $getResponse = is_array($response)? implode('; ', $response[0]): $response;
        }

        return response()->json([
            "response" => $getResponse,
            "success" => $isSuccess,
            "errors" => [...$errors],
            "exception" => env('APP_ENV') === 'local'?$exception: null
        ]);
    }

    public function exec($data, string $text = '', bool $isSuccess = false) // for GraphQL
    {
        return response()->json([
            "success" => $isSuccess,
            "data" => $data,
            "text" => $text
        ]);
    }

    public function feedback(mixed $response, string $title = '', bool $is_help = false, Exception $exception = null) // for GraphQL
    {
        $help = $is_help? $this->help: '';
        $getResponse = is_array($response)? implode('; ', $response): $response;
        $getCount = is_array($response)? count($response): 1;

        return response()->json([
            "success" => true,
            "exception" => $exception,
            "total" => $getCount,
            "title" => $title,
            "response" => $getResponse,
            "help" => $help
        ]);
    }

    public function graphql(array $model, bool $success = true, mixed $message = null, mixed $exception = null, array $errors = []) // for GraphQL
    {
        $res = [
            "success" => $success,
            "message" => $message,
            "exception" => env('APP_ENV') === 'local'?$exception: null,
            "errorInfo" => [...$errors]
        ];

        $response = [
            ...$model,
            'response' => $res
        ];

        return $response;
    }

    public function stats(array $model, bool $success = true, string $message = null, $exception = null, array $errors = []) // for GraphQL
    {
        $res = [
            "success" => $success,
            "message" => $message,
            "exception" => env('APP_ENV') === 'local'?$exception: null,
            "errorInfo" => [...$errors]
        ];

        $response = [
            "response" => $model,
            ...$res
        ];

        return $response;
    }

    public function res(array $request, string $title = '', int $status = 200, bool $is_help = false)
    {
        $help = $is_help? $this->help: '';

        $response = json_encode([
            "total" => count($request),
            "title" => $title,
            "response" => $request,
            "help" => $help
        ]);

        return response($response, $status)->header('Content-Type', 'text/json');
    }

    public function toString($error) {
        $res = '';
        try {
            if(env('APP_ENV') === 'local') {
                $res = implode(",", $error);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function alertMessage($msg, $type = 'success', $important = false)
    {
        $getMessage = $msg;
        if(is_array($msg)) {
            $getMessage = implode("; ", $msg);
        }
        switch ($type) {
            case 'success':
                $important?flash($getMessage)->success()->important():
                flash($getMessage)->success();
                break;
            case 'error':
                $important?flash($getMessage)->error()->important():
                flash($getMessage)->error();
                break;
            case 'warning':
                $important?flash($getMessage)->warning()->important():
                flash($getMessage)->warning();
                break;

            default:
                $important?flash($getMessage)->important():
                flash($getMessage);
                break;
        }
    }
}
