<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (NotFoundHttpException $e) {
            return response()->json(['message' => trans('validation.custom.resource_notfound')], 404);
        });

        $this->renderable(function (MethodNotAllowedHttpException $e) {
            return response()->json(['message' => trans('validation.custom.Method_Not_Allowed')], 405);
        });

        $this->renderable(function (ThrottleRequestsException $e) {
            return response()->json(['message' => trans('validation.custom.Too_Many_Requests')], 429);
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            $model = explode('\\', $exception->getModel());
            $modelPhrase = ucwords(implode('', preg_split('/(?=[A-Z])/', end($model))));
            return response()->json([
                'message' => $modelPhrase .' '. trans('validation.custom.not_found'),
            ], 200);
        }
        return parent::render($request, $exception);
    }
}
