<?php
namespace JINRO_JOSEKI;

class Score
{
    private $user_size = 0;
    private $role_size_list = [];
    private $score = [];
    private $bool_list = [];
    
    private $diff_user = [];
    private $diff_role = [];

    public function __construct($role_size_list)
    {
        $this->role_size_list = $role_size_list;
        // for user_size
        $this->user_size = 0;
        foreach ($role_size_list as $role_id => $role_size) {
            $this->user_size += $role_size;
        }
        // calc init_value
        $init_value = 1.0 / $this->user_size;
        $this->score = [];
        $this->bool_list = [];
        for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
            foreach ($role_size_list as $role_id => $role_size) {
                $this->score[$user_id][$role_id] = $init_value * $role_size;
                $this->bool_list[$user_id] = -1;
            }
        }
    }
    public function setBool(int $user_id, int $val)
    {
        $this->bool_list[$user_id] = $val;
    }
    public function getBool(int $user_id)
    {
        return $this->bool_list[$user_id];
    }
    public function getBoolList()
    {
        return $this->bool_list;
    }
    public function getScore()
    {
        return $this->score;
    }
    public function print()
    {
        for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
            $bool_str = $this->bool_list[$user_id];
            if ($this->bool_list[$user_id] === -1) {
                $bool_str = '-';
            }
            printf("%02d", $user_id);
            print '[' . $bool_str . '] ';
            foreach ($this->score[$user_id] as $val) {
                printf("%.3f ", $val);
            }
            print "\n";
        }
        print "\n";
    }
    public function setZero(int $uid, array $rid_list)
    {
        // validate
        if (!$this->validate($uid, $rid_list)) {
            return false;
        }
        // 初期化
        $user_id_list = $this->initUser();
        $role_id_list = $this->initRole();
        if (!\in_array($uid, $user_id_list, true)) {
            return true;
        }
//$this->print();
        // 該当ユーザーの該当役職の値を書き込み
        foreach ($rid_list as $rid) {
            $this->diff_role[$rid] = $this->score[$uid][$rid];
            $this->diff_user[$uid] += $this->diff_role[$rid];
            $this->score[$uid][$rid] = 0.0;
        }
        while (count($user_id_list) > 0 && count($role_id_list) > 0) {
//$this->print();
            list($min_user_idx, $min_user_id, $min_user_size) = $this->getMinUser($user_id_list, $role_id_list);
            list($min_role_idx, $min_role_id, $min_role_size) = $this->getMinRole($user_id_list, $role_id_list);
            if ($min_role_size < $min_user_size) {
//print "role_id = " . $min_role_id . ", diff = " . $this->diff_role[$min_role_id] . "\n";
                $this->setUserVal($user_id_list, $min_role_id, $this->diff_role[$min_role_id]);
                array_splice($role_id_list, $min_role_idx, 1);
                unset($this->diff_role[$min_role_id]);
            } else {
//print "user_id = " . $min_user_id . ", diff = " . $this->diff_user[$min_user_id] . "\n";
                $this->setRoleVal($min_user_id, $role_id_list, $this->diff_user[$min_user_id]);
                array_splice($user_id_list, $min_user_idx, 1);
                unset($this->diff_user[$min_user_id]);
            }
        }
//$this->print();
        return true;
    }
    private function initUser()
    {
        $this->diff_user = [];
        $user_id_list = [];
        for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
            // すべての職種の値が 0 or 1 のとき、そのユーザーは対象から除く
            $is_boundary = true;
            foreach ($this->role_size_list as $role_id => $role_size) {
                if ($this->score[$user_id][$role_id] !== 0.0 && $this->score[$user_id][$role_id] !== 1.0) {
                    $is_boundary = false;
                    break;
                }
            }
            if (!$is_boundary) {
                $user_id_list[] = $user_id;
                $this->diff_user[$user_id] = 0.0;
            }
        }
        return $user_id_list;
    }
    private function initRole()
    {
        $this->diff_role = [];
        $role_id_list = [];
        foreach ($this->role_size_list as $role_id => $role_size) {
            // すべてのユーザーの値が 0 or 1 のとき、その職種は対象から除く
            $is_boundary = true;
            for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
                if ($this->score[$user_id][$role_id] !== 0.0 && $this->score[$user_id][$role_id] !== 1.0) {
                    $is_boundary = false;
                    break;
                }
            }
            if (!$is_boundary) {
                $role_id_list[] = $role_id;
                $this->diff_role[$role_id] = 0.0;
            }
        }
        return $role_id_list;
    }
    private function validate(int $uid, array $rid_list)
    {
        // 現在の該当ユーザー該当役職の値を保持
        $target_val_list = [];
        foreach ($rid_list as $rid) {
            $target_val_list[$rid] = $this->score[$uid][$rid];
        }
        // validate for target_value
        // セットしようとしていた位置の値がすべて 0 のときはそのまま
        $all_flag = true;
        foreach ($target_val_list as $target_val) {
            if ($target_val !== 0.0) {
                $all_flag = false;
                break;
            }
        }
        if ($all_flag) {
            return true;
        }
        // セットしようとしていた位置の値が1つでも 1 のときは矛盾
        foreach ($target_val_list as $target_val) {
            if ($target_val === 1.0) {
                return false;
            }
        }
        // validate for target_user
        // 該当ユーザーの値で 0 or 1 ではない値がないかを確認
        $not_boundary_role_list = [];  // 0 or 1 ではないrole_id
        foreach ($this->role_size_list as $role_id => $role_size) {
            if (array_key_exists($role_id, $target_val_list)) {
                continue;
            }
            if ($this->score[$uid][$role_id] === 0.0 || $this->score[$uid][$role_id] === 1.0) {
                continue;
            }
            $not_boundary_role_list[] = $role_id;
        }
        // 0 or 1 しかないときは矛盾
        $not_boundary_role_size = count($not_boundary_role_list);
        if ($not_boundary_role_size === 0) {
            return false;
        }
        // validate for target role
        // 該当役職の値で o or 1 ではない値がないかを確認
        $not_boundary_user_list = [];  // 0 or 1 ではないuser_id
        for ($user_id = 0; $user_id < $this->user_size; $user_id++) {
            if ($user_id === $uid) {
                continue;
            }
            // 合計が 0 or 1 のときはスキップ
            $sum = 0.0;
            foreach ($rid_list as $rid) {
                $sum += $this->score[$user_id][$rid];
            }
            if ($sum === 0.0 || $sum === 1.0) {
                continue;
            }
            $not_boundary_user_list[] = $user_id;
        }
        // 0 or 1 しかないときは矛盾
        $not_boundary_user_size = count($not_boundary_user_list);
        if ($not_boundary_user_size === 0) {
            return false;
        }
        return true;
    }
    /** 値が確定していないユーザーの中で、0 or 1 ではない職種の値の数が一番少ないユーザーを求める */
    private function getMinUser(array $user_id_list, array $role_id_list)
    {
        $size_list = [];
        foreach ($user_id_list as $user_idx => $user_id) {
            $size_list[$user_idx] = $this->getNotBoundaryRoleSize($user_id, $role_id_list);
        }
        asort($size_list);
        $min = reset($size_list);
        $min_idx = key($size_list);
        $min_id = $user_id_list[$min_idx];
        return [$min_idx, $min_id, $min];
    }
    /** 値が確定していない職種の中で、0 or 1 ではないユーザーの値の数が一番少ない職種を求める */
    private function getMinRole(array $user_id_list, array $role_id_list)
    {
        $size_list = [];
        foreach ($role_id_list as $role_idx => $role_id) {
            $size_list[$role_idx] = $this->getNotBoundaryUserSize($user_id_list, $role_id);
        }
        asort($size_list);
        $min = reset($size_list);
        $min_idx = key($size_list);
        $min_id = $role_id_list[$min_idx];
        return [$min_idx, $min_id, $min];
    }
    /** 指定職種で、ユーザーの値が 0 or 1 ではない数 */
    private function getNotBoundaryUserSize(array $user_id_list, int $rid)
    {
        $size = 0;
        $sum = 0.0;
        foreach ($user_id_list as $user_id) {
            if ($this->score[$user_id][$rid] === 0.0 || $this->score[$user_id][$rid] === 1.0) {
                continue;
            }
            $size++;
            $sum += $this->score[$user_id][$rid];
        }
        // すべてのユーザーを0.0にするときは優先する
        if (sprintf("%.5f", $sum) === sprintf("%.5f", 0.0 - $this->diff_role[$rid])) {
            return 0;
        }
        // すでに計算したユーザーが0.0のときは優先する
        if (sprintf("%.5f", $sum + $this->diff_role[$rid]) === sprintf("%.5f", $this->role_size_list[$rid])) {
            $size /= 2;  // 理由は不明だがバランスがよくなる
        }
        return $size;
    }
    /** 指定ユーザーで、職種の値が 0 or 1 ではない数 */
    private function getNotBoundaryRoleSize(int $uid, array $role_id_list)
    {
        $size = 0;
        $sum = 0.0;
        foreach ($role_id_list as $role_id) {
            if ($this->score[$uid][$role_id] === 0.0 || $this->score[$uid][$role_id] === 1.0) {
                continue;
            }
            $size += $this->role_size_list[$role_id];
            $sum += $this->score[$uid][$role_id];
        }
        if (sprintf("%.5f", $sum) === sprintf("%.5f", 0.0 - $this->diff_user[$uid])) {
            return 0;
        }
        if (sprintf("%.5f", $sum + $this->diff_user[$uid]) === sprintf("%.5f", 1.0)) {
            $size /= 2;
        }
        return $size;
    }
    private function setUserVal(array $user_id_list, int $rid, float $val)
    {
        $sum = 0;
        $not_boundary_user_list2 = [];
        foreach ($user_id_list as $user_id) {
            // 0 or 1 は除く
            if ($this->score[$user_id][$rid] === 0.0 || $this->score[$user_id][$rid] === 1.0) {
                continue;
            }
            $sum += $this->score[$user_id][$rid];
            $not_boundary_user_list2[] = $user_id;
        }
        foreach ($not_boundary_user_list2 as $user_id) {
            $diff_val = $val * $this->score[$user_id][$rid] / $sum;
            $this->score[$user_id][$rid] = $this->boundary($this->score[$user_id][$rid] + $diff_val);
            $this->diff_user[$user_id] = $this->boundary($this->diff_user[$user_id] - $diff_val);
        }
    }
    private function setRoleVal(int $uid, array $role_id_list, float $val)
    {
        $sum = 0;
        $not_boundary_role_list2 = [];
        foreach ($role_id_list as $role_id) {
            // 0 or 1 は除く
            if ($this->score[$uid][$role_id] === 0.0 || $this->score[$uid][$role_id] === 1.0) {
                continue;
            }
            $sum += $this->score[$uid][$role_id];
            $not_boundary_role_list2[] = $role_id;
        }
        foreach ($not_boundary_role_list2 as $role_id) {
            $diff_val = $val * $this->score[$uid][$role_id] / $sum;
            $this->score[$uid][$role_id] = $this->boundary($this->score[$uid][$role_id] + $diff_val);
            $this->diff_role[$role_id] = $this->boundary($this->diff_role[$role_id] - $diff_val);
        }
    }
    /** 0 or 1付近の値を丸める */
    private function boundary(float $val)
    {
        if (-0.000001 < $val && $val < 0.000001) {
            return 0.0;
        } elseif (0.999999 < $val && $val < 1.000001) {
            return 1.0;
        }
        return $val;
    }
}
