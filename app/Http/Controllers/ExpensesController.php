<?php

namespace App\Http\Controllers;

use App\Models\Expenses;
use App\Models\Category;
use App\Models\Budget;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB;
use DateTime;
use DatePeriod;
use DateInterval;

class ExpensesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Expenses::where( 'user_id', $request->user()->id );

        if( $request->has('start') && $request->has('end') ) {
            $query->whereBetween('date', [$request->get('start'), date('Y-m-d 23:59:59', strtotime($request->get('end'))) ]);
        }

        if( $request->has('limitTo') ) {
            $query->skip(0)->take($request->get('limitTo'));
        }

        $expenses = $query->orderBy('date', 'DESC')->get();
        $total = 0;

        foreach ($expenses as $index => $obj) {
            $category_ids = json_decode($obj->category_ids);
            $categories = [];
            foreach ($category_ids as $value) {
                $category_details = Category::find($value);
                if($category_details){
                    $categories[] = $category_details;
                }
            }
            $obj['categories'] = $categories;

            $total += $obj->amount;
        }

        return response([ 
            'expenses' => $expenses,
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
            'description' => 'required',
            'amount' => 'required',
            'date' => 'required',
        ]);

        $expenses = Expenses::create([
            'description' => $fields['description'],
            'amount' => $fields['amount'],
            'category_ids' => json_encode($request->get('category_ids')),
            'user_id' => $request->user()->id,
            'date' => $fields['date'],
        ]);

        if($expenses){
            return response([ 
                'message' => 'Create Expenses Successful.', 
                'status' => true 
            ], 201);
        }

        return response([ 
            'message' => 'Create Expenses Failed.', 
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

        $expenses = Expenses::find($id);

        if($expenses){
            return response([ 
                'expenses' => $expenses,
                'message' => 'Success', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Expenses not found', 
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
        $check_expenses = Expenses::find($id);
        if(!$check_expenses){
            return response([ 
                'message' => 'Expenses not found', 
                'status' => false 
            ], 404);
        }

        $fields = $request->validate([
            'description' => 'required',
            'amount' => 'required',
            'date' => 'required',
        ]);

        $expenses = Expenses::find($id)->update([
            'description' => $fields['description'],
            'amount' => $fields['amount'],
            'category_ids' => json_encode($request->get('category_ids')),
            'date' => $fields['date'],
        ]);

        if($expenses){
            return response([ 
                'message' => 'Update Expenses Successful.', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Update Expenses Failed.', 
            'status' => false 
        ], 500);
    }

    
    public function bulkAddAndUpdate(Request $request)
    {

        $fields = $request->validate([
            'transactions' => 'required',
        ]);

        $arr = $request->get('transactions');

        foreach ($arr as $key => $item) {
            if(isset($item['id'])){
                $transaction = Expenses::where( 'id', $item['id'] )
                                    ->update([
                                        'user_id' => $request->user()->id,
                                        'amount' => $item['amount'],
                                        'date' => $item['date'],
                                        'description' => $item['description'],
                                        'category_ids' => json_encode($item['category_ids']),
                                    ]);
            }else{
                $transaction = Expenses::create([
                    'user_id' => $request->user()->id,
                    'amount' => $item['amount'],
                    'date' => $item['date'],
                    'description' => $item['description'],
                    'category_ids' => json_encode($item['category_ids']),
                ]);
            }

            if(!$transaction){
                return response([ 
                    'message' => 'Bulk Add and Update Transactions Failed.', 
                    'status' => false 
                ], 500);
                break; 
            }
        }
        return response([ 
            'message' => 'Bulk Add and Update Transactions Successful.', 
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
        $check_expenses = Expenses::find($id);
        if(!$check_expenses){
            return response([ 
                'message' => 'Expenses not found', 
                'status' => false 
            ], 404);
        }

        $expenses = Expenses::destroy($id);

        if($expenses){
            return response([ 
                'message' => 'Delete Expenses Successful.', 
                'status' => true 
            ], 200);
        }

        return response([ 
            'message' => 'Delete Expenses Failed.', 
            'status' => false 
        ], 500);
    }

    /**
     * Get Expenses Summary Values
     *
     * @return \Illuminate\Http\Response
     */
    public function getExpensesSummary(Request $request)
    {
        if( !$request->has('start') && !$request->has('end') ) {
            return response([ 
                'message' => 'Start and End date is required.', 
                'status' => false 
            ], 500);
        }

        $monthYear = date('F Y', strtotime( $request->get('start') ));
        $monthStart = date('Y-m-d', strtotime( 'first day of ' . $monthYear ));
        $monthEnd = date('Y-m-d', strtotime( 'last day of ' . $monthYear ));

        $expenses = Expenses::where( 'user_id', $request->user()->id )
                            ->whereBetween('date', [$request->get('start'), date('Y-m-d 23:59:59', strtotime($request->get('end')))])
                            ->selectRaw(
                                'sum(amount) as total, round(AVG(amount),0) as average',
                            )
                            ->first();
                            
        $getBudget = Budget::where( 'user_id', $request->user()->id )
                            ->whereBetween('date', [$monthStart, $monthEnd])
                            ->first();

        $spent = $expenses->total ? $expenses->total : 0;
        $balance = 0;
        $average = $expenses->average ? $expenses->average : 0;

        $getMonthTotalSpent = Expenses::where( 'user_id', $request->user()->id )
                            ->whereBetween('date', [$monthStart, $monthEnd])
                            ->selectRaw(
                                'sum(amount) as total, round(AVG(amount),0) as average',
                            )
                            ->first();

        $getTotalIncome = Income::where( 'user_id', $request->user()->id )
                        ->whereBetween('date', [$monthStart, $monthEnd])
                        ->selectRaw('sum(amount) as total')
                        ->first();
        
        // Get Planned Income
        $getIncomeBudget = Budget::where( 'user_id', $request->user()->id )
                        ->whereBetween('date', [$monthStart, $monthEnd])
                        ->where('name', 'income')
                        ->first();
        // Get Planned Spent
        $getSpentBudget = Budget::where( 'user_id', $request->user()->id )
                        ->whereBetween('date', [$monthStart, $monthEnd])
                        ->where('name', 'spent')
                        ->first();
        
        $spent = ($getMonthTotalSpent ? $getMonthTotalSpent->total : 0);
        $income = ($getTotalIncome ? $getTotalIncome->total : 0);
        $balance = $income - $spent;

        return response([ 
            'summary' => [
                'balance' => $balance, 
                'income' => [
                    'planned' => $getIncomeBudget ? $getIncomeBudget->amount : 0,
                    'actual' => $income,
                ],
                'spent' => [
                    'planned' => $getSpentBudget ? $getSpentBudget->amount : 0,
                    'actual' => $spent,
                ],
            ],
            'message' => 'Success', 
            'status' => true 
        ], 200);
    }

    /**
     * Get Expenses Summary Values
     *
     * @return \Illuminate\Http\Response
     */
    public function getExpensesSpent(Request $request)
    {
        
        if( !$request->has('start') && !$request->has('end') ) {
            return response([ 
                'message' => 'Start and End date is required.', 
                'status' => false 
            ], 500);
        }
        if( !$request->has('type') ) {
            return response([ 
                'message' => 'type is required.', 
                'status' => false 
            ], 500);
        }

        $year = date('Y', strtotime( $request->get('start') ));
        $month = date('m', strtotime( $request->get('start') ));
        $start = $request->get('start');
        $end = $request->get('end');

        $type = $request->get('type');
        $spentArr = [];

        if($type == 'daily'){
            $period = new DatePeriod(
                new DateTime($start),
                new DateInterval('P1D'),
                new DateTime($end . ' +1 day')
            );
    
            foreach ($period as $key => $value) {
                // date_format(date, '%b %d') as label, 
                $query = DB::select(
                            DB::raw("SELECT 
                                    date_format(date, '%d') as label, 
                                    sum(amount) as total
                                FROM 
                                    expenses 
                                WHERE 
                                    category_ids != '[8]'
                                    AND
                                    category_ids != '[9]'
                                    AND
                                    date_format(date, '%Y-%m-%d') = :date
                                    AND
                                    user_id = :userID 
                                GROUP BY 
                                    date_format(date, '%d')"
                            ), 
                            array( 
                                'date' => $value->format('Y-m-d'), 
                                'userID' => $request->user()->id 
                            )
                        );
                if(sizeof($query) > 0){
                    $spentArr[] = $query[0];
                }else{
                    $spentArr[] = [
                        "label" => $value->format('d'),
                        // "label" => $value->format('M d'),
                        "total" => 0
                    ];
                }
            }
        }

        if($type == 'weekly'){
            
            $weekArr = $this->getWeekDays($month, $year);

            foreach ($weekArr as $index => $value) {
                $query = Expenses::where( 'user_id', $request->user()->id )
                                ->where( 'category_ids', '!=' , '[8]' )
                                ->where( 'category_ids', '!=' , '[9]' )
                                ->whereBetween('date', [$value[0], $value[sizeof($value)-1]])
                                ->selectRaw('sum(amount) as total')
                                ->get();

                $spentArr[] = [
                    "label" => date('M d', strtotime($value[0])) . ' - ' . date('M d', strtotime($value[sizeof($value)-1])),
                    "total" => $query[0]->total ? $query[0]->total : 0
                ];
            }
        }

        if($type == 'monthly'){
            for ( $i = 1; $i <= 12 ; $i++ ) { 
                $query = DB::select(
                            DB::raw("SELECT 
                                    date_format(date, '%M') as label, 
                                    sum(amount) as total
                                FROM 
                                    expenses 
                                WHERE 
                                    category_ids != '[8]'
                                    AND
                                    category_ids != '[9]'
                                    AND
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
                    $spentArr[] = $query[0];
                }else{
                    $spentArr[] = [
                        "label" => date("F", mktime(0, 0, 0, $i, 10)),
                        "total" => 0
                    ];
                }
            }
        }

        return response([ 
            'spent' => $spentArr,
            'message' => 'Success', 
            'status' => true 
        ], 200);
    }

    public function getWeekDays($month, $year)
    {
        $p = new DatePeriod(
            new DateTime("$year-$month-01"),
            new DateInterval('P1D'),
            new DateTime("$year-$month-01" . " +1 month")
        );

        $dateByWeek = array();
        foreach ($p as $d) {
            $dateByWeek[ $d->format('W') ][] = $d->format('Y-m-d');
        }
        return $dateByWeek;
    }
}
