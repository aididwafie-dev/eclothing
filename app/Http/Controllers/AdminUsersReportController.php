<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Response;
use Session;

class AdminUsersReportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        return view('admin/orders/users_report');
    }

    public function orderedSizeCountUniformwise($uniforms_id)
    {
        $uniform_clothes = DB::table('uniform_clothes')->where('uniforms_id', '=', $uniforms_id)->get();
        $ordered_clothes = [];

        foreach ($uniform_clothes as $value) {
            $order = DB::table('ordered_clothes')->where('clothes_slug', '=', $value->clothes_slug)->groupBy('size')->get();
            foreach ($order as $orders) {
                $ordered_clothes[] = [
                    'orders' => $orders,
                    'count' => DB::table('ordered_clothes')->where('clothes_slug', '=', $value->clothes_slug)->where('size', '=', $orders->size)->groupBy('size')->count(),
                ];
            }
        }
        return $ordered_clothes;
    }

    public function orderDetailsWithUserDetails($uniforms_id)
    {
        $orders = DB::table('orders')->where('deleted', '=', 0)->where('uniforms_id', '=', $uniforms_id)->get();
        $orders_detail = [];

        foreach ($orders as $order) {
            $personal_details = DB::table('personal_details')->where('user_id', '=', $order->user_id)->first();
            if (!empty($personal_details)) {
                $orders_detail[] = [
                    'user_details' => $personal_details,
                    'rank' => DB::table('pangkats')->where('id', '=', $personal_details->pangkat)->first(),
                    'unit' => DB::table('units')->where('id', '=', $personal_details->unit)->first(),
                    'cloth_details' => DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get(),
                ];
            }
        }

        return !empty($orders_detail) ? $orders_detail : 0;
    }

    public function userSelectUnitWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $units = DB::table('units')->get();
        return view('admin/users/select_report_unitWise', ["units" => $units]);
    }

    public function getReportUniformTag(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }

        $users = DB::table('gen_users')
            ->leftJoin("personal_details", "gen_users.id", "=", "personal_details.user_id")
            ->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")
            ->leftJoin("units", "units.id", "=", "personal_details.unit")
            ->where('status', '=', 1)
            ->where('name_tag', '!=', '')
            ->whereNotNull('name_tag');

        if ($request->has('unit_id') && $request->input('unit_id')) {
            $users->where('personal_details.unit', '=', $request->input('unit_id'));
        }
        if ($request->has('officer_recruit') && $request->input('officer_recruit')) {
            $users->where('pangkats.officer_recruit', '=', $request->input('officer_recruit'));
        }
        $users = $users->get();

        return view('admin/users/uniformTagReport', ["users" => $users, "units" => DB::table('units')->get()]);
    }

    public function getReportStrengthUnits(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }

        $users = DB::table('gen_users')
            ->leftJoin("personal_details", "gen_users.id", "=", "personal_details.user_id")
            ->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")
            ->where('status', '=', 1)
            ->get();

        $totals = [];
        foreach ($users as $user) {
            if (!isset($totals[$user->unit_lama])) {
                $totals[$user->unit_lama] = ['name' => $user->unit_lama, "man_officer" => 0, "woman_officer" => 0, "man_other" => 0, "woman_other" => 0];
            }

            if ($user->jantina == "1" && $user->ketukangan_type == "1") {
                $totals[$user->unit_lama]['man_officer']++;
            } elseif ($user->jantina == "1" && $user->ketukangan_type == "2") {
                $totals[$user->unit_lama]['man_other']++;
            } elseif ($user->jantina == "2" && $user->ketukangan_type == "1") {
                $totals[$user->unit_lama]['woman_officer']++;
            } elseif ($user->jantina == "2" && $user->ketukangan_type == "2") {
                $totals[$user->unit_lama]['woman_other']++;
            }
        }

        return view('admin/users/StrengthUnitsReport', ["totals" => $totals]);
    }

    public function loadClothAjax(Request $request)
    {
        $uniforms_id = $request->input('uniforms_id');
        $clothes = DB::table('uniform_clothes')->where('uniforms_id', '=', $uniforms_id)->get();

        echo "<div class='form-group'>
                <label class='label_'>Select Cloth Type</label>
                <select required class='form-control' id='clothes_slug' name='clothes_slug'>
                    <option value=''>Choose cloth type....</option>";
        foreach ($clothes as $value) {
            echo "<option value=" . $value->clothes_slug . ">" . $value->clothes_type . "</option>";
        }
        echo "</select></div>";
    }

    public function orderedSizeCountClothwise($clothes_slug)
    {
        $order = DB::table('ordered_clothes')->where('clothes_slug', '=', $clothes_slug)->groupBy('size')->get();
        $ordered_clothes = [];

        foreach ($order as $orders) {
            $ordered_clothes[] = [
                'orders' => $orders,
                'count' => DB::table('ordered_clothes')->where('clothes_slug', '=', $clothes_slug)->where('size', '=', $orders->size)->groupBy('size')->count(),
            ];
        }
        return $ordered_clothes;
    }

    public function getReportWithoutOrders(Request $request)
    {
        $users = DB::table('gen_users')
            ->leftJoin("personal_details", "gen_users.id", "=", "personal_details.user_id")
            ->leftJoin("units", "units.id", "=", "personal_details.unit")
            ->where('status', '=', 1);

        if ($request->has('unit_id') && $request->input('unit_id')) {
            $users->where('personal_details.unit', '=', $request->input('unit_id'));
        }

        $users = $users->get();
        $users_result = [];

        foreach ($users as $user) {
            $orders = DB::table('orders')->where('user_id', '=', $user->id)->first();
            if (!$orders && $user && $user->s_id) {
                $users_result[] = $user;
            }
        }

        $units = DB::table('units')->get();
        return view('admin/users/withoutOrdersReport', ["users" => $users_result, "units" => $units]);
    }

    public function getReportUnitWise(Request $request)
    {
        $units_all = DB::table('units')->get();
        $units = [];
        $users = [];
        $total_users = [];

        foreach ($units_all as $unit) {
            $units[$unit->id] = $unit->value;    
            $total_users[$unit->id] = DB::table('personal_details')->where("personal_details.unit", "=", $unit->id)->count();
            $orders = DB::table('orders')->leftJoin('personal_details', 'personal_details.user_id', '=', "orders.user_id")->where('deleted', '=', 0)->where('personal_details.unit', '=', $unit->id)->get();
            foreach ($orders as $order) {
                $users[$unit->id][$order->user_id] = $order->user_id;
            }
        }

        return view('admin/users/unitwise_usersReport', ["users" => $users, "units" => $units, "total" => $total_users]);
    }

    public function adminCreatExcelUnit(Request $request)
    {
        $units_all = DB::table('units')->get();
        $units = [];
        $users = [];
        $total_users = [];

        foreach ($units_all as $unit) {
            $units[$unit->id] = $unit->value;    
            $total_users[$unit->id] = DB::table('personal_details')->where("personal_details.unit", "=", $unit->id)->count();
            $orders = DB::table('orders')->leftJoin('personal_details', 'personal_details.user_id', '=', "orders.user_id")->where('deleted', '=', 0)->where('personal_details.unit', '=', $unit->id)->get();
            foreach ($orders as $order) {
                $users[$unit->id][$order->user_id] = $order->user_id;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->setCellValue('A1', 'Unit name');
        $sheet->setCellValue('B1', 'Users with order');
        $sheet->setCellValue('C1', 'Users without order');

        $total_count = 2;
        foreach ($units as $unit_id => $unit_name) {
            $sheet->setCellValue('A' . $total_count, $units[$unit_id]);
            $sheet->setCellValue('B' . $total_count, isset($users[$unit_id]) ? count($users[$unit_id]) : 0);
            $sheet->setCellValue('C' . $total_count, isset($users[$unit_id]) ? $total_users[$unit_id] - count($users[$unit_id]) : $total_users[$unit_id]);
            $total_count++;
        }

        $filename = 'Users by unit ' . time() . '.xlsx';
        $path = storage_path() . '/Order-Report/' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return Response::download($path, $filename);
    }

    public function adminCreatExcelStrengthUnits(Request $request)
    {
        $users = DB::table('gen_users')
            ->leftJoin("personal_details", "gen_users.id", "=", "personal_details.user_id")
            ->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")
            ->where('status', '=', 1)
            ->get();

        $totals = [];
        foreach ($users as $user) {
            if (!isset($totals[$user->unit_lama])) {
                $totals[$user->unit_lama] = ['name' => $user->unit_lama, "man_officer" => 0, "woman_officer" => 0, "man_other" => 0, "woman_other" => 0];
            }

            if ($user->jantina == "1" && $user->ketukangan_type == "1") {
                $totals[$user->unit_lama]['man_officer']++;
            } elseif ($user->jantina == "1" && $user->ketukangan_type == "2") {
                $totals[$user->unit_lama]['man_other']++;
            } elseif ($user->jantina == "2" && $user->ketukangan_type == "1") {
                $totals[$user->unit_lama]['woman_officer']++;
            } elseif ($user->jantina == "2" && $user->ketukangan_type == "2") {
                $totals[$user->unit_lama]['woman_other']++;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->setCellValue('A1', 'Unit');
        $sheet->setCellValue('B1', 'Officer-Men');
        $sheet->setCellValue('C1', 'Officer-Women');
        $sheet->setCellValue('D1', 'Officer-Total');
        $sheet->setCellValue('E1', 'Other Rank-Men');
        $sheet->setCellValue('F1', 'Other Rank-Women');
        $sheet->setCellValue('G1', 'Other Rank-Total');
        $sheet->setCellValue('H1', 'Overall');

        $total_count = 2;
        $overall_count = 0;

        foreach ($totals as $total) {
            $overall_count += $total['man_officer'] + $total['woman_officer'] + $total['man_other'] + $total['woman_other'];
            $sheet->setCellValue('A' . $total_count, $total['name']);
            $sheet->setCellValue('B' . $total_count, $total['man_officer']);
            $sheet->setCellValue('C' . $total_count, $total['woman_officer']);
            $sheet->setCellValue('D' . $total_count, $total['man_officer'] + $total['woman_officer']);
            $sheet->setCellValue('E' . $total_count, $total['man_other']);
            $sheet->setCellValue('F' . $total_count, $total['woman_other']);   
            $sheet->setCellValue('G' . $total_count, $total['man_other'] + $total['woman_other']);
            $sheet->setCellValue('H' . $total_count, $total['man_officer'] + $total['woman_officer'] + $total['man_other'] + $total['woman_other']);
            $total_count++;
        }

        $total_count++;
        $sheet->setCellValue('A' . $total_count, 'Total order');
        $sheet->setCellValue('C' . $total_count, $overall_count);

        $filename = 'Users strength ' . time() . '.xlsx';
        $path = storage_path() . '/Order-Report/' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return Response::download($path, $filename);
    }

    public function adminCreatExcelUniformTags(Request $request)
    {
        $users = DB::table('gen_users')
            ->leftJoin("personal_details", "gen_users.id", "=", "personal_details.user_id")
            ->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")
            ->where('status', '=', 1)
            ->where('name_tag', '!=', '')
            ->whereNotNull('name_tag')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->setCellValue('A1', 'Service ID');
        $sheet->setCellValue('B1', 'Rank');
        $sheet->setCellValue('C1', 'Name');
        $sheet->setCellValue('D1', 'Unit');
        $sheet->setCellValue('E1', 'Uniform Tag');

        $total_count = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $total_count, $user->s_id);
            $sheet->setCellValue('B' . $total_count, $user->value);
            $sheet->setCellValue('C' . $total_count, $user->name);
            $sheet->setCellValue('D' . $total_count, $user->unit_lama);
            $sheet->setCellValue('E' . $total_count, $user->name_tag);
            $total_count++;
        }

        $total_count++;
        $sheet->setCellValue('A' . $total_count, 'Total order');
        $sheet->setCellValue('C' . $total_count, count($users));

        $filename = 'Users uniform tags ' . time() . '.xlsx';
        $path = storage_path() . '/Order-Report/' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return Response::download($path, $filename);
    }

    public function adminCreatExcelWithoutOrders(Request $request)
    {
        $users = DB::table('gen_users')
            ->leftJoin("personal_details", "gen_users.id", "=", "personal_details.user_id")
            ->leftJoin("units", "units.id", "=", "personal_details.unit")
            ->where('status', '=', 1)
            ->get();

        $users_result = [];
        foreach ($users as $user) {
            $orders = DB::table('orders')->where('user_id', '=', $user->id)->first();
            if (!$orders && $user && $user->s_id) {
                $users_result[] = $user;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->setCellValue('A1', 'Service ID');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Telephone');
        $sheet->setCellValue('D1', 'Unit');
        $sheet->setCellValue('E1', 'Created');
        $sheet->setCellValue('F1', 'Updated');

        $total_count = 2;
        foreach ($users_result as $user) {
            $sheet->setCellValue('A' . $total_count, $user->s_id);
            $sheet->setCellValue('B' . $total_count, $user->name);
            $sheet->setCellValue('C' . $total_count, $user->telephone_number);
            $sheet->setCellValue('D' . $total_count, $user->value);
            $sheet->setCellValue('E' . $total_count, $user->created_at);
            $sheet->setCellValue('F' . $total_count, $user->updated_at);
            $total_count++;
        }

        $total_count++;
        $sheet->setCellValue('A' . $total_count, 'Total order');
        $sheet->setCellValue('C' . $total_count, count($users_result));

        $filename = 'Users without orders ' . time() . '.xlsx';
        $path = storage_path() . '/Order-Report/' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return Response::download($path, $filename);
    }
}
