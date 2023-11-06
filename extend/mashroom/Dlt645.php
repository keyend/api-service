<?php
namespace mashroom;
/**
 * Dlt645 信号解析
 * @author Administrator
 */
use think\Container;

class Dlt645 extends Container
{
    const T97 = [0x01,0x02,0x03,0x04,0x05,0x08,0x0A,0x0C,0x0F,0x10];
    const T97_DICTIONARY = [
        "read",
        "remarry",
        "reread",
        "write",
        "time",
        "setaddress",
        "changespeed",
        "changepwd",
        "maxclean"
    ];
    const T07 = [0x11,0x12,0x13,0x14,0x15,0x16,0x17,0x18,0x19,0x1A,0x1B,0x1C];
    const T07_DICTIONARY = [
        "read",
        "remarry",
        "getaddress",
        "write",
        "setAddress",
        "lock",
        "changespeed",
        "changepwd",
        "maxclean",
        "clean",
        "eventclean",
        "extra"
    ];

    protected static $macros = [];

    protected $T07_Identification_code_table = [];
    protected $T97_Identification_code_table_DI1 = [];
    protected $T97_Identification_code_table_DI0 = [];

    /**
     * 构造函数
     *
     * @param string $data
     */
    public function __construct()
    {
        $this->initializeCodeTable();
    }

    /**
     * 标识编码表
     *
     * @return void
     */
    private function initializeCodeTable()
    {
        $T07_Identification_code_table["00000000"] = ["title" => "组合有功总", "name" => "CombinedActiveTotal"];
        $T07_Identification_code_table["0000FF00"] = ["title" => "组合有功数据块", "name" => "CombinedActiveDataBlock"];
        $T07_Identification_code_table["00010000"] = ["title" => "正向有功总", "name" => "PositiveActivePower"];
        $T07_Identification_code_table["0001FF00"] = ["title" => "正向有功数据块", "name" => "PositiveActiveDataBlock"];
        $T07_Identification_code_table["00020000"] = ["title" => "反向有功总", "name" => "ReverseActivePower"];
        $T07_Identification_code_table["0002FF00"] = ["title" => "反向有功数据块", "name" => "ReverseActiveDataBlock"];
        $T07_Identification_code_table["05040001"] = ["title" => "整点冻结时间", "name" => "HourFreezeTime"];
        $T07_Identification_code_table["05040101"] = ["title" => "整点正向有功电量", "name" => "FullPointPositiveActivePowerConsumption"];
        $T07_Identification_code_table["05060001"] = ["title" => "日冻结时间", "name" => "DaliyFreezeTime"];
        $T07_Identification_code_table["05060101"] = ["title" => "日冻结正向有功电量", "name" => "FullDaliyPositiveActivePowerConsumption"];
        $T07_Identification_code_table["04000102"] = ["title" => "时间", "name" => "Time"];
        $T07_Identification_code_table["04000101"] = ["title" => "日期", "name" => "Date"];
        $T07_Identification_code_table["02010100"] = ["title" => "A相电压", "name" => "A-phaseVoltage"];
        $T07_Identification_code_table["02010200"] = ["title" => "B相电压", "name" => "B-phaseVoltage"];
        $T07_Identification_code_table["02010300"] = ["title" => "C相电压", "name" => "C-phaseVoltage"];
        $T07_Identification_code_table["0201FF00"] = ["title" => "电压块", "name" => "VoltageBlock"];
        $T07_Identification_code_table["02020100"] = ["title" => "A相电流", "name" => "A-phaseCurrent"];
        $T07_Identification_code_table["0202FF00"] = ["title" => "电流块", "name" => "CurrentBlock"];
        $T07_Identification_code_table["02030000"] = ["title" => "总有功功率", "name" => "TotalActivePower"];
        $T07_Identification_code_table["02030100"] = ["title" => "A相有功功率", "name" => "A-phaseActivePower"];
        $T07_Identification_code_table["02030200"] = ["title" => "B相有功功率", "name" => "B-phaseActivePower"];
        $T07_Identification_code_table["02030300"] = ["title" => "C相有功功率", "name" => "C-phaseActivePower"];
        $T07_Identification_code_table["0203FF00"] = ["title" => "有功功率块", "name" => "ActivePowerBlock"];

        $T97_Identification_code_table_DI0["10010000"] = ["title" => "电能量有功", "name" => "ActivePower"];
        $T97_Identification_code_table_DI0["10010001"] = ["title" => "电能量无功", "name" => "NonePower"];
        $T97_Identification_code_table_DI1["00010000"] = ["title" => "正向总电能", "name" => "Positive"];
        $T97_Identification_code_table_DI1["00010001"] = ["title" => "正向费率1", "name" => "PositiveRatio1"];
        $T97_Identification_code_table_DI1["00010010"] = ["title" => "正向费率2", "name" => "PositiveRatio2"];
        $T97_Identification_code_table_DI1["00010011"] = ["title" => "正向费率3", "name" => "PositiveRatio3"];
        $T97_Identification_code_table_DI1["00010100"] = ["title" => "正向费率4", "name" => "PositiveRatio4"];
        $T97_Identification_code_table_DI1["00100000"] = ["title" => "反向总电能", "name" => "Reverse"];
        $T97_Identification_code_table_DI1["00100001"] = ["title" => "反向费率1", "name" => "ReverseRatio1"];
        $T97_Identification_code_table_DI1["00100010"] = ["title" => "反向费率2", "name" => "ReverseRatio2"];
        $T97_Identification_code_table_DI1["00100011"] = ["title" => "反向费率3", "name" => "ReverseRatio3"];
        $T97_Identification_code_table_DI1["00100100"] = ["title" => "反向费率4", "name" => "ReverseRatio4"];
    }

