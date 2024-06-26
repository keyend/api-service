<?php
namespace app\controller\admin;
/**
 * 控制台控制器基础类
 * 
 * @version 1.0.0
 */
use app\BaseController;
use app\model\system\Rule;
use think\Response;

class Controller extends BaseController
{
    /**
     * 过滤规则
     *
     * @var array
     */
    protected $filter = [];

    /**
     * 当前参数
     *
     * @var array
     */
    protected $params = [];

    /**
     * 表单验证器
     *
     * @var [type]
     */
    protected $validate;

    /**
     * 魔术方法
     *
     * @var array
     */
    private function magic()
    {
        return [
            'logger' => [
                'call' => 'info', 
                'class' => \app\model\system\Logs::class
            ]
        ];
    }

    // 初始化
    protected function initialize()
    {
        // 只显示严重的错误
        error_reporting(E_ERROR | E_PARSE);

        $this->params = input();
        if ($this->request->isAjax() || $this->request->isPost()) {
            $controller = ucfirst(str_replace('.', '', $this->request->controller()));
            $class = $this->request->addon() !== '' ? "\\app\\addons\\" . $this->request->addon() . "\\admin\\validate\\{$controller}" : "\\app\\validate\\{$controller}";
            if (class_exists($class)) {
                $scene = $this->request->action();
                $method = $this->request->isPost() ? 'post' : 'param';
                $this->validate = $this->app->make($class)->scene($scene);
                $this->params = $this->validate->$method($this->filter);
            }
        } else {
            $this->assign('rule', $this->request->rule()->getName());
            $this->assign('admin', $this->request->user);
            $this->assign('base', $this->app->getRootPath() . "app/view/base.html");
            $this->assign('params', $this->params);
            $this->assign('get', input('get.'));
            $this->initMenu();
        }
    }

    /**
     * 获取菜单列表
     *
     * @return void
     */
    protected function getMenuList()
    {
        return $this->app->make(Rule::class)->getList(1, 9999, [ ['module', '=', 'admin'], ['rule_id', 'IN', $this->request->user['access']] ], '`rule_id` as id,`title` as `label`,parent_id,type,name,module,addon,rule,icon,is_page,is_show')["list"];
    }

    /**
     * 绑定子菜单
     *
     * @return void
     */
    private function initMenu()
    {
        $rule = $this->request->rule()->getRule();
        $menus = $this->pointMenu($this->getMenuList(), $rule);
        $menus = parseTree($menus, 'rule_id', 'parent_id', 'children');
        foreach($menus as $v) {
            if ($v["selected"]) {
                $menus = $v["children"];
                break;
            }
        }
        $this->assign("menus", $menus);
        $this->assign("tags", []);
    }

    /**
     * 定位菜单
     *
     * @param [type] $menu
     * @param [type] $rule
     * @return void
     */
    private function pointMenu($menus, $rule = '') 
    {
        // $crumbs = [];
        // $crumbs_keys = ["parent_id", "label", "rule_id", "rule"];
        foreach($menus as $id => $item) {
            $this->addRoute($item);
            $_rule = !empty($item["addon"]) ? $item["addon"] . "/" . $item["rule"] : $item["rule"];
            if ($_rule == $rule) {
                $parent_id = $item["parent_id"];
                $menus[$id]["selected"] = true;
                // $crumbs[] = array_keys_filter($menus[$id], $crumbs_keys);
                while ($parent_id != 0) {
                    if (!isset($menus[$parent_id]["selected"])) {
                        $menus[$parent_id]["selected"] = true;
                        // $crumbs[] = array_keys_filter($menus[$parent_id], $crumbs_keys);
                    }
                    $parent_id = $menus[$parent_id]["parent_id"];
                }
            }
        }
        // 面包屑
        // $crumbs = parseTree($crumbs, 'rule_id', 'parent_id', 'children');
        // $this->assign("crumbs", $crumbs);
        return $menus;
    }

    /**
     * 添加局部路由
     *
     * @param array $item
     * @return void
     */
    private function addRoute($item = [])
    {
        if (!empty($item['name'])) {
            $this->app->route->rule((!empty($item['addon']) ? $item['addon'] . "/" : "") . $item['rule'], "/goto")->name($item['name']);
            // var_dump("<!--addRoute({$item['name']}, " . ((!empty($item['addon']) ? $item['addon'] . "/" : "") . $item['rule']) . ")-->");
        }
    }

    /**
     * 遍历返回值
     * @param Array   $args 参数列表
     * @param String  $message 默认返回MSG
     * @param Integer $status 默认返回状态值
     * @return ResponseArray
     */
    protected function dispatch($args, $message = '', $code = 0)
    {
        if (count($args) > 0) {
            if (is_string($args[0])) {
                $message = array_splice_value($args);
            } elseif(is_integer($args[0]) || is_numeric($args)) {
                $code = array_splice_value($args);
            }

            foreach ($args as $i => $arg) {
                if (is_array($arg)) {
                    $data = array_splice_value($args, $i);
                } elseif ($arg instanceof \app\Model) {
                    $data = $arg;
                } elseif (is_callable([$this, $args[0]])) {
                    $method = array_splice_value($args, $i);
                    $data = $this->$method(...$args);
                } elseif (is_callable($args[0])) {
                    $method = array_splice_value($args, $i);
                    $data = call_user_func($method, $args);
                } elseif (is_integer($args[0]) || is_numeric($args)) {
                    $code = array_splice_value($args, $i);
                } elseif (is_string($args[0])) {
                    $message = array_splice_value($args, $i);
                }
            }
        }

        if (isset($data)) {
            if ($data instanceof Arrayable) {
                $data = $data->toArray();
            } elseif ($arg instanceof \app\Model) {
                $data = $data->toArray();
            } elseif ($data instanceof Response) {
                return $data;
            } elseif(!is_array($data)) {
                $data = (array)$data;
            }
        } else {
            $data = null;
        }

        return compact('code', 'message', 'data');
    }

    protected function success(...$args)
    {
        if (IS_AJAX) {
            return $this->dispatch($args, 'success', 0);
        } else {
            $data = $this->dispatch($args, 'success', 0);
            $this->assign($data);
            return $this->fetch('Common/success');
        }
    }

    protected function error(...$args)
    {
        if (IS_AJAX) {
            return $this->dispatch($args, 'failed', 500);
        } else {
            $data = $this->dispatch($args, 'success', 0);
            $this->assign($data);
            return $this->fetch('Common/error');
        }
    }

    /**
     * 返回请求的分页信息
     * @return Array
     */
    protected function getPaginator() 
    {
        return array_values(array_keys_filter($this->request->param(), [['page', 1], ['limit', 10]]));
    }

    /**
     * 调用魔术方法
     *
     * @param string $name
     * @param mixed $arguments
     * @return object
     */
    public function __call($name, $arguments)
    {
        $magic = $this->magic();
        if (in_array($name, array_keys($magic))) {
            $params = $magic[$name];
            $class = $this->app->make($params['class']);
            return call_user_func_array([$class, $params['call']], $arguments);
        }
    }
}