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

class AnnouncementsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        return view('admin/announcements');
    }

    public function orderSelectUniformWise(Request $request)
    {

        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms = DB::table('uniforms')->where("active", 1)->get();
        return view('admin/orders/selectUniformwise_report', array("uniforms" => $uniforms));
    }

    public function orderSelectUniformUnitWise(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms = DB::table('uniforms')->where("active", 1)->get();
        $units = DB::table('units')->get();
        return view('admin/orders/selectUniformUnitwise_report', array("uniforms" => $uniforms, "units" => $units));
    }

    public function orderedSizeCountUniformwise($uniforms_id)
    {

        $uniform_clothes = DB::table('uniform_clothes')->where('uniforms_id', '=', $uniforms_id)->get();

        $i = 0;
        foreach ($uniform_clothes as $value) {

            $order = array();
            $order = DB::table('ordered_clothes')->where('clothes_slug', '=', $value->clothes_slug)->groupBy('size')->get();

            $j = 0;
            foreach ($order as $orders) {

                $ordered_clothes[$i][$j] = [
                    'orders' => $orders,
                    'count' => DB::table('ordered_clothes')->where('clothes_slug', '=', $value->clothes_slug)->where('size', '=', $orders->size)->groupBy('size')->count(),
                ];
                $j++;
            }
            $i++;
        }
        return $ordered_clothes;
    }

    public function orderDetailsWithUserDetails($uniforms_id)
    {

        $orders = DB::table('orders')->leftJoin("personal_details", "personal_details.user_id", "=", "orders.user_id")->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")->where('uniforms_id', '=', $uniforms_id)->where('deleted', '=', 0)->orderBy("pangkats_order", "asc")->orderBy("s_id", "asc")->get();
			
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

        if (!empty($orders_detail)) {
            return $orders_detail;
        } else {
            return 0;
        }
    }

    public function getReportUniformWise(Request $request)
    {

        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms_id = $request->input('uniforms_id');
        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
        $orders = DB::table('orders')->where('deleted', '=', 0)->where('uniforms_id', '=', $uniforms_id)->first();
        if (!empty($orders)) {
            $ordered_clothes = $this->orderedSizeCountUniformwise($uniforms_id);
        } else {
            $ordered_clothes = 0;
        }
        return view('admin/orders/uniformwise_orderReport', array("uniforms" => $uniforms, "ordered_clothes" => $ordered_clothes));
    }

    public function adminCreatExcelUniform(Request $request, $id)
    {

        $uniforms_id = $id;
        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
        $ordered_clothes = $this->orderedSizeCountUniformwise($uniforms_id);

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getStyle("A1:D100")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle("A1:D1")->applyFromArray(array("font" => array("bold" => true)));
        $objPHPExcel->getActiveSheet()->getStyle("A2")->applyFromArray(array("font" => array("bold" => true)));
        $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Uniform Type');
        $objPHPExcel->getActiveSheet()->SetCellValue('B1', 'Clothes Type');
        $objPHPExcel->getActiveSheet()->SetCellValue('C1', 'Clothes Size');
        $objPHPExcel->getActiveSheet()->SetCellValue('D1', 'Clothes Quantity');
        $objPHPExcel->getActiveSheet()->SetCellValue('A2', $uniforms->uniform_type);
        $count = 2;
        $number_fo_cloth = 0;
        foreach ($ordered_clothes as $orderedClothes) {
            $number_fo_cloth++;
            foreach ($orderedClothes as $clothes) {

                $objPHPExcel->getActiveSheet()->SetCellValue('B' . $count, $clothes['orders']->clothes);
                $objPHPExcel->getActiveSheet()->SetCellValue('C' . $count, $clothes['orders']->size);
                $objPHPExcel->getActiveSheet()->SetCellValue('D' . $count, $clothes['count']);

                $count++;
            }
        }
        $total_count = 0;
        foreach ($ordered_clothes[0] as $clothes) {
            $total_count = $total_count + $clothes['count'];
        }
        $count++;
        $objPHPExcel->getActiveSheet()->getStyle("B" . $count . ":D" . $count)->applyFromArray(array("font" => array("bold" => true)));
        $objPHPExcel->getActiveSheet()->SetCellValue('B' . $count, 'Total clothes');
        $objPHPExcel->getActiveSheet()->SetCellValue('D' . $count, 'Total order');
        $count++;
        $objPHPExcel->getActiveSheet()->getStyle("B" . $count . ":D" . $count)->applyFromArray(array("font" => array("bold" => true)));
        $objPHPExcel->getActiveSheet()->SetCellValue('B' . $count, $number_fo_cloth);
        $objPHPExcel->getActiveSheet()->SetCellValue('D' . $count, $total_count);

        $objPHPExcel->getActiveSheet()->setTitle('Orders');
        $filename = 'Uniform ' . $uniforms->uniform_type . ' orders ' . time() . '.xlsx';
        $path = storage_path() . '/Order-Report/' . $filename;

        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save($path);

        return Response::download($path, $filename);
    }

    public function orderSelectUserWise(Request $request)
    {

        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms = DB::table('uniforms')->where("active", 1)->get();
        return view('admin/orders/selectUserwise_report', array("uniforms" => $uniforms));
    }

    public function orderSelectUserWiseUnit(Request $request)
    {

        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $units = DB::table('units')->get();
        return view('admin/orders/selectUserwise_report_unit', array("units" => $units));
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
        if (!empty($orders)) {
            $orders_detail = $this->orderDetailsWithUserDetails($uniforms_id);
        } else {
            $orders_detail = 0;
        }
        return view('admin/orders/userDetailsWith_orderReport', array("uniforms" => $uniforms, "uniform_clothes" => $uniform_clothes, "orders_detail" => $orders_detail));
    }

    public function getReportUserWiseUnit(Request $request)
    {

        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }

        $unit_id = $request->input('unit_id');
        $unit = DB::table('units')->where('id', '=', $unit_id)->first();
        $users = DB::table('personal_details')->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")->where('unit', '=', $unit_id)->orderBy("pangkats_order", "asc")->orderBy("s_id", "asc")->get();
			
        $userids = [];
        foreach ($users as $user) {
            $userids[] = $user->user_id;
        }

        $uniforms_all = DB::table('uniforms')->where("active", 1)->get();
        foreach ($uniforms_all as $uniform_info) {
            $uniforms[$uniform_info->id] = $uniform_info->uniform_type;
        }
        $orders = DB::table('orders')->where('deleted', '=', 0)->whereIn('orders.user_id', $userids)->get();
			
        $pangkats_obj = DB::table('pangkats')->get();
        $pangkats = [];
        foreach ($pangkats_obj as $pangkat) {
            $pangkats[$pangkat->id] = $pangkat->value;
        }

        return view('admin/orders/userDetailsWith_orderReport_unit', array("unit" => $unit, "orders" => $orders, "uniforms" => $uniforms, "users" => $users, "pangkats" => $pangkats));
    }

    public function adminCreatExcelUniformWithUserDetails(Request $request, $id)
    {

        $uniforms_id = $id;
        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
        $uniform_clothes = DB::table('uniform_clothes')->where('uniforms_id', '=', $uniforms_id)->get();
        $orders_detail = $this->orderDetailsWithUserDetails($uniforms_id);

        $alphabets = array('-4' => 'A', '-3' => 'B', '-2' => 'C', '-1' => 'D', '0' => 'E', '1' => 'F', '2' => 'G', '3' => 'H', '4' => 'I', '5' => 'J', '6' => 'K');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        foreach ($alphabets as $key => $alphabet) {
            if ($key < 0) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($alphabet)->setAutoSize(true);
                $objPHPExcel->getActiveSheet()->getStyle($alphabet . "2")->applyFromArray(array("font" => array("bold" => true)));
            }
        }
        $objPHPExcel->getActiveSheet()->getStyle("A1:K100")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle("A1:B1")->applyFromArray(array("font" => array("bold" => true)));
        $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Uniform type =');
        $objPHPExcel->getActiveSheet()->SetCellValue('B1', $uniforms->uniform_type);
        $objPHPExcel->getActiveSheet()->SetCellValue('A2', 'SERVICE ID');
        $objPHPExcel->getActiveSheet()->SetCellValue('B2', 'RANK');
        $objPHPExcel->getActiveSheet()->SetCellValue('C2', 'NAME');
        $objPHPExcel->getActiveSheet()->SetCellValue('D2', 'UNIT');
        foreach ($uniform_clothes as $uniform_cloth_key => $uniform_cloth) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($alphabets[$uniform_cloth_key])->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getStyle($alphabets[$uniform_cloth_key] . '2')->applyFromArray(array("font" => array("bold" => true)));
            $objPHPExcel->getActiveSheet()->SetCellValue($alphabets[$uniform_cloth_key] . '2', $uniform_cloth->clothes_type);
        }
        $count = 3;
			
        foreach ($orders_detail as $order_details) {
            $objPHPExcel->getActiveSheet()->SetCellValue('A' . $count, $order_details['user_details']->s_id);
					if (isset($order_details['rank'])) {
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $count, $order_details['rank']->value);
					}
            $objPHPExcel->getActiveSheet()->SetCellValue('C' . $count, ($order_details['user_details'] ? $order_details['user_details']->name: ''));
            $objPHPExcel->getActiveSheet()->SetCellValue('D' . $count, ($order_details['unit'] ? $order_details['unit']->value : ''));
            foreach ($order_details['cloth_details'] as $cloth_detail_key => $cloth_detail) {
                $objPHPExcel->getActiveSheet()->SetCellValue($alphabets[$cloth_detail_key] . '' . $count, $cloth_detail->size);
            }
            $count++;
        }
        $objPHPExcel->getActiveSheet()->setTitle('Orders');
        $filename = 'Uniform ' . $uniforms->uniform_type . ' Each user order ' . time() . '.xlsx';
        $path = storage_path() . '/Order-Report/' . $filename;

        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save($path);

        return Response::download($path, $filename);
    }

    public function adminCreatExcelUniformWithUserDetailsUnit(Request $request, $id)
    {

        $unit_id = $id;

        $unit = DB::table('units')->where('id', '=', $unit_id)->first();
        $users = DB::table('personal_details')->leftJoin("pangkats", "pangkats.id", "=", "personal_details.pangkat")->where('unit', '=', $unit_id)->orderBy("pangkats_order", "asc")->orderBy("s_id", "asc")->get();
        $userids = [];
        foreach ($users as $user) {
            $userids[] = $user->user_id;
        }
					  $orders = DB::table('orders')->where('deleted', '=', 0)->whereIn('user_id', $userids)->get();

        $uniforms_all = DB::table('uniforms')->where("active", 1)->get();
        foreach ($uniforms_all as $uniform_info) {
            $uniforms[$uniform_info->id] = $uniform_info->uniform_type;
        }

        $pangkats_obj = DB::table('pangkats')->get();
        $pangkats = [];
        foreach ($pangkats_obj as $pangkat) {
            $pangkats[$pangkat->id] = $pangkat->value;
        }

        $alphabets = range('A','Z');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
			
        foreach ($alphabets as $key => $alphabet) {
					$objPHPExcel->getActiveSheet()->getColumnDimension($alphabet)->setAutoSize(true);
					$objPHPExcel->getActiveSheet()->getStyle($alphabet . "2")->applyFromArray(array("font" => array("bold" => true)));
        }
			
        $objPHPExcel->getActiveSheet()->getStyle("A1:C999")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle("A1:Z2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle("A1:Z2")->applyFromArray(array("font" => array("bold" => true)));
        $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Unit name =');
        $objPHPExcel->getActiveSheet()->SetCellValue('B1', $unit->value);
        $objPHPExcel->getActiveSheet()->SetCellValue('A2', '#');
        $objPHPExcel->getActiveSheet()->SetCellValue('B2', 'SERVICE ID');
        $objPHPExcel->getActiveSheet()->SetCellValue('C2', 'RANK');
        $objPHPExcel->getActiveSheet()->SetCellValue('D2', 'NAME');

        foreach ($uniforms as $uniform_cloth_key => $uniform_cloth) {
					$objPHPExcel->getActiveSheet()->getColumnDimension($alphabets[$uniform_cloth_key+3])->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getStyle($alphabets[$uniform_cloth_key+3] . '2')->applyFromArray(array("font" => array("bold" => true)));
            $objPHPExcel->getActiveSheet()->SetCellValue($alphabets[$uniform_cloth_key+3] . '2', $uniform_cloth);
        }
        $count = 3;

        foreach ($users as $id => $user) {
            $objPHPExcel->getActiveSheet()->SetCellValue('A' . $count, $id + 1);
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $count, $user->s_id);
            $objPHPExcel->getActiveSheet()->SetCellValue('C'.$count, $pangkats[$user->pangkat]);
            $objPHPExcel->getActiveSheet()->SetCellValue('D' . $count, $user->name);
            
						foreach ($uniforms_all as $uniform_cloth_key => $uniform_cloth) {
							foreach ($orders as $order) {
								if ($order->uniforms_id == $uniform_cloth->id && $user->user_id == $order->user_id) {
									
							$objPHPExcel->getActiveSheet()->SetCellValue($alphabets[$uniform_cloth_key+4].''.$count, 'Y');
								}
							}
						}
					/*foreach($order_details['cloth_details'] as $cloth_detail_key => $cloth_detail) {
            $objPHPExcel->getActiveSheet()->SetCellValue($alphabets[$cloth_detail_key].''.$count, $cloth_detail->size);
            }*/
            $count++;
        }

        $objPHPExcel->getActiveSheet()->setTitle('Orders');
        $filename = 'Unit ' . $unit->value . ' orders ' . time() . '.xlsx';
        $path = storage_path() . '/Order-Report/' . $filename;

        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save($path);

        return Response::download($path, $filename);
    }

    public function orderSelectClothWise(Request $request)
    {

        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $uniforms = DB::table('uniforms')->where("active", 1)->get();
        return view('admin/orders/selectClothwise_report', array("uniforms" => $uniforms));
    }

    public function orderSelectUnitWise(Request $request)
    {

        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        $units = DB::table('units')->get();
        return view('admin/orders/selectUnitwise_report', array("units" => $units));
    }

    public function loadClothAjax(Request $request)
    {

        $uniforms_id = $request->input('uniforms_id');

        $clothes = DB::table('uniform_clothes')->where('uniforms_id', '=', $uniforms_id)->get();

        echo "<div class='form-group'>
			    					<label class='label_'>Select Cloth Type</label><select required class='form-control' id='clothes_slug' name='clothes_slug'>
                <option value=''>Choose cloth type....</option>";
        foreach ($clothes as $value) {
            echo "<option value=" . $value->clothes_slug . ">" . $value->clothes_type . "</option>";
        }
        echo "</select></div>";
    }

    public function orderedSizeCountClothwise($clothes_slug)
    {

        $order = DB::table('ordered_clothes')->where('clothes_slug', '=', $clothes_slug)->groupBy('size')->get();
        $j = 0;
        foreach ($order as $orders) {
            $ordered_clothes[$j] = [
                'orders' => $orders,
                'count' => DB::table('ordered_clothes')->where('clothes_slug', '=', $clothes_slug)->where('size', '=', $orders->size)->groupBy('size')->count(),
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

        if (!empty($orders)) {
            $ordered_clothes = $this->orderedSizeCountClothwise($clothes_slug);
        } else {
            $ordered_clothes = 0;
        }
        return view('admin/orders/clothwise_orderReport', array("uniforms" => $uniforms, "ordered_clothes" => $ordered_clothes));
    }

    public function getReportunitWise(Request $request)
    {

        $unit_id = $request->input('unit_id');

        $users = DB::table('personal_details')->where('unit', '=', $unit_id)->get();
        foreach ($users as $user) {
            $userids[] = $user->user_id;
        }
        $uniforms_all = DB::table('uniforms')->where("active", 1)->get();
        $unit = DB::table('units')->where('id', '=', $unit_id)->first();

        foreach ($uniforms_all as $uniform_info) {
            $uniforms[$uniform_info->id] = $uniform_info->uniform_type;
        }
        $orders = DB::table('orders')->where('deleted', '=', 0)->whereIn('user_id', $userids)->get();

        return view('admin/orders/unitwise_orderReport', array("orders" => $orders, "uniforms" => $uniforms, "id" => $unit_id, "unit" => $unit));
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
        foreach ($users as $user) {
            $userids[] = $user->user_id;
        }
			
			$orders = DB::table('orders')->where('deleted', '=', 0)->whereIn('user_id', $userids)->rightJoin("uniform_clothes", "uniform_clothes.uniforms_id", "=", "orders.uniforms_id")->where('orders.uniforms_id', '=', $uniforms_id)->get();
			
        return view('admin/orders/uniformunitwise_orderReport', array("uniforms" => $uniforms, "units" => $units, "ordered_clothes" => $orders));
    }

	
    public function adminCreatExcelCloth(Request $request, $id, $slug)
    {

        $uniforms_id = $id;
        $clothes_slug = $slug;
        $ordered_clothes = $this->orderedSizeCountClothwise($clothes_slug);
        $uniforms = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getStyle("A1:D100")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle("A1:D1")->applyFromArray(array("font" => array("bold" => true)));
        $objPHPExcel->getActiveSheet()->getStyle("A2:B2")->applyFromArray(array("font" => array("bold" => true)));
        $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Uniform Type');
        $objPHPExcel->getActiveSheet()->SetCellValue('B1', 'Clothes Type');
        $objPHPExcel->getActiveSheet()->SetCellValue('C1', 'Clothes Size');
        $objPHPExcel->getActiveSheet()->SetCellValue('D1', 'Clothes Quantity');
        $objPHPExcel->getActiveSheet()->SetCellValue('A2', $uniforms->uniform_type);
        $objPHPExcel->getActiveSheet()->SetCellValue('B2', $ordered_clothes[0]['orders']->clothes);
        $count = 2;
        foreach ($ordered_clothes as $clothes) {

            $objPHPExcel->getActiveSheet()->SetCellValue('C' . $count, $clothes['orders']->size);
            $objPHPExcel->getActiveSheet()->SetCellValue('D' . $count, $clothes['count']);
            $count++;
        }
        $total_count = 0;
        foreach ($ordered_clothes as $clothes) {
            $total_count = $total_count + $clothes['count'];
        }
        $count++;
        $objPHPExcel->getActiveSheet()->getStyle("A" . $count . ":D" . $count)->applyFromArray(array("font" => array("bold" => true)));
        $objPHPExcel->getActiveSheet()->SetCellValue('A' . $count, 'Total order');
        $objPHPExcel->getActiveSheet()->SetCellValue('D' . $count, $total_count);

        $objPHPExcel->getActiveSheet()->setTitle('Orders');
        $filename = 'Cloth ' . $ordered_clothes[0]['orders']->clothes . ' Uniform ' . $uniforms->uniform_type . ' orders ' . time() . '.xlsx';
        $path = storage_path() . '/Order-Report/' . $filename;

        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save($path);

        return Response::download($path, $filename);
    }
}
