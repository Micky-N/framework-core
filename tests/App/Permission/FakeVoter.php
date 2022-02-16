<?php


namespace MkyCore\Tests\App\Permission;


use MkyCore\Exceptions\Voter\VoterException;

class FakeVoter implements \MkyCore\Interfaces\VoterInterface
{

    /**
     * @inheritDoc
     */
    public function canVote(string $permission, $subject = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function vote($user, string $permission, $subject = null): bool
    {
        if(!$subject instanceof TestProduct){
            throw new VoterException('Subject must be an instance of '. TestProduct::class);
        }
        return true;
    }
}