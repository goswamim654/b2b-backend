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
        $products = Product::with('photos')->with('units')->get();

        if(count($products) > 0)
        {
            $productList = [];
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
        $product = Product::where('id', '=', $id)->with('photos')->with('units')->get();

        if(count($product) > 0)
        {
            $status = 2;
            $message = "Product retrieved successfully";
        }
        else
        {
            $status = 3;
            $message = "Product not available";
        }
        return ResponseBuilder::result($status, $message, $product);
    }

    public function store(Request $request)
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

            $product = new Product();
            $product->category = $request->category;
            $product->sub_category = $request->sub_category;
            $product->name = $request->name;
            $product->description = $request->description;
            $product->tax = $request->tax;
            $product->save();
            
            // save images
            foreach ($request->product_images as $key => $image) {
                $product_photo = new ProductPhoto();
                $product_photo->product_id = $product->id;

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
            \DB::commit();
            
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['message' => 'Something went wrong!'], 404);
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

        $imgThumbnail = Image::make($image_base64)->resize(100,null,function($constraint)
        {
            $constraint->aspectRatio();
        });
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
            $images_originals[] = $photo->photo_url;
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
    public function updateProductAvalibility(Request $request)
    {
        $unit = ProductUnit::find($request->unit_id);
        $unit->available  = $request->availability;
        $unit->save();
        $status = 2;
        $message = "Product availability updated succesfully";
        return ResponseBuilder::result($status, $message, $unit);

    }
    public function updateProductStatus(Request $request)
    {
        $unit = ProductUnit::find($request->unit_id);
        $unit->status  = $request->status;
        $unit->save();
        $status = 2;
        $message = "Product status updated succesfully";
        return ResponseBuilder::result($status, $message, $unit);

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
        
        if(count($request->images) == 0)
        {   
            $status=3;
            $content = '';
            $message = "Image file is required";
            return ResponseBuilder::result($status, $message, $content);
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

        // $validator = Validator::make($request->all(), [
        //     'images.*' => 'max:5048'
        // ]);

        // if ($validator->fails()) {
        //     $status=3;
        //     $content = '';
        //     $message = "Max file size allowed is 5MB";
        //     return ResponseBuilder::result($status, $message, $content);
        // }
        
        
    
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
                } 
            }
            $status = 2;
            $message = "Images saved succesfully";
        }
        else {
            $status = 6;
            $message = "Something wrong !";
            $product_photo = '';
        }
        return ResponseBuilder::result($status, $message,  $uploaded_images);
    }
}