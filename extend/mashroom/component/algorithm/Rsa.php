<?php
namespace mashroom\component\algorithm;
/**
 * RSA非对称加密、解密
 * 
 * Rsa::build('/extras/openssl/openssl.cnf');
 * Rsa::getPublicKey()
 * Rsa::getPrivateKey()
 * Rsa::encrypt('AAAAA')
 * Rsa::decrypt('AAAAA')
 *
 * @date    2021-05-10 20:19:31
 * @version 1.0
 * @author  easyzf.cn
 * @description 1公钥 2私钥
 */
use mashroom\exception\HttpException;

class Rsa
{
    /**
     * 使用公钥还是私钥
     *
     * @var string
     */
    protected $type = null;

    /**
     * 公钥
     *
     * @var string
     */
    protected $publicKey = null;

    /**
     * 私钥
     *
     * @var string
     */
    protected $privateKey = null;

    /**
     * 优先度
     *
     * @var string
     */
    protected $index = 0;

    /**
     * 配置信息
     *
     * @var array
     */
    protected $options = [
        'type' => null,
        // 解密
        'decrypt' => [
            // 使用证书
            'type' => 'private'
        ],
        // 加密
        'encrypt' => [
            // 使用证书
            'type' => 'public'
        ]
    ];

    /**
     * 构造函数
     *
     * @param array $options 参数配置
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * 配置参数
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * 设置使用的证书
     *
     * @param string $type
     * @return object
     */
    public function type(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * 验证钥匙
     *
     * @param string $key
     * @return boolean
     */
    private function validateCertKey(string $key) : bool
    {
        if (preg_match('/[\r\n]/', $key)) {
            if (strpos($key, 'KEY--') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 返回钥匙
     *
     * @param string $type
     * @return string
     */
    private function getKey(int $ind = 1)
    {
        $types = ['', 'public', 'private'];

        if ($ind === 0) {
            if ($this->publicKey !== null) {
                $ind = 1;
            } elseif($this->publicKey !== null) {
                $ind = 2;
            } else {
                throw new HttpException("Uncaught Error: Undefined operation pointer");
            }
        }

        $type     = $types[$ind];
        $key      = $ind === 1?$this->publicKey:$this->privateKey;

        $fallback = "openssl_pkey_get_{$type}";
        $encrypt  = "openssl_{$type}_encrypt";
        $decrypt  = "openssl_{$type}_decrypt";

        if (true !== $this->validateCertKey($key)) {
            $key = $this->formatCertKey($key, $type);
        }

        return compact('key', 'type', 'fallback', 'encrypt', 'decrypt');
    }

    /**
     * 格式化钥匙
     *
     * @param string $str
     * @param string $type
     * @return string
     */
    private function formatCertKey(string $str, string $type)
    {
        // 如果是一段路径
        if (strlen($str) < 512) {
            $sep = substr($str, 0, 1);
            if (in_array($sep, ['/', '\\'])) {
                $str = "." . $str;
            }

            if (!file_exists($str)) {
                throw new HttpException("Uncaught Error: No such file or directory \"$str\"");
            }

            $str = file_get_contents($str);

            if (true === $this->validateCertKey($str)) {
                return $str;
            }
        }

        $content = chunk_split($str, 64, "\n");
        $sepatator = str_repeat("-", 5);
        $type = strtoupper($type);

        $result = [
            "{$sepatator}BEGIN {$type} KEY{$sepatator}\r\n",
            $content,
            "{$sepatator}END {$type} KEY{$sepatator}\r\n"
        ];

        return implode('', $result);
    }

    /**
     * 生成证书
     * @warning WIN系统中，需要在参数中携带openssl.cnf地址
     *
     * @param string $cnf
     * @return void
     */
    public function build(string $cnf = '')
    {
        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $data = openssl_pkey_new($config);
        if (!$data) {
            if (strpos(PHP_OS, 'WIN') !== false) {
                $config['config'] = $cnf;
                $data = openssl_pkey_new($config);
            }
        }

        if (!$data) {
            throw new HttpException("Uncaught Error: Failed to generate certificate");
        }

        $privateKey = '';
        openssl_pkey_export($data, $privateKey, null, $config);
        $pub = openssl_pkey_get_details($data);

        $this->privateKey = $privateKey;
        $this->publicKey = $pub["key"];

        return $this;
    }

    /**
     * 获取公钥
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * 获取公钥
     *
     * @return string
     */
    public function getPrivateKey()
    {
      return $this->privateKey;
    }

    /**
     * 设置公钥
     *
     * @param string $key
     * @return object
     */
    public function setPublicKey(string $key = '')
    {
        if ($key === '') {
            $this->publicKey = __DIR__ . DIRECTORY_SEPARATOR. 'certs' . DIRECTORY_SEPARATOR . 'rsa_public_key.pem';
            $this->index = 1;
        } else {
            $this->publicKey = $key;
        }

        return $this;
    }

    /**
     * 设置私钥
     *
     * @param string $key
     * @return object
     */
    public function setPrivateKey(string $key = '')
    {
        if ($key === '') {
            $this->privateKey = __DIR__ . DIRECTORY_SEPARATOR. 'certs' . DIRECTORY_SEPARATOR . 'rsa_private_key.pem';
            $this->index = 2;
        } else {
            $this->privateKey = $key;
        }

        return $this;
    }

    /**
     * 加密数据
     *
     * @param string $str
     * @param integer $ind
     * @return string
     */
    public function encrypt(string $str, int $ind = 0) : string
    {
        // 选择获取的证书
        $ind = $ind?:$this->index;
        // 获取证书
        $algo = $this->getKey($ind);
        extract($algo);

        $res = $fallback($key);
        // 加密失败
        if (!$res) {
            throw new HttpException("Error: Encryption failed");
        }

        $detail = openssl_pkey_get_details($res);
        $block  = $detail['bits']/8-11;
        $plain  = str_split($str, $block);
        $result = '';

        foreach ($plain as $chunk) {
            $partial = '';
            if ($encrypt($chunk, $partial, $key, OPENSSL_PKCS1_PADDING) === false) {
                return false;
            }

            $result .= $partial;
        }

        return base64_encode($result);
    }

    /**
     * 解密数据
     *
     * @param string $str
     * @param integer $ind
     * @return string
     */
    public function decrypt(string $str, int $ind = 0) : string
    {
        // 选择获取的证书
        $ind = $ind?:$this->index;
        // 获取证书
        $algo = $this->getKey($ind);
        extract($algo);

        $res = $fallback($key);
        // 加密失败
        if (!$res) {
            throw new HttpException("Error: Encryption failed");
        }

        $detail = openssl_pkey_get_details($res);
        $block  = $detail['bits']/8;
        $str    = base64_decode($str);
        $plain  = str_split($str, $block);
        $result = '';

        foreach ($plain as $chunk) {
            $partial = '';
            if ($decrypt($chunk, $partial, $key, OPENSSL_PKCS1_PADDING) === false) {
                throw new HttpException("Error: Encryption failed");
            }

            $result .= $partial;
        }

        return $result;
    }
}
