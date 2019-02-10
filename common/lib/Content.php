<?php
namespace JINRO_JOSEKI\Common\Lib;

require_once('common/data/Role.php');
require_once('common/data/Species.php');
require_once('common/data/Team.php');
require_once('client/lib/Topic.php');
require_once('client/lib/TalkType.php');

use JINRO_JOSEKI\Common\Data\Role;
use JINRO_JOSEKI\Common\Data\Species;
use JINRO_JOSEKI\Common\Data\Team;
use JINRO_JOSEKI\Client\Lib\Topic;
use JINRO_JOSEKI\Client\Lib\TalkType;

class Content
{
    private $text = '';
    private $topic_id = -1;
    private $subject_id = -1;
    private $target_id = -1;
    private $role_id = -1;
    private $result_id = -1;
    private $talk_type_id = TalkType::TALK;
    private $talk_day = 0;
    private $talk_id = 0;

    public function __construct(array $map)
    {
        if (array_key_exists('text', $map)) {
            // text を含んでいるとき
            $this->text = $map['text'];
            $this->setPropertyByText($this->text);
        } else {
            // text を含んでいないとき
            if (array_key_exists('topic_id', $map)) {
                $this->topic_id = $map['topic_id'];
            }
            if (array_key_exists('subject_id', $map)) {
                $this->subject_id = $map['subject_id'];
            }
            if (array_key_exists('target_id', $map)) {
                $this->target_id = $map['target_id'];
            }
            if (array_key_exists('role_id', $map)) {
                $this->role_id = $map['role_id'];
            }
            if (array_key_exists('result_id', $map)) {
                $this->result_id = $map['result_id'];
            }
        }
    }
    public function getText()
    {
        return $this->text;
    }
    public function getTopicId()
    {
        return $this->topic_id;
    }
    public function getSubjectId()
    {
        return $this->subject_id;
    }
    public function getTargetId()
    {
        return $this->target_id;
    }
    public function getRoleId()
    {
        return $this->role_id;
    }
    public function getResultId()
    {
        return $this->result_id;
    }
    public function getTalkTypeId()
    {
        return $this->talk_type_id;
    }
    public function getTalkDay()
    {
        return $talk_day;
    }
    public function getTalkID()
    {
        return $talk_id;
    }

