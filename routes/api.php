<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

$api_path='/Api';
// require __DIR__."{$api_path}/Owner/Permission.php";
// require __DIR__."{$api_path}/User/Auth.php";
// require __DIR__."{$api_path}/User/Halls.php";
// require __DIR__."{$api_path}/Owner/User.php";
// require __DIR__."{$api_path}/Application/Application.php";
require __DIR__."{$api_path}/Auth.php";
require __DIR__."{$api_path}/Owner.php";

require __DIR__."{$api_path}/Hall.php";
require __DIR__."{$api_path}/App.php";
require __DIR__."{$api_path}/ServiceProvider.php";
require __DIR__."{$api_path}/Government.php";

