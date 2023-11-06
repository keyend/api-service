<?php
namespace mashroom;

class Excel
{
    private $excel;
    private $border = array(
        'borders' => array(
            'allborders' => array(
                'style' => \PHPExcel_Style_Border::BORDER_THIN
            )
        )
    );
    private $sums = array();
    private $options = null;

    public function output()
    {
        $buffer = \PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007');
        $buffer->save('php://output');
        die;
    }

    private function getDefaultHeader($list = [])
    {
        $headers = [];
        foreach($list as $row) {
            foreach($row as $key => $value) {
                $header = [
                    "title" => $key,
                    "field" => $key,
                    "width" => 18
                ];
                if (strpos($key, "_money") !== false) {
                    $header["width"] = 12;
                    $header["sum"] = 1;
                } elseif (strpos($key, "num") !== false) {
                    $header["width"] = 12;
                    $header["sum"] = 1;
                } elseif (strpos($key, "_no") !== false) {
                    $header["type"] = "numeric";
                } elseif (strpos($key, "remark") !== false) {
                    $header["width"] = 48;
                } elseif (strpos($key, "desc") !== false) {
                    $header["width"] = 48;
                } elseif (strpos($key, "content") !== false) {
                    $header["width"] = 48;
                } elseif (strpos($key, "image") !== false) {
                    $header["type"] = "image";
                }

                $headers[] = $header;
            }
        }
        return $headers;
    }

