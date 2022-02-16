<?php


namespace MkyCore\Tests\App\Permission;



class SpecificVoter implements \MkyCore\Interfaces\VoterInterface
{

    public function canVote(string $permission, $subject = null): bool
    {
        return $permission === 'specific';
    }

    public function vote($user, string $permission, $subject = null): bool
    {
        return true;
    }
}