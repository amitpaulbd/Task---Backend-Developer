<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HomeController extends Controller {

    /**
     * 
     * @return type
     */
    public function index() {
        return view('home.index');
    }

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function ajaxCalculateComission(Request $request) {
        $ret = ['success' => false];

        if (!empty($request['test'])) {
            $_staticDataStr = '2014-12-31,4,private,withdraw,1200.00,EUR
2015-01-01,4,private,withdraw,1000.00,EUR
2016-01-05,4,private,withdraw,1000.00,EUR
2016-01-05,1,private,deposit,200.00,EUR
2016-01-06,2,business,withdraw,300.00,EUR
2016-01-06,1,private,withdraw,30000,JPY
2016-01-07,1,private,withdraw,1000.00,EUR
2016-01-07,1,private,withdraw,100.00,USD
2016-01-10,1,private,withdraw,100.00,EUR
2016-01-10,2,business,deposit,10000.00,EUR
2016-01-10,3,private,withdraw,1000.00,EUR
2016-02-15,1,private,withdraw,300.00,EUR
2016-02-19,5,private,withdraw,3000000,JPY';
            $_staticDataArr = explode(PHP_EOL, $_staticDataStr);
            $dataSet = [];
            foreach ($_staticDataArr as $_dataRow):
                $dataSet[] = explode(',', $_dataRow);
            endforeach;

            $ret['commission_data'] = $this->_calculate($dataSet, true);
            $ret['success'] = true;
            return response()->json($ret);
        }
        
        
        

        $validator = Validator::make($request->all(), ['file' => 'required|file']);
        if (!$validator->passes()):
            $ret['message'] = implode(",", $validator->messages()->all());
            return response()->json($ret);
        endif;

        if (!$request->file('file')->isValid()):
            $ret['message'] = 'File not valid!';
            return response()->json($ret);
        endif;

        $extension = $request->file('file')->getClientOriginalExtension();
        if ($extension !== 'csv'):
            $ret['message'] = 'Select csv file only!';
            return response()->json($ret);
        endif;


        $path = $request->file('file')->getRealPath();
        $dataSet = array_map('str_getcsv', file($path));

        if (empty($dataSet)):
            $ret['message'] = 'No data found on your CSV file!';
            return response()->json($ret);
        endif;

        $ret['commission_data'] = $this->_calculate($dataSet);
        $ret['success'] = true;
        return response()->json($ret);
    }

    /**
     * 
     * @param type $data_set
     * @return type
     */
    private function _calculate($data_set, $test = false) {

        if ($test):
            $_rates['rates']['USD'] = 1.1497;
            $_rates['rates']['JPY'] = 129.53;
        else:
            $_rates = $this->_getRates();
            if (empty($_rates['rates'])):
                return [];
            endif;
        endif;

        $_privateWithdraw = [];
        $_commissionData = [];
        foreach ($data_set as $_data):
            if (!empty($_data[5])):

                if ($_data[3] === 'deposit'):
                    $_commissionData[] = $this->_roundUp(($_data[4] * 0.03) / 100);
                elseif ($_data[3] === 'withdraw'):
                    if ($_data[2] === 'private'):
                        $_amt = ($_data[5] === 'EUR') ? $_data[4] : $_data[4] / $_rates['rates'][$_data[5]];

                        $_wsIndex = Carbon::parse($_data[0])->startOfWeek()->format('Ymd');

                        $_privateWithdraw[$_data[1]][$_wsIndex]['count'] = !empty($_privateWithdraw[$_data[1]][$_wsIndex]['count']) ? $_privateWithdraw[$_data[1]][$_wsIndex]['count']++ : 1;
                        $_privateWithdraw[$_data[1]][$_wsIndex]['amt'] = !empty($_privateWithdraw[$_data[1]][$_wsIndex]['amt']) ? $_privateWithdraw[$_data[1]][$_wsIndex]['amt'] + $_amt : $_amt;

                        if ($_privateWithdraw[$_data[1]][$_wsIndex]['count'] <= 3):
                            if ($_privateWithdraw[$_data[1]][$_wsIndex]['amt'] > 1000):
                                if (($_privateWithdraw[$_data[1]][$_wsIndex]['amt'] - $_amt) < 1000):
                                    $_commInEuro = (($_privateWithdraw[$_data[1]][$_wsIndex]['amt'] - 1000) * 0.3) / 100;
                                    $_commInCurr = ($_data[5] == 'EUR') ? $_commInEuro : $_rates['rates'][$_data[5]] * $_commInEuro;
                                else:
                                    $_commInEuro = ($_amt * 0.3) / 100;
                                    $_commInCurr = ($_data[5] == 'EUR') ? $_commInEuro : $_rates['rates'][$_data[5]] * $_commInEuro;
                                endif;
                                $_commissionData[] = $this->_roundUp($_commInCurr);
                            else:
                                $_commissionData[] = 0;
                            endif;
                        else:
                            $_commissionData[] = $this->_roundUp(($_data[4] * 0.3) / 100);
                        endif;
                    elseif ($_data[2] === 'business'):
                        $_commissionData[] = $this->_roundUp(($_data[4] * 0.5) / 100);
                    endif;
                endif;

            endif;
        endforeach;

        return $_commissionData;
    }

    /**
     * 
     * @return type
     */
    private function _getRates() {
        $response = Http::get('https://api.exchangeratesapi.io/latest');
        return json_decode($response, true);
    }

    /**
     * 
     * @param type $number
     * @param type $precision
     * @return type
     */
    private function _roundUp($number, $precision = 2) {
        $fig = pow(10, $precision);
        return number_format((ceil($number * $fig) / $fig), 2, '.', '');
    }

}
