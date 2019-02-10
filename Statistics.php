<?php
require_once('common/net/GameInfo.php');
require_once('common/net/GameInfo.php');
require_once('Game.php');

use JINRO_JOSEKI\Game;
use JINRO_JOSEKI\Common\Net\GameSetting;
use JINRO_JOSEKI\Common\Net\GameInfoCommon;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Agent;

class Statistics
{
    private $game_setting = null;
    private $game_info_common = null;
    //
    private $agent_size = 0;
    private $role_size_map = [];
    //
    private $win_matrix = [];
    //
    private $log_prefix = '';
    private $log_mode = '';

    public function __construct(array $role_id_list, array $agent_list, string $log_prefix = '')
    {
        $this->game_setting = new GameSetting();
        $this->game_setting->setRoleIdList($role_id_list);
        $this->log_prefix = $log_prefix;
        $this->game_info_common = new GameInfoCommon($this->game_setting);
        $this->game_info_common->setAgentList($agent_list);

        $this->agent_size = count($agent_list);
        $this->role_size_map = $this->game_setting->getRoleNumMap();
        foreach ($agent_list as $agent_id => $agent) {
            foreach ($this->role_size_map as $role_id => $role_size) {
                if ($role_size === 0) {
                    continue;
                }
                $this->win_matrix[$agent_id][$role_id] = 0;
            }
        }
    }
    public function loop(int $max)
    {
        for ($ii = 0; $ii < $max; $ii++) {
            $logfile = $this->getLogFileName($ii);
            $game = new Game($this->game_setting, $this->game_info_common, $logfile);
            $win_team_id = $game->exec();
            //
            for ($agent_id = 0; $agent_id < $this->agent_size; $agent_id++) {
                $role_id = $this->game_info_common->getRoleId($agent_id);
                if (Role::getTeamId($role_id) === $win_team_id) {
                    $this->win_matrix[$agent_id][$role_id]++;
                }
            }
        }
        $this->print_win_matrix();
    }
    private function print_win_matrix()
    {
        $agent_sum = array_fill(0, $this->agent_size, 0);
        $role_sum = [];

        print '  ';
        foreach ($this->role_size_map as $role_id => $role_size) {
            if ($role_size === 0) {
                continue;
            }
            $role_sum[$role_id] = 0;
            $role_name = Role::toJapaneseName($role_id);
            print '  ' . $role_name;
        }
        print '  ' . '計' . "\n";

        for ($agent_id = 0; $agent_id < $this->agent_size; $agent_id++) {
            printf("%02d", $agent_id);
            foreach ($this->role_size_map as $role_id => $role_size) {
                if ($role_size === 0) {
                    continue;
                }
                printf(" %03d", $this->win_matrix[$agent_id][$role_id]);
                $agent_sum[$agent_id] += $this->win_matrix[$agent_id][$role_id];
                $role_sum[$role_id] += $this->win_matrix[$agent_id][$role_id];
            }
            printf(" %03d", $agent_sum[$agent_id]);
            print "\n";
        }

        print '計';
        foreach ($this->role_size_map as $role_id => $role_size) {
            if ($role_size === 0) {
                continue;
            }
            printf(" %03d", $role_sum[$role_id]);
        }
        print "\n";
    }
    private function getLogFileName(int $ii)
    {
        if ($this->log_prefix === '') {
            return '';
        } else {
            return $this->log_prefix . sprintf("%04d", $ii) . '.log';
        }
    }
}
/*
$stat = new Statistics([
    Role::VILLAGER,
    Role::VILLAGER,
    Role::WEREWOLF,
    Role::SEER,
    Role::POSSESSED,
],[
    new Agent('hoge0'),
    new Agent('hoge1'),
    new Agent('hoge2'),
    new Agent('hoge3'),
    new Agent('hoge4'),
]
,'log/村村狼占狂' . date("YmdHis") . '_'
);
*/
$stat = new Statistics([
    Role::VILLAGER,
    Role::VILLAGER,
//    Role::VILLAGER,
    Role::WEREWOLF,
    Role::WEREWOLF,
    Role::SEER,
    Role::MEDIUM,
    Role::BODYGUARD,
    Role::POSSESSED,
],[
    new Agent('hoge0'),
    new Agent('hoge1'),
    new Agent('hoge2'),
    new Agent('hoge3'),
    new Agent('hoge4'),
    new Agent('hoge5'),
    new Agent('hoge6'),
    new Agent('hoge7'),
//    new Agent('hoge8'),
]
,'log/村村村狼狼占霊騎狂' . date("YmdHis") . '_'
);
$stat->loop(1);
