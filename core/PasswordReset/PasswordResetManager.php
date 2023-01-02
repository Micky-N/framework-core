<?php

namespace MkyCore\PasswordReset;

use MkyCore\Abstracts\Manager;

/**
 * @Entity('MkyCore\PasswordReset\PasswordReset')
 * @Table('password_reset_tokens')
 */
class PasswordResetManager extends Manager
{

}