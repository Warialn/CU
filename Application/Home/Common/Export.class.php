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
class Export
{

    public static function excel($data, callable $colCallback = null, callable $valueCallback = null, $filename = "", $count)
    {
       
       // dump($data);
        $rootPath = C("DOWN_PATH");
     
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Byzoro百卓网络")
            ->setTitle("Byzoro百卓网络");
    
        if (isset($data["models"])) {
            $data = $data["models"];
        }

        $data_count = count($data);
        //dump($data_count);die;
        if ($data_count === 0 && $filename === ""){
            return array('status' => 1,'message' => '无数据导出');
        } else if ($data_count === 0 && $filename !== "") {
            return array('status' => 2,'message' => '导出完毕','filename' => $filename,'count' => $count);
        }
       // var_dump($data);die;
        $colscount = 0;
        $linecount = 2;
        $cols      = [];
        $wcols     = [];

        foreach ($data as $key => $row) {
            foreach ($row as $col => $value) {
                if (!isset($cols[$col])) {
                    $c = $col;
                    $colCallback !== null && $c = $colCallback($col);
                    if ($c === "") continue;
                    $intCol     = self::int2col($colscount);
                    $wcols[$c]  = $intCol;
                    $cols[$col] = $intCol;
                    $colscount++;
                }
                $valueCallback !== null && $value = $valueCallback($col, $value);
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue($cols[$col] . $linecount, $value);
            }
            $linecount++;
        }
        $objActSheet = $objPHPExcel->getActiveSheet();
        foreach ($wcols as $c => $v) {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue($v . 1, $c);
            $objActSheet->getColumnDimension($v)->setWidth(20);
        }

        // header('Content-Type: application/Excel2007');
        // header('Content-Disposition: attachment;filename="Export_' . date("Y_m_d_H_i_s", time()) . '.xls"');
        // header('Cache-Control: max-age=0');
 
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        // $objWriter->save('php://output');

       
        $count++;
        $filename = $filename === "" ? "Export_" . date("Y_m_d_H_i_s", time()) : $filename;
        if (!is_dir($rootPath.$filename))
        { 
            mkdir ($rootPath.$filename, 0777);
        } 
        $savename = $rootPath.$filename."/".$filename."(".$count.")".".xls";

        $objWriter->save($savename);
        // Byzoro::$app->end();
        if ($data_count < 10000) {
            return array('status' => 2,'message' => '导出完毕','filename' => $filename,'count' => $count);
        } else {
            return array('status' => 3,'filename' => $filename,'count' => $count);
        }

    }

    public static function int2col($int)
    {
        $c = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n",
            "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"];

        if ($int < 26) {
            return $c[$int];
        } elseif ($int > 255) {
            throw new Exception("ExportExcel列过多");
        } else {
            $y = $int % 26;
            $i = (intval($int / 26)) - 1;

            return $c[$i] . $c[$y];
        }
    }

}