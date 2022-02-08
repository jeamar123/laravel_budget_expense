<?php

namespace App\Http\Controllers;

use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Income::where( 'user_id', $request->user()->id );
        
        if( $request->has('start') && $request->has('end') ) {
            $query->whereBetween('date', [$request->get('start'), date('Y-m-d 23:59:59', strtotime($request->get('end')))]);
        }

        $incomes = $query->orderBy('date', 'ASC')->get();

        $total = 0;

        foreach ($incomes as $index => $obj) {
            $total += $obj->amount;
        }

        return response([ 
            'incomes' => $incomes,
            'total' => $total,
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
            'description' => 'required',
            'date' => 'required',
        ]);

        $income = Income::create([
            'amount' => $fields['amount'],
            'description' => $fields['description'],
            'user_id' => $request->user()->id,
            'date' => $fields['date'],
        ]);

        if($income){
            return response([ 
                'message' => 'Create Income Successful.', 
                'status' => true 
            ], 201);
        }

        return response([ 
            'message' => 'Create Income Failed.', 
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

        $income = Income::find($id);

        if($income){
            return response([ 
                'income' => $income,
                'message' => 'Success', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Income not found', 
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
        $check_income = Income::find($id);
        if(!$check_income){
            return response([ 
                'message' => 'Income not found', 
                'status' => false 
            ], 404);
        }

        $fields = $request->validate([
            'amount' => 'required',
            'description' => 'required',
            'date' => 'required',
        ]);

        $income = Income::find($id)->update([
            'amount' => $fields['amount'],
            'description' => $fields['description'],
            'date' => $fields['date'],
        ]);

        if($income){
            return response([ 
                'message' => 'Update Income Successful.', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Update Income Failed.', 
            'status' => false 
        ], 500);
    }

    public function bulkAddAndUpdate(Request $request)
    {

        $fields = $request->validate([
            'incomes' => 'required',
        ]);

        $arr = $request->get('incomes');

        foreach ($arr as $key => $item) {
            if(isset($item['id'])){
                $income = Income::where( 'id', $item['id'] )
                                    ->update([
                                        'user_id' => $request->user()->id,
                                        'amount' => $item['amount'],
                                        'date' => $item['date'],
                                        'description' => $item['description'],
                                    ]);
            }else{
                $income = Income::create([
                    'user_id' => $request->user()->id,
                    'amount' => $item['amount'],
                    'date' => $item['date'],
                    'description' => $item['description'],
                ]);
            }

            if(!$income){
                return response([ 
                    'message' => 'Bulk Add and Update Income Failed.', 
                    'status' => false 
                ], 500);
                break; 
            }
        }
        return response([ 
            'message' => 'Bulk Add and Update Income Successful.', 
            'status' => true 
        ], 200);
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
        $check_income = Income::find($id);
        if(!$check_income){
            return response([ 
                'message' => 'Income not found', 
                'status' => false 
            ], 404);
        }

        $income = Income::destroy($id);

        if($income){
            return response([ 
                'message' => 'Delete Income Successful.', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Delete Income Failed.', 
            'status' => false 
        ], 500);
    }

    /**
     * Get Total Income by Month
     *
     * @return \Illuminate\Http\Response
     */
    public function getMonthlyTotal(Request $request)
    {
        $year = date('Y');

        if( $request->has('year') ) {
            $year = $request->get('year');
        }

        // $start = date('Y-m-d', strtotime( 'first day of January ' . $year ));
        // $end = date('Y-m-d', strtotime( 'last day of December ' . $year ));

        $monthsArr = [];
        
        for ( $i = 1; $i <= 12 ; $i++ ) { 
            $query = DB::select(
                        DB::raw("SELECT 
                                date_format(date, '%M') as month, 
                                sum(amount) as total
                            FROM 
                                income 
                            WHERE 
                                date_format(date, '%m') = :monthNum
                                AND
                                date_format(date, '%Y') = :yearNum
                                AND
                                user_id = :userID 
                            GROUP BY 
                                date_format(date, '%M')"
                        ), 
                        array( 
                            'monthNum' => $i, 
                            'yearNum' => $year, 
                            'userID' => $request->user()->id 
                        )
                    );
            if(sizeof($query) > 0){
                $monthsArr[] = $query[0];
            }else{
                $monthsArr[] = [
                    "month" => date("F", mktime(0, 0, 0, $i, 10)),
                    "total" => 0
                ];
            }
        }

        return response([ 
            'months' => $monthsArr,
            'message' => 'Success', 
            'status' => true 
        ], 200);
    }
}
