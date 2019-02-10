<?php
namespace JINRO_JOSEKI\Client\Lib;

class Topic
{
    const ESTIMATE = 0;
    const COMINGOUT = 1;
    const DIVINATION = 2;
    const DIVINED = 3;
    const IDENTIFIED = 4;
    const GUARD = 5;
    const GUARDED = 6;
    const VOTE = 7;
    const ATTACK = 8;
    const AGREE = 9;
    const DISAGREE = 10;
    const OVER = 11;
    const SKIP = 12;
    const OPERATOR = 13;

    const NAME_LIST = [
        'ESTIMATE',
        'COMINGOUT',
        'DIVINATION',
        'DIVINED',
        'IDENTIFIED',
        'GUARD',
        'GUARDED',
        'VOTE',
        'ATTACK',
        'AGREE',
        'DISAGREE',
        'Over',
        'Skip',
        'OPERATOR',
    ];

    private $topic_id = self::ESTIMATE;

    public function __construct(int $topic_id)
    {
        $this->topic_id = $topic_id;
    }
    public function getTopicId()
    {
        return $this->topic_id;
    }
    public function toString()
    {
        return self::NAME_LIST[$this->topic_id];
    }
    public static function toTopicId(string $topic_name)
    {
        $name_map = array_flip(self::NAME_LIST);
        if (!array_key_exists($topic_name, $name_map)) {
            return -1;
        }
        return $name_map[$topic_name];
    }
    public static function toTopicName(int $topic_id)
    {
        if (!array_key_exists($topic_id, self::NAME_LIST)) {
            return '';
        }
        return self::NAME_LIST[$topic_id];
    }
}