    /**
     * 解析数据
     *
     * @param string $data
     * @return void
     */
    public function parse($data = '')
    {
        /**
         * 前导码FE过滤，由于不同厂家不同型号的表前导码FE的个数是不同的，
         * 还有些厂家不会发送前导码FE，解析接收数据的一般方法是忽略前FE
         * @version 1.0.0
         */
        while(substr($data, 0, 2) != '68') {
            $data = substr($data, 2);
            if (empty($data)) {
                throw new \InvalidArgumentException("参数错误");
            }
        }

        /**
         * 排除完前导码后，如果数据为空那么数据桢结构错误
         * @version 1.0.0
         */
        if (empty($data)) {
            throw new \InvalidArgumentException("参数错误: {$data}");
        }

        $length = hexdec(substr($data, 18, 2));
        $checksum = substr($data, -4, 2);
        $validate = $this->checksum(substr($data, 0, -4));
        if ($checksum !== $validate) {
            throw new \InvalidArgumentException("参数校验错误: {$data} {$checksum}/{$validate}");
        }

        $this->bind("length", $length);
        $address = substr($data, 2, 12);
        $this->bind("address", $this->filp($address));
        $dataControl = substr($data, 16, 2);
        $this->bind("control", $this->reverseControl($dataControl));
        $dataContent = substr($data, 20, $length * 2);
        $this->bind("data", $dataContent);
        $this->parseData($dataContent, $length);
    }

    /**
     * 高低位交换
     *
     * @param [type] $str
     * @return void
     */
    private function filp($str) {
        $result = '';
        for($i = strlen($str) - 2; $i >= 0; $i -= 2) {
            $result .= substr($str, $i, 2);
        }
        return $result;
    }

    /**
     * 校验和计算
     *
     * @param [type] $data
     * @return void
     */
    private function checksum($data) {
        $sum = 0;
        var_dump($data);
        $bytes = str_split($data, 2);
        foreach ($bytes as $byte) {
            $sum += hexdec($byte);
        }
        $sum &= 0xFF;
        return sprintf('%02X', $sum);
    }

    /**
     * 返回地址
     *
     * @return void
     */
    public function getAddress()
    {
        $control = $this->get("control");
        if ($control["protocol"] == 07) {
            // 补充6位BCD码
            return substr($this->get("address") . "000000000000", 0, 12);
        } else {
            return $this->get("address");
        }
    }

