<?php
namespace JINRO_JOSEKI;

require_once('common/data/Role.php');
require_once('action.php');

use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;

class Parser
{
    private $user_size = 0;

    public function __construct(int $user_size)
    {
        $this->user_size = $user_size;
    }
    public function parse(string $str)
    {
        $word_list = explode(" ", $str);
        $verb = $word_list[0];
        if ($verb === 'ROLE') {
            if (count($word_list) !== 2) {
                return false;
            }
            $rid = $this->isRole($word_list[1]);
            if ($rid === false) {
                return false;
            }
            return [Action::ROLE, $rid];
        } elseif ($verb === 'COMINGOUT') {
            if (count($word_list) !== 2) {
                return false;
            }
            $rid = $this->isRole($word_list[1]);
            if ($rid === false) {
                return false;
            }
            return [Action::COMINGOUT, $rid];
        } elseif ($verb === 'DIVINED') {
            if (count($word_list) !== 3) {
                return false;
            }
            $uid = $this->isUser($word_list[1]);
            $bool = $this->isBool($word_list[2]);
            if ($uid === false || $bool === false) {
                return false;
            }
            return [Action::DIVINED, $uid, $bool];
        } elseif ($verb === 'IDENTIFIED') {
            if (count($word_list) !== 3) {
                return false;
            }
            $uid = $this->isUser($word_list[1]);
            $bool = $this->isBool($word_list[2]);
            if ($uid === false || $bool === false) {
                return false;
            }
            return [Action::IDENTIFIED, $uid, $bool];
        } elseif ($verb === 'GUARDED') {
            if (count($word_list) !== 2) {
                return false;
            }
            $uid = $this->isUser($word_list[1]);
            if ($uid === false) {
                return false;
            }
            return [Action::GUARDED, $uid];
        } elseif ($verb === 'VOTE') {
            if (count($word_list) !== 2) {
                return false;
            }
            $uid = $this->isUser($word_list[1]);
            if ($uid === false) {
                return false;
            }
            return [Action::VOTE, $uid];
        } elseif ($verb === 'VOTED') {
            if (count($word_list) !== 1) {
                return false;
            }
            return [Action::VOTED];
        } elseif ($verb === 'ATTACKED') {
            if (count($word_list) !== 1) {
                return false;
            }
            return [Action::ATTACKED];
        } elseif ($verb === 'JUDGED') {
            if (count($word_list) !== 2) {
                return false;
            }
            $bool = $this->isBool($word_list[1]);
            if ($bool === false) {
                return false;
            }
            return [Action::JUDGED, $bool];
        } else {
            return false;
        }
    }
    private function isUser(string $user_str)
    {
        $user_id = (int)$user_str;
        if ($user_id < 0 || $user_id >= $this->user_size) {
            return false;
        }
        return $user_id;
    }
    private function isRole(string $role_str)
    {
        $role_id = Role::toRoleIdJapanese($role_str);
        if ($role_id === Role::UNDEF) {
            return false;
        }
        return $role_id;
    }
    private function isBool(string $bool_str)
    {
        if ($bool_str === 'true' || $bool_str === 'TRUE' || $bool_str === '1') {
            return 1;
        }
        if ($bool_str === 'false' || $bool_str === 'FALSE' || $bool_str === '0') {
            return 0;
        }
        return false;
    }
}
