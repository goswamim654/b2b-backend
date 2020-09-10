<?php

namespace App\Http\Controllers;

use  App\Preference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Helper\ResponseBuilder;

class PreferenceContoller extends Controller
{
    
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getPreferences() 
    {
        $preference = Preference::where('user_id', '=', Auth::id())->first();
        if($preference)
            $preference->categories = explode(',',$preference->categories);
        $status = '2';
        $message = "Preferences data";
        return ResponseBuilder::result($status, $message, $preference);
    }
    public function updatePreferences(Request $request) 
    {
        $preference = Preference::where('user_id', '=', Auth::id())->first();
        if ($preference === null) {
            $preference = new Preference();
        }
        $preference->user_id = Auth::id();
        $preference->customer_type = $request->customer_type;
        $preference->visibility = $request->visibility;
        $preference->SKU = $request->SKU;
        $preference->product_code = $request->product_code;
        $preference->display_rate = $request->display_rate;
        $preference->display_mrp = $request->display_mrp;
        $preference->display_margin = $request->display_margin;
        $preference->general_info = $request->general_info;
        $preference->categories = implode(',',$request->categories);
        $preference->save();

        $status = 2;
        $message = "Preferences data updated succesfully";
        return ResponseBuilder::result($status, $message, $preference);

    }

    public function getProductCategories() {
        $preference = Preference::where('user_id', '=', Auth::id())->first();
        if($preference)
            $preference->categories = explode(',',$preference->categories);
        $status = 2;
        $message = "Product Category fetched succesfully";
        return ResponseBuilder::result($status, $message, $preference->categories);
    }
}
