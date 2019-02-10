<?php
namespace JINRO_JOSEKI\Players;

require_once('State.php');
require_once('ScoreAverage.php');
require_once('Hypothesize.php');
require_once('DecideVote.php');
require_once('CoManager.php');
require_once('common/net/GameInfo.php');
require_once('common/net/GameSetting.php');
require_once('common/data/Talk.php');
require_once('common/lib/Content.php');
require_once('client/lib/Topic.php');

use JINRO_JOSEKI\Players;
use JINRO_JOSEKI\State;
use JINRO_JOSEKI\ScoreAverage;
use JINRO_JOSEKI\Action;
use JINRO_JOSEKI\DecideVote;
use JINRO_JOSEKI\CoManager;
use JINRO_JOSEKI\Common\Net\GameInfo;
use JINRO_JOSEKI\Common\Net\GameSetting;
use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Common\Data\Agent;
use JINRO_JOSEKI\Common\Data\Talk;
use JINRO_JOSEKI\Common\Data\Status;
use JINRO_JOSEKI\Common\Lib\Content;
use JINRO_JOSEKI\Client\Lib\Topic;

class Player
{
    private $name = 'hoge';
    protected $game_setting = null;
    protected $game_info = null;
    // day
    protected $day = 0;
    // for me
    protected $agent = null;
    protected $agent_id = 0;
    protected $role_id = 0;
    // role_num_map
    protected $role_num_map = [];
    // for talk
    protected $talk_list_head = 0;
    protected $whisper_list_head = 0;
    protected $talk_id = 0;
    // talk queue
    protected $talk_queue = [];
    protected $whisper_queue = [];
    // state
    protected $state = null;
    protected $state_talk = null;  // 会話中の行動のみ
    // co_manager
    protected $co_manager = null;

    public function getName()
    {
        return $this->name;
    }
    public function getAgentId()
    {
        return $this->agent_id;
    }
    public function getRoleId()
    {
        return $this->role_id;
    }
    public function getState()
    {
        return $this->state_talk;
    }
    /**
     * @param GameInfo $game_info
     * @param GameSetting $game_setting
     */
    public function initialize(GameInfo $game_info, GameSetting $game_setting)
    {
        $this->game_setting = $game_setting;
        $this->game_info = $game_info;
        // for me
        $this->agent = $game_info->getAgent();
        $this->agent_id = $game_info->getAgentId();
        $this->role_id = $game_info->getRoleId();
        // role_num_map
        $this->role_num_map = $game_setting->getRoleNumMap();
        // for state
        $this->state = new State($this->role_num_map);
        $this->state->init();
        $this->state_talk = new State($this->role_num_map);
        $this->state_talk->init();
        // for co_manager
        $this->co_manager = new CoManager();
        $this->co_manager->setRoleSizeMap($this->role_num_map);

        // 自分の役職をセット
        $this->state->action_id($this->agent_id, Action::ROLE, [$this->role_id]);
        // WEREWOLF or FREEMASON のときは味方もセット
        $agent_id_list = $this->game_info->getRoleMap();
        foreach ($agent_id_list as $agent_id) {
            $this->state->action_id($agent_id, Action::ROLE, [$this->role_id]);
        }
    }
    public function dayStart()
    {
        $this->day = $this->game_info->getDay();
        // for talk
        $this->talk_list_head = 0;
        $this->talk_id = 0;
        // 追放された人の処理(霊媒結果の取得含む)
        $this->execute();
        // 襲撃された人の処理(護衛結果の取得含む)(狐の処理は未対応)
        $this->dead();
        // 占い結果の取得(占い師のみ)
        // co_managerの日付はdayStartの処理後(昨日のことをco_managerにセット)
        $this->co_manager->setDay($this->day);
    }
    public function update(GameInfo $game_info)
    {
        $this->game_info = $game_info;
        // talk の処理
        $talk_list = $this->game_info->getTalkList();
        $talk_list_size = count($talk_list);
        for ($ii = $this->talk_list_head; $ii < $talk_list_size; $ii++) {
            $talk = $talk_list[$ii];
            $agent_id = $talk->getAgentId();
            $content = new Content(['text' => $talk->getText()]);
            $topic_id = $content->getTopicId();
            // talk内容別の処理
            if ($topic_id === Topic::COMINGOUT) {
                $this->comingout($agent_id, $content);
            } elseif ($topic_id === Topic::DIVINED) {
                $this->divined($agent_id, $content);
            } elseif ($topic_id === Topic::IDENTIFIED) {
                $this->identified($agent_id, $content);
            } elseif ($topic_id === Topic::GUARDED) {
                $this->guarded($agent_id, $content);
            } else {
                ;
            }
        }
        $this->talk_list_head = $talk_list_size;
    }
    private function comingout(int $agent_id, Content $content)
    {
        $role_id = $content->getRoleId();
        // comingoutは自分がtalkした内容も含める
        $this->state->action_id($agent_id, Action::COMINGOUT, [$role_id]);
        $this->state_talk->action_id($agent_id, Action::COMINGOUT, [$role_id]);
        // co_manager
        $this->co_manager->setCoRoleId($agent_id, $role_id);
    }
    private function divined(int $agent_id, Content $content)
    {
        $target_id = $content->getTargetId();
        $species_id = $content->getResultId();
        // 自分がtalkした内容は自分の状態には含めない
        if ($agent_id !== $this->agent_id) {
            $this->state->action_id($agent_id, Action::DIVINED, [$target_id, $species_id]);
        }
        $this->state_talk->action_id($agent_id, Action::DIVINED, [$target_id, $species_id]);
        // co_manager
        $this->co_manager->setDivinedMap($agent_id, $target_id, $species_id);
    }
    private function identified(int $agent_id, Content $content)
    {
        $target_id = $content->getTargetId();
        $species_id = $content->getResultId();
        // 自分がtalkした内容は自分の状態には含めない
        if ($agent_id !== $this->agent_id) {
            $this->state->action_id($agent_id, Action::IDENTIFIED, [$target_id, $species_id]);
        }
        $this->state_talk->action_id($agent_id, Action::IDENTIFIED, [$target_id, $species_id]);
        // co_manager
        $this->co_manager->setIdentifiedMap($agent_id, $target_id, $species_id);
    }
    private function guarded(int $agent, Content $content)
    {
        $target_id = $content->getTargetId();
        if ($agent_id !== $this->agent_id) {
            $this->state->action_id($agent_id, Action::GUARDED, [$target_id]);
        }
        $this->state_talk->action_id($agent_id, Action::GUARDED, [$target_id]);
        // co_manager
        $this->co_manager->setGuardedMap($agent_id, $target_id, true);
    }
    private function execute()
    {
        $executed_id = $this->game_info->getExecutedId();
        if ($executed_id === -1) {
            return;
        }
        // 人狼が1人のときは追放された人は人狼ではない
        if ($this->getRoleSize(Role::WEREWOLF) === 1) {
            $this->state->action_id($executed_id, Action::VOTED, []);
            $this->state_talk->action_id($executed_id, Action::VOTED, []);
        }
        // co_manager
        $this->co_manager->setExecutedId($executed_id);
    }
    private function dead()
    {
        $dead_id_list = $this->game_info->getLastDeadIdList();
        // 死亡処理(襲撃処理のみで狐の処理は未実装)
        foreach ($dead_id_list as $agent_id) {
            $this->state->action_id($agent_id, Action::ATTACKED);
            $this->state_talk->action_id($agent_id, Action::ATTACKED);
            // co_manager
            $this->co_manager->setDeadId($agent_id);
        }
    }

