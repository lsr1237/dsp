<?php

namespace backend\controllers;

use backend\models\LoginLogModel;
use Yii;
use yii\db\Exception as DBException;
use yii\base\Exception as BaseException;
use yii\helpers\Json;
use backend\models\Admin;
use backend\models\AdminModel;
use backend\bases\BackendController;

class AdminController extends BackendController
{
    /**
     * 管理员列表
     * @return string
     */
    public function actionIndex()
    {
        $data = [];
        $request = Yii::$app->request;
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 20);
        $state = $request->get('state', '');
        $username = $request->get('username', '');
        $realName = $request->get('real_name', '');
        $result = AdminModel::getList($state, $realName, $username, $offset, $limit);
        foreach ($result['list'] as $row) {
            $data[] = [
                'id' => $row->id,
                'username' => $row->username,
                'real_name' => $row->real_name,
                'state' => $row->state,
                'role' => isset($row->role) ? $row->role->item_name : '',
                'login_time' => $row->login_time,
            ];
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'count' => (int)$result['count'],
            'results' => $data
        ]);
    }

    /**
     * 添加管理员
     * @return string
     */
    public function actionAdd()
    {
        try {
            $request = Yii::$app->request;
            $username = trim($request->post('username'));
            if (empty($username)) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入用户名']);
            }
            $password = trim($request->post('password'));
            if (empty($password)) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入密码']);
            }
            $realName = trim($request->post('real_name'));
            if (empty($realName)) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入真实姓名']);
            }
            //判断用户名是否已经存在
            $checkExist = AdminModel::findOneByCond(['username' => $username]);
            if ($checkExist) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '用户名已存在']);
            }
            $auth = Yii::$app->authManager;
            $role = $auth->getRole($request->post('role'));
            if (!$role) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请选择角色']);
            }
            $data = [
                'username' => $username,
                'real_name' => $realName,
                'password' => Yii::$app->getSecurity()->generatePasswordHash($password),
            ];
            $transaction = Yii::$app->db->beginTransaction();
            $admin = AdminModel::add($data);
            if (!$admin) {
                $transaction->rollBack();
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '保存失败']);
            }
            if (!$auth->assign($role, $admin->id)) {
                $transaction->rollBack();
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '分配角色失败']);
            }
            $transaction->commit();
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
        } catch (DBException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (BaseException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $e->getMessage()]);
        }
    }

    /**
     * 更新管理员信息
     * @return string
     */
    public function actionUpdate()
    {
        try {
            $request = Yii::$app->request;
            $id = $request->get('id');
            $admin = Admin::findOne(['id' => $id]);
            if (!$admin) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '参数错误']);
            }
            $username = trim($request->post('username'));
            if (empty($username)) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入用户名']);
            }
            $realName = trim($request->post('real_name'));
            if (empty($realName)) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请输入真实姓名']);
            }
            //判断用户名是否已经存在
            $checkExist = AdminModel::findOneByCond([
                'and',
                ['username' => $username],
                ['<>', 'id', $id],
            ]);
            if ($checkExist) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '用户名已存在']);
            }
            $auth = Yii::$app->authManager;
            $role = $auth->getRole($request->post('role'));
            if (!$role) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '请选择角色']);
            }
            $data = [
                'username' => $username,
                'real_name' => $realName,
            ];
            $transaction = Yii::$app->db->beginTransaction();
            $admin = AdminModel::update($id, $data);
            if (!$admin) {
                $transaction->rollBack();
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '保存失败']);
            }
            $auth->revokeAll($admin->id);
            if (!$auth->assign($role, $admin->id)) {
                $transaction->rollBack();
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '分配角色失败']);
            }
            $transaction->commit();
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
        } catch (DBException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (BaseException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $e->getMessage()]);
        }
    }

    /**
     * 管理员详情
     * @return string
     */
    public function actionDetail()
    {
        try {
            $request = Yii::$app->request;
            $id = (int)$request->get('id');
            $admin = AdminModel::findDetailById($id);
            if (!$admin) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '参数错误']);
            }
            return Json::encode([
                'status' => self::STATUS_SUCCESS,
                'error_message' => '',
                'results' => $admin
            ]);
        } catch (DBException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (BaseException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $e->getMessage()]);
        }
    }

    /**
     * 设置管理员离职
     * @return string
     */
    public function actionSetLeave()
    {
        try {
            $request = Yii::$app->request;
            $id = (int)$request->post('admin_id');
            if (!$id) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '参数错误']);
            }
            $admin = AdminModel::findOneByCond(['id' => $id]);
            if (!$admin) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '未查询到管理员信息']);
            }
            $ret = AdminModel::update($id, [
                'state' => AdminModel::RESIGNED,
            ]);
            if (!$ret) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '设置失败']);
            }
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
        } catch (DBException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (BaseException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $e->getMessage()]);
        }
    }

    /**
     * 修改密码
     * @return string
     */
    public function actionSetPassword()
    {
        try {
            $request = Yii::$app->request;
            $adminId = (int)$request->post('id', '');
            $password = trim($request->post('password', ''));
            if (empty($adminId) || empty($password)) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '参数错误']);
            }
            $admin = AdminModel::findOneByCond(['id' => $adminId]);
            if (!$admin) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '未查询到管理员信息']);
            }
            $data = [
                'password' => Yii::$app->getSecurity()->generatePasswordHash($password),
            ];
            $ret = AdminModel::update($adminId, $data);
            if (!$ret) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '设置失败']);
            }
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
        } catch (DBException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (BaseException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $e->getMessage()]);
        }
    }

    /**
     * 管理员解锁
     * @return string
     */
    public function actionSetUnlock()
    {
        try {
            $request = Yii::$app->request;
            $id = (int)$request->post('admin_id');
            if (!$id) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '参数错误']);
            }
            $admin = AdminModel::findOneByCond(['id' => $id]);
            if (!$admin) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '未查询到管理员信息']);
            }
            if ($admin->state !== AdminModel::LOCK) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '当前状态无需解锁']);
            }
            $ret = AdminModel::update($id, [
                'state' => AdminModel::SIGNED,
                'err_at' => null,
                'err_cnt' => 0,
            ]);
            if (!$ret) {
                return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '设置失败']);
            }
            return Json::encode(['status' => self::STATUS_SUCCESS, 'error_message' => '']);
        } catch (DBException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (BaseException $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => '服务器错误，请联系管理员']);
        } catch (\Exception $e) {
            Yii::error($e->getMessage());
            return Json::encode(['status' => self::STATUS_FAILURE, 'error_message' => $e->getMessage()]);
        }
    }

    /**
     * 当前登录信息
     * @return string
     */
    public function actionBasic()
    {
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'error_message' => '',
            'results' => [['name' => Yii::$app->user->identity->username]]
        ]);
    }

    /**
     * 登入日志
     * @return string
     */
    public function actionLoginLog()
    {
        $data = [];
        $request = Yii::$app->request;
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 20);
        $username = trim($request->get('username', ''));
        $user = Yii::$app->user->identity;
        if ($user->username != self::ARROW_ACCESS_USER) {
            $cond = ['<>', 'username', self::ARROW_ACCESS_USER];
        }
        $result = LoginLogModel::getList($username, $offset, $limit, $cond ?? []);
        foreach ($result['list'] as $row) {
            $data[] = [
                'id' => $row->id,
                'username' => $row->admin->username ?? '',
                'login_ip' => $row->login_ip,
                'login_time' => $row->login_time,
            ];
        }
        return Json::encode([
            'status' => self::STATUS_SUCCESS,
            'count' => (int)$result['count'],
            'results' => $data
        ]);
    }
}
