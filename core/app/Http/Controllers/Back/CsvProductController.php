<?php

namespace App\Http\Controllers\Back;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ChieldCategory;
use App\Models\Item;
use App\Models\Order;
use App\Models\Subcategory;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

class CsvProductController extends Controller
{

    public function index()
    {
        return view('back.item.bulk-upload');
    }
    
    public function export()
    {
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=products_csv_export.csv',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $lists = Item::where('item_type', '!=', 'affilite')->get();
        $new_list = [];
        foreach ($lists->toArray() as $list) {
            $list['photo'] = url('/core/public/storage/images/' . $list['photo']);
            $list['slug'] = Str::random(3) . $list['slug'] . Str::random(2);
            $list['category'] = (Category::find($list['category_id'])) ? Category::find($list['category_id'])->name : "";
            $list['subcategory'] = (Subcategory::find($list['subcategory_id'])) ? Subcategory::find($list['subcategory_id'])->name : '';
            $list['childcategory'] = (ChieldCategory::find($list['childcategory_id'])) ? ChieldCategory::find($list['childcategory_id'])->name : '';
            $list['brand'] = (Brand::find($list['brand_id'])) ? Brand::find($list['brand_id'])->name : '';
            unset($list['category_id']);
            unset($list['subcategory_id']);
            unset($list['childcategory_id']);
            unset($list['brand_id']);
            $new_list[] = $list;
        }

        # add headers for each column in the CSV download
        array_unshift($new_list, array_keys($new_list[0]));

        $callback = function () use ($new_list) {
            $FH = fopen('php://output', 'w');
            foreach ($new_list as $row) {
                fputcsv($FH, $row);
            }
            fclose($FH);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function transactionExport()
    {
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=transaction_export.csv',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $lists = Transaction::all()->toArray();
        $new_list = [];
        foreach ($lists as $list) {
            $new_list[] = $list;
        }


        # add headers for each column in the CSV download
        array_unshift($new_list, array_keys($new_list[0]));

        $callback = function () use ($new_list) {
            $FH = fopen('php://output', 'w');
            foreach ($new_list as $row) {
                fputcsv($FH, $row);
            }
            fclose($FH);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function orderExport()
    {
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=order_csv_export.csv',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $lists = Order::all()->toArray();
        $new_list = [];
        foreach ($lists as $list) {
            $new_list[] = $list;
        }

        # add headers for each column in the CSV download
        array_unshift($new_list, array_keys($new_list[0]));

        $callback = function () use ($new_list) {
            $FH = fopen('php://output', 'w');
            foreach ($new_list as $row) {
                fputcsv($FH, $row);
            }
            fclose($FH);
        };
        return response()->stream($callback, 200, $headers);
    }

    //*** POST Request
    public function import(Request $request)
    {

        try {
            $filename = '';
            if ($file = $request->file('csv')) {
                $filename = time() . "." . $file->getClientOriginalExtension();
                $file->move('assets/temp_files', $filename);
            }

            $file = fopen('assets/temp_files/' . $filename, "r");
            


            $i = 1;

            while (($line = fgetcsv($file)) !== false) {

                if ($i != 1) {

                    $category_id = $line[39] ? (Category::whereName($line[39])->exists() ? Category::whereName($line[39])->first()->id : 0) : 0;
                    $subcategory_id = $line[40] ? (SubCategory::whereName($line[40])->exists() ? SubCategory::whereName($line[40])->first()->id : 0) : 0;
                    $childcategory_id = $line[41] ? (ChieldCategory::whereName($line[41])->exists() ? ChieldCategory::whereName($line[41])->first()->id : 0) : 0;
                    $brand_id = $line[42] ? (Brand::whereName($line[40])->exists() ? Brand::whereName($line[40])->first()->id : 0) : 0;

                    $input['category_id'] = $category_id;
                    $input['subcategory_id'] = $subcategory_id;
                    $input['childcategory_id'] = $childcategory_id;
                    $input['brand_id'] = $brand_id;
                    $input['tax_id'] = $line[1];
                    $input['name'] = $line[2];
                    $input['slug'] = $line[3];
                    $input['sku'] = $line[4];
                    $input['tags'] = $line[5];
                    $input['video'] = $line[6];
                    $input['sort_details'] = $line[7];
                    $input['specification_name'] = $line[8];
                    $input['specification_description'] = $line[9];
                    $input['is_specification'] = $line[10];
                    $input['details'] = $line[11];
                    $input['discount_price'] = $line[21];
                    $input['previous_price'] = $line[22];
                    $input['stock'] = $line[23];
                    $input['meta_keywords'] = $line[24];
                    $input['meta_description'] = $line[25];
                    $input['status'] = $line[26];
                    $input['is_type'] = $line[27];
                    $input['date'] = $line[28];
                    $input['file'] = $line[29];
                    $input['link'] = $line[30];
                    $input['file_type'] = $line[31] ? $line[31] : null;

                    $input['item_type'] = $line[36];

                    $images_name = $line[12] ? $this->uploadImage($line[12], 'images') : ['undefined','undefined'];
                    

                    $input['photo'] = $images_name[1];
                    $input['thumbnail'] = $images_name[1];



                    $data = new Item();
                    $data->fill($input)->save();
                }

                $i++;
            }
            fclose($file);

            $removefiles = glob('/assets/temp_files/*');

            // Deleting all the files in the list
            foreach ($removefiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            return back()->withSuccess(__('Bulk Product File Imported Successfully.'));
        } catch (\Throwable $th) {
            dd($th);
           return back()->withError(__('Something is wrong!'));
        }
    }

    public function uploadImage($file, $path, $delete = null)
    {
        if ($file) {

            if ($delete) {
                Storage::delete($path . '/' . $delete);
            }

            $photoName = 'OM_' . time() .  Str::random(8) . '.png';
            $thumbnailName = 'OM_' . time() .  Str::random(8) . '.png';

            Storage::putFileAs($path, $file, $photoName);


            $image = \Image::make($file)->resize(230, 230);


            $thumbnailPath = $path . '/' . $thumbnailName;
            Storage::put($thumbnailPath, (string) $image->encode());


            return [$photoName, $thumbnailName];
        }
    }
}
