<?php
namespace app\controller\admin;
/**
 * 管理会员组
 * 
 * @version 1.0.0
 */
use app\model\system\UserGroup;
use app\model\system\UserGroupRole;
use app\model\system\UserRole;

class Group extends Controller
{
    /**
     * 管理组列表
     *
     * @return void
     */
    public function lists(UserGroup $user_group_model)
    {
        if (IS_AJAX) {
            $filter = array_keys_filter($this->request->param(), [
                ['keyword', 0]
            ]);
            [$page, $limit] = $this->getPaginator();
            $data = $user_group_model->getList($page, $limit, $filter);
            return $this->success($data);
        }

        return $this->fetch();
    }

    /**
     * 添加用户组
     *
     * @param UserGroup $user_group_model
     * @return void
     */
    public function add(UserGroup $user_group_model)
    {
        if (IS_POST) {
            $data = array_keys_filter($this->request->post(), [
                'group',
                'group_remark',
                ['group_range', 'platform.' . MODULE],
                ['user_id', S1],
                'roles'
            ]);

            if ($user_group_model->where('group', $data['group'])->find()) {
                return $this->fail('用户组已存在!');
            }
            /**
             * 主账户ID
             * 账户结构为二级，主账户下可开启用户组
             */
            $data['group_id'] = $user_group_model->insertGetId(array_keys_filter($data, [
                'user_id', 
                'group', 
                'group_range', 
                'group_remark'
            ]));

            if ($data['group_id']) {
                $roles = [];
                foreach($data['roles'] as $role_id) {
                    $roles[] = [
                        'role_id'  => $role_id,
                        'group_id' => $data['group_id']
                    ];
                }
    
                $this->app->make(UserGroupRole::class)->insertAll($roles);
            }
            $this->logger('logs.sys.group.create', 'CREATEED', $data);
            return $this->success($data);
        } else {
            $edit = [];
            $edit['internal'] = 0;
            $edit['user_id'] = S1;
            $userRoleModel = app()->make(UserRole::class);
            $userRole = $userRoleModel->getList(1, 9999, ['internal' => 0]);
            $this->assign('userRole', $userRole['list']);
            $this->assign('edit', $edit);
        }

        return $this->fetch('Group/form');
    }

    /**
     * 编辑用户组
     *
     * @param UserGroup $user_group_model
     * @param UserGroupRole $user_group_role_model
     * @return void
     */
    public function edit(UserGroup $user_group_model, UserGroupRole $user_group_role_model)
    {
        $id = (int)$this->request->param('id');
        $edit = $user_group_model->with(['roles'])->find($id);
        if (empty($edit)) {
            return $this->fail('用户组不存在!');
        }

        if (IS_POST) {
            $data = array_keys_filter($this->request->post(), [
                'group_remark',
                'roles'
            ]);
            $edit->save(array_keys_filter($data, [
                'group_remark'
            ]));
            $user_group_role_model->where('group_id', $id)->delete();
            if (!super($edit->group_range)) {
                $roles = [];
                foreach($data['roles'] as $role_id) {
                    $roles[] = [
                        'role_id'  => $role_id,
                        'group_id' => $id
                    ];
                }
                $user_group_role_model->insertAll($roles);
            }
            $this->logger('logs.sys.group.update', 'UPDATED', $edit);
            return $this->success();
        } else {
            $edit['access'] = array_values(array_column($edit->roles->toArray(), 'role_id'));
            $condition = [];
            if ($edit['user_id'] != 0) {
                $condition['internal'] = 0;
            } else {
                $condition['internal'] = 1;
            }

            $userRoleModel = app()->make(UserRole::class);
            $userRole = $userRoleModel->getList(1, 9999, $condition);
            $this->assign('userRole', $userRole['list']);
            $this->assign('edit', $edit);
        }
        
        return $this->fetch('Group/form');
    }

    /**
     * 删除用户组
     *
     * @param UserGroup $user_group_model
     * @return void
     */
    public function delete(UserGroup $user_group_model)
    {
        $id = (int)$this->request->param('id');
        $edit = $user_group_model->with(['roles'])->find($id);
        if (empty($edit)) {
            return $this->fail('用户组不存在!');
        }
        $this->logger('logs.sys.group.delete', 'DELETE', $edit);
        $edit->together(['roles'])->delete();
        return $this->success();
    }
}