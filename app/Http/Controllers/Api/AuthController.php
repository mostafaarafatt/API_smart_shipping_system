<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Order;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'loginDriver', 'registerDriver']]);
    }

    public function login(Request $request)
    {
        $data = validator()->make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        if ($data->fails()) {
            return response([$data->errors()]);
        }

        $credentials = $request->only('email', 'password');

        // Generate a token for the user if the credentials are valid
        $token = Auth::attempt($credentials);
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get the currently authenticated user
        $user = Auth::user();
        return response()->json([
            'status' => 'success',
            'user' => $user,
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
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|max:255|unique:users',
            'image' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($data->fails()) {
            return response([$data->errors()]);
        }

        $request->merge(['password' => bcrypt($request->password)]);
        $user = User::create($request->all());

        $token = Auth::login($user);

        if ($request->hasFile('image')) {
            if ($request->file('image')->getSize() < (5 * 1024 * 1024)) {
                $filename = hash_hmac('sha256', hash_hmac('sha256', preg_replace('/\\.[^.\\s]{3,4}$/', '', $request->image), false), false);
                $image_name = $request->image->getClientOriginalName();
                $file = $request->image->move(public_path('Attachments/' . 'user'), $image_name);
                $user->image = $image_name;
                $user->save();
            }
        }

        return response([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ], 200);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function me()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function createOrder(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response([
                'status' => 'error',
                'user' => $user->id
            ]);
        } else {
            $data = validator()->make($request->all(), [
                'order_name' => 'required|string|max:255',
                'length' => 'required',
                'height' => 'required',
                'width' => 'required',
                'start_place' => 'required|string|max:255',
                'end_place' => 'required|string|max:255',
            ]);

            if ($data->fails()) {
                return response([$data->errors()]);
            }

            $length = $request->length;
            $height = $request->height;
            $width = $request->width;

            $weight = $length * $height * $width;

            $request->merge([
                'user_id' => auth()->user()->id, 'client_phone' => $user->phone, 'client_name' => $user->name, 'weight' => $weight,
            ]);
            $order = Order::create($request->all());

            return response([
                'status' => 'success',
                'message' => 'order created successfully',
                'order' => $order,
            ], 200);
        }
    }

    //get all orders created by this user
    public function userOrders()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response([
                'status' => 'error',
                'user' => 'Not authenticated user'
            ]);
        } else {
            $orders = Order::where('user_id', $user->id)->get();
            return response([
                'status' => 'all user orders',
                'order' => $orders
            ]);
        }
    }

    //تحديث بيانات الطلب كلها او بعض منها
    public function updateOrder(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response([
                'status' => 'error',
                'user' => 'Not authenticated user'
            ]);
        } else {

            $data = validator()->make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'order_name' => 'string|max:255',
                'length' => 'string|max:255',
                'height' => 'string|max:255',
                'width' => 'string|max:255',
                'start_place' => 'string|max:255',
                'end_place' => 'string|max:255',
            ]);

            if ($data->fails()) {
                return response([$data->errors()]);
            }

            $order1 = Order::where('id', $request->order_id);

            $keys = ['order_name', 'length', 'height', 'width', 'start_place', 'end_place'];
            foreach ($keys as $key) {
                if ($request->hasAny($key)) {
                    $order1->update([
                        $key => $request->$key
                    ]);
                }
            }
            //هنا بقا بتاع الفرونت المفروض هيسحسب الوزن الكلى ويعمله ابديت
            // $weight = $order1->length * $order1->height * $order1->width;
            // $order1->update(['weight'=>$weight]);

            return response([
                'status' => 'success',
                'message' => 'order updated successfully',
                'order' => $order1->get(),
            ], 200);
        }
    }

    //تحديث بيانات المستخدم كلها او بعض منها
    public function updateUserInfo(Request $request)
    {
        $userfound = JWTAuth::parseToken()->authenticate();
        if (!$userfound) {
            return response([
                'status' => 'error',
                'user' => 'Not authenticated user'
            ]);
        } else {
            $data = validator()->make($request->all(), [
                'id' => 'required|exists:users,id',
                'name' => 'string|max:255',
                'email' => 'string|email|max:255',
                'password' => 'string|min:6',
                'phone' => 'string|max:255',
                'image' => 'image|mimes:jpeg,png,jpg|max:2048'
            ]);

            if ($data->fails()) {
                return response([$data->errors()]);
            }

            $user = User::where('id', $request->id);

            if ($request->hasFile('image')) {
                $filename = public_path('Attachments/' . 'user' . $user->image);
                if (file_exists($filename)) {
                    unlink($filename);
                }
                $filename = hash_hmac('sha256', hash_hmac('sha256', preg_replace('/\\.[^.\\s]{3,4}$/', '', $request->image), false), false);
                $image_name = $request->image->getClientOriginalName();
                $file = $request->image->move(public_path('Attachments/' . 'user'), $image_name);
                $user->image = $image_name;
                $user->save();
            }

            $keys = ['name', 'email', 'password', 'phone',];
            foreach ($keys as $key) {
                if ($request->hasAny($key)) {
                    $user->update([
                        $key => $request->$key
                    ]);
                }
            }
            return response([
                'status' => 'تم التحديث بنجاح',
                'user' => $user->get()
            ]);
        }
    }


    // add rate for driver
    public function addRate(Request $request)
    {
        $userfound = JWTAuth::parseToken()->authenticate();
        if (!$userfound) {
            return response([
                'status' => 'error',
                'user' => 'Not authenticated user'
            ]);
        } else {
            $data = validator()->make($request->all(), [
                'id' => 'required|exists:users,id',
                'driver_rate' => 'required|string|max:6',
            ]);
            if ($data->fails()) {
                return response([$data->errors()]);
            }

            $user = User::where('id', $request->id)->update([
                'driver_rate' => $request->driver_rate
            ]);
            return response([
                'status' => 'تم اضافة ال rate',
                'user' => User::where('id', $request->id)->get()
            ]);
        }
    }
}
