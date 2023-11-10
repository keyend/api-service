<?php
namespace app\controller\admin;
/**
 * 会员
 * 
 * @version 1.0.0
 */
use think\facade\Cache as ThinkCache;
use app\model\system\User as UserModel;
use app\model\system\UserGroup;
use app\model\system\Logs;

class User extends Controller
{
    // 初始化
    protected function initialize()
    {
        parent::initialize();
        listen('OpeartorSecurity', 'app\event\OpeartorSecurity');
    }

    /**
     * 登出
     *
     * @return void
     */
    public function logout()
    {
        if ($this->request->isAjax()) {
            $this->logger('logs.user.logout', 'LOGOUT', $this->request->user);
            redis()->delete("usr.{$this->request->user['SESSION_ID']}");
            cookie('token', null);
            return $this->success();
        }
    }

    /**
     * 修改密码
     *
     * @param UserModel $user_model
     * @return void
     */
    public function modity_pwd(UserModel $user_model)
    {
        if (IS_POST) {
            $data = array_keys_filter($this->request->post(), [
                ['password', ''],
                ['password1', '']
            ]);

            if (empty($data['password1'])) {
                return $this->error("INVALID PARAM");
            }

            $user = $user_model->find(S1);
            if (!password_check($user->password, $user->salt, $data['password'])) {
                return $this->error("当前密码输入不正确!");
            }

            $user->password = password_check($data['password1'], $user->salt);
            if ($user->save()) {
                $this->logger('logs.sys.user.edit', 'UPDATED', $user);
                event("OpeartorSecurity", $this->request->user);
            }

            return $this->success();
        } else {
            return $this->fetch('User/security');
        }
    }

    /**
     * 用户管理
     *
     * @param UserModel $user
     * @return void
     */
    public function lists(UserModel $user, UserGroup $group)
    {
        if ($this->request->isAjax()) {
            $filter = array_keys_filter($this->params, [ ['keyword', 0] ]);
            [$page, $limit] = $this->getPaginator();
            $data = $user->getList($page, $limit, $filter);
            return $this->success($data);
        } else {
            $userGroup = $group->getList();
            $this->assign('userGroup', $userGroup);
            return $this->fetch();
        }
    }

    /**
     * 修改用户状态
     *
     * @param UserModel $user_model
     * @return void
     */
    public function status_modify(UserModel $user_model)
    {
        if (IS_AJAX) {
            $id = (int)$this->request->get('id');
            $data = array_keys_filter($this->request->post(), [
                ['status', 0]
            ]);
            $data['status'] = (int)$data['status'];
            $data['user_id'] = $id;
            $user = $user_model->where('user_id', $id)->with(['group', 'attr'])->find();
            if (!$user) {
                return $this->error(lang('no exist'));
            } elseif (super($user->group->group_range)) {
                return $this->error(lang('no access'));
            }
            $user->update($data);
            $this->logger('logs.sys.user.status', 'UPDATED', $user);
            return $this->success();
        }
    }
    
    /**
     * 添加用户
     *
     * @param UserModel $user_model
     * @param UserGroup $group
     * @return void
     */
    public function add(UserModel $user_model, UserGroup $group)
    {
        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'parent_id',
                    'username',
                    'nickname',
                    'password',
                    'group_id',
                    ['avatar', ''],
                    ['realname', ''],
                    ['mobile', ''],
                    ['remark', '']
                ], true);
                $data['parent_id'] = (int)$data['parent_id'];
                $data['group_id'] = (int)$data['group_id'];
                $data['status'] = 1;
                $data['create_time'] = TIMESTAMP;
                $data['expire_time'] = TIMESTAMP + 63072000;
                if ($data['parent_id'] === 0 || $data['group_id'] === 0) {
                    return $this->error('INVALID PARAM');
                }
                $data['salt'] = rand_string();
                $data['password'] = password_check($data['password'], $data['salt']);
                $res = $user_model->addUser($data);
                return $this->success($res);
            } catch(\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $item = ['is_delete' => 1, 'parent_id' => S1, 'group' => []];
            $userGroup = $group->getList();
            $this->assign('userGroup', $userGroup);
            $this->assign('item', $item);
        }

        return $this->fetch('User/form');
    }

    /**
     * 编辑用户
     *
     * @param UserModel $user_model
     * @param UserGroup $group
     * @return void
     */
    public function edit(UserModel $user_model, UserGroup $group)
    {
        $id = (int)$this->request->param('id');
        $item = $user_model->getUser([['user_id', '=', $id]]);
        if (empty($item)) {
            return $this->error('用户不存在!');
        }

        if (IS_POST) {
            try {
                $data = array_keys_filter($this->params, [
                    'username',
                    'nickname',
                    'group_id',
                    ['password', ''],
                    ['avatar',   ''],
                    ['realname', ''],
                    ['mobile',   ''],
                    ['remark',   '']
                ], true);
                $data['update_time'] = TIMESTAMP;
                if (!empty($data['password'])) {
                    $data['salt'] = rand_string();
                    $data['password'] = password_check($data['password'], $data['salt']);
                } else {
                    unset($data['password']);
                }
                $res = $item->editUser($data);
                return $this->success($res);
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $userGroup = $group->getList();
            $this->assign('userGroup', $userGroup);
            $this->assign('item', $item);
        }

        return $this->fetch('User/form');
    }

    /**
     * 删除用户
     *
     * @param UserModel $user_model
     * @return void
     */
    public function delete(UserModel $user_model)
    {
        if (IS_POST) {
            $id = (int)$this->request->post('id');
            if ($id === S1) {
                return $this->error(lang('no access'));
            }

            $user = $user_model->getUser([['user_id', '=', $id]]);
            if (!$user) {
                return $this->error(lang('no exist'));
            }
            $user->delUser();

            return $this->success();
        }
    }
}