    /**
     * 导出为EXCEL
     *
     * @param array $list
     * @param array $options
     * @return void
     */
    public function excel($list = [], $options = [])
    {
        static $excel;
        static $beginAscll = 65;
        static $prefix = '';
        static $prefixIndex = 0;
        static $stage = 0;
        static $rowIndex = 1;
        static $cellIndex = 1;
        static $drawing = null;
        static $isSum = false;

        if($excel == null) {
            $options['title'] = $options['title']??"记录导出";
            $options['headers'] = $options['headers']??$this->getDefaultHeader($list);
            if(empty($options['headers'])) {
                throw new \Exception('Export failed of options invalid.');
            }

            $this->excel = new \PHPExcel();
            $this->options = $options;

            $excel = $this->excel;
            $excel->setActiveSheetIndex(0);
            $excel->getActiveSheet()->setTitle($options['title']);
            $excel->getActiveSheet()->getRowDimension($rowIndex)->setRowHeight(32);
            $excel->getActiveSheet()->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $excel->getActiveSheet()->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            foreach($options['headers'] as $header) {
                if ($stage > 25 || $stage > 51) {
                    $prefix = chr($beginAscll + $prefixIndex); //AA AB AC AE
                    $prefixIndex += 1;
                    $stage = 0;
                }

                $id = $prefix . chr($stage + $beginAscll); // A B C D E
                $rowId = $id . $rowIndex;

                $excel->getActiveSheet()->setCellValue($id . $rowIndex, $header['title']);
                $excel->getActiveSheet()->getColumnDimension($id)->setWidth($header['width']);

                if ($header['type'] === 'id') {
                    $excel->getActiveSheet()->getStyle($rowId)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                } else {
                    $excel->getActiveSheet()->getStyle($rowId)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                }

                $excel->getActiveSheet()->getStyle($rowId)->getFill()->applyFromArray(array(
                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => array(
                        'rgb' => 'F0E7DB'
                    )
                ));
                $excel->getActiveSheet()->getStyle($rowId)->applyFromArray($this->border);

                $stage += 1;
            }

            $filename = $options['title'] . '_' . date('ymdHis') . '.xlsx';

            header("Pragma: public");
            header("Expires: 0");
            header('Cache-Control: max-age=0');
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");;
            header('Content-Disposition:attachment;filename="' . $filename . '"');
            header("Content-Transfer-Encoding:binary");
        }

        foreach($list as $i => $row) {
            $rowIndex += 1;
            $prefix = '';
            $prefixIndex = 0;
            $stage = 0;
            $rowImage = false;

            foreach($options['headers'] as $mapper) {
                $key = $mapper['field'];
                $image = false;
                $drawing = null;

                if ($stage === 26 || $stage === 52) {
                    $prefix = chr($beginAscll + $prefixIndex); //AA AB AC AE
                    $prefixIndex += 1;
                    $stage = 0;
                }

                $id = $prefix . chr($stage + $beginAscll);  // A B C D E
                $rowId = $id . $rowIndex;

                if ($rowIndex == 2) {
                    if (false === $isSum) {
                        if (isset($mapper['sum']) && $mapper['sum'])
                            $isSum = true;
                    }
                }

                if ($mapper['type'] != 'id') {
                    $excel->getActiveSheet()->getStyle($rowId)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                } else {
                    $excel->getActiveSheet()->getStyle($rowId)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }

                if ($mapper['type'] == 'id') {
                    $value = $rowIndex - 1;
                } elseif($mapper['type'] == 'money') {
                    $value = "¥".number_format(floatval($row[$key]) / 100, 2, '.', '');
                } elseif($mapper['type'] == 'numeric') {
                    $value = "\t".$row[$key];
                } elseif($mapper['type'] == 'datetime') {
                    $value = $row[$key];
                    if (is_numeric($value)) {
                        $value = date('Y-m-d H:i:s', $value);
                    }
                    $value = "\t".$value;
                } elseif($mapper['type'] == 'date') {
                    $value = $row[$key];
                    if (is_numeric($value)) {
                        $value = date('Y-m-d', $value);
                    }
                    $value = "\t".$value;
                } elseif($mapper['type'] == 'qrcode') {
                    $image = true;
                    $value = m('qrcode')->createQrcode($row[$key]);
                    $values = [$value];
                } elseif($mapper['type'] == 'link') {
                    $excel->getActiveSheet()->getCell($rowId)->getHyperlink()->setUrl($value);
                    $value = strip_tags($row[$key]);
                } elseif($mapper['type'] == 'text') {
                    $value = strip_tags($row[$key]);
                } elseif($mapper['type'] == 'image') {
                    $values = explode(",", $row[$key]);
                    $image = true;
                } else {
                    $value = $row[$key];
                }

                if ($image) {
                    foreach($values as $image) {
                        if (!empty($image)) {
                            $localimage = str_replace($_W['siteroot'], IA_ROOT . '/', $image);

                            if (file_exists($localimage)) {
                                $drawing = new \PHPExcel_Worksheet_Drawing();
                                $drawing->setPath($localimage);
                                $drawing->setWidth(50);
                                $drawing->setHeight(50);
                                $drawing->setCoordinates($rowId);
                                $drawing->setWorksheet($excel->getActiveSheet());
                            } else {
                                $excel->getActiveSheet()->getCell($rowId)->getHyperlink()->setUrl($image);
                                $value = $image;
                            }
                        }

                        break;
                    }

                    // 如果已存在画图，则提前结束
                    if ($drawing != null) {
                        $stage += 1;
                        $rowImage = true;
                        continue;
                    }
                }

                $excel->getActiveSheet()->setCellValue($rowId, $value);
                $excel->getActiveSheet()->getStyle($rowId)->applyFromArray($this->border);
                $value = "";

                $stage += 1;
            }

            $excel->getActiveSheet()->getRowDimension($rowIndex)->setRowHeight($rowImage ? 50 : 26);
        }

        if (empty($list)) {
            if ($isSum) {
                $rowIndex += 1;
                $prefix = '';
                $prefixIndex = 0;
                $stage = 0;
                foreach($this->options['headers'] as $mapper) {
                    if ($stage === 26 || $stage === 52) {
                        $prefix = chr($beginAscll + $prefixIndex); //AA AB AC AE
                        $prefixIndex += 1;
                        $stage = 0;
                    }
                    $id = $prefix . chr($stage + $beginAscll);  // A B C D E
                    $rowId = $id . $rowIndex;
                    $value = "";
                    if (isset($mapper['sum']) && $mapper['sum']) {
                        $value = "=SUM({$id}2:{$id}" . ($rowIndex-1) . ")";
                    }
                    if ($mapper['type'] != 'id') {
                        $excel->getActiveSheet()->getStyle($rowId)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                    } else {
                        $excel->getActiveSheet()->getStyle($rowId)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    }
                    $excel->getActiveSheet()->setCellValue($rowId, $value);
                    $excel->getActiveSheet()->getStyle($rowId)->applyFromArray($this->border);
                    $excel->getActiveSheet()->getStyle($rowId)->getFill()->applyFromArray(array(
                        'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                        'startcolor' => array(
                            'rgb' => 'F0F0F0'
                        )
                    ));
                    $stage += 1;
                }
                $excel->getActiveSheet()->getRowDimension($rowIndex)->setRowHeight(26);
            }

            return $this->output();
        }

        return $this;
    }
}