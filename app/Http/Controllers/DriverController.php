<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class DriverController extends Controller
{
    public function login(Request $request)
    {
        $data = validator()->make($request->all(), [
            'national_ID' => 'string',
            'password' => 'required|string'
        ]);
        if ($data->fails()) {
            return response([$data->errors()]);
        }

        $credentials = $request->only('national_ID', 'password');
        $token = auth()->guard('driver')->attempt($credentials);
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $driver = auth()->guard('driver')->user();
        return response()->json([
            'status' => 'success',
            'user' => $driver,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function register(Request $request)
    {
        $data = validator()->make($request->all(), [
            'name' => 'required|string|max:255',
            'national_ID' => 'required|string|max:14|unique:drivers',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|max:255|unique:drivers',
            'car_number' => 'required|string',
            'image' => 'image|mimes:jpeg,jpg,png|max:2048'
        ]);

        if ($data->fails()) {
            return response([$data->errors()]);
        }

        $request->merge(['password' => bcrypt($request->password)]);
        $driver = Driver::create($request->all());

        $token = Auth::login($driver);

        if ($request->hasFile('image')) {
            if ($request->file('image')->getSize() < (5 * 1024 * 1024)) {
                $filename = hash_hmac('sha256', hash_hmac('sha256', preg_replace('/\\.[^.\\s]{3,4}$/', '', $request->image), false), false);
                $image_name = $request->image->getClientOriginalName();
                $file = $request->image->move(public_path('Attachments/' . 'driver'), $image_name);
                $driver->image = $image_name;
                $driver->save();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Driver created successfully',
            'user' => $driver,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ], 200);
    }

    public function me()
    {
        return response([auth()->guard('driver')->user()]);
    }

    public function logout()
    {
        auth()->guard('driver')->logout();
        return response([
            'status' => 'success',
            'message' => 'Driver logout successfully'
        ]);
    }

    // ممكن اشيلها ملهاش لازمة اووى ... انا حططها بس فى حاله ان السواق حب يفتح الاودر ويعرف تفاصيل اكتر عليه
    public function selectOrder(Request $request)
    {
        $driver = JWTAuth::parseToken()->authenticate();
        if (!$driver) {
            return response([
                'status' => 'error',
                'driver' => 'Not authenticated'
            ]);
        } else {
            $order = Order::where('id', $request->order_id)->get();
            if ($order) {
                $data = validator()->make($request->all(), [
                    'order_id' => 'required|exists:orders,id'
                ]);
                if ($data->fails()) {
                    return response([
                        $data->errors()
                    ]);
                }

                return response([
                    'status' => 'show this order',
                    'order' => $order
                ]);
            }
        }
    }

    // فى حالة قبول الاوردر
    public function acceptOrder(Request $request)
    {

        $driver = JWTAuth::parseToken()->authenticate();
        if (!$driver) {
            return response([
                'status' => 'error',
                'driver' => 'Not authenticated'
            ]);
        } else {
            $data = validator()->make($request->all(), [
                'order_id' => 'required|exists:orders,id'
            ]);
            if ($data->fails()) {
                return response([
                    $data->errors()
                ]);
            }

            $order = Order::where('id', $request->order_id)->update(['driver_id' => $driver->id, 'state' => 1]);

            return response([
                'status' => 'done',
                'order' => Order::where('id', $request->order_id)->get()
            ]);
        }
    }

    //get all orders created by this driver
    public function driverOrders(Request $request)
    {

        $driver = JWTAuth::parseToken()->authenticate();
        if (!$driver) {
            return response([
                'status' => 'error',
                'driver' => 'Not authenticated'
            ]);
        } else {
            $orders = Order::where('driver_id', $driver->id)->get();
            return response([
                'status' => 'all driver orders',
                'order' => $orders
            ]);
        }
    }

    //تحديث بيانات السواق كلها او بعض منها
    public function updateDriverInfo(Request $request)
    {
        $driverfound = JWTAuth::parseToken()->authenticate();
        if (!$driverfound) {
            return response([
                'status' => 'error',
                'driver' => 'Not Authenticated'
            ]);
        } else {

            $data = validator()->make($request->all(), [
                'driver_id' => 'required|exists:drivers,id',
                'name' => 'string|max:255',
                'password' => 'string|min:6',
                'phone' => 'string|max:15',
                'national_ID' => 'string|max:14',
                'car_number' => 'string',
                'address' => 'string|max:255',
                'image' => 'image|mimes:jpeg,jpg,png|max:2048'
            ]);
            if ($data->fails()) {
                return response([$data->errors()]);
            }

            $driver = Driver::where('id', $request->driver_id);
            if ($request->hasFile('image')) {
                $filename = public_path('Attachments/' . 'driver' . $driver->image);
                if (file_exists($filename)) {
                    unlink($filename);
                }
                $filename = hash_hmac('sha256', hash_hmac('sha256', preg_replace('/\\.[^.\\s]{3,4}$/', '', $request->image), false), false);
                $image_name = $request->image->getClientOriginalName();
                $file = $request->image->move(public_path('Attachments/' . 'driver'), $image_name);
                $driver->image = $image_name;
                $driver->save();
            }
            $keys = ['name', 'password', 'phone', 'national_ID', 'car_number', 'address'];
            foreach ($keys as $key) {
                if ($request->hasAny($key)) {
                    $driver->update([
                        $key => $request->$key
                    ]);
                }
            }
            return response([
                'status' => 'تم التحديث بنجاح',
                'user' => $driver->get()
            ]);
        }
    }

    // add rate for user
    public function addRate(Request $request)
    {
        $driverfound = JWTAuth::parseToken()->authenticate();
        if (!$driverfound) {
            return response([
                'status' => 'error',
                'user' => 'Not authenticated user'
            ]);
        } else {
            $data = validator()->make($request->all(), [
                'id' => 'required|exists:drivers,id',
                'user_rate' => 'required|string|max:6',
            ]);
            if ($data->fails()) {
                return response([$data->errors()]);
            }

            $user = Driver::where('id', $request->id)->update([
                'user_rate' => $request->user_rate
            ]);
            return response([
                'status' => 'تم اضافة ال rate',
                'user' => Driver::where('id', $request->id)->get()
            ]);
        }
    }
}