    public function vote()
    {
        $candidate_list = $this->getCandidateList();
        $decide_vote = new DecideVote($this->agent_id, $this->role_id, $this->state->copyScoreSet(), $this->state_talk->copyScoreSet(), $this->co_manager);
        $score_list = $decide_vote->calc($candidate_list);
        $agent_id = $decide_vote->select($score_list);
        return $agent_id;
    }
    public function talk()
    {
        if (count($this->talk_queue) === 0) {
            // queueが空のときはSKIP
            $talk_text = 'SKIP';
        } else {
            // 先頭から1つ取得
            $talk_text = array_shift($this->talk_queue);
        }
        $this->talk_id++;
        return $talk_text;
    }
    public function whisper()
    {
    }
    public function attack()
    {
    }
    public function divine()
    {
    }
    public function guard()
    {
    }
    public function finish()
    {
//        $this->state->printAverage();
    }
    protected function getStatusIdMap()
    {
        return $this->game_info->getStatusIdList();
    }
    /** 生存している人数 */
    protected function getAliveSize()
    {
        $status_id_map = $this->getStatusIdMap();
        $alive_size = 0;
        foreach ($status_id_map as $user_id => $status_id) {
            if ($status_id === Status::DEAD) {
                continue;
            }
            $alive_size++;
        }
        return $alive_size;
    }
    protected function getCandidateList(array $id_list = [])
    {
        $status_id_map = $this->getStatusIdMap();
        $candidate_list = [];
        foreach ($status_id_map as $user_id => $status_id) {
            // 死亡している人を除く
            if ($status_id === Status::DEAD) {
                continue;
            }
            // 自分を除く
            if ($user_id === $this->agent_id) {
                continue;
            }
            // WEREWOLF or FREEMASON のときの仲間は除く
            $agent_id_list = $this->game_info->getRoleMap();
            if (\in_array($user_id, $agent_id_list, true)) {
                continue;
            }
            // 引数のid_listに含まれているuser_idは除く
            if (\in_array($user_id, $id_list, true)) {
                continue;
            }
            $candidate_list[] = $user_id;
        }
        return $candidate_list;
    }

    protected function getRoleSize(int $role_id)
    {
        return $this->role_num_map[$role_id];
    }
    public function getRoleNum(int $role_id)
    {
        return $this->role_num_map[$role_id];
    }
    protected function selectMaxScoreAgent($score_list, array $candidate_list)
    {
        $max_id_list = [];
        $max_score = 0.0;
        foreach ($candidate_list as $agent_id) {
            $score = (float)sprintf("%.4f", $score_list[$agent_id]);
            if ($score < 0.0) {
                $score = 0.0;
            }
            if ($score > 1.0) {
                $score = 1.0;
            }
            if ($score > $max_score) {
                $max_score = $score;
                $max_id_list = [];
                $max_id_list[] = $agent_id;
            } elseif ($score === $max_score) {
                $max_id_list[] = $agent_id;
            }
        }
        // 最大スコアのagentからランダムで1人選ぶ
        \shuffle($max_id_list);
        return $max_id_list[0];
    }
}