    private function setPropertyByText(string $text)
    {
        $word_list = explode(" ", $text);
        $topic_name = array_shift($word_list);
        $topic_id = Topic::toTopicId($topic_name);
        if ($topic_id === Topic::ESTIMATE) {
            if (count($word_list) !== 2) {
                return false;
            }
            $target_id = $this->toAgentId($word_list[0]);
            $role_id = Role::toRoleId($word_list[1]);
            if ($target_id === -1 || $role_id === -1) {
                return false;
            }
            $this->topic_id = $topic_id;
            $this->target_id = $target_id;
            $this->role_id = $role_id;
        } elseif ($topic_id === Topic::COMINGOUT) {
            if (count($word_list) !== 2) {
                return false;
            }
            $target_id = $this->toAgentId($word_list[0]);
            $role_id = Role::toRoleId($word_list[1]);
            if ($target_id === -1 || $role_id === -1) {
                return false;
            }
            $this->topic_id = $topic_id;
            $this->target_id = $target_id;
            $this->role_id = $role_id;
        } elseif ($topic_id === Topic::DIVINATION) {
            
        } elseif ($topic_id === Topic::DIVINED) {
            if (count($word_list) !== 2) {
                return false;
            }
            $target_id = $this->toAgentId($word_list[0]);
            $species_id = Species::toSpeciesId($word_list[1]);
            if ($target_id === -1 || $species_id === -1) {
                return false;
            }
            $this->topic_id = $topic_id;
            $this->target_id = $target_id;
            $this->result_id = $species_id;
        } elseif ($topic_id === Topic::IDENTIFIED) {
            if (count($word_list) !== 2) {
                return false;
            }
            $target_id = $this->toAgentId($word_list[0]);
            $species_id = Species::toSpeciesId($word_list[1]);
            if ($target_id === -1 || $species_id === -1) {
                return false;
            }
            $this->topic_id = $topic_id;
            $this->target_id = $target_id;
            $this->result_id = $species_id;
        } elseif ($topic_id === Topic::GUARD) {
            
        } elseif ($topic_id === Topic::GUARDED) {
            if (count($word_list) !== 1) {
                return false;
            }
            $target_id = $this->toAgentId($word_list[0]);
            if ($target_id === -1) {
                return false;
            }
            $this->topic_id = $topic_id;
            $this->target_id = $target_id;
        } elseif ($topic_id === Topic::VOTE) {
            if (count($word_list) !== 1) {
                return false;
            }
            $target_id = $this->toAgentId($word_list[0]);
            if ($target_id === -1) {
                return false;
            }
            $this->topic_id = $topic_id;
            $this->target_id = $target_id;
        } elseif ($topic_id === Topic::ATTACK) {
                
        } elseif ($topic_id === Topic::AGREE) {
                
        } elseif ($topic_id === Topic::DISAGREE) {
                
        } elseif ($topic_id === Topic::OVER) {
            $this->topic_id = $topic_id;
        } elseif ($topic_id === Topic::SKIP) {
            $this->topic_id = $topic_id;
        } elseif ($topic_id === Topic::OPERATOR) {

        } else {
            return false;
        }
    }
    private function toAgentId(string $str)
    {
        if (strcmp(substr($str, 0, 6), 'AGENT[') !== 0 && strcmp(substr($str, 0, 6), 'Agent[') !== 0) {
            return -1;
        }
        if (strcmp(substr($str, 8, 1), ']') !== 0) {
            return -1;
        }
        return (int)substr($str, 6, 2);
    }
    private function toAgentName(int $agent_id)
    {
        return 'AGENT[' . sprintf("%02d", $agent_id) . ']';
    }
    public function toString()
    {
        $topic_id = $this->topic_id;
        $topic_name = Topic::toTopicName($topic_id);
        if ($topic_id === Topic::ESTIMATE) {
            $tareget_name = $this->toAgentName($this->target_id);
            $role_name = Role::toRoleName($this->role_id);
            return $topic_name . ' ' . $target_name . ' ' . $role_name;
        } elseif ($topic_id === Topic::COMINGOUT) {
            $target_name = $this->toAgentName($this->target_id);
            $role_name = Role::toRoleName($this->role_id);
            return $topic_name . ' ' . $target_name . ' ' . $role_name;
        } elseif ($topic_id === Topic::DIVINATION) {
            return $topic_name;
        } elseif ($topic_id === Topic::DIVINED) {
            $target_name = $this->toAgentName($this->target_id);
            $species_name = Species::toSpeciesName($this->result_id);
            return $topic_name . ' ' . $target_name . ' ' . $species_name;
        } elseif ($topic_id === Topic::IDENTIFIED) {
            $target_name = $this->toAgentName($this->target_id);
            $species_name = Species::toSpeciesName($this->result_id);
            return $topic_name . ' ' . $target_name . ' ' . $species_name;
        } elseif ($topic_id === Topic::GUARD) {
            return $topic_name;
        } elseif ($topic_id === Topic::GUARDED) {
            $target_name = $this->toAgentName($this->target_id);
            return $topic_name . ' ' . $target_name;
        } elseif ($topic_id === Topic::VOTE) {
            $target_name = $this->toAgentName($this->target_id);
            return $topic_name . ' ' . $target_name;
        } elseif ($topic_id === Topic::ATTACK) {
            return $topic_name;
        } elseif ($topic_id === Topic::AGREE) {
            return $topic_name;
        } elseif ($topic_id === Topic::DISAGREE) {
            return $topic_name;
        } elseif ($topic_id === Topic::OVER) {
            return $topic_name;
        } elseif ($topic_id === Topic::SKIP) {
            return $topic_name;
        } elseif ($topic_id === Topic::OPERATOR) {
            return $topic_name;
        } else {
            return '';
        }
    }
}
/*
$content0 = new Content(['text' => 'AAA']);
print 'text0 = ' . $content0->toString() . "\n";
$content1 = new Content(['text' => 'COMINGOUT AGENT[00] WEREWOLF']);
print 'text1 = ' . $content1->toString() . "\n";
*/
