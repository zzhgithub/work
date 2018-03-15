<?php
/**
 * ,__,
 * (oo)_____
 * (__)     )\
 * ````||---|| *
 * Class Excel <br>
 * @package app\lib <br>
 * @author mutou <br>
 * @version 1.0.0
 * @date 11/02/2018 <br>
 */

namespace app\lib;

class Excel
{
    const CELL_DATATYPE_STRING = 's';
    const CELL_DATATYPE_FORMULA = 'f';
    const CELL_DATATYPE_NUMERIC = 'n';
    const CELL_DATATYPE_BOOL = 'b';
    const CELL_DATATYPE_NULL = 's';
    const CELL_DATATYPE_INLINE = 'inlineStr';
    const MAX_ROW_NUM = 65536;

    /**
     * 创建人
     */
    public $excelCreator;

    /**
     * 标题
     */
    public $excelTitle;

    /**
     * 主题
     */
    public $excelSubject;

    /**
     * 描述
     */
    public $excelDescription;
    public $has_title = true;
    public $title = array();
    public $file_name;

    /**
     * 合并单元格
     */
    public $mergeCells = array();

    public $reader;

    const TYPE_2007 = 'Excel2007';
    const TYPE_5 = 'Excel5';

    /**
     * 导出到Excel
     * @param array $data
     * @param array $cellDataType
     * @return bool|string
     */
    public function exportExcel($data = array(),$cellDataType = array())
    {
        if (!$data || count($data) > self::MAX_ROW_NUM) {
            return false;
        }
        //获得execl列
        $cells = $this->_getColumnNumber();
        $objPHPExcel = new \PHPExcel();

        if ($this->excelCreator) {
            $objPHPExcel->getProperties()->setCreator($this->excelCreator); //创建人
            $objPHPExcel->getProperties()->setLastModifiedBy($this->excelCreator); //最后修改人
        }
        if ($this->excelTitle) {
            $objPHPExcel->getProperties()->setTitle($this->excelTitle); //标题
        }
        if ($this->excelSubject) {
            $objPHPExcel->getProperties()->setSubject($this->excelSubject); //主题
        }
        if ($this->excelDescription) {
            $objPHPExcel->getProperties()->setDescription($this->excelDescription); //描述
        }

        try {
            $objPHPExcel->setActiveSheetIndex(0);
            $objActSheet = $objPHPExcel->getActiveSheet();

            //第二维数组中元素下标
            $attrNames = array_keys(reset($data));
            //设置标题
            if ($this->has_title) {
                if ($this->title) {
                    $title = $this->title;
                } else {
                    $title = $attrNames;
                }
                $i = 0;
                foreach ($title as $attr) {
                    $cell = $cells[$i] . '1';
                    $objActSheet->setCellValue($cell, $attr);
                    $i++;
                }
                $rowStart = 1;
            } else {
                $rowStart = 0;
            }
            //遍历内容
            /* foreach ($data as $rowKey => $row) {
              $j = 0;
              foreach ($attrNames as $attr) {
              $cell = $cells[$j] . ($i + 1);
              $cvalue = $data[$rowKey][$attr];
              if (array_key_exists($attr, $cellDataType)) {
              $objActSheet->setCellValueExplicit($cell, $cvalue, $cellDataType[$attr]);
              } else {
              $objActSheet->setCellValue($cell, $cvalue);
              }

              $j++;
              }
              $i++;
              } */

            $colNo = 0;
            foreach ($attrNames as $attr) {
                $rowNo = $rowStart;
                $columnType = null;
                if (array_key_exists($attr, $cellDataType)) {
                    $columnType = $cellDataType[$attr];
                }
                foreach ($data as $rowKey => $row) {
                    $cell = $cells[$colNo] . ($rowNo + 1);
                    $cvalue = $data[$rowKey][$attr];
                    if ($columnType) {
                        $objActSheet->setCellValueExplicit($cell, $cvalue, $columnType);
                    } else {
                        $objActSheet->setCellValue($cell, $cvalue);
                    }
                    $rowNo++;
                }
                $colNo++;
            }
            if ($this->mergeCells) {
                foreach ($this->mergeCells as $value) {
                    $objActSheet->mergeCells($value);
                }
            }

            if ($this->file_name) {
                $file_name = $this->file_name;
            } else {
                $file_name = date('YmdHis') . str_pad(mt_rand(0, 99), 2, 0, STR_PAD_LEFT);
            }
            $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $file_name . '.xls"');
            header('Cache-Control: max-age=0');
            $objWriter->save("php://output");
            return null;
        } catch (\PHPExcel_Exception $e) {
            return "Caught Exception ('{$e->getMessage()}')\n{$e}\n";
        }
    }

    /**
     * 设置Reader
     * @param $type
     * @return string
     */
    private function setReader($type)
    {
        try {
            $this->reader = \PHPExcel_IOFactory::createReader($type);
        } catch (\PHPExcel_Reader_Exception $e) {
            return "Caught Exception ('{$e->getMessage()}')\n{$e}\n";
        }
        return null;
    }

    /**
     * 上传excel，导入
     * @param $file
     * @param array $rowRange
     * @param array $columnRange
     * @return array
     */
    public function importExcelByUpload($file, $rowRange = array(), $columnRange = array())
    {
        $file_types = explode(".", $file['name']);
        $file_type = $file_types [count($file_types) - 1];
        if ($file_type == 'xlsx') {
            $type = self::TYPE_2007;
        } else {
            $type = self::TYPE_5;
        }
        $this->setReader($type);
        return $this->_importExcel($file['tmp_name'], $rowRange, $columnRange);
    }

    /**
     * 导入Excel
     * @param string $path
     * @param array $rowRange
     * @param array $columnRange
     * @return array
     */
    public function importExcel($path = '', $rowRange = array(), $columnRange = array())
    {
        $pathinfo = pathinfo($path);
        if ($pathinfo['extension'] == 'xlsx') {
            $type = self::TYPE_2007;
        } else {
            $type = self::TYPE_5;
        }
        $this->setReader($type);
        return $this->_importExcel($path, $rowRange, $columnRange);
    }

    public function _importExcel($path, $rowRange = array(), $columnRange = array())
    {
        if (!$this->reader) {
            return false;
        }
        $PHPExcel = $this->reader->load($path);
        $sheet = $PHPExcel->getSheet(0);
        $rowCount = $sheet->getHighestRow(); // 取得总行数
        $columnCount = $sheet->getHighestColumn(); // 取得总列数
        $colNumber = $this->_getColumnNumber(); //获得列
        $max_column_index = array_search($columnCount, $colNumber); //获得最大列的索引
        $colNumber = array_slice($colNumber, 0, $max_column_index + 1);

        //获取行的范围
        if (isset($rowRange[0])) {
            $rowNo = $rowRange[0] + 1;
        } else {
            $rowNo = 1;
        }
        if (isset($rowRange[1]) && $rowRange[1] >= 0 && $rowCount > $rowRange[1]) {
            $rowCount = $rowRange[1] + 1;
        }
        //获取列的范围
        if (isset($columnRange[0])) {
            if (is_string($columnRange[0])) {
                $min_index = array_search($columnRange[0], $colNumber);
                if ($min_index && $min_index < $max_column_index) {
                    $min_column_index = $min_index;
                }
            } elseif ($columnRange[0] >= 0 && $columnRange[0] < $max_column_index) {
                $min_column_index = $columnRange[0];
            }
        } else {
            $min_column_index = 0;
        }

        if (isset($columnRange[1])) {
            if (is_string($columnRange[1])) {
                $max_index = array_search($columnRange[1], $colNumber);
                if ($max_index && $max_index < $max_column_index) {
                    $colCount = $max_index;
                }
            } elseif ($columnRange[1] >= 0 && $columnRange[1] < $max_column_index) {
                $colCount = $columnRange[1];
            }
        } else {
            $colCount = $max_column_index;
        }
        //获取标题
        if ($this->has_title) {
            if ($this->title) {
                $title = $this->title;
            } else {
                for ($colNo = $min_column_index; $colNo <= $colCount; $colNo++) {
                    $val = $sheet->getCellByColumnAndRow($colNo, 1)->getValue();
                    $title[$colNo] = $val;
                }
            }
        }

        $data = array();
        for ($rowNo; $rowNo <= $rowCount; $rowNo++) {
            $data2 = array();
            for ($colNo = $min_column_index; $colNo <= $colCount; $colNo++) {
                $val = $sheet->getCellByColumnAndRow($colNo, $rowNo)->getValue();
                if ($this->has_title) {
                    $data2[$title[$colNo]] = $val;
                } else {
                    $data2[] = $val;
                }
            }
            $data[] = $data2;
        }
        return $data;
    }

    private function _getColumnNumber()
    {
        $cells1 = range('A', 'Z');
        $cells2 = array();
        foreach ($cells1 as $value1) {
            foreach ($cells1 as $value2) {
                $cells2[] = $value1 . $value2;
                if ($value1 . $value2 == 'IV') {
                    break;
                }
            }
            if ($value1 == 'I') {
                break;
            }
        }
        $cells = array_merge($cells1, $cells2);
        return $cells;
    }

    /**
     * 导出Excel（多张worksheet）
     * @param array $data
     * @param array $cellDataType
     * @return bool|null
     * @throws \PHPExcel_Exception
     */
    public function exportMultiSheetExcel($data = array(), $cellDataType = array())
    {
        if (!$data || count($data) > self::MAX_ROW_NUM) {
            return false;
        }
        //获得execl列
        $cells = $this->_getColumnNumber();

        $objPHPExcel = new \PHPExcel();
        if ($this->excelCreator) {
            $objPHPExcel->getProperties()->setCreator($this->excelCreator); //创建人
            $objPHPExcel->getProperties()->setLastModifiedBy($this->excelCreator); //最后修改人
        }
        if ($this->excelSubject) {
            $objPHPExcel->getProperties()->setSubject($this->excelSubject); //主题
        }
        if ($this->excelDescription) {
            $objPHPExcel->getProperties()->setDescription($this->excelDescription); //描述
        }
        $sheetnum = 0;
        $count = count($data);
        foreach ($data as $key => $sheet) {
            if ($sheetnum > $count) {
                break;
            }
            if ($sheetnum > 0) {
                $objPHPExcel->createSheet();
            }
            $objPHPExcel->setActiveSheetIndex($sheetnum);
            $objActSheet = $objPHPExcel->getActiveSheet();
            if ($key) {
                $objActSheet->setTitle($key); //标题
            }
            //第二维数组中元素下标
            $attrNames = array_keys(reset($sheet));
            //设置标题
            if ($this->has_title) {
                if ($this->title) {
                    $title = $this->title;
                } else {
                    $title = $attrNames;
                }
                $i = 0;
                foreach ($title as $attr) {
                    $cell = $cells[$i] . '1';
                    $objActSheet->setCellValue($cell, $attr);
                    $i++;
                }
                $rowStart = 1;
            } else {
                $rowStart = 0;
            }

            $colNo = 0;
            foreach ($attrNames as $attr) {
                $rowNo = $rowStart;
                $columnType = null;
                if (array_key_exists($attr, $cellDataType)) {
                    $columnType = $cellDataType[$attr];
                }
                foreach ($sheet as $rowKey => $row) {
                    $cell = $cells[$colNo] . ($rowNo + 1);
                    $cvalue = $sheet[$rowKey][$attr];
                    if ($columnType) {
                        $objActSheet->setCellValueExplicit($cell, $cvalue, $columnType);
                    } else {
                        $objActSheet->setCellValue($cell, $cvalue);
                    }
                    $rowNo++;
                }
                $colNo++;
            }
            if ($this->mergeCells) {
                foreach ($this->mergeCells as $value) {
                    $objActSheet->mergeCells($value);
                }
            }
            $sheetnum++;
        }

        if ($this->file_name) {
            $file_name = $this->file_name;
        } else {
            $file_name = date('YmdHis') . str_pad(mt_rand(0, 99), 2, 0, STR_PAD_LEFT);
        }
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save("php://output");
        return null;
    }

    public function importMultiSheetExcelByUpload($file, $rowRange = array(), $columnRange = array()) {
        $file_types = explode ( ".", $file['name'] );
        $file_type = $file_types [count ( $file_types ) - 1];
        if ($file_type == 'xlsx') {
            $type = self::TYPE_2007;
        } else {
            $type = self::TYPE_5;
        }
        $this->setReader($type);
        return $this->_importMultiSheetExcel($file['tmp_name'], $rowRange, $columnRange);
    }

    public function _importMultiSheetExcel($path, $rowRange = array(), $columnRange = array()) {
        if (!$this->reader) {
            return false;
        }
        $PHPExcel = $this->reader->load($path);
        $count = $PHPExcel->getSheetCount();
        $sheettitlearr = $PHPExcel->getSheetNames();
        $res = array();

        for ($sheetnum=0;$sheetnum<$count;$sheetnum++){
            $sheettitle = $sheettitlearr[$sheetnum];
            $sheet = $PHPExcel->getSheet($sheetnum);
            $rowCount = $sheet->getHighestRow(); // 取得总行数
            $columnCount = $sheet->getHighestColumn(); // 取得总列数
            $colNumber = $this->_getColumnNumber(); //获得列
            $max_column_index = array_search($columnCount, $colNumber); //获得最大列的索引
            $colNumber = array_slice($colNumber, 0, $max_column_index + 1);

            //获取行的范围
            if (isset($rowRange[0])) {
                $rowNo = $rowRange[0] + 1;
            } else {
                $rowNo = 1;
            }
            if (isset($rowRange[1]) && $rowRange[1] >= 0 && $rowCount > $rowRange[1]) {
                $rowCount = $rowRange[1] + 1;
            }
            //获取列的范围
            if (isset($columnRange[0])) {
                if (is_string($columnRange[0])) {
                    $min_index = array_search($columnRange[0], $colNumber);
                    if ($min_index && $min_index < $max_column_index) {
                        $min_column_index = $min_index;
                    }
                } elseif ($columnRange[0] >= 0 && $columnRange[0] < $max_column_index) {
                    $min_column_index = $columnRange[0];
                }
            } else {
                $min_column_index = 0;
            }

            if (isset($columnRange[1])) {
                if (is_string($columnRange[1])) {
                    $max_index = array_search($columnRange[1], $colNumber);
                    if ($max_index && $max_index < $max_column_index) {
                        $colCount = $max_index;
                    }
                } elseif ($columnRange[1] >= 0 && $columnRange[1] < $max_column_index) {
                    $colCount = $columnRange[1];
                }
            } else {
                $colCount = $max_column_index;
            }
            //获取标题
            if ($this->has_title) {
                if ($this->title) {
                    $title = $this->title;
                } else {
                    for ($colNo = $min_column_index; $colNo <= $colCount; $colNo++) {
                        $val = $sheet->getCellByColumnAndRow($colNo, 1)->getValue();
                        $title[$colNo] = $val;
                    }
                }
            }
            $data = array();
            for ($rowNo; $rowNo <= $rowCount; $rowNo++) {
                $data2 = array();
                for ($colNo = $min_column_index; $colNo <= $colCount; $colNo++) {
                    $val = $sheet->getCellByColumnAndRow($colNo, $rowNo)->getValue();
                    if ($this->has_title) {
                        $data2[$title[$colNo]] = $val;
                    } else {
                        $data2[] = $val;
                    }
                }
                $data[] = $data2;
            }
            $res[$sheettitle] = $data;
        }
        return $res;
    }
}