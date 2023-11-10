<?php
namespace app\model\system;
/*
 * 用户表
 * @package app.common.model
 * @Author: k.
 * @Date: 2021-05-10 20:19:31
 */
use app\Model;
use think\facade\Db;
use mashroom\exception\UserNotFoundException;
use mashroom\exception\UserException;
use mashroom\exception\HttpException;

class User extends Model
{
    protected $name = 'sys_user';
    protected $pk = 'user_id';

    /**
     * 可用
     * @var integer
     */
    const STATUS_ENABLE = 1;

    /**
     * 锁定
     * @var integer
     */
    const STATUS_LOCK = 2;

    /**
     * 所属用户组
     * @collection relation.model
     */
    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'group_id', 'group_id');
    }

    /**
     * 用户应用角色列表
     * @collection relation.model
     */
    public function roles()
    {
        return $this->hasManyThrough(UserGroupRole::class, UserGroup::class, 'group_id', 'group_id', 'group_id', 'group_id');
    }

    /**
     * 返回当前用户应用的角色列表
     * @return Array
     */
    protected function getRoles()
    {
        $roles = [];
        $groupRoles = $this->getAttr('roles');

        if ($groupRoles) {
            foreach($groupRoles as $groupRole) {
                $roles[] = $groupRole->role;
            }
        }

        return $roles;
    }

    /**
     * 当前用户的权限列表
     * @return Array [{rule_id: 1, rule: '', extends: {...}},{}, ...]
     */
    public function getAccess()
    {
        $rules = [];

        foreach($this->getRoles() as $role) {
            if ($role->role === 'EVERYONE') {
                return app()->make(Rule::class)->getAllow('admin');
            } else {
                foreach ($role->rules as $rule) {
                    $rule->extends;
                    $rules[] = $rule->toArray();
                }
            }
        }

        return array_column_bind($rules, 'extends', 'attr', 'value');
    }

    /**
     * 返回用户明细
     * @param array $map
     * @return Collection
     */
    public function getUser($map)
    {
        $user = parent::where($map)->with(['group'])->find();
        if ($user && $user->group) {
            $user->group = $user->group;
        }
        return $user;
    }

    /**
     * 管理账户登录
     * 
     * @param Array $data 用户数据
     * @throws UserException
     * @throws UserNotFoundException
     * @return mixed
    */
    public function validate($data)
    {
        $user = $this->getUser(['username' => $data['username']]);
        if (!$user) {
            throw new UserNotFoundException('账户不存在');
        } elseif(($user->status & self::STATUS_ENABLE) !== 1) {
            throw new UserNotFoundException('账户不可用');
        } elseif(($user->status & self::STATUS_LOCK) !== 0) {
            throw new UserException('账户已锁定');
        } elseif(!password_check($user->password, $user->salt, $data['password'])) {
            // throw new UserNotFoundException('登录密码填写不正确');
        }

        // 更新用户在线时间
        $user->lastonline_time = TIMESTAMP;
        $user->lastlogin_ip = request()->ip();
        $user->lastlogin_time = TIMESTAMP;
        $user->token = rand_string();
        $user->save();

        // 保存用户登录信息
        $data = array_keys_filter($user->toArray(), [
            'user_id',
            'username',
            'group_id',
            'parent_id',
            'token',
            'lastlogin_ip',
            'lastonline_time',
            'nickname',
            'avatar'
        ]);
        // 用户组
        $data['group'] = $user->group->group;
        // 组所属成员
        $data['group_range'] = $user->group->group_range;
        // 父级用户ID
        $data['parent_id'] = $user->parent_id === 0 ? $user->user_id : $user->parent_id;
        // 权限列表
        $data['access'] = array_values(array_column($this->getAccess(), 'rule_id'));
        // 默认语言
        $data['lang'] = app()->get(\think\Lang::class)->getLangSet();

        return $data;
    }

    /**
     * 返回用户列表
     * @return array
     */
    public function getList(int $page, int $limit, $filter = [])
    {
        $condition = [];
        if (isset($filter['keyword']) && !empty($filter['keyword'])) {
            $condition[] = ['username|nickname|realname|mobile', 'LIKE', "%{$filter['keyword']}%"];
        }
        $query = self::where($condition)->order('user_id DESC');
        /**
         * 用户列表返回规则：
         * 1. 品牌用户返回主用户及子用户
         * 2. 工厂用户返回主用户及子用户
         * 3. 平台用户返回主用户及子用户
         * 4. 超级管理员返回所有主用户
         */
        $query->when(super(), function($query) {
            $query->where('parent_id', '<>', 0);
        })->when(!super(), function($query) {
            $query->where('user_id|parent_id', S3);
        });
        $count = $query->count();

        $query->withoutField('password,salt,parent_id')->with(['group' => function($query) {
            $query->field('group_id,group');
        }]);
        $list = $query->page($page, $limit)->withAttr('create_time', function($value) {
            return parseTime($value);
        })->withAttr('lastonline_time', function($value) {
            return parseTime($value);
        })->withAttr('lastlogin_time', function($value) {
            return parseTime($value);
        })->select()->toArray();

        array_walk($list, function(&$item) {
            $item['is_delete'] = $item['user_id'] !== S1 && $item['user_id'] !== S3;
        });

        return compact('count', 'list', 'sql');
    }

    /**
     * 建立新用户
     * 
     * @access public
     * @param array  $data       数据数组
     * @param array  $attrs      扩展字段
     * @return int
     */
    public function addUser(array $data)
    {
        Db::transaction(function () use(&$data) {
            try {
                $data['user_id'] = parent::insertGetId(array_keys_filter($data, [
                    'username',
                    'password',
                    'nickname',
                    ['realname', ''],
                    ['mobile', ''],
                    ['avatar', ''],
                    'salt',
                    'status',
                    'group_id',
                    'parent_id',
                    'create_time'
                ], true));
                Db::commit();
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage());
                Db::rollback();
            }
        });

        return $data;
    }

    /**
     * 编辑用户
     * 
     * @access public
     * @param array  $data       数据数组
     * @param array  $attrs      扩展字段
     * @return int
     */
    public function editUser($data = [])
    {
        Db::transaction(function () use(&$data) {
            $userData = array_keys_filter($data, [
                'username',
                'password',
                'nickname',
                ['realname', ''],
                ['mobile', ''],
                ['avatar', ''],
                ['remark', ''],
                'salt',
                'status',
                'group_id',
                'parent_id',
                'create_time',
                'update_time'
            ]);
            $this->save($userData);
            Db::commit();
        });
    }

    /**
     * 删除用户
     *
     * @return void
     */
    public function delUser()
    {
        $group = $this->getAttr('group');
        if (super($group->group_range) || $this->parent_id == 0) {
            throw new HttpException('操作失败(不允许删除该用户)!');
        }
        $user_model = new static();
        $user_child = $user_model->where('parent_id', $this->user_id)->field('user_id')->find();
        if (!empty($user_child)) {
            throw new HttpException('操作失败(存在子账户)!');
        }
        try {
            $this->logger('logs.sys.user.delete', 'DELETE', $this->getData());
            $this->delete();
        } catch(\Exception $e) {
            throw new HttpException($e->getMessage());
        }
    }
}
