<?php


namespace MkyCore\Interfaces;


use App\Models\User;

interface VoterInterface
{
    /**
     * Check if Voter can vote
     *
     * @param string $permission
     * @param null $subject
     * @return bool
     */
    public function canVote(string $permission, $subject = null): bool;

    /**
     * Get Voter vote
     *
     * @param mixed $user
     * @param string $permission
     * @param null $subject
     * @return bool
     */
    public function vote($user, string $permission, $subject = null): bool;
}