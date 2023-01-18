<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public function showAllOrder()
    {

        $orders = Order::get();
        return response([$orders]);
    }

    public function deleteOrder(Request $request)
    {

        $data = validator()->make($request->all(), [
            'order_id' => 'required|exists:orders,id'
        ]);
        if ($data->fails()) {
            return response([
                'status' => 'order not found'
            ]);
        }

        $order = Order::where('id', $request->order_id)->delete();
        return response([
            'status' => 'order deleted successfully'
        ]);
    }

    //method to get all rates from all users that deal with this driver and set total rates in count_rate column in driver table
    public function ratesForDriver(Request $request)
    {
        $data = validator()->make($request->all(), [
            'driver_id' => 'required|exists:drivers,id'
        ]);

        $orders = Order::select('user_id')->where('driver_id', $request->driver_id)->pluck('user_id');
        $value = [];
        foreach ($orders as $key) {
            $user = User::where('id', $key)->pluck('driver_rate');
            if ($user != null) {
                array_push($value, $user);
            }
        }

        $sum = 0;
        foreach ($value as $key) {
            $sum += $key[0];
        }

        Driver::where('id', $request->driver_id)->update(['count_rate' => $sum]);
        $driver = Driver::where('id', $request->driver_id)->get();

        return response([
            'status' => 'all orderers for dirver ' . $request->driver_id,
            'orders' => $driver
        ]);
    }

    //method to get all rates from all drivers that deal with this user and set total rates in count_rate column in user table
    public function ratesForUser(Request $request)
    {
        $data = validator()->make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        $orders = Order::select('driver_id')->where('user_id', $request->user_id)->pluck('driver_id');
        $value = [];
        foreach ($orders as $key) {
            $driver = Driver::where('id', $key)->pluck('user_rate');
            if ($driver != null) {
                array_push($value, $driver);
            }
        }

        $sum = 0;
        foreach ($value as $key) {
            $sum += $key[0];
        }

        User::where('id', $request->user_id)->update(['count_rate' => $sum]);
        $user = User::where('id', $request->user_id)->get();

        return response([
            'status' => 'all orderers for user ' . $request->user_id,
            'orders' => $user
        ]);
    }
}
