<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Http\Request;

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->group(['prefix' => 'api'], function () use ($app) {

    $app->post('/users', function (Request $request) {
        $this->validate($request, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|max:16|confirmed',
            'redirect' => 'required|url'
        ]);

        $data = $request->all();
        $data['password'] = \Hash::make($data['password']);
        $user = \App\User::create($data);
        $user->verification_token = md5(str_random(16));
        $user->save();
        $redirect = route('verification_account', [
            'token' => $user->verification_token,
            'redirect' => $request->get('redirect')
        ]);
        \Notification::send($user, new \App\Notifications\AccountCreated($user, $redirect));
        return response()->json($user, 201);
    });

    $app->get('/verification-account/{token}', [
        'as' => 'verification_account',
        function (Request $request, $token) {
            $user = \App\User::where('verification_token', $token)->firstOrFail();
            $user->verified = true;
            $user->verification_token = null;
            $user->save();
            $redirect = $request->get('redirect');
            return redirect()->to($redirect);
        }
    ]);

    $app->post('/login', function (Request $request) {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $email = $request->get('email');
        $password = $request->get('password');
        $user = \App\User::where('email', '=', $email)->first();

        if (!$user || !\Hash::check($password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 400);
        }

        $expiration = new \Carbon\Carbon();
        $expiration->addHour(2);
        $user->api_token = sha1(str_random(32)) . '.' . sha1(str_random(32));
        $user->api_token_expiration = $expiration->format('Y-m-d H:i:s');
        $user->save();

        return [
            'api_token' => $user->api_token,
            'api_token_expiration' => $user->api_token_expiration
        ];
    });

    $app->post('/refresh-token', [
        'middleware' => 'auth',
        function () {
            $user = \Auth::user();
            $expiration = new \Carbon\Carbon();
            $expiration->addHour(2);
            $user->api_token = sha1(str_random(32)) . '.' . sha1(str_random(32));
            $user->api_token_expiration = $expiration->format('Y-m-d H:i:s');
            $user->save();
            return [
                'api_token' => $user->api_token,
                'api_token_expiration' => $user->api_token_expiration
            ];
        }
    ]);


    $app->group(['middleware' => ['auth', 'is-verified', 'token-expired']], function () use ($app) {
        $app->get('/clients', function () {
            return \App\Client::all();
        });

        $app->get('/user-auth', function (Request $request) {
            return $request->user();
        });
    });
});
