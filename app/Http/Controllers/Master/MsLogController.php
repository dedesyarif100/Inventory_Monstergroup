<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use App\Models\master\Asset;
use App\Models\master\Employee;
use App\Models\master\Log;
use App\Models\master\Category;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Helpers\FunctionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MsLogController extends Controller
{
    public function editorLog(Request $request)
    {
        $log = Log::find($request->log_id);
        $assets = Asset::all();
        // $employees = Employee::all();
        $employees = DB::connection('sqlsrv')->table('dbo.EmployeeInformation')->get();
        return view('ms_log.editor', compact('log', 'assets', 'employees'));
    }

    public function index()
    {
        // $cek = Log::select('qty_in', 'qty_out')->sum('qty_in', 'qty_out');
        // $log = Log::select(DB::raw('SUM(qty_in) AS qty_in, SUM(qty_out) AS qty_out'))->get(); // dadi collection
        // $log[0]->test = 'test';
        // dd($log[0]); // dadi array
        // $query_in = DB::select("SELECT SUM(qty_in) AS qty_in, SUM(qty_out) AS qty_out FROM logs");

        // $logs = Log::all();
        // $logs = DB::table('categories')->where('code', 'GA')->value('name');
        // $logs = DB::table('categories')->find(3);
        // $logs = DB::table('categories')->pluck('name', 'code');
        // $logs = Log::latest('id')->select('id as id_log', 'date as date_log', 'asset_id as asset', 'qty_in as jumlah_masuk')->first();
        // $logs = Log::latest('id')->select('id as id_log', 'date as date_log', 'asset_id as asset', 'qty_in as jumlah_masuk')->get()->chunk(10);
        // $logs = Log::latest('id')->select('id as id_log', 'date as date_log', 'asset_id as asset', 'qty_in as jumlah_masuk', 'notes')->where('notes', 'LIKE', '%service%')->get();
        // $logs = Log::first('id')->select('id as id_log', 'date as date_log', 'asset_id as asset', 'qty_in as jumlah_masuk', 'notes')->where('notes', 'LIKE', '%service%')->get();

        // $asset = DB::table('assets')->count();
        // $asset = DB::table('assets')->max('code');
        // $asset = DB::table('assets')->min('code');
        // $asset = DB::table('assets')->where('code', 'LIKE', '%IT%')->sum('quantity');
        // $asset =  DB::table('assets')->where('code', 'LIKE', '%IT%')->avg('quantity');
        // $asset = DB::table('assets')->where('code', 'LIKE', '%IT%')->get('quantity');

        // $asset = DB::table('logs')->where('notes', 'LIKE', '%rusak%')->exists();
        // $asset = DB::table('logs')->where('notes', 'LIKE', '%rusak%')->doesntExist();
        // $logs = DB::table('logs')->where('notes', 'LIKE', '%dikembalikan%')->distinct()->get();

        // $query = DB::table('logs')->select('notes');
        // $logs = $query->addSelect('asset_id')->get();
        // dd($logs);

        $logs = Log::all();
        return view('ms_log.index', compact('logs'));

        // $emp = DB::connection('sqlsrv')->table('dbo.EmployeeInformation')->get();
        // dd($emp);
    }

    public function getLog()
    {
        // dd(FunctionHelper::RUSAK);
        $log = Log::orderBy('id', 'DESC')->get();
        // dd($log->toArray());
        return DataTables::of($log)
        ->addIndexColumn()
        ->editColumn('code', function($log) {
            return $log->asset->code;
        })
        ->editColumn('asset', function($log) {
            return $log->asset->name;
        })
        ->addColumn('employee', function($log) {
            if ($log->employee_id == null) {
                return '-';
            } else {
                return $log->employee->Name;
            }
        })
        ->editColumn('qty_in', function($log) {
            if ($log->qty_in != 0) {
                return '<div style="background-color: #bfdeff;"> '.$log->qty_in.' </div>';
            } else {
                return $log->qty_in;
            }
        })
        ->editColumn('qty_out', function($log) {
            if ($log->qty_out != 0) {
                return '<div style="background-color: #bfdeff;"> '.$log->qty_out.' </div>';
            } else {
                return $log->qty_out;
            }
        })
        ->editColumn('type', function($log) {
            if (FunctionHelper::DIKEMBALIKAN == $log->type) {
                return '<span class="name badge bg-success" style="color: white;"> DIKEMBALIKAN </span>';
            } else if (FunctionHelper::DIPINJAMKAN == $log->type) {
                return '<span class="name badge bg-primary" style="color: white;"> DIPINJAMKAN </span>';
            } else if (FunctionHelper::SERVICE == $log->type) {
                return '<span class="name badge bg-warning" style="color: white;"> SERVICE </span>';
            } else if (FunctionHelper::RUSAK == $log->type) {
                return '<span class="name badge bg-danger" style="color: white;"> RUSAK </span>';
            } else if (FunctionHelper::HILANG == $log->type) {
                return '<span class="name badge bg-danger" style="color: white;"> HILANG </span>';
            } else if (FunctionHelper::KELUAR == $log->type) {
                return '<span class="name badge bg-info" style="color: white;"> KELUAR </span>';
            } else if (FunctionHelper::HIBAH == $log->type) {
                return '<span class="name badge bg-secondary" style="color: white;"> HIBAH </span>';
            } else if (FunctionHelper::BELI == $log->type) {
                return '<span class="name badge bg-success" style="color: white;"> BELI </span>';
            } else if (FunctionHelper::JUAL == $log->type) {
                return '<span class="name badge bg-primary" style="color: white;"> JUAL </span>';
            } else if (FunctionHelper::STOCK_AWAL == $log->type) {
                return '<span class="name badge bg-success" style="color: white;"> STOCK AWAL </span>';
            }
        })
        ->rawColumns(['qty_in', 'qty_out', 'type'])
        ->make(true);
    }

    public function sendToDB(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'date' => ['required'],
                'qrcode' => ['required'],
                'asset_id' => ['required'],
                'type' => ['required'],
                'employee_id' => $request->type == FunctionHelper::DIKEMBALIKAN || $request->type == FunctionHelper::DIPINJAMKAN ? ['required'] : '',
                'qty_in' => ['required'],
            ],
        );
        // $asset = Asset::all();
        $asset = Asset::find($request->asset_id);
        // dd($beforeQuantity);
        // dd($asset->quantity < $request->qty_in);

        // $log = new Log();
        // dd(Log::where('asset_id', $request->asset_id)->count());
        // dd($log->asset_id->count() > 1);

        if($validator->fails()) {
            if (empty($request->date)) {
                return response()->json(['code' => 0, 'msg' => 'date is required']);
            } else if (empty($request->asset_id)) {
                return response()->json(['code' => 0, 'msg' => 'asset is required']);
            } else if (empty($request->type)) {
                return response()->json(['code' => 0, 'msg' => 'type is required']);
            } else if (empty($request->qty_in)) {
                return response()->json(['code' => 0, 'msg' => 'employee / quantity is required']);
            }
            // return response()->json(['code' => 0, 'msg' => 'error']);
        } else {
            // $asset = Asset::find($request->asset_id);

            $log = new Log();
            $log->date = $request->date;
            $log->qrcode = uniqid();
            $log->asset_id = $request->asset_id;
            $log->type = $request->type;
            $log->employee_id = $request->employee_id;
            $log->notes = $request->notes;

            if ($request->type == FunctionHelper::DIKEMBALIKAN || $request->type == FunctionHelper::HIBAH || $request->type == FunctionHelper::BELI) {
                $cek = Log::where([
                    ['date', $request->date],
                    ['asset_id', $request->asset_id],
                    // ['type', $request->type],
                    // ['employee_id', $request->employee_id],
                    ['qty_in', $request->qty_in]
                ])->exists();
                // dd($cek);
                // dd($cek == true);

                // $cek = Log::find($request->asset_id);
                $log->qty_in = $request->qty_in;
                $log->qty_out = 0;
                // Asset::where('id', $request->asset_id)->update([
                //     'quantity' => $asset->quantity + $request->qty_in,
                // ]);

            } else {
                if ($asset->quantity < $request->qty_in) {
                    return response()->json(['code' => 0, 'msg' => 'barang tidak cukup']);
                }
                $log->qty_in = 0;
                $log->qty_out = $request->qty_in;
                // Asset::where('id', $request->asset_id)->update([
                //     'quantity' => $asset->quantity - $request->qty_in,
                // ]);
            }

            $query = $log->save();
            if (!$query) {
                return response()->json(['code' => 0, 'msg' => 'Something went wrong']);
            } else {
                return response()->json(['code' => 1, 'msg' => 'New Log has been successfuly saved']);
            }
        }
    }
}
