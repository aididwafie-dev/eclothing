<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Response;
use Session;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        return view('admin/orders/order_report');
    }

    public function orderSelectUniformWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms = DB::table('uniforms')->where("active", 1)->get();
        return view('admin/orders/selectUniformwise_report', ["uniforms" => $uniforms]);
    }

    public function orderSelectUniformUnitWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms = DB::table('uniforms')->where("active", 1)->get();
        $units = DB::table('units')->get();
        return view('admin/orders/selectUniformUnitwise_report', ["uniforms" => $uniforms, "units" => $units]);
    }

    public function orderedSizeCountUniformwise($uniforms_id)
    {
        $uniform_clothes = DB::table('uniform_clothes')->where('uniforms_id', '=', $uniforms_id)->get();

        $ordered_clothes = [];
        $i = 0;
        foreach ($uniform_clothes as $value) {
            $order = DB::table('ordered_clothes')
                ->where('clothes_slug', '=', $value->clothes_slug)
                ->groupBy('size')
                ->get();

            $j = 0;
            foreach ($order as $orders) {
                $ordered_clothes[$i][$j] = [
                    'orders' => $orders,
                    'count' => DB::table('ordered_clothes')
                        ->where('clothes_slug', '=', $value->clothes_slug)
                        ->where('size', '=', $orders->size)
                        ->count(),
                ];
                $j++;
            }
            $i++;
        }
        return $ordered_clothes;
    }

    public function orderDetailsWithUserDetails($uniforms_id)
    {
        $orders = DB::table('orders')
            ->leftJoin("personal_details", "personal_details.user_id", "=", "orders.user_id")
            ->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")
            ->where('uniforms_id', '=', $uniforms_id)
            ->where('deleted', '=', 0)
            ->orderBy("pangkats_order", "asc")
            ->orderBy("s_id", "asc")
            ->get();

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

    public function getReportUniformWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms_id = $request->input('uniforms_id');
        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
        $orders = DB::table('orders')->where('deleted', '=', 0)->where('uniforms_id', '=', $uniforms_id)->first();

        $ordered_clothes = !empty($orders) ? $this->orderedSizeCountUniformwise($uniforms_id) : 0;

        return view('admin/orders/uniformwise_orderReport', ["uniforms" => $uniforms, "ordered_clothes" => $ordered_clothes]);
    }

    // Refactored Excel export for Uniform
    public function adminCreatExcelUniform(Request $request, $id)
    {
        $uniforms_id = $id;
        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
        $ordered_clothes = $this->orderedSizeCountUniformwise($uniforms_id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set column widths
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Style header and alignment
        $sheet->getStyle('A1:D100')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getFont()->setBold(true);

        // Set headers
        $sheet->setCellValue('A1', 'Uniform Type');
        $sheet->setCellValue('B1', 'Clothes Type');
        $sheet->setCellValue('C1', 'Clothes Size');
        $sheet->setCellValue('D1', 'Clothes Quantity');
        $sheet->setCellValue('A2', $uniforms->uniform_type);

        $count = 2;
        $number_of_cloth = 0;

        foreach ($ordered_clothes as $orderedClothes) {
            $number_of_cloth++;
            foreach ($orderedClothes as $clothes) {
                $sheet->setCellValue('B' . $count, $clothes['orders']->clothes);
                $sheet->setCellValue('C' . $count, $clothes['orders']->size);
                $sheet->setCellValue('D' . $count, $clothes['count']);
                $count++;
            }
        }

        $total_count = 0;
        foreach ($ordered_clothes[0] as $clothes) {
            $total_count += $clothes['count'];
        }
        $count++;

        $sheet->getStyle("B{$count}:D{$count}")->getFont()->setBold(true);
        $sheet->setCellValue('B' . $count, 'Total clothes');
        $sheet->setCellValue('D' . $count, 'Total order');
        $count++;

        $sheet->getStyle("B{$count}:D{$count}")->getFont()->setBold(true);
        $sheet->setCellValue('B' . $count, $number_of_cloth);
        $sheet->setCellValue('D' . $count, $total_count);

        $sheet->setTitle('Orders');

        $filename = 'Uniform ' . $uniforms->uniform_type . ' orders ' . time() . '.xlsx';
        $path = storage_path('Order-Report/' . $filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path, $filename);
    }

    public function orderSelectUserWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms = DB::table('uniforms')->where("active", 1)->get();
        return view('admin/orders/selectUserwise_report', ["uniforms" => $uniforms]);
    }

    public function orderSelectUserWiseUnit(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $units = DB::table('units')->get();
        return view('admin/orders/selectUserwise_report_unit', ["units" => $units]);
    }

    public function getReportUserWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms_id = $request->input('uniforms_id');
        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
        $uniform_clothes = DB::table('uniform_clothes')->where('uniforms_id', '=', $uniforms_id)->get();
        $orders = DB::table('orders')->where('deleted', '=', 0)->where('uniforms_id', '=', $uniforms_id)->first();

        $orders_detail = !empty($orders) ? $this->orderDetailsWithUserDetails($uniforms_id) : 0;

        return view('admin/orders/userDetailsWith_orderReport', ["uniforms" => $uniforms, "uniform_clothes" => $uniform_clothes, "orders_detail" => $orders_detail]);
    }

    public function getReportUserWiseUnit(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }

        $unit_id = $request->input('unit_id');
        $unit = DB::table('units')->where('id', '=', $unit_id)->first();
        $users = DB::table('personal_details')
            ->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")
            ->where('unit', '=', $unit_id)
            ->orderBy("pangkats_order", "asc")
            ->orderBy("s_id", "asc")
            ->get();

        $userids = $users->pluck('user_id')->toArray();

        $uniforms_all = DB::table('uniforms')->where("active", 1)->get();
        $uniforms = [];
        foreach ($uniforms_all as $uniform_info) {
            $uniforms[$uniform_info->id] = $uniform_info->uniform_type;
        }

        $orders = DB::table('orders')->where('deleted', '=', 0)->whereIn('user_id', $userids)->get();

        $pangkats_obj = DB::table('pangkats')->get();
        $pangkats = [];
        foreach ($pangkats_obj as $pangkat) {
            $pangkats[$pangkat->id] = $pangkat->value;
        }

        return view('admin/orders/userDetailsWith_orderReport_unit', ["unit" => $unit, "orders" => $orders, "uniforms" => $uniforms, "users" => $users, "pangkats" => $pangkats]);
    }

    // Refactored adminCreatExcelUniformWithUserDetails
    public function adminCreatExcelUniformWithUserDetails(Request $request, $id)
    {
        $uniforms_id = $id;
        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
        $uniform_clothes = DB::table('uniform_clothes')->where('uniforms_id', '=', $uniforms_id)->get();
        $orders_detail = $this->orderDetailsWithUserDetails($uniforms_id);

        $alphabets = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set columns for negative keys (A-D)
        foreach (array_slice($alphabets, 0, 4) as $alphabet) {
            $sheet->getColumnDimension($alphabet)->setAutoSize(true);
            $sheet->getStyle($alphabet . "2")->getFont()->setBold(true);
        }

        $sheet->getStyle("A1:K100")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:B1")->getFont()->setBold(true);

        $sheet->setCellValue('A1', 'Uniform type =');
        $sheet->setCellValue('B1', $uniforms->uniform_type);

        $sheet->setCellValue('A2', 'SERVICE ID');
        $sheet->setCellValue('B2', 'RANK');
        $sheet->setCellValue('C2', 'NAME');
        $sheet->setCellValue('D2', 'UNIT');

        // Set uniform clothes headers starting from E (index 4)
        foreach ($uniform_clothes as $key => $uniform_cloth) {
            $col = $alphabets[$key + 4] ?? null;
            if ($col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $sheet->getStyle($col . '2')->getFont()->setBold(true);
                $sheet->setCellValue($col . '2', $uniform_cloth->clothes_type);
            }
        }

        $count = 3;
        foreach ($orders_detail as $order_details) {
            $sheet->setCellValue('A' . $count, $order_details['user_details']->s_id);
            if (isset($order_details['rank'])) {
                $sheet->setCellValue('B' . $count, $order_details['rank']->value);
            }
            $sheet->setCellValue('C' . $count, $order_details['user_details']->name ?? '');
            $sheet->setCellValue('D' . $count, $order_details['unit']->value ?? '');

            foreach ($order_details['cloth_details'] as $cloth_detail_key => $cloth_detail) {
                $col = $alphabets[$cloth_detail_key] ?? null;
                if ($col) {
                    $sheet->setCellValue($col . $count, $cloth_detail->size);
                }
            }
            $count++;
        }

        $sheet->setTitle('Orders');
        $filename = 'Uniform ' . $uniforms->uniform_type . ' Each user order ' . time() . '.xlsx';
        $path = storage_path('Order-Report/' . $filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path, $filename);
    }

    // Refactored adminCreatExcelUniformWithUserDetailsUnit
    public function adminCreatExcelUniformWithUserDetailsUnit(Request $request, $id)
    {
        $unit_id = $id;
        $unit = DB::table('units')->where('id', '=', $unit_id)->first();
        $users = DB::table('personal_details')
            ->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")
            ->where('unit', '=', $unit_id)
            ->orderBy("pangkats_order", "asc")
            ->orderBy("s_id", "asc")
            ->get();

        $userids = $users->pluck('user_id')->toArray();
        $orders = DB::table('orders')->where('deleted', '=', 0)->whereIn('user_id', $userids)->get();

        $uniforms_all = DB::table('uniforms')->where("active", 1)->get();
        $uniforms = [];
        foreach ($uniforms_all as $uniform_info) {
            $uniforms[$uniform_info->id] = $uniform_info->uniform_type;
        }

        $pangkats_obj = DB::table('pangkats')->get();
        $pangkats = [];
        foreach ($pangkats_obj as $pangkat) {
            $pangkats[$pangkat->id] = $pangkat->value;
        }

        $alphabets = range('A', 'Z');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($alphabets as $alphabet) {
            $sheet->getColumnDimension($alphabet)->setAutoSize(true);
            $sheet->getStyle($alphabet . "2")->getFont()->setBold(true);
        }

        $sheet->getStyle("A1:C999")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:Z2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:Z2")->getFont()->setBold(true);

        $sheet->setCellValue('A1', 'Unit name =');
        $sheet->setCellValue('B1', $unit->value);

        $sheet->setCellValue('A2', '#');
        $sheet->setCellValue('B2', 'SERVICE ID');
        $sheet->setCellValue('C2', 'RANK');
        $sheet->setCellValue('D2', 'NAME');

        foreach ($uniforms as $key => $uniform_cloth) {
            $col = $alphabets[$key + 3] ?? null;
            if ($col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $sheet->getStyle($col . '2')->getFont()->setBold(true);
                $sheet->setCellValue($col . '2', $uniform_cloth);
            }
        }

        $count = 3;
        foreach ($users as $id => $user) {
            $sheet->setCellValue('A' . $count, $id + 1);
            $sheet->setCellValue('B' . $count, $user->s_id);
            $sheet->setCellValue('C' . $count, $pangkats[$user->pangkat] ?? '');
            $sheet->setCellValue('D' . $count, $user->name);

            foreach ($uniforms_all as $uniform_cloth_key => $uniform_cloth) {
                $col = $alphabets[$uniform_cloth_key + 4] ?? null;
                if ($col) {
                    foreach ($orders as $order) {
                        if ($order->uniforms_id == $uniform_cloth->id && $user->user_id == $order->user_id) {
                            $sheet->setCellValue($col . $count, 'Y');
                            break; // Found order for this uniform & user, no need to check further
                        }
                    }
                }
            }
            $count++;
        }

        $sheet->setTitle('Orders');
        $filename = 'Unit ' . $unit->value . ' orders ' . time() . '.xlsx';
        $path = storage_path('Order-Report/' . $filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path, $filename);
    }

    public function orderSelectClothWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms = DB::table('uniforms')->where("active", 1)->get();
        return view('admin/orders/selectClothwise_report', ["uniforms" => $uniforms]);
    }

    public function orderSelectUnitWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $units = DB::table('units')->get();
        return view('admin/orders/selectUnitwise_report', ["units" => $units]);
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
            echo "<option value='{$value->clothes_slug}'>{$value->clothes_type}</option>";
        }
        echo "</select></div>";
    }

    public function orderedSizeCountClothwise($clothes_slug)
    {
        $order = DB::table('ordered_clothes')->where('clothes_slug', '=', $clothes_slug)->groupBy('size')->get();
        $ordered_clothes = [];
        $j = 0;
        foreach ($order as $orders) {
            $ordered_clothes[$j] = [
                'orders' => $orders,
                'count' => DB::table('ordered_clothes')
                    ->where('clothes_slug', '=', $clothes_slug)
                    ->where('size', '=', $orders->size)
                    ->count(),
            ];
            $j++;
        }
        return $ordered_clothes;
    }

    public function getReportUniformTag(Request $request)
    {
        $uniforms_id = $request->input('uniforms_id');
        $clothes_slug = $request->input('clothes_slug');

        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
        $orders = DB::table('ordered_clothes')->where('clothes_slug', '=', $clothes_slug)->first();

        $ordered_clothes = !empty($orders) ? $this->orderedSizeCountClothwise($clothes_slug) : 0;

        return view('admin/orders/clothwise_orderReport', ["uniforms" => $uniforms, "ordered_clothes" => $ordered_clothes]);
    }

    public function getReportunitWise(Request $request)
    {
        $unit_id = $request->input('unit_id');

        $users = DB::table('personal_details')->where('unit', '=', $unit_id)->get();
        $userids = $users->pluck('user_id')->toArray();

        $uniforms_all = DB::table('uniforms')->where("active", 1)->get();
        $unit = DB::table('units')->where('id', '=', $unit_id)->first();

        $uniforms = [];
        foreach ($uniforms_all as $uniform_info) {
            $uniforms[$uniform_info->id] = $uniform_info->uniform_type;
        }
        $orders = DB::table('orders')->where('deleted', '=', 0)->whereIn('user_id', $userids)->get();

        return view('admin/orders/unitwise_orderReport', ["orders" => $orders, "uniforms" => $uniforms, "id" => $unit_id, "unit" => $unit]);
    }

    public function getReportUniformUnitWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }

        $uniforms_id = $request->input('uniforms_id');
        $unit_id = $request->input('unit_id');

        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
        $units = DB::table('units')->where('id', '=', $unit_id)->first();

        $users = DB::table('personal_details')->where('unit', '=', $unit_id)->get();
        $userids = $users->pluck('user_id')->toArray();

        $orders = DB::table('orders')
            ->where('deleted', '=', 0)
            ->whereIn('user_id', $userids)
            ->rightJoin("uniform_clothes", "uniform_clothes.uniforms_id", "=", "orders.uniforms_id")
            ->where('orders.uniforms_id', '=', $uniforms_id)
            ->get();

        return view('admin/orders/uniformunitwise_orderReport', ["uniforms" => $uniforms, "units" => $units, "ordered_clothes" => $orders]);
    }

    // Refactored adminCreatExcelCloth
    public function adminCreatExcelCloth(Request $request, $id, $slug)
    {
        $uniforms_id = $id;
        $clothes_slug = $slug;
        $ordered_clothes = $this->orderedSizeCountClothwise($clothes_slug);
        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle("A1:D100")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1:D1")->getFont()->setBold(true);
        $sheet->getStyle("A2:B2")->getFont()->setBold(true);

        $sheet->setCellValue('A1', 'Uniform Type');
        $sheet->setCellValue('B1', 'Clothes Type');
        $sheet->setCellValue('C1', 'Clothes Size');
        $sheet->setCellValue('D1', 'Clothes Quantity');
        $sheet->setCellValue('A2', $uniforms->uniform_type);
        $sheet->setCellValue('B2', $ordered_clothes[0]['orders']->clothes);

        $count = 2;
        foreach ($ordered_clothes as $clothes) {
            $sheet->setCellValue('C' . $count, $clothes['orders']->size);
            $sheet->setCellValue('D' . $count, $clothes['count']);
            $count++;
        }

        $total_count = 0;
        foreach ($ordered_clothes as $clothes) {
            $total_count += $clothes['count'];
        }
        $count++;

        $sheet->getStyle("A{$count}:D{$count}")->getFont()->setBold(true);
        $sheet->setCellValue('A' . $count, 'Total order');
        $sheet->setCellValue('D' . $count, $total_count);

        $sheet->setTitle('Orders');

        $filename = 'Cloth ' . $ordered_clothes[0]['orders']->clothes . ' Uniform ' . $uniforms->uniform_type . ' orders ' . time() . '.xlsx';
        $path = storage_path('Order-Report/' . $filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path, $filename);
    }
}
