<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expenses;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Budget::where( 'user_id', $request->user()->id );
        
        if( $request->has('start') && $request->has('end') ) {
            $query->whereBetween('date', [$request->get('start'), $request->get('end')]);
        }

        $budgets = $query->orderBy('date', 'ASC')->get();

        return response([ 
            'budgets' => $budgets,
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
            'amount' => 'required',
            'date' => 'required',
        ]);

        $monthYear = date('F Y', strtotime( $request->get('date') )) ; 

        $start = date('Y-m-d', strtotime( 'first day of ' . $monthYear ));
        $end = date('Y-m-d', strtotime( 'last day of ' . $monthYear ));

        $check_budget = Budget::where( 'user_id', $request->user()->id )
                        ->whereBetween('date', [$start, $end])
                        ->first();
        
        if($check_budget){
            $budget = Budget::where( 'user_id', $request->user()->id )
                                ->whereBetween('date', [$start, $end])
                                ->update([
                                    'amount' => $fields['amount'],
                                    'date' => $fields['date'],
                                ]);
        }else{
            $budget = Budget::create([
                'amount' => $fields['amount'],
                'user_id' => $request->user()->id,
                'date' => $fields['date'],
            ]);
        }

        if($budget){
            return response([ 
                'message' => 'Create Budget Successful.', 
                'status' => true 
            ], 201);
        }

        return response([ 
            'message' => 'Create Budget Failed.', 
            'status' => false 
        ], 500);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function get(Request $request)
    {
        if(!$request->has('date')){
            return response([ 
                'message' => 'Date is required', 
                'status' => false 
            ], 404);
        }

        $monthYear = date('F Y', strtotime( $request->get('date') )) ; 

        $start = date('Y-m-d', strtotime( 'first day of ' . $monthYear ));
        $end = date('Y-m-d', strtotime( 'last day of ' . $monthYear ));

        // Get Planned Income
        $get_income_budget = Budget::where( 'user_id', $request->user()->id )
                        ->whereBetween('date', [$start, $end])
                        ->where('name', 'income')
                        ->first();
        // Get Planned Spent
        $get_spent_budget = Budget::where( 'user_id', $request->user()->id )
                        ->whereBetween('date', [$start, $end])
                        ->where('name', 'spent')
                        ->first();

        $planned = [
            0 => [
                'id' => $get_income_budget ? $get_income_budget->id : null,
                'amount' => $get_income_budget ? $get_income_budget->amount : 0,
                'date' => $get_income_budget ? $get_income_budget->date : $start,
                'name' => 'income',
            ],
            1 => [
                'id' => $get_spent_budget ? $get_spent_budget->id : null,
                'amount' => $get_spent_budget ? $get_spent_budget->amount : 0,
                'date' => $get_spent_budget ? $get_spent_budget->date : $start,
                'name' => 'spent'
            ]
        ];

        // Get Budgets
        $budgets = Budget::where( 'user_id', $request->user()->id )
                        ->whereBetween('date', [$start, $end])
                        ->where('name', '!=', 'spent')
                        ->where('name', '!=', 'income')
                        ->get();
        
        foreach ($budgets as $index => $obj) {
            $category_id = $obj->category_id;
            $category_details = Category::find($category_id);
            $obj['category'] = $category_details ? $category_details : null;
        }
        

        return response([ 
            'planned' => $planned,
            'budgets' => $budgets,
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
    public function bulkAddAndUpdate(Request $request)
    {
        if(!$request->has('date')){
            return response([ 
                'message' => 'Date is required', 
                'status' => false 
            ], 404);
        }
        

        $planned = $request->get('planned');
        $budgets = $request->get('budgets');

        if(!isset($planned[0])){
            return response([ 'message' => 'Income is required', 'status' => false ], 404);
        }
        if(!isset($planned[1])){
            return response([ 'message' => 'Spent is required', 'status' => false ], 404);
        }

        $monthYear = date('F Y', strtotime( $request->get('date') )) ; 

        $start = date('Y-m-d', strtotime( 'first day of ' . $monthYear ));
        $end = date('Y-m-d', strtotime( 'last day of ' . $monthYear ));

        foreach ($planned as $key => $item) {
            if(isset($item['id'])){
                $planned_budget = Budget::where( 'id', $item['id'] )
                                        ->update([
                                            'amount' => $item['amount'],
                                        ]);
            }else{
                $planned_budget = Budget::create([
                    'user_id' => $request->user()->id,
                    'amount' => $item['amount'],
                    'name' => $item['name'],
                    'date' => $item['date'],
                    'category_id' => 0,
                ]);
            }

            if(!$planned_budget){
                return response([ 
                    'message' => 'Bulk Add and Update Budget Failed.', 
                    'status' => false 
                ], 500);
                break; 
            }
        }

        foreach ($budgets as $key => $item) {
            if(isset($item['id'])){
                $budget = Budget::where( 'id', $item['id'] )
                                    ->update([
                                        'amount' => $item['amount'],
                                        'name' => $item['name'],
                                        'category_id' => $item['category_id'],
                                    ]);
            }else{
                $check_duplicate = Budget::where( 'user_id', $request->user()->id )
                                    ->whereBetween('date', [$start, $end])
                                    ->where('name', $item['name'])
                                    ->first();
                if($check_duplicate){
                    return response([ 
                        'message' => 'Duplicate Budget Category. Bulk Add and Update Budget Failed.', 
                        'status' => false 
                    ], 502);
                    break;
                }
                $budget = Budget::create([
                    'user_id' => $request->user()->id,
                    'date' => $item['date'],
                    'amount' => $item['amount'],
                    'name' => $item['name'],
                    'category_id' => $item['category_id'],
                ]);
            }

            if(!$budget){
                return response([ 
                    'message' => 'Bulk Add and Update Budget Failed.', 
                    'status' => false 
                ], 500);
                break; 
            }
        }
        

        return response([ 
            'message' => 'Update Budgets Successful.', 
            'status' => true 
        ], 201);
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
        $check_budget = Budget::find($id);
        if(!$check_budget){
            return response([ 
                'message' => 'Budget not found', 
                'status' => false 
            ], 404);
        }

        $budget = Budget::destroy($id);

        if($budget){
            return response([ 
                'message' => 'Delete Budget Successful.', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Delete Budget Failed.', 
            'status' => false 
        ], 500);
    }
    

    public function getBudgetSummary(Request $request)
    {
        if(!$request->has('date')){
            return response([ 
                'message' => 'Date is required', 
                'status' => false 
            ], 404);
        }

        $monthYear = date('F Y', strtotime( $request->get('date') )) ; 

        $start = date('Y-m-d', strtotime( 'first day of ' . $monthYear ));
        $end = date('Y-m-d', strtotime( 'last day of ' . $monthYear ));


        // Get Budgets
        $budgets = Budget::where( 'user_id', $request->user()->id )
                        ->whereBetween('date', [$start, $end])
                        ->where('name', '!=', 'spent')
                        ->where('name', '!=', 'income')
                        ->get();
        
        $budgetSummary = [];
        
        foreach ($budgets as $index => $obj) {
            $category_id = $obj->category_id;
            $category_details = Category::find($category_id);
            $obj['category'] = $category_details ? $category_details : null;

            $getBudgetSpent = Expenses::where( 'user_id', $request->user()->id )
                                ->where( 'category_ids', json_encode([$category_details->id]) )
                                ->whereBetween('date', [$start, $end])
                                ->selectRaw('sum(amount) as total')
                                ->first();
            $budgetSpent = $getBudgetSpent->total ? $getBudgetSpent->total : 0;
            $budgetRemaining = ($obj->amount - $budgetSpent);

            $budgetSummary[$index] = [
                "name" => $category_details->name,
                "budget" => $obj->amount,
                "spent" => $budgetSpent,
                "remaining" =>  $budgetRemaining < 0 ? 0 : $budgetRemaining,
            ];
        }

        return response([ 
            'summary' => $budgetSummary,
            'message' => 'Success', 
            'status' => true 
        ], 200);
    }
}
