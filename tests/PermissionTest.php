<?php

namespace MkyCore\Tests;


use MkyCore\App;
use MkyCore\Exceptions\Voter\VoterException;
use MkyCore\Permission;
use PHPUnit\Framework\TestCase;
use MkyCore\Tests\App\Permission\AlwaysNoVoter;
use MkyCore\Tests\App\Permission\AlwaysYesVoter;
use MkyCore\Tests\App\Permission\FakeVoter;
use MkyCore\Tests\App\Permission\SellerVoter;
use MkyCore\Tests\App\Permission\SpecificVoter;
use MkyCore\Tests\App\Permission\TestProduct;

class PermissionTest extends TestCase
{

    /**
     * @var Permission
     */
    private Permission $permission;

    public function setUp(): void
    {
        $this->permission = new Permission();
    }

    public function testEmptyVoters()
    {
        $user = new \stdClass();
        $user->id = 7;
        $this->assertFalse($this->permission->authorize($user, 'demo'));

        App::setConfig('app', [
            'permission' => [
                'allow_if_all_abstain' => true
            ]
        ]);
        $this->assertTrue($this->permission->authorize($user, 'demo'));
    }

    public function testWithTrueVoter()
    {
        $this->permission->addVoter(new AlwaysYesVoter());
        $user = new \stdClass();
        $user->id = 7;
        $this->assertTrue($this->permission->authorize($user, 'demo'));
    }

    public function testWithFalseVoter()
    {
        $this->permission->addVoter(new AlwaysNoVoter());
        $user = new \stdClass();
        $user->id = 7;
        $this->assertFalse($this->permission->authorize($user, 'demo'));
    }

    public function testAffirmativeStrategy()
    {
        App::setConfig('app', [
            'permission' => [
                'strategy' => 'affirmative'
            ]
        ]);
        $user = new \stdClass();
        $user->id = 7;
        $this->permission->addVoter(new AlwaysYesVoter());
        $this->permission->addVoter(new AlwaysNoVoter());
        $this->assertTrue($this->permission->authorize($user, 'demo'));
    }

    public function testWithSpecificVoter()
    {
        $user = new \stdClass();
        $user->id = 7;
        $this->permission->addVoter(new SpecificVoter());
        $this->assertFalse($this->permission->authorize($user, 'demo'));
        $this->assertTrue($this->permission->authorize($user, 'specific'));
    }

    public function testWithConditionVoter()
    {
        $user = new \stdClass();
        $user->id = 7;
        $user2 = new \stdClass();
        $user2->id = 1;
        $product = new TestProduct($user);
        $this->permission->addVoter(new SellerVoter());
        $this->assertTrue($this->permission->authorize($user, SellerVoter::EDIT, $product));
        $this->assertFalse($this->permission->authorize($user2, SellerVoter::EDIT, $product));
        try {
            $this->permission->addVoter(new FakeVoter());
            $this->assertTrue($this->permission->authorize($user, true, $product));
            $this->permission->authorize($user, true, $user2);
        }catch (VoterException $ex){
            $this->assertInstanceOf(VoterException::class, $ex);
        }
    }

    public function testConsensusStrategy()
    {
        App::setConfig('app', [
            'permission' => [
                'strategy' => 'consensus'
            ]
        ]);
        $user = 'user';
        $this->permission->addVoter(new AlwaysNoVoter());
        $this->permission->addVoter(new AlwaysNoVoter());
        $this->permission->addVoter(new AlwaysYesVoter());
        $this->assertFalse($this->permission->authorize($user, ''));

        $this->permission->addVoter(new AlwaysYesVoter());
        $this->permission->addVoter(new AlwaysNoVoter());
        $this->permission->addVoter(new AlwaysYesVoter());
        $this->assertTrue($this->permission->authorize($user, ''));

        $this->permission->addVoter(new AlwaysYesVoter());
        $this->permission->addVoter(new AlwaysNoVoter());
        $this->assertTrue($this->permission->authorize($user, ''));

        App::setConfig('app', [
            'permission' => [
                'strategy' => 'consensus',
                'allow_if_equal_granted_denied' => false
            ]
        ]);
        $this->permission->addVoter(new AlwaysYesVoter());
        $this->permission->addVoter(new AlwaysNoVoter());
        $this->assertFalse($this->permission->authorize($user, ''));
    }

    public function testUnanimousStrategy()
    {
        App::setConfig('app', [
            'permission' => [
                'strategy' => 'unanimous'
            ]
        ]);
        $user = 'user';
        $this->permission->addVoter(new AlwaysNoVoter());
        $this->permission->addVoter(new AlwaysYesVoter());
        $this->permission->addVoter(new AlwaysYesVoter());
        $this->assertFalse($this->permission->authorize($user, ''));
    }
}
