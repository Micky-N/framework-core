<?php


namespace MkyCore;


use Exception;
use MkyCore\Facades\StandardDebugBar;
use MkyCore\Interfaces\VoterInterface;

class Permission
{
    private const AFFIRMATIVE = 'affirmative';
    private const CONSENSUS = 'consensus';
    private const UNANIMOUS = 'unanimous';
    private const DEFAULT_ALLOW_IF_ALL_ABSTAIN = false;
    private const DEFAULT_ALLOW_IF_EQUAL_GRANTED_DENIED = true;
    private const DEFAULT_STRATEGY = 'affirmative';

    /**
     * @var VoterInterface[]
     */
    private array $voters = [];

    /**
     * Authorize if true
     *
     * @param string $permission
     * @param null $subject
     * @return bool|void
     * @throws Exception
     */
    public function can(string $permission, $subject = null)
    {
        if($this->authorizeAuth($permission, $subject) === false){
            return ErrorController::forbidden();
        }
        return true;
    }

    /**
     * Authorize permission for Auth
     *
     * @param string $permission
     * @param null $subject
     * @return bool
     * @throws Exception
     */
    public function authorizeAuth(string $permission, $subject = null): bool
    {
        $auth = new AuthManager();
        if($auth->isLogin()){
            return $this->authorize($auth->getAuth(), $permission, $subject);
        }
        return false;
    }

    /**
     * Authorize permission
     *
     * @param mixed $user
     * @param string $permission
     * @param null $subject
     * @return bool
     * @throws Exception
     */
    public function authorize($user, string $permission, $subject = null): bool
    {
        $granted = 0;
        $denied = 0;
        foreach ($this->voters as $voter) {
            if($voter->canVote($permission, $subject)){
                $vote = $voter->vote($user, $permission, $subject);
                if(config('env') === 'local'){
                    $this->voterDebugBar($voter, $vote, $permission);
                }
                if($vote === true){
                    $granted += 1;
                } else {
                    $denied += 1;
                }
            }
        }
        return $this->result($granted, $denied);
    }

    private function result(int $granted, int $denied): bool
    {
        $allow_if_all_abstain = config('permission.allow_if_all_abstain') ?? self::DEFAULT_ALLOW_IF_ALL_ABSTAIN;
        $allow_if_equal_granted_denied = config('permission.allow_if_equal_granted_denied') ?? self::DEFAULT_ALLOW_IF_EQUAL_GRANTED_DENIED;
        $strategy = config('permission.strategy') ?? self::DEFAULT_STRATEGY;

        if($granted || $denied){
            if($strategy === self::AFFIRMATIVE){
                return $granted > 0;
            }

            if($strategy === self::CONSENSUS){
                return $granted != $denied ? $granted > $denied : $allow_if_equal_granted_denied;
            }

            if($strategy === self::UNANIMOUS){
                return $denied == 0;
            }
        }
        return $allow_if_all_abstain;
    }

    /**
     * Add voter
     *
     * @param VoterInterface $voter
     */
    public function addVoter(VoterInterface $voter): void
    {
        $this->voters[] = $voter;
    }

    /**
     * Add authorized voter to debugBar
     *
     * @param VoterInterface $voter
     * @param bool $vote
     * @param string $permission
     */
    private function voterDebugBar(VoterInterface $voter, bool $vote, string $permission)
    {
        $className = get_class($voter);
        $type = $vote ? 'info' : 'error';
        $message = "$className : " . ($vote ? "yes" : "no") . " on $permission";
        StandardDebugBar::addMessage('Voters', $message, $type);
    }
}