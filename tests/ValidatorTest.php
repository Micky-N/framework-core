<?php

namespace MkyCore\Tests;

use MkyCore\Exceptions\Validator\RuleNotFoundException;
use MkyCore\Validate\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testEmptyPassed()
    {
        $validator = new Validator([], []);
        $validator2 = new Validator(['name' => 'micky'], []);
        $this->assertTrue($validator->passed());
        $this->assertTrue($validator2->passed());
    }

    public function testRequired()
    {
        $rules = ['name' => 'required'];
        $validator = new Validator([], $rules);
        $validator2 = new Validator(['name' => 'micky'], $rules);

        $this->assertFalse($validator->passed());
        $this->assertCount(1, $validator->getErrors());

        $this->assertTrue($validator2->passed());
        $this->assertCount(0, $validator2->getErrors());
    }

    public function testMaxMinPassed()
    {
        $ruleMax = ['age' => 'max:4'];
        $ruleMin = ['age' => 'min:4'];
        $validator = new Validator(['age' => 0], $ruleMax);
        $validator2 = new Validator(['age' => 5], $ruleMin);
        $validator3 = new Validator(['age' => 5], $ruleMax);
        $validator4 = new Validator(['age' => 0], $ruleMin);

        $this->assertTrue($validator->passed());
        $this->assertTrue($validator2->passed());

        $this->assertFalse($validator3->passed());
        $this->assertCount(1, $validator3->getErrors());

        $this->assertFalse($validator4->passed());
        $this->assertCount(1, $validator4->getErrors());
    }

    public function testMaxMinLengthPassed()
    {
        $ruleMax = ['comment' => 'maxL:4'];
        $ruleMin = ['comment' => 'minL:4'];
        $validator = new Validator(['comment' => 'Max'], $ruleMax);
        $validator2 = new Validator(['comment' => 'Love you'], $ruleMin);
        $validator3 = new Validator(['comment' => 'Love you'], $ruleMax);
        $validator4 = new Validator(['comment' => 'Max'], $ruleMin);

        $this->assertTrue($validator->passed());
        $this->assertTrue($validator2->passed());

        $this->assertFalse($validator3->passed());
        $this->assertCount(1, $validator3->getErrors());

        $this->assertFalse($validator4->passed());
        $this->assertCount(1, $validator4->getErrors());
    }

    public function testTextFieldPassed()
    {
        $data = ['password' => '1234', 'fake_confirm' => '123', 'confirm' => '1234'];

        $fake_rules = ['password' => 'confirmed:field.fake_confirm'];
        $confirm_rules = ['password' => 'confirmed:field.confirm'];
        $same_rule = ['password' => 'same:field.confirm'];
        $different_rule = ['password' => 'different:field.fake_confirm'];
        $validator_fake = new Validator($data, $fake_rules);
        $validator_confirm = new Validator($data, $confirm_rules);
        $validator_same = new Validator($data, $same_rule);
        $validator_different = new Validator($data, $different_rule);

        $validator_different->passed();
        $this->assertTrue($validator_confirm->passed());
        $this->assertTrue($validator_same->passed());
        $this->assertTrue($validator_different->passed());

        $this->assertFalse($validator_fake->passed());
        $this->assertCount(1, $validator_fake->getErrors());
    }

    public function testTextPassed()
    {
        $data = ['text' => 'Hello',];
        $confirm_block_rules = ['text' => 'confirmed:Hello'];
        $same_rule = ['text' => 'same:Hello'];
        $different_rule = ['text' => 'different:hello'];
        $validator_block_confirm = new Validator($data, $confirm_block_rules);
        $validator_same = new Validator($data, $same_rule);
        $validator_different = new Validator($data, $different_rule);

        $this->assertTrue($validator_same->passed());
        $this->assertTrue($validator_different->passed());
        $this->assertFalse($validator_block_confirm->passed());
        $this->assertCount(1, $validator_block_confirm->getErrors());
    }

    public function testDatePassed()
    {
        $before = date('Y-m-d', strtotime('-2 year'));
        $after = date('Y-m-d', strtotime('+2 year'));
        $data = [
            'before' => date('Y-m-d', strtotime('-1 year')),
            'after' => date('Y-m-d', strtotime('+1 year'))
        ];
        $now_rule = ['before' => 'beforeDate:now', 'after' => 'afterDate:now'];
        $date_rule = ['before' => 'beforeDate:'.$after, 'after' => 'afterDate:'.$before];
        $block_data_rule = ['before' => 'beforeDate:'.$before, 'after' => 'afterDate:'.$after];
        $validator_now = new Validator($data, $now_rule);
        $validator_date = new Validator($data, $date_rule);
        $validator_block_date = new Validator($data, $block_data_rule);

        $this->assertTrue($validator_now->passed());
        $this->assertTrue($validator_date->passed());
        $this->assertFalse($validator_block_date->passed());
        $this->assertCount(2, $validator_block_date->getErrors());
    }

    public function testMultipleRulesPassed()
    {
        $rules = ['text' => 'required|max:5|same:code'];
        $validator_passed = new Validator(['text' => 'code'], $rules);
        $validator_block = new Validator(['text' => 'c'], $rules);

        $this->assertTrue($validator_passed->passed());
        $this->assertFalse($validator_block->passed());
        $this->assertCount(1, $validator_block->getErrors());

        // Get the last error_message
        $this->assertEquals('text field must be same as code', $validator_block->getErrors()['text']);
    }

    public function testException()
    {
        // error on named same as samed
        $rules = ['text' => 'required|max:5|samed:code'];
        $validator = new Validator(['text' => 'code'], $rules);
        try {
            $validator->passed();
        }catch (\Exception $ex){
            $this->assertInstanceOf(RuleNotFoundException::class, $ex);
        }
    }
}
