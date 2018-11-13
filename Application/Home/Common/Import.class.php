<?php
/**
 * Created by zhou.
 * User: zhou
 * Date: 2015/9/17
 * Time: 15:16
 */

namespace Home\Common;


use PHPExcel;
use PHPExcel_IOFactory;

Vendor('Classes.PHPExcel');

class Import
{

    public static function excel($filePath = "")
    {
        $filePath = C('DOWN_PATH')."excel/".$filePath; // 要读取的文件的路径
        //dump($filePath);die;
        $PHPExcel = new PHPExcel(); // 拿到实例，待会儿用
        $PHPReader = new \PHPExcel_Reader_Excel2007(); // Reader很关键，用来读excel文件
        if (!$PHPReader->canRead($filePath)) { // 这里是用Reader尝试去读文件，07不行用05，05不行就报错。注意，这里的return是Yii框架的方式。
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                $errorMessage = "Can not read file.";
                return $this->render('error', ['errorMessage' => $errorMessage]);
            }
        }
        $PHPExcel = $PHPReader->load($filePath); // Reader读出来后，加载给Excel实例
        $currentSheet = $PHPExcel->getSheet(0); // 拿到第一个sheet（工作簿？）
        $result = [];

        foreach ($currentSheet->getRowIterator() as $row) { // 行迭代器

            $cellIterator = $row->getCellIterator(); // 拿到行中的cell迭代器
            $cellIterator->setIterateOnlyExistingCells(false); // 设置cell迭代器，遍历所有cell，哪怕cell没有值
            $lineVal = [];
            foreach ($cellIterator as $cell) {

                if ($cell->getDataType() == \PHPExcel_Cell_DataType::TYPE_NUMERIC) { // 这里是比较cell中数据类型是不是number
                    $cellStyleFormat = $cell->getStyle( $cell->getCoordinate() )->getNumberFormat(); // 接下来这两句是拿到这个number的格式
                    $formatCode = $cellStyleFormat->getFormatCode(); // 如果是普通的数字，formatCode将为General, 如果是如6/12/91 12:00的时间格式，formatCode将为/d/yy h:mm（反正就是时间格式了）
                    //dump($formatCode);
                    if ($formatCode == 'm/d/yy h:mm') {
                        $value = \PHPExcel_Shared_Date::ExcelToPHP($cell->getValue()) - 28800;
                        
                    } else {
                        $value = $cell->getValue();
                    }
                } else {
                    $value = $cell->getValue();
                }
                array_push($lineVal, RemoveXSS($value));

            }
            array_push($result, $lineVal);
        }

        $config = $result[0];
        $result_info = [];

        foreach ($result as $key => $value) {
            if ($key == 0) {
                unset($result[$key]);
                continue;
            }
            foreach ($value as $k => $v) {
                $result_info[$key][$config[$k]] = $v;
            }

        }

        return $result_info;
    }

}