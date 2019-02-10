<?php
namespace JINRO_JOSEKI;

require_once('common/net/GameInfoCommon.php');
require_once('common/data/Role.php');
require_once('common/lib/Content.php');
require_once('ScoreSet.php');
require_once('LogJapanese.php');
require_once('Debugger.php');

use JINRO_JOSEKI;
use JINRO_JOSEKI\Common\Net\GameInfoCommon;
use JINRO_JOSEKI\LogJapanese;
use JINRO_JOSEKI\Debugger;
use JINRO_JOSEKI\ScoreSet;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;
use JINRO_JOSEKI\Common\Data\Vote;
use JINRO_JOSEKI\Common\Lib\Content;
use JINRO_JOSEKI\Client\Lib\Topic;

class CoManager
{
    private $day = 0;
    private $role_id_list = [];      // [agent_id]=role_id (optional)
    private $role_size_map = [];     // [role_id]=role_size (required)
    private $co_role_id_list = [];   // [agent_id]=co_role_id
    private $divined_map = [];       // [agent_id][day][target_id]=species_id
    private $identified_map = [];    // [agent_id][day][target_id]=species_id
    private $guarded_map = [];       // [agent_id][day][target_id]=result
    private $dead_id_list = [];      // [day] = agent_id[]
    private $attacked_id_list = [];  // [day] = agent_id
    private $executed_id_list = [];  // [day] = agent_id

    public function __construct()
    {
    }
    public function setRoleIdList(array $role_id_list)
    {
        $this->role_id_list = $role_id_list;
        // role_size_mapの作成
        $role_size_map = [];
        foreach ($this->role_id_list as $agent_id => $role_id) {
            if (array_key_exists($role_id, $role_size_map)) {
                $role_size_map[$role_id]++;
            } else {
                $role_size_map[$role_id] = 1;
            }
        }
        ksort($role_size_map);
        $this->setRoleSizeMap($role_size_map);
    }
    public function setRoleSizeMap(array $role_size_map)
    {
        $this->role_size_map = $role_size_map;
        $this->init();
    }
    /** 変数の初期化 */
    private function init()
    {
        $agent_size = 0;
        foreach ($this->role_size_map as $role_id => $role_size) {
            $agent_size += $role_size;
        }
        $this->co_role_id_list = array_fill(0, $agent_size, Role::UNDEF);
    }
    // day
    public function getDay()
    {
        return $this->day;
    }
    public function setDay(int $day)
    {
        $this->day = $day;
    }
    // co_role_id_list
    public function getCoRoleIdList()
    {
        return $this->co_role_id_list;
    }
    public function getCoRoleId(int $agent_id)
    {
        return $this->co_role_id_list[$agent_id];
    }
    public function setCoRoleId(int $agent_id, int $role_id)
    {
        $this->co_role_id_list[$agent_id] = $role_id;
    }
    // divined_map
    public function getDivinedMap(int $agent_id)
    {
        if (\array_key_exists($agent_id, $this->divined_map)) {
            return $this->divined_map[$agent_id];
        } else {
            return [];
        }
    }
    public function setDivinedMap(int $agent_id, int $target_id, int $species_id)
    {
        $this->divined_map[$agent_id][$this->day][$target_id] = $species_id;
    }
    // identified_map
    public function getIdentifiedMap(int $agent_id)
    {
        if (\array_key_exists($agent_id, $this->identified_map)) {
            return $this->identified_map[$agent_id];
        } else {
            return [];
        }
    }
    public function setIdentifiedMap(int $agent_id, int $target_id, int $species_id)
    {
        $this->identified_map[$agent_id][$this->day][$target_id] = $species_id;
    }
    // guarded_map
    public function getGuardedMap(int $agent_id)
    {
        if (\array_key_exists($agent_id, $this->guarded_map)) {
            return $this->guarded_map[$agent_id];
        } else {
            return [];
        }
    }
    public function setGuardedMap(int $agent_id, int $target_id, bool $result)
    {
        $this->guarded_map[$agent_id][$this->day][$target_id] = $result;
    }
    // dead_id_list
    public function getDeadIdList()
    {
        return $this->dead_id_list;
    }
    public function setDeadId(int $agent_id)
    {
        $this->dead_id_list[$this->day][] = $agent_id;
    }
    // attacked_id_list
    public function getAttackedIdList()
    {
        return $this->attacked_id_list;
    }
    public function setAttackedId(int $agent_id)
    {
        $this->attacked_id_list[$this->day] = $agent_id;
        $this->setDeadId($agent_id);
    }
    // executed_id_list
    public function getExecutedIdList()
    {
        return $this->executed_id_list;
    }
    public function setExecutedId(int $agent_id)
    {
        $this->executed_id_list[$this->day] = $agent_id;
        // $this->setDeadId($agent_id); deadには含めない
    }

