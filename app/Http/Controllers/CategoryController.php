<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expenses;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $categories = [];
        $query = Category::where('user_id', $request->user()->id);
        if( $request->has('search') ) {
            $query->where('name', 'like', '%'.$request->get('search').'%');
        }

        $categories = $query->orderBy('name', 'ASC')->get();

        return response([ 
            'categories' => $categories,
            'message' => 'Success', 
            'status' => true 
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
        ]);

        // Check duplicate email
        $category_name = Category::where('name', $fields['name'])->first();

        if($category_name){
            return response([
                'message' => 'Category already exist',
                'status' => false
            ], 409);
        }

        $category = Category::create([
            'name' => $fields['name'],
            'user_id' => $request->user()->id,
        ]);

        if($category){
            return response([ 
                'message' => 'Create Category Successful.', 
                'status' => true 
            ], 201);
        }

        return response([ 
            'message' => 'Create Category Failed.', 
            'status' => false 
        ], 500);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request, $id)
    {
        if(!$id){
            return response([ 
                'message' => 'ID id required', 
                'status' => false 
            ], 404);
        }

        $category = Category::find($id);
        

        if($category){
            return response([ 
                'category' => $category,
                'message' => 'Success', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Category not found', 
            'status' => false 
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Check if user exist
        $check_category = Category::find($id);
        if(!$check_category){
            return response([ 
                'message' => 'Category not found', 
                'status' => false 
            ], 404);
        }

        $fields = $request->validate([
            'name' => 'required|string',
        ]);

        // Check duplicate email
        $category_name = Category::where('name', $fields['name'])->where('id', '!=', $id)->first();
        if($category_name){
            return response([
                'message' => 'Category name already exist',
                'status' => false
            ], 409);
        }

        $category = Category::find($id)->update([
            'name' => $fields['name'],
        ]);

        if($category){
            return response([ 
                'message' => 'Update Category Successful.', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Update Category Failed.', 
            'status' => false 
        ], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Check if user exist
        $check_category = Category::find($id);
        if(!$check_category){
            return response([ 
                'message' => 'Category not found', 
                'status' => false 
            ], 404);
        }

        $category = Category::destroy($id);

        if($category){
            return response([ 
                'message' => 'Delete Category Successful.', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Delete Category Failed.', 
            'status' => false 
        ], 500);
    }

    /**
     * Get Category Summary Percentage
     *
     * @return \Illuminate\Http\Response
     */
    public function getCategoryPercentage(Request $request)
    {
        if( !$request->has('start') && !$request->has('end') ) {
            return response([ 
                'message' => 'Start and End date is required.', 
                'status' => false 
            ], 500);
        }

        $thisMonthText = date('F', strtotime($request->get('start')));
        $thisMonthStart = $request->get('start');
        $thisMonthEnd = $request->get('end');

        $expenses = Expenses::where( 'user_id', $request->user()->id )
                            ->whereBetween('date', [$thisMonthStart, $thisMonthEnd])
                            ->where('category_ids', '!=' , '[8]')
                            ->where('category_ids', '!=' , '[9]')
                            ->get();

        $expenses_count = sizeof($expenses); 
        $expenses_total = 0;

        $categories = [];
        foreach ($expenses as $index => $obj) {
            $category_ids = json_decode($obj->category_ids);
            foreach ($category_ids as $value) {
                $category_details = Category::find($value);
                if($category_details){
                    if(!array_key_exists($category_details->name, $categories)){
                        $categories[$category_details->name] = [
                            'value' => 0,
                            'percentage' => 0,
                        ];
                    }

                    $categories[$category_details->name]['value'] += $obj->amount;
                }
            }

            $expenses_total += $obj->amount;
        }

        foreach ($categories as $index => $obj) {
            $categories[$index]['percentage'] = ($obj['value'] / $expenses_total) * 100;
        }

        arsort($categories);

        array_splice($categories, $request->get('limitTo'));

        return response([ 
            'values' => [
                'thisMonth' => strtolower($thisMonthText),
                'count' => $expenses_count,
                'overall_total' => $expenses_total,
                'category_total' => $categories,
            ],
            'message' => 'Success', 
            'status' => true 
        ], 200);
    }
}
