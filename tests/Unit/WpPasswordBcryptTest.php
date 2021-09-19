<?php

namespace Roots\PasswordBcrypt\Tests\Unit;

use Brain\Monkey;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryTestCase;

// phpcs:disable PSR12.Properties.ConstantVisibility.NotFound
// phpcs:disable PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.voidFound

class WpPasswordBcryptTest extends MockeryTestCase
{
    const PASSWORD = 'password';
    const HASH_BCRYPT = '$2y$10$KIMXDMJq9camkaNHkdrmcOaYJ0AT9lvovEf92yWA34sKdfnn97F9i';
    const HASH_PHPASS = '$P$BDMJH/qCLaUc5Lj8Oiwp7XmWzrCcJ21';

    /**
    * Setup the test case.
    *
    * @return void
    */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        global $wpdb;

        $wpdb = M::mock('wpdb');
        $wpdb
            ->shouldReceive('update')
            ->withAnyArgs()
            ->andReturnNull();
        $wpdb->users = 'wp_users';
    }

    /**
    * Tear down the test case.
    *
    * @return void
    */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /** @test */
    public function a_password_is_hashed_using_bcrypt()
    {
        $userId = 1;

        Monkey\Functions\expect('wp_cache_delete')->once()->andReturn(true);

        $bcrypt_hash = wp_set_password(self::PASSWORD, $userId);
        $this->assertTrue(password_verify(self::PASSWORD, $bcrypt_hash));
    }

    /** @test */
    public function hashing_password_applies_filter()
    {
        wp_hash_password(self::PASSWORD);

        Monkey\Filters\expectApplied('wp_hash_password_options')->andReturn(self::HASH_BCRYPT);
    }

    /** @test */
    public function bcrypt_passwords_should_be_verified()
    {
        $bad_hash = 'randomhash';

        $bcrypt_check = wp_check_password(self::PASSWORD, self::HASH_BCRYPT);
        $bad_check = wp_check_password(self::PASSWORD, $bad_hash);

        $this->assertTrue($bcrypt_check);
        $this->assertFalse($bad_check);
    }

    /** @test */
    public function phpass_passwords_should_be_verified_and_converted_to_bcrypt()
    {
        global $wp_hasher;

        /** @var int $userId This is necessary to trigger wp_set_password() */
        $userId = 1;

        $wp_hasher = M::mock('PasswordHash');
        $wp_hasher
            ->shouldReceive('CheckPassword')
            ->once()
            ->with(self::PASSWORD, self::HASH_PHPASS)
            ->andReturn(true);

        Monkey\Functions\expect('wp_cache_delete')->once()->andReturn(true);

        $phpass_check = wp_check_password(self::PASSWORD, self::HASH_PHPASS, $userId);
        $this->assertTrue($phpass_check);
    }
}
