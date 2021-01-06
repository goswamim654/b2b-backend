<?php

namespace App\Http\Controllers;
use App\Category;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Helper\ResponseBuilder;


class CategoriesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    //
    public function getCategories()
    {
        $user = User::find(Auth::id());
        $categories = [];
        $contents = Category::where('business_category', '=', strtolower($user->business_category))->get();
        foreach($contents as $content)
        {
            $categories[] = $content->name;
        }
        $status = 2;
        $message = "Categories retirved successfully";
        return ResponseBuilder::result($status, $message, $categories);

    }
}