    // print
    public function printCoAll()
    {
        $co_agent_id_list = $this->getCoAgentIdMatrix();
        foreach ($co_agent_id_list as $co_role_id => $agent_id_list) {
            foreach ($agent_id_list as $agent_id) {
                print 'CO(' . Role::toJapaneseName($co_role_id) . ')';
                print ' ' . $this->getAgentStr($agent_id) . ':';
                if ($co_role_id === Role::SEER) {
                    print $this->getJudgedStr($this->getDivinedMap($agent_id));
                } elseif ($co_role_id === Role::MEDIUM) {
                    print $this->getJudgedStr($this->getIdentifiedMap($agent_id));
                } elseif ($co_role_id === Role::BODYGUARD) {
                    print $this->getJudgedStr($this->getGuardedMap($agent_id));
                }
                print "\n";
            }
        }
    }
    public function printAllCompact()
    {
        // 吊
        $executed_list = array_fill(0, count($this->co_role_id_list), '－');
        foreach ($this->executed_id_list as $day => $agent_id) {
            $executed_list[$agent_id] = $this->getBlackNum($day);
        }
        // 噛
        $attacked_list = array_fill(0, count($this->co_role_id_list), '－');
        foreach ($this->attacked_id_list as $day => $agent_id) {
            $attacked_list[$agent_id] = $this->getBlackNum($day);
        }
        // CO
        $co_role_name_list = [];
        $seer_list = [];
        $medium_list = [];
        foreach ($this->co_role_id_list as $agent_id => $role_id) {
            $co_role_name_list[] = Role::toJapaneseName($role_id);
            if ($role_id === Role::SEER) {
                $seer_list[] = $agent_id;
            }
            if ($role_id === Role::MEDIUM) {
                $medium_list[] = $agent_id;
            }
        }
        print '吊    :' . implode('', $executed_list) . "\n";
        print '噛    :' . implode('', $attacked_list) . "\n";
        print 'CO    :' . implode('', $co_role_name_list) . "\n";
        // divined
        foreach ($seer_list as $agent_id) {
            $divined_map = $this->getDivinedMap($agent_id);
            $divined_list = $this->getSpeciedList($divined_map);
            print '占' . sprintf("%02d", $agent_id) . '  :' . implode('', $divined_list) . "\n";
        }
        // identified
        foreach ($medium_list as $agent_id) {
            $identified_map = $this->getIdentifiedMap($agent_id);
            $identified_list = $this->getSpeciedList($identified_map);
            print '霊' . sprintf("%02d", $agent_id) . '  :' . implode('', $identified_list) . "\n";
        }
    }
    private function getSpeciedList(array $map)
    {
        $list = \array_fill(0, count($this->co_role_id_list), '－');
        foreach ($map as $day => $data) {
            foreach ($data as $target_id => $species_id) {
                if ($species_id === Species::WEREWOLF) {
                    $list[$target_id] = $this->getBlackNum($day);
                } else {
                    $list[$target_id] = $this->getWhiteNum($day);
                }
            }
        }
        return $list;
    }
    private function getBlackNum(int $num)
    {
        $black_num = [
            '⓿','❶','❷','❸','❹','❺','❻','❼','❽','❾',
            '❿','⓫','⓬','⓭','⓮','⓯','⓰','⓱','⓲','⓳',
        ];
        if ($num < 0 || $num > 19) {
            return '●';
        } else {
            return $black_num[$num];
        }
    }
    private function getWhiteNum(int $num)
    {
        $white_num = [
            '⓪','①','②','③','④','⑤','⑥','⑦','⑧','⑨',
            '⑩','⑪','⑫','⑬','⑭','⑮','⑯','⑰','⑱','⑲'
        ];
        if ($num < 0 || $num > 19) {
            return '〇';
        } else {
            return $white_num[$num];
        }
    }
    /** role_id別のagent_idの配列を取得 */
    public function getCoAgentIdMatrix()
    {
        $co_agent_id_list = [];  // role_id別のagent_id
        foreach ($this->co_role_id_list as $agent_id => $co_role_id) {
            if ($co_role_id === Role::UNDEF) {
                continue;
            }
            $co_agent_id_list[$co_role_id][] = $agent_id;
        }
        ksort($co_agent_id_list);
        return $co_agent_id_list;
    }
    public function getCoAgentIdList(int $co_role_id)
    {
        $co_agent_id_list = $this->getCoAgentIdMatrix();
        if (!array_key_exists($co_role_id, $co_agent_id_list)) {
            return [];
        }
        return $co_agent_id_list[$co_role_id];
    }
    private function getAgentStr(int $agent_id)
    {
        $role_id = $this->getRoleId($agent_id);
        if ($role_id === Role::UNDEF) {
            return sprintf("%02d", $agent_id);
        } else {
            return sprintf("%02d", $agent_id) . '(' . Role::toJapaneseName($role_id) . ')';
        }
    }
    private function getAgentSpaceStr()
    {
        if ($this->role_id_list === null) {
            return '  ';
        } else {
            return '      ';
        }
    }
    private function getBlackWhiteStr(int $species_id)
    {
        $black_white_str = '白';
        if ($species_id === Species::WEREWOLF) {
            $black_white_str = '黒';
        }
        return $black_white_str;
    }
    /**
     * @param array $judged_map  [day][target_id]=species_id
     */
    private function getJudgedStr(array $judged_map)
    {
        $judged_str = '';
        for ($day = 1; $day <= $this->day; $day++) {
            $judged_str .= ' ';
            if (!\array_key_exists($day, $judged_map)) {
                // 空白の追加 + 黒or白 分の空白
                $judged_str .= $this->getAgentSpaceStr();
                $judged_str .= '  ';
            } else {
                // 1日に1つしかないはずだが foreach で処理する
                foreach ($judged_map[$day] as $target_id => $species_id) {
                    $judged_str .= $this->getAgentStr($target_id);
                    $judged_str .= $this->getBlackWhiteStr($species_id);
                }
            }
        }
        return $judged_str;
    }
    private function getRoleId(int $agent_id)
    {
        if ($this->role_id_list === null) {
            return Role::UNDEF;
        }
        if (!array_key_exists($agent_id, $this->role_id_list)) {
            return Role::UNDEF;
        }
        return $this->role_id_list[$agent_id];
    }
}