    /**
     * 解析控制码
     * {sender: "", error: 1, remarry: 0, protocol: "97", ability: "clean"}
     * 
     * @param [type] $dataControl
     * @return void
     * @version 1.0.0
     */
    private function reverseControl($dataControl)
    {
        $result = [];
        $reverseControl = str_split(substr("00000000" . base_convert($dataControl, 16, 2), -8));
        // 发送者是设置device还是采集器collector
        $result["sender"] = array_splice($reverseControl, 0, 1)?"collector":"device";
        // 设备是否有异常
        $result["error"] = (int)array_splice($reverseControl, 0, 1);
        // 是否有后续侦数据
        $result["remarry"] = (int)array_splice($reverseControl, 0, 1);
        // 功能码解析
        var_dump($reverseControl);die;
        $control = base_convert(implode("", $reverseControl), 2, 16);
        $t97max = max(self::T97);
        $result["protocol"] = $t97max < $control ? "07" : "97";
        foreach(constant("self::T{$result['protocol']}") as $i => $value) {
            if ($value == $control) {
                $dictionary = constant("self::T{$result['ability']}_DICTIONARY");
                $result["ability"] = $dictionary[$i];
                break;
            }
        }
        if (!isset($result["ability"])) {
            throw new \InvalidArgumentException("参数错误：解析功能码失败!");
        }
        return $result;
    }

    /**
     * 解析数据
     *
     * @param [type] $data
     * @param integer $length
     * @return void
     */
    public function parseData($data, $length = 0)
    {
        $half = intval($length / 2);
        $previous = "";
        while($half-- > 0) {
            $previous .= substr("00" . (intval(substr($data, $half, 2)) - 0x33), -2);
        }
    }

    /**
     * 获取控制码
     *
     * @param string $cmd
     * @return void
     */
    private function getCommand($cmd)
    {
        $control = $this->get("control");
        $ctrlCode = constant("self::T{$control['protocol']}");
        foreach(constant("self::T{$control['protocol']}_DICTIONARY") as $i => $value) {
            if ($value == $cmd) {
                return $ctrlCode[$i];
            }
        }
    }

    /**
     * 返回规约、数据域
     *
     * @param string $name
     * @return void
     */
    private function getDataDomain($name)
    {
        $control = $this->get("control");
        $result = [];
        if ($name == 'PositiveActivePower') {
            if ($control["protocol"] == "97") {
                foreach($this->T97_Identification_code_table_DI0 as $key => $value) {
                    if ($value["name"] == "ActivePower") {
                        $result[] = bin2hex($key) + 0x33;
                    }
                }
                foreach($this->T97_Identification_code_table_DI1 as $key => $value) {
                    if ($value["name"] == "Positive") {
                        $result[] = bin2hex($key) + 0x33;
                    }
                }
            } elseif($control["protocol"] == "07") {
                foreach($this->T07_Identification_code_table as $key => $value) {
                    if ($value["name"] == $name) {
                        foreach(str_split($key, 2) as $val) {
                            $result[] = unpack("H*", $val) + 0x33;
                        }
                    }
                }
            }
        }

        return $this->filp($result);
    }

    /**
     * 查表全文返回
     *
     * @param string $address
     * @return string
     */
    public function getQueryCommand($address = '')
    {
        $address = $address ? $this->get("address") : $address;
        if (empty($address)) {
            throw new \InvalidArgumentException("Address cannot be empty");
        }
        $filpAddress = $this->filp($address);
        $length = 0;
        $result[] = "68";
        $result[] = array_merge($result, str_split($filpAddress, 2));
        $result[] = "68";
        $result[] = $this->getCommand("read");
        $result[] = &$length;
        $dataDomain = $this->getDataDomain("PositiveActivePower");
        $length = strlen($dataDomain) / 2;
        $result[] = array_merge($result, $dataDomain);
        $result[] = $this->checksum($result);
        $result[] = "16";
        return $result;
    }

    /**
     * Marcoable Bind
     *
     * @param [type] $name
     * @param [type] $macro
     * @return void
     */
    public static function macro($name, $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Marcoable Exists
     *
     * @param [type] $name
     * @return boolean
     */
    public static function hasMacro($name)
    {
        return static::$macros[$name]??false;
    }

    /**
     * Marcoable Trait
     *
     * @param [type] $method
     * @param [type] $parameters
     * @return void
     */
    public function __call($method, $parameters)
    {
        if (!static::hasMacro($method)) {
            throw new \InvalidArgumentException("Method {$method} does not exist.");
        }
        $macro = static::$macros[$method];
        if ($macro instanceof Closure) {
            return call_user_func_array($macro->bindTo($this, static::class), $parameters);
        }
        return call_user_func_array($macro, $parameters);
    }
}