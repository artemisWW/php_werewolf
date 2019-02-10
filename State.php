<?php
namespace JINRO_JOSEKI;

require_once('common/data/Role.php');
require_once('Parser.php');
require_once('ScoreSet.php');

use JINRO_JOSEKI;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Species;

class State
{
    /** @var int[] size of each role */
    private $role_size_list = [];
    /** @var int size of user */
    private $user_size = 0;
    /** @var Parser */
    private $parser = null;
    /** @var ScoreSet */
    private $score_set = null;
    /** @var string[]  for debug */
    private $action_list = [];

    public function __construct(array $role_num_map)
    {
        $user_size = 0;
        foreach ($role_num_map as $role_id => $role_size) {
            if ($role_size === 0) {
                continue;
            }
            $this->role_size_list[$role_id] = $role_size;
            $user_size += $role_size;
        }
        $this->user_size = $user_size;
    }
    public function init()
    {
        // parserの初期化
        $this->parser = new Parser($this->user_size);
        // score_setの初期化
        $this->score_set = new ScoreSet($this->role_size_list);
        // action_listの初期化
        $this->action_list = [];
    }
    /**
     * uidがstrの行動を実行
     * @param int $uid
     * @param string $str
     * @return bool
     */
    public function action(int $uid, string $str)
    {
        $term_list = $this->parser->parse($str);
        if ($term_list === false) {
            return false;
        }
        $aid = array_shift($term_list);
        return $this->action_id($uid, $aid, $term_list);
    }
    /**
     * uidがstrの行動を実行
     * @param int $uid
     * @param string $str
     * @return bool
     */
    public function action_id(int $uid, int $aid, array $list = [])
    {
        $this->setActionList($uid, $aid, $list);
        return $this->score_set->action_id($uid, $aid, $list);
    }
    /**
     * 行動を追加
     */
    private function setActionList(int $uid, int $aid, array $list = []) {
        $this->action_list[] = [$uid, $aid, $list];
    }
    /**
     * 過去の行動を取得
     */
    public function getActionList()
    {
        return $this->action_list;
    }
    /**
     * score_set をコピー
     * @return ScoreSet
     */
    public function copyScoreSet()
    {
        return clone $this->score_set;
    }
    public function getScoreSet()
    {
        return $this->score_set;
    }
    /**
     * 全スコアを表示
     */
    public function printAllScore()
    {
        $this->score_set->printAllScore();
    }
}
