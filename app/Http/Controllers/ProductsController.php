<?php

namespace App\Http\Controllers;

use Validator;
use Image;
use App\ProductUnit;
use App\Product;
use App\ProductPhoto;
use App\PreviewProductUnit;
use App\PreviewProduct;
use App\PreviewProductPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Helper\ResponseBuilder;
use  App\User;
use  App\Order;

class ProductsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $fractal;

    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * GET /products
     * 
     * @return array
     */
    public function index()
    {
        $productList = [];
        $products = Product::where('user_id', '=', Auth::id())->with('photos')->with('units')->get();

        if(count($products) > 0)
        {
            $product_details = [];
            foreach ($products as $key => $product) 
            {
                $product_details['id'] = $product->id;
                $product_details['slug'] = str_replace(' ', '-', strtolower($product->name)).'-'.$product->id;
                $product_details['name'] = $product->name;
                foreach ($product->units as $key => $unit) {
                    $product_details['unit_id'] = $unit->id;
                    $product_details['code'] = $unit->code;
                    $product_details['rate'] = $unit->rate;
                    $product_details['available'] = $unit->available;
                    $product_details['status'] = $unit->status;
                    $product_details['stock'] = $unit->stock;
                }
                foreach ($product->photos as $key => $photo) {
                    $product_details['banner_image'] = $photo->photo_url;
                    break;
                }
                $thumbnail_image = [];
                foreach ($product->photos as $key => $photo) {
                    $thumbnail_image[] = url('/uploads/products/thumbnail/'.$photo->photo_name);
                }
                $product_details['thumbnail_image'] = $thumbnail_image;
                
                $product_details['photos'] = $product->photos;
                $productList[] = $product_details;
            }
            $status = 2;
            $message = "Products retrived succesfully";
        }
        else
        {
            $status = 3;
            $message = "Products not available";
        }
        return ResponseBuilder::result($status, $message, $productList);
    }

    public function show($id)
    {
        $products = Product::where('id', '=', $id)->with('photos')->with('units')->get();

        foreach($products as $product)
        {
            foreach ($product->photos as $key => $photo) 
            {
                $product->photos[$key]['thumbnail_image'] = url('/uploads/products/thumbnail/'.$photo->photo_name);
                $product->photos[$key]['big_image'] = url('/uploads/products/photos/'.$photo->photo_name);
            }
            
        }
        if(count($products) > 0)
        {
            $status = 2;
            $message = "Product retrieved successfully";
        }
        else
        {
            $status = 3;
            $message = "Product not available";
        }
        return ResponseBuilder::result($status, $message, $products);
    }

    public function store(Request $request)
    {
        if(isset($request->product['unit']))
        {
            $validator = Validator::make($request->all(), [
                'category' => 'max:255',
                'sub_category' => 'max:255',
                'name' => 'required|max:255',
                'description' => 'max:600',
                'tax' => 'numeric',
                "product.*.unit"  => "required|string|min:3",
            ]);
        }
        else
        {
            $validator = Validator::make($request->all(), [
                'category' => 'max:255',
                'sub_category' => 'max:255',
                'name' => 'required|max:255',
                'description' => 'max:600',
                'tax' => 'numeric',
            ]);
        }
        
        

        if ($validator->fails()) {
            return response()->json([
                'status' => 3, 
                'data' =>  $validator->errors(),
                'message' => 'Validation failed' 
            ]);
        }

        try {
            \DB::beginTransaction();
            //validate request parameters
            
            $product = new Product();
            $product->user_id = Auth::id();
            $product->category = $request->category;
            $product->sub_category = $request->sub_category;
            $product->name = $request->name; 
            $product->description = $request->description;
            $product->tax = $request->tax;
            $product->is_item_grouped = $request->is_item_grouped;
            $sound = " ";
            $words = explode(" ", $request->name);
            foreach($words as $word)
            {
                $sound .= metaphone($word). " ";
            }
            $product->indexing = $sound;
            $product->save();
            // save product from gallery
            if($request->selected_images)
            {
                foreach ($request->selected_images as $key => $image) {
                    $product_photo = new ProductPhoto();
                    $product_photo->product_id = $product->id;
                    $path_parts = pathinfo($image);
                    $product_photo->photo_name = $path_parts['basename'];
                    $product_photo->photo_url = $image;
                    $product_photo->save();
                }
            }
            // save images
            if($request->product_images)
            {
                foreach ($request->product_images as $key => $image) {
                    if($image !== null)
                    {
                        $product_photo = new ProductPhoto();
                        $product_photo->product_id = $product->id;
                    
                        $destinationPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR;


                        $image_parts = explode(";base64,", $image);
                        $image_type_aux = explode("image/", $image_parts[0]);
                        $image_type = $image_type_aux[1];
                        $image_base64 = base64_decode($image_parts[1]);
                        $photo_name = uniqid() .'.'. $image_type;
                        $file = $destinationPath . $photo_name;
                        file_put_contents($file, $image_base64);

                        $this->resizeImageSave($image_base64, $photo_name);
                        $product_photo->photo_name = $photo_name;
                        $product_photo->photo_url = url('/uploads/products/'.$photo_name);
                        $product_photo->save();


                    } 
                }
            }

            if($request->product)
            {
                
                // // save units
                foreach ($request->product as $key => $product_unit) 
                {
                   
                    $productUnits = new ProductUnit();
                    $productUnits->product_id = $product->id;
                    $productUnits->units = $product_unit['unit'];
                    $productUnits->code = $product_unit['code'];
                    $productUnits->mrp = $product_unit['mrp'];
                    $productUnits->rate = $product_unit['rate'];
                    $productUnits->moq = $product_unit['moq'];
                    $productUnits->available = $product_unit['available'];
                    $productUnits->stock = $product_unit['stock'];
                    $productUnits->save();

                }
            }
            else
            {

            }
            \DB::commit();
            
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['message' => $e], 404);
        }
        
        $status = 2;
        $message = "Product saved succesfully";
        $product = Product::where('id', '=', $product->id)->with('photos')->with('units')->get();
        return ResponseBuilder::result($status, $message, $product);
        
    }

    public function update($id, Request $request)
    {
        try {
            \DB::beginTransaction();
            //validate request parameters
            $this->validate($request, [
                'category' => 'max:255',
                'sub_category' => 'max:255',
                'name' => 'required|max:255',
                'description' => 'max:600',
                'tax' => 'max:255',
            ]);

            $product = Product::find($id);
            $product->category = $request->category;
            $product->sub_category = $request->sub_category;
            $product->name = $request->name;
            $product->description = $request->description;
            $product->tax = $request->tax;
            $sound = " ";
            $words = explode(" ", $request->name);
            foreach($words as $word)
            {
                $sound .= metaphone($word). " ";
            }
            $product->indexing = $sound;
            $product->save();
            
            $destinationPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR;

            $destinationPhotosPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR. "photos". DIRECTORY_SEPARATOR;

            $destinationThumbnailPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR. "thumbnail". DIRECTORY_SEPARATOR;

            // delete existing photos
            // $product_photos = ProductPhoto::where('product_id', $id)->get();
            // if($product_photos)
            // {
            //     foreach ($product_photos as $key => $product_photo) 
            //     {
            //         unlink($destinationPath.$product_photo->photo_name); 
            //         unlink($destinationPhotosPath.$product_photo->photo_name); 
            //         unlink($destinationThumbnailPath.$product_photo->photo_name); 
            //         ProductPhoto::findOrFail($product_photo->id)->delete();
            //     }
            // }
            // save product from gallery
            if($request->selected_images)
            {
                foreach ($request->selected_images as $key => $image) {
                    $product_photo = new ProductPhoto();
                    $product_photo->product_id = $product->id;
                    $path_parts = pathinfo($image);
                    $product_photo->photo_name = $path_parts['filename'];
                    $product_photo->photo_url = $image;
                    $product_photo->save();
                }
            }

            //save images
            foreach ($request->product_images as $key => $image) 
            {
                if($image)
                {
                    $product_photo = new ProductPhoto();
                    $product_photo->product_id = $id;
                    $image_parts = explode(";base64,", $image);
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $photo_name = uniqid() .'.'. $image_type;
                    $file = $destinationPath . $photo_name;
                    file_put_contents($file, $image_base64);
                    
                    $this->resizeImageSave($image_base64, $photo_name);
                    $product_photo->photo_name = $photo_name;
                    $product_photo->photo_url = url('/uploads/products/'.$photo_name);
                    
                    $product_photo->save();
                } 
            }

            //delete the existing units.
            $productUnit = ProductUnit::where('product_id', $id)->get();
            if($productUnit)
            {
                foreach ($productUnit as $key => $unit) 
                {
                    ProductUnit::findOrFail($unit->id)->delete();
                }
            }

            // save units
            foreach ($request->product as $key => $product_unit) 
            {
                $productUnits = new ProductUnit();
                $productUnits->product_id = $id;
                $productUnits->units = $product_unit['unit'];
                $productUnits->code = $product_unit['code'];
                $productUnits->mrp = $product_unit['mrp'];
                $productUnits->rate = $product_unit['rate'];
                $productUnits->moq = $product_unit['moq'];
                $productUnits->available = $product_unit['available'];
                $productUnits->stock = $product_unit['stock'];
                $productUnits->save();

            }
            \DB::commit();
            
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['message' => 'Something went wrong!'], 40);
        }
        
        $status = 2;
        $message = "Product updated succesfully";
        $product = Product::where('id', '=', $product->id)->with('photos')->with('units')->get();
        return ResponseBuilder::result($status, $message, $product);

       
    }

    public function destroy($id){
        
        //Return error 404 response if product was not found
        if(!Product::find($id)) return $this->errorResponse('Product not found!', 404);

        //Return 410(done) success response if delete was successful
        if(Product::find($id)->delete()){
            return $this->customResponse('Product deleted successfully!', 410);
        }

        //Return error 400 response if delete was not successful
        return $this->errorResponse('Failed to delete product!', 400);
    }

    public function customResponse($message = 'success', $status = 200)
    {
        return response(['status' =>  $status, 'message' => $message], $status);
    }

    public function resizeImageSave($image_base64, $photo_name)
    {
        $destinationPhotosPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR. "photos". DIRECTORY_SEPARATOR;

        $destinationThumbnailPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR. "thumbnail". DIRECTORY_SEPARATOR;
        
        $img = Image::make($image_base64)->resize(500,null,function($constraint)
        {
            $constraint->aspectRatio();
        });
        $img->save($destinationPhotosPath . $photo_name);

        $imgThumbnail = Image::make($image_base64)->resize(50,50);
        $imgThumbnail->save($destinationThumbnailPath . $photo_name);

    }
    public function getImages() 
    {
        $productPhotos = ProductPhoto::all();
        $images = [];
        $images_thumbnails = [];
        $images_originals = [];
        $images_photos = [];
        foreach ($productPhotos as $key => $photo) {
            $images_originals[$photo->id] = $photo->photo_url;
            $images_thumbnails[] = url('/uploads/products/thumbnail/'.$photo->photo_name);
            $images_photos[] = url('/uploads/products/photos/'.$photo->photo_name);
        }
        $images['originals'] = $images_originals;
        $images['thumbnails'] = $images_thumbnails;
        $images['photos'] = $images_photos;

        $status = 2;
        $message = "Product photo retrieved successfully";
        return ResponseBuilder::result($status, $message, $images);
    }

    public function deleteGalleryImage($id) 
    {
        //Return error 404 response if product was not found
        $productImage = ProductPhoto::find($id);
        if(!$productImage) 
        {
            $content="";
            $status = 4;
            $message = "Image not found !";
        }
        else
        {
            $destinationOriginalPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR. "products". DIRECTORY_SEPARATOR;
            unlink($destinationOriginalPath.$productImage->photo_name);

            $destinationPhotosPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR. "photos". DIRECTORY_SEPARATOR;

            unlink($destinationPhotosPath.$productImage->photo_name);

            $destinationThumbnailPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR. "thumbnail". DIRECTORY_SEPARATOR;

            unlink($destinationThumbnailPath.$productImage->photo_name);

            $productImage->delete();

            $content = "";
            $status = 2;
            $message = "Image deleted successfully";
        }
        
        return ResponseBuilder::result($status, $message, $content);
    }

    public function updateProductAvalibility(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|numeric',
            'availability' => 'required| in:yes, no'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 3, 
                'data' =>  $validator->errors(),
                'message' => 'Validation failed' 
            ]);
        }

        $unit = ProductUnit::find($request->unit_id);
        if($unit)
        {
            $unit->available  = $request->availability;
            $unit->save();
            $status = 2;
            $message = "Product availability updated successfully";
            return ResponseBuilder::result($status, $message, $unit);
        }
        else
        {
            $data = '';
            $status = 4;
            $message = "Unit id does not exists";
            return ResponseBuilder::result($status, $message, $data);
        }

    }

    public function updateProductStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|numeric',
            'status' => 'required| in:active, inactive'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 3, 
                'data' =>  $validator->errors(),
                'message' => 'Validation failed' 
            ]);
        }


        $unit = ProductUnit::find($request->unit_id);
        if($unit) 
        {
            $unit->status  = $request->status;
            $unit->save();
            $status = 2;
            $message = "Product status updated successfully";
            return ResponseBuilder::result($status, $message, $unit);
        }
        else
        {
            $data = '';
            $status = 4;
            $message = "Unit id does not exists";
            return ResponseBuilder::result($status, $message, $data);
        }
    }

    // preview product
    public function previewProducts(Request $request)
    {
        // delete existing photos
        // $destinationPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR;

        // $destinationPhotosPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR. "photos". DIRECTORY_SEPARATOR;

        // $destinationThumbnailPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR. "thumbnail". DIRECTORY_SEPARATOR;
        
        // $product_photos = ProductPhoto::all();
        // if($product_photos)
        // {
        //     foreach ($product_photos as $key => $product_photo) 
        //     {
        //         unlink($destinationPath.$product_photo->photo_name); 
        //         unlink($destinationPhotosPath.$product_photo->photo_name); 
        //         unlink($destinationThumbnailPath.$product_photo->photo_name); 
        //     }
        // }

        PreviewProductPhoto::truncate();
        PreviewProductUnit::truncate();
        PreviewProduct::truncate();

        try {
            
            \DB::beginTransaction();
            //validate request parameters
            $this->validate($request, [
                'category' => 'max:255',
                'sub_category' => 'max:255',
                'name' => 'required|max:255',
                'description' => 'max:600',
                'tax' => 'max:255',
            ]);
            $product = new PreviewProduct();
            $product->category = $request->category;
            $product->sub_category = $request->sub_category;
            $product->name = $request->name;
            $product->description = $request->description;
            $product->tax = $request->tax;
            $product->save();
            // save images
            foreach ($request->product_images as $key => $image) {
                $product_photo = new PreviewProductPhoto();
                $product_photo->preview_product_id = $product->id;

                if($image)
                {
                   
                    $destinationPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR;


                    $image_parts = explode(";base64,", $image);
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $photo_name = uniqid() .'.'. $image_type;
                    $file = $destinationPath . $photo_name;
                    file_put_contents($file, $image_base64);

                    $this->resizeImageSave($image_base64, $photo_name);
                    $product_photo->photo_name = $photo_name;
                    $product_photo->photo_url = url('/uploads/products/'.$photo_name);
                    


                } 
                $product_photo->save();
            }


            // // save units
            foreach ($request->product as $key => $product_unit) 
            {
                $productUnits = new PreviewProductUnit();
                $productUnits->preview_product_id = $product->id;
                $productUnits->units = $product_unit['unit'];
                $productUnits->code = $product_unit['code'];
                $productUnits->mrp = $product_unit['mrp'];
                $productUnits->rate = $product_unit['rate'];
                $productUnits->moq = $product_unit['moq'];
                $productUnits->available = $product_unit['available'];
                $productUnits->stock = $product_unit['stock'];
                $productUnits->save();

            }
            \DB::commit();
            
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['message' => 'Something went wrong!'], 401);
        }
        
        $status = 2;
        $message = "Product saved succesfully";
        $product = PreviewProduct::where('id', '=', $product->id)->with('photos')->with('units')->get();
        return ResponseBuilder::result($status, $message, $product);
        
    }

    public function previewProductsDetails($id)
    {
        $product = PreviewProduct::where('id', '=', $id)->with('photos')->with('units')->get();

        if(count($product) > 0)
        {
            $status = 2;
            $message = "Product retrived succesfully";
        }
        else
        {
            $status = 3;
            $message = "Product not available";
        }
        return ResponseBuilder::result($status, $message, $product);
    }

    public function saveImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images.*' => 'required|image',
            'images.*' => 'max:5048'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 3, 
                'data' =>  $validator->errors(),
                'message' => 'Validation failed' 
            ]);
        }

        if($request->hasFile('images'))
        {
            $allowedfileExtension=['jpg','png','jpeg','gif','bmp'];
            $files = $request->file('images');
            foreach($files as $file)
            {
                $filename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $check=in_array($extension,$allowedfileExtension);
                if(! $check)
                {
                    $status=3;
                    $content = '';
                    $message = "Sorry Only Upload png, jpg, jpeg, gif, bmp";
                    return ResponseBuilder::result($status, $message, $content);
                }

            }
        }
        
        
        $uploaded_image = [];
        $uploaded_images = [];
        //save images
        if($request->images)
        {
            foreach ($request->images as $key => $image) 
            {
                
                if($image)
                {
                    
                    $destinationPath = ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "uploads". DIRECTORY_SEPARATOR . "products". DIRECTORY_SEPARATOR;
                     $product_photo = new ProductPhoto();
                     $product_photo->product_id = 0;
                     $image_parts = explode(";base64,", $image);
                     $image_type_aux = explode("image/", $image_parts[0]);
                     $image_type = $image_type_aux[1];
                     $image_base64 = base64_decode($image_parts[1]);
                     $photo_name = uniqid() .'.'. $image_type;
                     $file = $destinationPath . $photo_name;
                     file_put_contents($file, $image_base64);
                     
                     $this->resizeImageSave($image_base64, $photo_name);
                     $product_photo->photo_name = $photo_name;
                     $product_photo->photo_url = url('/uploads/products/'.$photo_name);
                     $uploaded_image['photo_name'] = $photo_name;
                     $uploaded_image['photo_url'] = url('/uploads/products/'.$photo_name);
                     $uploaded_images[] = $uploaded_image;
                    $product_photo->save();
                    $status = 2;
                    $message = "Images saved succesfully";
                    $data = uploaded_image;

                } 
            }
       
        }
        else {
            $status = 6;
            $message = "Something wrong !";
            $data = '';
        }
        //return ResponseBuilder::result($status, $message,  $data);
    }

    public function search($keyword)
    {
        $keyword = str_replace('%20', ' ',$keyword);
        
        $keyword = strtolower($keyword);
        $keyword_arr = explode(" ", $keyword);
        $search_string = "";
        foreach($keyword_arr as $word)
        {
            $search_string .= metaphone($word). " "; 
        }

        $user = User::find(Auth::id());
       
        $data = User::where('indexing', 'LIKE', '%' . $search_string . '%')->where('business_category', '=', $user->business_category)->orWhereRaw('LOWER(business_name) like ?', [strtolower('%'.$keyword . '%')])->orWhereRaw('LOWER(business_category) like ?', [strtolower('%'.$keyword . '%')])->orWhereRaw('LOWER(state) like ?', [strtolower('%'.$keyword . '%')])->pluck('business_name');
        $data = collect($data->toArray())->flatten()->all();

        
        $user_id = User::where('business_category', '=', $user->business_category)->pluck('id');
        
        $products = Product::where('indexing', 'LIKE', '%' . $search_string . '%')->where('user_id','=', $user_id)->orWhereRaw('LOWER(name) like ?', [strtolower('%'.$keyword . '%')])->pluck('name');
        
        //dd($products);
        foreach ($products as $key => $product) 
        {
            $data[] = $product;
        }
        $data =  array_intersect_key($data, array_unique(array_map('strtolower', $data)));

        if(count($data) > 0)
        {
            $status = 2;
            $message = "Search result found";
        }
        else
        {
            $message = 'No Details found. Try to search again !';
            $status = 4;
            $data = '';
        }
        return ResponseBuilder::result($status, $message, $data);

    }
    public function getSearchResult(Request $request)
    {
        $keyword = $request->search_keyword;
        $state = $request->state;
        $keyword = str_replace('%20', ' ',$keyword);

        $user_type = Auth::user()->pluck('user_type');
       
        if($state)
            $data = User::where('business_name', '=', $keyword)->where('state', '=', $state)->orWhere('business_category', '=', $keyword)->select('id', 'logo_url','business_name', 'city', 'state', 'user_type')->get();
        else
            $data = User::where('business_name', '=', $keyword)->orWhere('business_category', '=', $keyword)->select('id', 'logo_url','business_name', 'city', 'state', 'user_type')->get();

        
            
       
        $products = Product::where('name', '=', $keyword)->get();

        $product_search = [];
        foreach ($products as $key => $product) 
        {
            if($state)
            {
                if($product->user->state == $state)
                {
                    $product_search['user_type'] = '';
                    $product_search['id'] = $product->id;
                    $product_search['business_name'] = $product->user->business_name; 
                    foreach($product->photos as $photo ) {
                        $product_search['logo_url'] = $photo->photo_url;
                        break;
                    }
                    $product_search['city'] = $product->user->city;
                    $product_search['state'] = $product->user->state;
                    $data[] = $product_search;
                }
            }
            else{
                $product_search['user_type'] = '';
                $product_search['id'] = $product->id;
                $product_search['business_name'] = $product->user->business_name; 
                foreach($product->photos as $photo ) {
                    $product_search['logo_url'] = $photo->photo_url;
                    break;
                }
                $product_search['city'] = $product->user->city;
                $product_search['state'] = $product->user->state;
                $data[] = $product_search;
            }
        }


        if(count($data) > 0)
        {
            $status = 2;
            $message = "Search result found";
        }
        else
        {
            $message = 'No Details found. Try to search again !';
            $status = 4;
            $data = '';
        }
        return ResponseBuilder::result($status, $message, $data);

    }

    public function getProducts($user_id)
    {
        $productList = [];
        $products = Product::where('user_id', $user_id)->with('photos')->with('units')->get();

        if(count($products) > 0)
        {
            $product_details = [];
            foreach ($products as $key => $product) 
            {
                $product_details['id'] = $product->id;
                $product_details['name'] = $product->name;
                foreach ($product->units as $key => $unit) {
                    $product_details['unit_id'] = $unit->id;
                    $product_details['code'] = $unit->code;
                    $product_details['rate'] = $unit->rate;
                    $product_details['available'] = $unit->available;
                    $product_details['status'] = $unit->status;
                }
                foreach ($product->photos as $key => $photo) {
                    $product_details['banner_image'] = $photo->photo_url;
                    break;
                }
                $thumbnail_image = [];
                foreach ($product->photos as $key => $photo) {
                    $thumbnail_image[] = url('/uploads/products/thumbnail/'.$photo->photo_name);
                }
                $product_details['thumbnail_image'] = $thumbnail_image;
                
                $product_details['photos'] = $product->photos;
                $productList[] = $product_details;
            }
            $status = 2;
            $message = "Products retrived succesfully";
        }
        else
        {
            $status = 3;
            $message = "Products not available";
        }
        return ResponseBuilder::result($status, $message, $productList);
    }

    public function deleteProduct($id) 
    {
        $order = Order::where('product_id', '=', $id)->get();
        if($order)
        {
            $status = 4;
            $message = "Product can't be deleted as Order created for this product.";
            
        }
        else
        {
            if(Product::find($id)->delete())
            {
                    $status = 2;
                    $message = "Product deleted successfully.";
                
            }
            else
            {
            
                $status = 4;
                $message = "Product not found!";
                

            }
        }
        $content = "";
        return ResponseBuilder::result($status, $message, $content);
    }
}