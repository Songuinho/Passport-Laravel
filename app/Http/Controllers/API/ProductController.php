<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;


class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        // Récupérer tous les produits
        $products = Product::all();
        return $this->sendResponse(ProductResource::collection($products), 'Products retrieved successfully !');
    }


    public function store(Request $request): JsonResponse
    {
        // Valider et enregistrer un nouveau produit
        $input = $request->all();
        $validator = Validator::make($input, [
            "name"=> "required|unique:products",
            "description"=> "required",
            "price"=>"required"
            ],[
                "name.unique" => "Product already exist."
            ]);

        if($validator->fails()){
            return $this->sendError("Validation Error.", $validator->errors());
        }

        $products = Product::all();
        // $sameproduct = false;
        foreach($products as $product){
            if($input['name'] == $product->name){
                // $sameproduct = true;
                return sendError("Validation Error.", $validator->errors());
            }else{
                $product = Product::create($input);
                return $this->sendResponse(new ProductResource($product), 'Product created successfully !');
            }
        }
        // if($sameproduct === false){
        //     $product = Product::create($input);
        // }

    }

    public function show($id): JsonResponse
    {
        // Afficher un produit spécifique
        $product = Product::find($id);
        if ($product) {
            return $this->sendResponse(new ProductResource($product), 'Product retrieve successfully !');
        } else {
            return $this->sendError("Product not found.");
        }
    }


    public function update(Request $request, Product $product): JsonResponse
    {
        // Mettre à jour un produit existant
                $input = $request->all();
                // print_r($input);dd();
                $validator = Validator::make($input, [
                    "name"=>"required|unique:products",
                    "description"=>"required",
                    "price"=>"required"
                ],[
                    "name.unique" => "One product already exist with this name."
                ]);

                if($validator->fails()){
                    return $this->sendError("Validation Error. ", $validator->errors());
                }
                $product->name = $input['name'];
                $product->description = $input['description'];
                $product->price = $input['price'];
                $product->save();

                return $this->sendResponse(new ProductResource($product), 'Product update successfully !');
    }

    public function destroy(Product $product): JsonResponse
    {
        // Supprimer un produit
        // print_r($product);die();
        if ($product) {
            $name = $product['name'];
            $product->delete();
            return $this->sendResponse($name,'Deleted successfully !');
        } else {
            return response()->json('Product not found.');
        }
    }
}
